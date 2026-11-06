<?php
// Simulador de leituras (tabela leituras).
// Gera leituras para um sensor e registra alertas automaticamente
// quando o valor passa do limite (ver includes/limites.php).

require __DIR__ . '/../../includes/proteger.php';
require __DIR__ . '/../../includes/limites.php';

$mensagem = '';
$erro = '';

// Gera um valor aleatório conforme o tipo do sensor.
// Retorna [valor, unidade].
function gerarValor(string $tipo, bool $foraDoNormal): array
{
    $tipo = mb_strtolower($tipo, 'UTF-8');

    if (str_contains($tipo, 'velocidade')) {
        return [$foraDoNormal ? mt_rand(140, 200) : mt_rand(0, 120), 'km/h'];
    }

    if (str_contains($tipo, 'temperatura')) {
        return [$foraDoNormal ? mt_rand(110, 150) : mt_rand(60, 100), '°C'];
    }

    if (str_contains($tipo, 'consumo') || str_contains($tipo, 'energia')) {
        return [($foraDoNormal ? mt_rand(1000, 1800) : mt_rand(200, 900)) / 100, 'kWh'];
    }

    if (str_contains($tipo, 'localização') || str_contains($tipo, 'localizacao')) {
        return [mt_rand(0, 50000) / 100, 'km'];
    }

    return [$foraDoNormal ? mt_rand(101, 150) : mt_rand(10, 100), 'un'];
}

// Processa o formulário do simulador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_leituras'])) {
    $idSensor     = filter_input(INPUT_POST, 'id_sensor', FILTER_VALIDATE_INT);
    $quantidade   = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
    $foraDoNormal = isset($_POST['fora_do_normal']);

    $sensor = null;

    if ($idSensor) {
        $stmt = $conexao->prepare('SELECT id_sensores, codigo, tipo FROM sensores WHERE id_sensores = ?');
        $stmt->bind_param('i', $idSensor);
        $stmt->execute();
        $sensor = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$idSensor || $idSensor < 1) {
        $erro = 'Selecione um sensor.';
    } elseif (!$quantidade || $quantidade < 1 || $quantidade > 200) {
        $erro = 'A quantidade deve estar entre 1 e 200.';
    } elseif (!$sensor) {
        $erro = 'O sensor selecionado não foi encontrado.';
    } else {
        try {
            $conexao->begin_transaction();

            $stmtLeitura = $conexao->prepare(
                'INSERT INTO leituras (id_sensor, data_hora, valor, unidade) VALUES (?, ?, ?, ?)'
            );
            $stmtAlerta = $conexao->prepare(
                'INSERT INTO alertas (id_leitura, tipo, severidade, mensagem, data_hora) VALUES (?, ?, ?, ?, ?)'
            );

            for ($i = 0; $i < $quantidade; $i++) {
                // Leituras com intervalos de cinco minutos, terminando agora
                $dataHora = date('Y-m-d H:i:s', time() - (($quantidade - 1 - $i) * 300));
                [$valor, $unidade] = gerarValor($sensor['tipo'], $foraDoNormal);

                // 1. Salva a leitura
                $stmtLeitura->bind_param('isds', $sensor['id_sensores'], $dataHora, $valor, $unidade);
                if (!$stmtLeitura->execute()) {
                    throw new Exception('Não foi possível salvar uma leitura.');
                }

                $idLeitura = $conexao->insert_id;

                // 2. Registra um alerta se a leitura não for normal
                $severidade = classificar($sensor['tipo'], (float) $valor);

                if ($severidade !== 'normal') {
                    $mensagemAlerta = 'Sensor ' . $sensor['codigo'] . ': leitura de ' . $valor . ' ' . $unidade . ' acima do limite esperado.';

                    $stmtAlerta->bind_param('issss', $idLeitura, $sensor['tipo'], $severidade, $mensagemAlerta, $dataHora);
                    if (!$stmtAlerta->execute()) {
                        throw new Exception('Não foi possível registrar o alerta.');
                    }
                }
            }

            $stmtLeitura->close();
            $stmtAlerta->close();

            // Confirma leituras e alertas juntos
            $conexao->commit();

            $_SESSION['sucesso_leituras'] = $quantidade . ' leituras geradas para o sensor ' . $sensor['codigo'] . '. Os alertas foram verificados automaticamente.';
            redirecionar('paginas/sensores/simulador.php');
        } catch (Throwable $e) {
            $conexao->rollback();
            $erro = $e->getMessage();
        }
    }
}

// Mensagem guardada antes do redirecionamento
if (isset($_SESSION['sucesso_leituras'])) {
    $mensagem = $_SESSION['sucesso_leituras'];
    unset($_SESSION['sucesso_leituras']);
}

// Filtros da listagem
$filtroSensor = filter_input(INPUT_GET, 'id_sensor', FILTER_VALIDATE_INT) ?: 0;
$filtroTrem   = filter_input(INPUT_GET, 'id_trem', FILTER_VALIDATE_INT) ?: 0;

$condicoes = [];
$parametros = [];
$tipos = '';

if ($filtroSensor > 0) {
    $condicoes[] = 'l.id_sensor = ?';
    $parametros[] = $filtroSensor;
    $tipos .= 'i';
}

if ($filtroTrem > 0) {
    $condicoes[] = 's.FK_id_trem = ?';
    $parametros[] = $filtroTrem;
    $tipos .= 'i';
}

$where = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';

$stmt = $conexao->prepare(
    "SELECT l.id_leitura, l.data_hora, l.valor, l.unidade, s.codigo, s.tipo, t.prefixo
     FROM leituras l
     INNER JOIN sensores s ON s.id_sensores = l.id_sensor
     INNER JOIN trem t ON t.id_trem = s.FK_id_trem
     $where
     ORDER BY l.data_hora DESC
     LIMIT 100"
);

if ($parametros) {
    $stmt->bind_param($tipos, ...$parametros);
}

$stmt->execute();
$leituras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Sensores e trens dos formulários
$sensores = $conexao
    ->query('SELECT id_sensores, codigo, tipo FROM sensores ORDER BY codigo')
    ->fetch_all(MYSQLI_ASSOC);

$trens = $conexao
    ->query('SELECT id_trem, prefixo, modelo FROM trem ORDER BY prefixo')
    ->fetch_all(MYSQLI_ASSOC);

$titulo = 'Simulador de leituras';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina">Simulador de leituras</h1>
<p class="subtitulo">Gere leituras de teste para os sensores. Leituras acima do limite geram alertas automaticamente.</p>

<?php if ($mensagem !== ''): ?>
    <p class="mensagem sucesso" role="status"><?= h($mensagem) ?></p>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <p class="mensagem erro" role="alert"><?= h($erro) ?></p>
<?php endif; ?>

<section class="painel">
    <h2><i class="fas fa-flask"></i> Gerar leituras</h2>

    <?php if (count($sensores) === 0): ?>
        <p class="nota">Nenhum sensor cadastrado. Cadastre um sensor antes de gerar leituras.</p>
    <?php else: ?>

        <form method="POST">
            <div class="form-grade">
                <div class="campo">
                    <label for="sensor_geracao">Sensor</label>
                    <select name="id_sensor" id="sensor_geracao" required>
                        <option value="">Selecione um sensor</option>
                        <?php foreach ($sensores as $sensor): ?>
                            <option value="<?= (int) $sensor['id_sensores'] ?>"><?= h($sensor['codigo'] . ' - ' . $sensor['tipo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="quantidade">Quantidade de leituras</label>
                    <input type="number" name="quantidade" id="quantidade" min="1" max="200" value="10" required>
                </div>
            </div>

            <label class="campo-opcao" for="fora_do_normal">
                <input type="checkbox" name="fora_do_normal" id="fora_do_normal" value="1">
                <span>
                    <strong>Gerar valores acima do normal</strong><br>
                    <span class="nota">Use esta opção para testar os alertas.</span>
                </span>
            </label>

            <div class="acoes">
                <button type="submit" name="gerar_leituras" class="botao">
                    <i class="fas fa-check"></i> Gerar leituras
                </button>
            </div>
        </form>

    <?php endif; ?>
</section>

<section class="painel">
    <h2><i class="fas fa-filter"></i> Consultar leituras</h2>

    <form method="GET" class="form-grade">
        <div class="campo">
            <label for="filtro_sensor">Sensor</label>
            <select name="id_sensor" id="filtro_sensor">
                <option value="0">Todos os sensores</option>
                <?php foreach ($sensores as $sensor): ?>
                    <option value="<?= (int) $sensor['id_sensores'] ?>" <?= $filtroSensor === (int) $sensor['id_sensores'] ? 'selected' : '' ?>>
                        <?= h($sensor['codigo'] . ' - ' . $sensor['tipo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="filtro_trem">Trem</label>
            <select name="id_trem" id="filtro_trem">
                <option value="0">Todos os trens</option>
                <?php foreach ($trens as $trem): ?>
                    <option value="<?= (int) $trem['id_trem'] ?>" <?= $filtroTrem === (int) $trem['id_trem'] ? 'selected' : '' ?>>
                        <?= h($trem['prefixo'] . ' - ' . $trem['modelo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="acoes campo-inteiro">
            <a href="simulador.php" class="botao-secundario">Limpar filtros</a>
            <button type="submit" class="botao">
                <i class="fas fa-search"></i> Filtrar
            </button>
        </div>
    </form>
</section>

<section class="painel">
    <h2><i class="fas fa-list"></i> Últimas leituras</h2>

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
                <?php foreach ($leituras as $leitura): ?>
                    <tr>
                        <td><?= (int) $leitura['id_leitura'] ?></td>
                        <td><?= h(formatarData($leitura['data_hora'], 'd/m/Y H:i:s')) ?></td>
                        <td><?= h($leitura['prefixo']) ?></td>
                        <td><?= h($leitura['codigo']) ?></td>
                        <td><?= h($leitura['tipo']) ?></td>
                        <td><?= number_format((float) $leitura['valor'], 2, ',', '.') ?> <?= h($leitura['unidade']) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (count($leituras) === 0): ?>
                    <tr>
                        <td colspan="6" class="vazio">Nenhuma leitura encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p class="nota">Exibindo no máximo 100 leituras, das mais recentes às mais antigas.</p>
</section>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
