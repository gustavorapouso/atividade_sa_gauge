<?php
// Leituras dos sensores (tabela dados_sensores): gerar leituras de exemplo e consultar.

require __DIR__ . '/../../includes/proteger.php';

$mensagem = '';
$erro = '';

$filtroSensor = (int) ($_GET['sensor'] ?? 0);
$filtroTrem   = (int) ($_GET['trem'] ?? 0);

// Gera leituras de exemplo para um sensor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_leituras'])) {
    $idSensor   = (int) ($_POST['id_sensor'] ?? 0);
    $quantidade = (int) ($_POST['quantidade'] ?? 10);

    $stmt = $conexao->prepare('SELECT tipo FROM sensores WHERE id_sensores = ?');
    $stmt->bind_param('i', $idSensor);
    $stmt->execute();
    $sensor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($quantidade < 1 || $quantidade > 100) {
        $erro = 'Escolha uma quantidade entre 1 e 100.';
    } elseif (!$sensor) {
        $erro = 'Selecione um sensor válido.';
    } else {
        // Faixa de valores de acordo com o tipo do sensor
        $tipo = strtolower($sensor['tipo']);

        [$minimo, $maximo] = match (true) {
            str_contains($tipo, 'temperatura')                                => [60, 100],
            str_contains($tipo, 'velocidade')                                 => [20, 120],
            str_contains($tipo, 'consumo') || str_contains($tipo, 'energia') => [10, 80],
            default                                                           => [1, 100],
        };

        $stmt = $conexao->prepare(
            'INSERT INTO dados_sensores (data_hora, valor_sensor, status_sensor, FK_id_sensores)
             VALUES (NOW(), ?, ?, ?)'
        );

        $status = 'normal';
        $valor = 0.0;
        $stmt->bind_param('dsi', $valor, $status, $idSensor);

        for ($i = 0; $i < $quantidade; $i++) {
            $valor = mt_rand($minimo * 10, $maximo * 10) / 10;

            if (!$stmt->execute()) {
                $erro = 'Não foi possível registrar as leituras.';
                break;
            }
        }

        $stmt->close();

        if ($erro === '') {
            $mensagem = $quantidade . ' leitura(s) gerada(s) com sucesso.';
        }
    }
}

// Sensores e trens para os formulários
$sensores = $conexao->query(
    'SELECT s.id_sensores, s.codigo, s.nome, t.prefixo
     FROM sensores s
     INNER JOIN trem t ON s.FK_id_trem = t.id_trem
     ORDER BY s.id_sensores DESC'
)->fetch_all(MYSQLI_ASSOC);

$trens = $conexao
    ->query('SELECT id_trem, prefixo FROM trem ORDER BY prefixo')
    ->fetch_all(MYSQLI_ASSOC);

// Últimas leituras (com filtros)
$stmt = $conexao->prepare(
    'SELECT
        d.id_dados_sensores, d.data_hora, d.valor_sensor, d.status_sensor,
        s.codigo, s.nome AS nome_sensor, s.tipo,
        t.prefixo
     FROM dados_sensores d
     INNER JOIN sensores s ON d.FK_id_sensores = s.id_sensores
     INNER JOIN trem t ON s.FK_id_trem = t.id_trem
     WHERE (? = 0 OR s.id_sensores = ?)
       AND (? = 0 OR t.id_trem = ?)
     ORDER BY d.data_hora DESC
     LIMIT 100'
);
$stmt->bind_param('iiii', $filtroSensor, $filtroSensor, $filtroTrem, $filtroTrem);
$stmt->execute();
$leituras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$titulo = 'Leituras dos sensores';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina">Leituras dos sensores</h1>
<p class="subtitulo">Acompanhe e simule as leituras dos sensores do Ferrorama Gauge.</p>

<?php if ($mensagem !== ''): ?>
    <p class="mensagem sucesso" role="status"><?= h($mensagem) ?></p>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <p class="mensagem erro" role="alert"><?= h($erro) ?></p>
<?php endif; ?>

<section class="painel">
    <h2><i class="fas fa-plus-circle"></i> Gerar leituras</h2>

    <form method="POST" class="form-grade">
        <div class="campo">
            <label for="id_sensor">Sensor</label>
            <select name="id_sensor" id="id_sensor" required>
                <option value="">Selecione um sensor</option>
                <?php foreach ($sensores as $sensor): ?>
                    <option value="<?= (int) $sensor['id_sensores'] ?>">
                        <?= h($sensor['codigo'] . ' - ' . $sensor['nome'] . ' - Trem ' . $sensor['prefixo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="quantidade">Quantidade de leituras</label>
            <input type="number" id="quantidade" name="quantidade" min="1" max="100" value="10" required>
        </div>

        <div class="acoes campo-inteiro">
            <button type="submit" name="gerar_leituras" class="botao">
                <i class="fas fa-check"></i> Gerar leituras
            </button>
        </div>
    </form>
</section>

<section class="painel">
    <h2><i class="fas fa-filter"></i> Consultar leituras</h2>

    <form method="GET" class="form-grade">
        <div class="campo">
            <label for="sensor">Sensor</label>
            <select name="sensor" id="sensor">
                <option value="0">Todos os sensores</option>
                <?php foreach ($sensores as $sensor): ?>
                    <option value="<?= (int) $sensor['id_sensores'] ?>" <?= $filtroSensor === (int) $sensor['id_sensores'] ? 'selected' : '' ?>>
                        <?= h($sensor['codigo'] . ' - ' . $sensor['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="trem">Trem</label>
            <select name="trem" id="trem">
                <option value="0">Todos os trens</option>
                <?php foreach ($trens as $trem): ?>
                    <option value="<?= (int) $trem['id_trem'] ?>" <?= $filtroTrem === (int) $trem['id_trem'] ? 'selected' : '' ?>>
                        <?= h($trem['prefixo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="acoes campo-inteiro">
            <a href="leituras.php" class="botao-secundario">Limpar filtros</a>
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
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($leituras as $leitura): ?>
                    <tr>
                        <td><?= (int) $leitura['id_dados_sensores'] ?></td>
                        <td><?= h(formatarData($leitura['data_hora'], 'd/m/Y H:i:s')) ?></td>
                        <td><?= h($leitura['prefixo']) ?></td>
                        <td><?= h($leitura['codigo'] . ' - ' . $leitura['nome_sensor']) ?></td>
                        <td><?= h($leitura['tipo']) ?></td>
                        <td><?= h($leitura['valor_sensor']) ?></td>
                        <td>
                            <span class="status <?= strtolower($leitura['status_sensor']) === 'normal' ? 'status-normal' : 'status-atencao' ?>">
                                <?= h($leitura['status_sensor']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (count($leituras) === 0): ?>
                    <tr>
                        <td colspan="7" class="vazio">Nenhuma leitura encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p class="nota">Exibindo no máximo 100 leituras, das mais recentes às mais antigas.</p>
</section>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
