
<?php
session_start();

require '../../data_base/conexao.php';

function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// Filtros
$filtro_severidade = $_GET['severidade'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';

$severidades_validas = ['atencao', 'critico'];

if (!in_array($filtro_severidade, ['', 'atencao', 'critico'], true)) {
    $filtro_severidade = '';
}

$condicoes = [];
$parametros = [];
$tipos_parametros = '';

if ($filtro_severidade !== '') {
    $condicoes[] = 'a.severidade = ?';
    $parametros[] = $filtro_severidade;
    $tipos_parametros .= 's';
}

if ($filtro_tipo !== '') {
    $condicoes[] = 's.tipo = ?';
    $parametros[] = $filtro_tipo;
    $tipos_parametros .= 's';
}

$where = $condicoes
    ? 'WHERE ' . implode(' AND ', $condicoes)
    : '';

// Consulta dos alertas
$sql = "
    SELECT
        a.id_alerta,
        a.id_leitura,
        a.tipo AS tipo_alerta,
        a.severidade,
        a.mensagem,
        a.data_hora,
        l.valor,
        l.unidade,
        l.data_hora AS data_leitura,
        s.codigo,
        s.tipo AS tipo_sensor,
        t.prefixo
    FROM alertas a
    INNER JOIN leituras l
        ON l.id_leitura = a.id_leitura
    INNER JOIN sensores s
        ON s.id_sensores = l.id_sensor
    INNER JOIN trem t
        ON t.id_trem = s.FK_id_trem
    $where
    ORDER BY
        CASE
            WHEN a.severidade = 'critico' THEN 1
            WHEN a.severidade = 'atencao' THEN 2
            ELSE 3
        END,
        a.data_hora DESC
    LIMIT 200
";

$stmt = $conexao->prepare($sql);

if (!$stmt) {
    die('Erro ao consultar alertas: ' . escapar($conexao->error));
}

if ($parametros) {
    $stmt->bind_param($tipos_parametros, ...$parametros);
}

$stmt->execute();
$resultado = $stmt->get_result();

$alertas = [];

while ($linha = $resultado->fetch_assoc()) {
    $alertas[] = $linha;
}

$stmt->close();

// Tipos de sensores disponíveis no filtro
$resultadoTipos = $conexao->query("
    SELECT DISTINCT tipo
    FROM sensores
    WHERE tipo IS NOT NULL AND tipo <> ''
    ORDER BY tipo
");

if (!$resultadoTipos) {
    die('Erro ao carregar tipos de sensores: ' . escapar($conexao->error));
}

// Separar os alertas em cartões, como na referência
$criticos = [];
$atencao = [];

foreach ($alertas as $alerta) {
    if ($alerta['severidade'] === 'critico') {
        $criticos[] = $alerta;
    } else {
        $atencao[] = $alerta;
    }
}

$totalCriticos = count($criticos);
$totalAtencao = count($atencao);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Alertas e notificações - Ferrorama Gauge</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="notificacoes_alertas.css">
</head>

<body>

    <!-- NAVBAR ORIGINAL DO PROJETO -->
    <header class="header">
        <div class="left">
            <i class="fas fa-bars" title="Menu"></i>
            <i class="fas fa-search" title="Pesquisar"></i>
        </div>

        <div class="logo">
            <img src="../assets/logo_gauge_menor.png" alt="Gauge Logo">
        </div>
    </header>

    <main class="conteudo">
        <h1>Alerta e notificações</h1>

        <p class="subtitulo">
            Acompanhe os alertas identificados pelos sensores dos trens.
        </p>

        <!-- FILTROS -->
        <section class="filtros">
            <form method="GET" action="notificacoes_alertas.php">
                <div class="campo">
                    <label for="severidade">Severidade</label>

                    <select name="severidade" id="severidade">
                        <option value="">Todas</option>
                        <option value="critico"
                            <?= $filtro_severidade === 'critico' ? 'selected' : '' ?>>
                            Crítico
                        </option>
                        <option value="atencao"
                            <?= $filtro_severidade === 'atencao' ? 'selected' : '' ?>>
                            Atenção
                        </option>
                    </select>
                </div>

                <div class="campo">
                    <label for="tipo">Tipo de sensor</label>

                    <select name="tipo" id="tipo">
                        <option value="">Todos os tipos</option>

                        <?php while ($tipo = $resultadoTipos->fetch_assoc()): ?>
                            <option
                                value="<?= escapar($tipo['tipo']) ?>"
                                <?= $filtro_tipo === $tipo['tipo'] ? 'selected' : '' ?>>
                                <?= escapar($tipo['tipo']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="acoes-filtro">
                    <button type="submit" class="botao-filtrar">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>

                    <a href="notificacoes_alertas.php" class="botao-limpar">
                        Limpar
                    </a>
                </div>
            </form>
        </section>

        <!-- CARTÕES DE ALERTAS -->
        <section class="grade-alertas">

            <!-- ALERTAS CRÍTICOS -->
            <article class="cartao cartao-critico">
                <div class="cabecalho-cartao">
                    <h2>
                        <i class="fas fa-triangle-exclamation"></i>
                        Críticos
                        <span class="contador"><?= $totalCriticos ?></span>
                    </h2>
                </div>

                <?php if (count($criticos) > 0): ?>
                    <ul class="lista-alertas">
                        <?php foreach ($criticos as $alerta): ?>
                            <li>
                                <strong>
                                    Trem <?= escapar($alerta['prefixo']) ?>:
                                </strong>

                                <?= escapar($alerta['mensagem']) ?>

                                <div class="detalhes-alerta">
                                    Sensor <?= escapar($alerta['codigo']) ?>
                                    · <?= escapar($alerta['valor']) ?>
                                    <?= escapar($alerta['unidade']) ?>
                                </div>

                                <time>
                                    <?= escapar(date(
                                        'd/m/Y H:i',
                                        strtotime($alerta['data_hora'])
                                    )) ?>
                                </time>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="sem-alertas">
                        Nenhum alerta crítico encontrado.
                    </p>
                <?php endif; ?>
            </article>

            <!-- ALERTAS DE ATENÇÃO -->
            <article class="cartao cartao-atencao">
                <div class="cabecalho-cartao">
                    <h2>
                        <i class="fas fa-triangle-exclamation"></i>
                        Alertas
                        <span class="contador"><?= $totalAtencao ?></span>
                    </h2>
                </div>

                <?php if (count($atencao) > 0): ?>
                    <ul class="lista-alertas">
                        <?php foreach ($atencao as $alerta): ?>
                            <li>
                                <strong>
                                    Trem <?= escapar($alerta['prefixo']) ?>:
                                </strong>

                                <?= escapar($alerta['mensagem']) ?>

                                <div class="detalhes-alerta">
                                    Sensor <?= escapar($alerta['codigo']) ?>
                                    · <?= escapar($alerta['valor']) ?>
                                    <?= escapar($alerta['unidade']) ?>
                                </div>

                                <time>
                                    <?= escapar(date(
                                        'd/m/Y H:i',
                                        strtotime($alerta['data_hora'])
                                    )) ?>
                                </time>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="sem-alertas">
                        Nenhum alerta de atenção encontrado.
                    </p>
                <?php endif; ?>
            </article>

        </section>

        <p class="nota">
            Exibindo até 200 alertas. Os alertas críticos aparecem primeiro.
        </p>
    </main>

    <!-- FOOTER ORIGINAL DO PROJETO -->
    <footer class="footer-gauge">
        <div class="footer-container">

            <div class="footer-bloco bloco-esquerda">
                <span class="footer-label">Entre em contato</span>
                <a href="tel:47999174896" class="footer-link">
                    (47) 99917-4896
                </a>
            </div>

            <div class="footer-bloco bloco-centro">
                <img
                    src="../assets/logo_gauge_menor.png"
                    alt="Gauge Logo"
                    class="footer-logo"
                >

                <p class="footer-copyright">
                    &copy; 2026 Gauge. Todos os direitos reservados.
                </p>
            </div>

            <div class="footer-bloco bloco-direita">
                <span class="footer-label">Precisa de Suporte?</span>
                <a
                    href="mailto:contato@gauge.com.br"
                    class="footer-link link-sublinhado"
                >
                    contato@gauge.com.br
                </a>
            </div>

        </div>
    </footer>

</body>
</html>
