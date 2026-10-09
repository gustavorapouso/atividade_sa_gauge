
<?php
session_start();

require '../../data_base/conexao.php';

$mensagem = '';
$erro = '';

function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// =====================================
// GERAR VALORES CONFORME O SENSOR
// =====================================

function gerarValor(string $tipo, bool $foraDoNormal): array
{
    $tipo = mb_strtolower($tipo, 'UTF-8');

    if (str_contains($tipo, 'velocidade')) {
        return $foraDoNormal
            ? [mt_rand(140, 200), 'km/h']
            : [mt_rand(0, 120), 'km/h'];
    }

    if (str_contains($tipo, 'temperatura')) {
        return $foraDoNormal
            ? [mt_rand(110, 150), '°C']
            : [mt_rand(60, 100), '°C'];
    }

    if (str_contains($tipo, 'consumo') ||
        str_contains($tipo, 'energia')) {
        return $foraDoNormal
            ? [mt_rand(1000, 1800) / 100, 'kWh']
            : [mt_rand(200, 900) / 100, 'kWh'];
    }

    if (str_contains($tipo, 'localização') ||
        str_contains($tipo, 'localizacao')) {
        return [mt_rand(0, 50000) / 100, 'km'];
    }

    return $foraDoNormal
        ? [mt_rand(101, 150), 'un']
        : [mt_rand(10, 100), 'un'];
}

// =====================================
// PROCESSAR FORMULÁRIO DO SIMULADOR
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['gerar_leituras'])) {

    $id_sensor = filter_input(
        INPUT_POST,
        'id_sensor',
        FILTER_VALIDATE_INT
    );

    $quantidade = filter_input(
        INPUT_POST,
        'quantidade',
        FILTER_VALIDATE_INT
    );

    $foraDoNormal = isset($_POST['fora_do_normal']);

    if (!$id_sensor || $id_sensor < 1) {
        $erro = 'Selecione um sensor.';
    } elseif (!$quantidade || $quantidade < 1 || $quantidade > 200) {
        $erro = 'A quantidade deve estar entre 1 e 200.';
    } else {
        // Confere se o sensor existe
        $sqlSensor = "
            SELECT id_sensores, codigo, tipo
            FROM sensores
            WHERE id_sensores = ?
        ";

        $stmtSensor = $conexao->prepare($sqlSensor);
        $stmtSensor->bind_param('i', $id_sensor);
        $stmtSensor->execute();

        $sensor = $stmtSensor->get_result()->fetch_assoc();
        $stmtSensor->close();

        if (!$sensor) {
            $erro = 'O sensor selecionado não foi encontrado.';
        } else {
            // Preparar a consulta apenas uma vez
            $sql = "
                INSERT INTO leituras
                    (id_sensor, data_hora, valor, unidade)
                VALUES (?, ?, ?, ?)
            ";

            $stmt = $conexao->prepare($sql);

            if (!$stmt) {
                $erro = 'Erro ao preparar a consulta.';
            } else {
                $id_sensor_db = (int) $sensor['id_sensores'];
                $data_hora = '';
                $valor = 0.0;
                $unidade = '';

                $stmt->bind_param(
                    'isds',
                    $id_sensor_db,
                    $data_hora,
                    $valor,
                    $unidade
                );

                try {
                    $conexao->begin_transaction();

                    for ($i = 0; $i < $quantidade; $i++) {
                        // Uma leitura a cada cinco minutos
                        $data_hora = date(
                            'Y-m-d H:i:s',
                            time() - (($quantidade - 1 - $i) * 300)
                        );

                        [$valor, $unidade] = gerarValor(
                            $sensor['tipo'],
                            $foraDoNormal
                        );

                        if (!$stmt->execute()) {
                            throw new Exception(
                                'Não foi possível salvar uma leitura.'
                            );
                        }
                    }

                    $conexao->commit();

                    $_SESSION['sucesso_leituras'] =
                        $quantidade . ' leituras geradas para o sensor ' .
                        $sensor['codigo'] . '.';

                    header('Location: simulador.php');
                    exit;
                } catch (Throwable $e) {
                    $conexao->rollback();
                    $erro = $e->getMessage();
                }

                $stmt->close();
            }
        }
    }
}

// =====================================
// MENSAGENS
// =====================================

if (isset($_SESSION['sucesso_leituras'])) {
    $mensagem = $_SESSION['sucesso_leituras'];
    unset($_SESSION['sucesso_leituras']);
}

// =====================================
// FILTROS DA LISTAGEM
// =====================================

$filtro_sensor = filter_input(
    INPUT_GET,
    'id_sensor',
    FILTER_VALIDATE_INT
) ?: 0;

$filtro_trem = filter_input(
    INPUT_GET,
    'id_trem',
    FILTER_VALIDATE_INT
) ?: 0;

$condicoes = [];
$parametros = [];
$tipos_parametros = '';

if ($filtro_sensor > 0) {
    $condicoes[] = 'l.id_sensor = ?';
    $parametros[] = $filtro_sensor;
    $tipos_parametros .= 'i';
}

if ($filtro_trem > 0) {
    $condicoes[] = 's.FK_id_trem = ?';
    $parametros[] = $filtro_trem;
    $tipos_parametros .= 'i';
}

$where = $condicoes
    ? 'WHERE ' . implode(' AND ', $condicoes)
    : '';

// =====================================
// CONSULTAR LEITURAS
// =====================================

$sqlLeituras = "
    SELECT
        l.id_leitura,
        l.data_hora,
        l.valor,
        l.unidade,
        s.codigo,
        s.tipo,
        t.prefixo
    FROM leituras l
    INNER JOIN sensores s
        ON s.id_sensores = l.id_sensor
    INNER JOIN trem t
        ON t.id_trem = s.FK_id_trem
    $where
    ORDER BY l.data_hora DESC
    LIMIT 100
";

$stmtLeituras = $conexao->prepare($sqlLeituras);

if (!$stmtLeituras) {
    die('Erro na consulta de leituras: ' . escapar($conexao->error));
}

if ($parametros) {
    $stmtLeituras->bind_param($tipos_parametros, ...$parametros);
}

$stmtLeituras->execute();
$resultadoLeituras = $stmtLeituras->get_result();

// =====================================
// LISTAR SENSORES E TRENS DOS FILTROS
// =====================================

$resultadoSensores = $conexao->query("
    SELECT id_sensores, codigo, tipo
    FROM sensores
    ORDER BY codigo
");

$resultadoTrens = $conexao->query("
    SELECT id_trem, prefixo, modelo
    FROM trem
    ORDER BY prefixo
");

if (!$resultadoSensores || !$resultadoTrens) {
    die('Erro ao carregar sensores ou trens: ' . escapar($conexao->error));
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Leituras dos sensores - Ferrorama Gauge</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="simulador.css">
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

    <main>
        <h1>Leituras dos sensores</h1>
        <p class="subtitulo">
            Acompanhe e simule as leituras dos sensores do Ferrorama Gauge.
        </p>

        <?php if ($mensagem !== ''): ?>
            <div class="aviso sucesso"><?= escapar($mensagem) ?></div>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <div class="aviso erro"><?= escapar($erro) ?></div>
        <?php endif; ?>

        <!-- GERAR LEITURAS -->
        <section class="cartao">
            <h2>Gerar leituras</h2>

            <?php if ($resultadoSensores->num_rows === 0): ?>
                <p class="nota">
                    Nenhum sensor cadastrado. Cadastre um sensor antes
                    de gerar leituras.
                </p>
            <?php else: ?>
                <form method="POST" action="simulador.php">
                    <div class="linha">
                        <div class="campo">
                            <label for="sensor_geracao">Sensor</label>

                            <select name="id_sensor" id="sensor_geracao" required>
                                <option value="">Selecione um sensor</option>

                                <?php
                                $resultadoSensores->data_seek(0);
                                while ($s = $resultadoSensores->fetch_assoc()):
                                ?>
                                    <option value="<?= (int) $s['id_sensores'] ?>">
                                        <?= escapar(
                                            $s['codigo'] . ' - ' . $s['tipo']
                                        ) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="campo">
                            <label for="quantidade">Quantidade de leituras</label>
                            <input
                                type="number"
                                name="quantidade"
                                id="quantidade"
                                min="1"
                                max="200"
                                value="10"
                                required
                            >
                        </div>
                    </div>

                    <label class="opcao" for="fora_do_normal">
                        <input
                            type="checkbox"
                            name="fora_do_normal"
                            id="fora_do_normal"
                            value="1"
                        >
                        <span>
                            <strong>Gerar valores acima do normal</strong>
                            <br>
                            <span class="nota">
                                Use esta opção para testar os alertas
                                da próxima entrega.
                            </span>
                        </span>
                    </label>

                    <div class="acoes">
                        <button type="submit" name="gerar_leituras">
                            <i class="fas fa-check"></i>
                            Gerar leituras
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <!-- FILTROS -->
        <section class="cartao">
            <h2>Consultar leituras</h2>

            <form method="GET" action="simulador.php">
                <div class="linha">
                    <div class="campo">
                        <label for="filtro_sensor">Sensor</label>
                        <select name="id_sensor" id="filtro_sensor">
                            <option value="0">Todos os sensores</option>

                            <?php
                            $resultadoSensores->data_seek(0);
                            while ($s = $resultadoSensores->fetch_assoc()):
                            ?>
                                <option
                                    value="<?= (int) $s['id_sensores'] ?>"
                                    <?= $filtro_sensor == $s['id_sensores']
                                        ? 'selected' : '' ?>
                                >
                                    <?= escapar($s['codigo'] . ' - ' . $s['tipo']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="filtro_trem">Trem</label>
                        <select name="id_trem" id="filtro_trem">
                            <option value="0">Todos os trens</option>

                            <?php while ($t = $resultadoTrens->fetch_assoc()): ?>
                                <option
                                    value="<?= (int) $t['id_trem'] ?>"
                                    <?= $filtro_trem == $t['id_trem']
                                        ? 'selected' : '' ?>
                                >
                                    <?= escapar($t['prefixo'] . ' - ' . $t['modelo']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="acoes">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                        Filtrar
                    </button>

                    <a class="botao-secundario" href="simulador.php">
                        Limpar filtros
                    </a>
                </div>
            </form>
        </section>

        <!-- TABELA DE LEITURAS -->
        <section class="cartao">
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
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($resultadoLeituras->num_rows > 0): ?>
                            <?php while ($leitura = $resultadoLeituras->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int) $leitura['id_leitura'] ?></td>
                                    <td>
                                        <?= escapar(date(
                                            'd/m/Y H:i:s',
                                            strtotime($leitura['data_hora'])
                                        )) ?>
                                    </td>
                                    <td><?= escapar($leitura['prefixo']) ?></td>
                                    <td><?= escapar($leitura['codigo']) ?></td>
                                    <td><?= escapar($leitura['tipo']) ?></td>
                                    <td>
                                        <?= escapar(number_format(
                                            (float) $leitura['valor'],
                                            2,
                                            ',',
                                            '.'
                                        )) ?>
                                        <?= escapar($leitura['unidade']) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="sem-resultados">
                                    Nenhuma leitura encontrada.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <p class="nota">
                Exibindo no máximo 100 leituras, das mais recentes às mais antigas.
            </p>
        </section>
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