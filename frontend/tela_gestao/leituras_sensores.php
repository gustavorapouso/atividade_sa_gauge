
<?php
session_start();

require '../../data_base/conexao.php';

function escapar($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$mensagem = '';
$erro = '';

$filtroSensor = isset($_GET['sensor']) ? (int) $_GET['sensor'] : 0;
$filtroTrem = isset($_GET['trem']) ? (int) $_GET['trem'] : 0;

/*
 * Busca os sensores e os trens relacionados.
 * Utiliza os nomes das colunas existentes no banco original.
 */
$sqlSensores = "
    SELECT
        s.id_sensores,
        s.codigo,
        s.nome,
        s.tipo,
        s.FK_id_trem,
        t.prefixo
    FROM sensores s
    INNER JOIN trem t ON s.FK_id_trem = t.id_trem
    ORDER BY s.id_sensores DESC
";

$resultadoSensores = $conexao->query($sqlSensores);

/* Lista de trens para o filtro */
$resultadoTrens = $conexao->query("
    SELECT id_trem, prefixo
    FROM trem
    ORDER BY prefixo
");

/*
 * Geração de leituras de exemplo.
 * A tabela dados_sensores original é mantida.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['gerar_leituras'])) {

    $idSensor = (int) ($_POST['id_sensor'] ?? 0);
    $quantidade = (int) ($_POST['quantidade'] ?? 10);

    if ($quantidade < 1 || $quantidade > 100) {
        $erro = 'Escolha uma quantidade entre 1 e 100.';
    } else {
        $stmtSensor = $conexao->prepare("
            SELECT id_sensores, tipo
            FROM sensores
            WHERE id_sensores = ?
        ");

        $stmtSensor->bind_param('i', $idSensor);
        $stmtSensor->execute();
        $sensorSelecionado = $stmtSensor->get_result()->fetch_assoc();
        $stmtSensor->close();

        if (!$sensorSelecionado) {
            $erro = 'Selecione um sensor válido.';
        } else {
            $tipo = strtolower($sensorSelecionado['tipo']);

            if (strpos($tipo, 'temperatura') !== false) {
                $valorMinimo = 60;
                $valorMaximo = 100;
            } elseif (strpos($tipo, 'velocidade') !== false) {
                $valorMinimo = 20;
                $valorMaximo = 120;
            } elseif (strpos($tipo, 'consumo') !== false
                || strpos($tipo, 'energia') !== false) {
                $valorMinimo = 10;
                $valorMaximo = 80;
            } else {
                $valorMinimo = 1;
                $valorMaximo = 100;
            }

            $stmtInsert = $conexao->prepare("
                INSERT INTO dados_sensores
                    (data_hora, valor_sensor, status_sensor, FK_id_sensores)
                VALUES (NOW(), ?, ?, ?)
            ");

            $status = 'normal';

            for ($i = 0; $i < $quantidade; $i++) {
                $valor = mt_rand(
                    $valorMinimo * 10,
                    $valorMaximo * 10
                ) / 10;

                $stmtInsert->bind_param(
                    'dsi',
                    $valor,
                    $status,
                    $idSensor
                );

                if (!$stmtInsert->execute()) {
                    $erro = 'Não foi possível registrar as leituras.';
                    break;
                }
            }

            if ($erro === '') {
                $mensagem = $quantidade . ' leitura(s) gerada(s) com sucesso.';
            }

            $stmtInsert->close();
        }
    }
}

/* Recarrega os sensores depois de uma possível geração */
$resultadoSensores = $conexao->query($sqlSensores);

/* Consulta das leituras */
$sqlLeituras = "
    SELECT
        d.id_dados_sensores,
        d.data_hora,
        d.valor_sensor,
        d.status_sensor,
        s.id_sensores,
        s.codigo,
        s.nome AS nome_sensor,
        s.tipo,
        t.id_trem,
        t.prefixo
    FROM dados_sensores d
    INNER JOIN sensores s
        ON d.FK_id_sensores = s.id_sensores
    INNER JOIN trem t
        ON s.FK_id_trem = t.id_trem
    WHERE (? = 0 OR s.id_sensores = ?)
      AND (? = 0 OR t.id_trem = ?)
    ORDER BY d.data_hora DESC
    LIMIT 100
";

$stmtLeituras = $conexao->prepare($sqlLeituras);
$stmtLeituras->bind_param(
    'iiii',
    $filtroSensor,
    $filtroSensor,
    $filtroTrem,
    $filtroTrem
);
$stmtLeituras->execute();
$resultadoLeituras = $stmtLeituras->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Leituras dos Sensores | Gauge</title>

    <link rel="stylesheet" href="leituras_sensores.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;600;700&display=swap"
          rel="stylesheet">
</head>
<body>

<header class="header">
    <div class="left">
        <a href="../tela_principal/dashboard.php" title="Voltar">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <div class="logo">
        <img src="../assets/logo_gauge_menor.png" alt="Gauge Logo">
    </div>

    <div class="right">
        <i class="fas fa-search" title="Pesquisar"></i>
    </div>
</header>

<main class="container">
    <div class="titulo-pagina">
        <h1>Leituras dos sensores</h1>
        <p>Acompanhe e simule as leituras dos sensores do Ferrorama Gauge.</p>
    </div>

    <?php if ($mensagem !== ''): ?>
        <div class="mensagem sucesso"><?= escapar($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro !== ''): ?>
        <div class="mensagem erro"><?= escapar($erro) ?></div>
    <?php endif; ?>

    <section class="painel">
        <h2>Gerar leituras</h2>

        <form method="POST" class="formulario">
            <div class="campo">
                <label for="id_sensor">Sensor</label>
                <select name="id_sensor" id="id_sensor" required>
                    <option value="">Selecione um sensor</option>

                    <?php while ($sensor = $resultadoSensores->fetch_assoc()): ?>
                        <option value="<?= (int) $sensor['id_sensores'] ?>">
                            <?= escapar($sensor['codigo']) ?> -
                            <?= escapar($sensor['nome']) ?> -
                            Trem <?= escapar($sensor['prefixo']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="campo">
                <label for="quantidade">Quantidade de leituras</label>
                <input
                    type="number"
                    id="quantidade"
                    name="quantidade"
                    min="1"
                    max="100"
                    value="10"
                    required
                >
            </div>

            <div class="acoes">
                <button type="submit" name="gerar_leituras">
                    <i class="fas fa-check"></i> Gerar leituras
                </button>
            </div>
        </form>
    </section>

    <section class="painel">
        <h2>Consultar leituras</h2>

        <form method="GET" class="formulario">
            <div class="campo">
                <label for="sensor">Sensor</label>
                <select name="sensor" id="sensor">
                    <option value="0">Todos os sensores</option>

                    <?php
                    $resultadoSensores->data_seek(0);

                    while ($sensor = $resultadoSensores->fetch_assoc()):
                    ?>
                        <option
                            value="<?= (int) $sensor['id_sensores'] ?>"
                            <?= $filtroSensor === (int) $sensor['id_sensores'] ? 'selected' : '' ?>
                        >
                            <?= escapar($sensor['codigo']) ?> -
                            <?= escapar($sensor['nome']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="campo">
                <label for="trem">Trem</label>
                <select name="trem" id="trem">
                    <option value="0">Todos os trens</option>

                    <?php
                    $resultadoTrens->data_seek(0);

                    while ($trem = $resultadoTrens->fetch_assoc()):
                    ?>
                        <option
                            value="<?= (int) $trem['id_trem'] ?>"
                            <?= $filtroTrem === (int) $trem['id_trem'] ? 'selected' : '' ?>
                        >
                            <?= escapar($trem['prefixo']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="acoes">
                <button type="submit">
                    <i class="fas fa-search"></i> Filtrar
                </button>

                <a href="leituras_sensores.php" class="botao-secundario">
                    Limpar filtros
                </a>
            </div>
        </form>
    </section>

    <section class="painel">
        <h2>Últimas leituras</h2>

        <div class="tabela-responsiva">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data e hora</th>
                        <th>Trem</th>
                        <th>Sensor</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($resultadoLeituras->num_rows > 0): ?>
                        <?php while ($leitura = $resultadoLeituras->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int) $leitura['id_dados_sensores'] ?></td>
                                <td><?= escapar($leitura['data_hora']) ?></td>
                                <td><?= escapar($leitura['prefixo']) ?></td>
                                <td>
                                    <?= escapar($leitura['codigo']) ?> -
                                    <?= escapar($leitura['nome_sensor']) ?>
                                </td>
                                <td><?= escapar($leitura['tipo']) ?></td>
                                <td><?= escapar($leitura['valor_sensor']) ?></td>
                                <td>
                                    <span class="status <?= strtolower($leitura['status_sensor']) === 'normal' ? 'normal' : 'atencao' ?>">
                                        <?= escapar($leitura['status_sensor']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="vazio">
                                Nenhuma leitura encontrada.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p class="observacao">
            Exibindo no máximo 100 leituras, das mais recentes às mais antigas.
        </p>
    </section>
</main>

<footer class="footer-gauge">
    <div class="footer-container">
        <div class="footer-bloco bloco-esquerda">
            <span class="footer-label">Entre em contato</span>
            <a href="tel:47999174896" class="footer-link">
                (47) 99917-4896
            </a>
        </div>

        <div class="footer-bloco bloco-centro">
            <img src="../assets/logo_gauge_menor.png"
                 alt="Gauge Logo"
                 class="footer-logo">

            <p class="footer-copyright">
                &copy; 2026 Gauge. Todos os direitos reservados.
            </p>
        </div>

        <div class="footer-bloco bloco-direita">
            <span class="footer-label">Precisa de Suporte?</span>
            <a href="mailto:contato@gauge.com.br"
               class="footer-link link-sublinhado">
                contato@gauge.com.br
            </a>
        </div>
    </div>
</footer>

</body>
</html>
<?php
$stmtLeituras->close();
$conexao->close();
?>