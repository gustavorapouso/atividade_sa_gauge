<?php
// Gestão de sensores com três abas: adicionar, remover e listar.

require __DIR__ . '/../../includes/proteger.php';

$abas = [
    'adicionar' => 'Cadastrar sensor',
    'remover'   => 'Remover sensor',
    'lista'     => 'Lista de sensores',
];

$acao = $_GET['acao'] ?? 'adicionar';

if (!isset($abas[$acao])) {
    $acao = 'adicionar';
}

// Os valores precisam ser iguais aos tipos usados nos indicadores dos painéis
// (includes/paineis.php) e nos dados de exemplo do banco.
$tiposSensor = [
    'Velocidade'  => 'Velocidade',
    'Temperatura' => 'Temperatura do motor',
    'Consumo'     => 'Consumo de energia',
    'Vibração'    => 'Vibração',
    'Localização' => 'Localização',
];

$erro = '';
$mensagem = match (true) {
    isset($_GET['sucesso'])  => 'Sensor cadastrado com sucesso!',
    isset($_GET['removido']) => 'Sensor removido com sucesso!',
    default                  => '',
};

// Cadastrar sensor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_sensor'])) {
    $codigo      = trim($_POST['codigo'] ?? '');
    $tipo        = trim($_POST['tipo'] ?? '');
    $localizacao = trim($_POST['localizacao'] ?? '');
    $segmento    = trim($_POST['segmento'] ?? '');
    $idTrem      = filter_input(INPUT_POST, 'FK_id_trem', FILTER_VALIDATE_INT);
    $idTrilho    = filter_input(INPUT_POST, 'FK_id_trilho', FILTER_VALIDATE_INT);

    if ($codigo === '' || $tipo === '' || $localizacao === '' || !$idTrem || !$idTrilho) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        // O código do sensor não pode se repetir
        $stmt = $conexao->prepare('SELECT id_sensores FROM sensores WHERE codigo = ?');
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $duplicado = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($duplicado) {
            $erro = 'Já existe um sensor com esse código.';
        } else {
            $dataAdicao = date('Y-m-d H:i:s');

            $stmt = $conexao->prepare(
                'INSERT INTO sensores
                    (nome, codigo, tipo, estacao_sensores, segmento, `data_adição`, FK_id_trem, FK_id_trilho)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('ssssssii', $codigo, $codigo, $tipo, $localizacao, $segmento, $dataAdicao, $idTrem, $idTrilho);

            if ($stmt->execute()) {
                $stmt->close();
                redirecionar('paginas/sensores/sensores.php?acao=adicionar&sucesso=1');
            }

            $erro = 'Erro ao cadastrar sensor: ' . $stmt->error;
            $stmt->close();
        }
    }
}

// Remover sensor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_sensor'])) {
    $idSensor = filter_input(INPUT_POST, 'id_sensor', FILTER_VALIDATE_INT);

    if (!$idSensor) {
        $erro = 'Selecione um sensor válido.';
    } else {
        try {
            $stmt = $conexao->prepare('DELETE FROM sensores WHERE id_sensores = ?');
            $stmt->bind_param('i', $idSensor);
            $removeu = $stmt->execute() && $stmt->affected_rows > 0;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $removeu = false;
        }

        if ($removeu) {
            redirecionar('paginas/sensores/sensores.php?acao=remover&removido=1');
        }

        $erro = 'Não foi possível remover o sensor.';
    }
}

// Dados para os formulários e a lista
$trens = $conexao
    ->query('SELECT id_trem, prefixo, modelo FROM trem ORDER BY prefixo')
    ->fetch_all(MYSQLI_ASSOC);

$trilhos = $conexao
    ->query('SELECT id_trilho, localizacao FROM trilho ORDER BY localizacao')
    ->fetch_all(MYSQLI_ASSOC);

// Sensores com o valor da última leitura
$sensores = $conexao->query(
    'SELECT
        s.*,
        t.prefixo,
        t.modelo,
        (
            SELECT l.valor
            FROM leituras l
            WHERE l.id_sensor = s.id_sensores
            ORDER BY l.data_hora DESC, l.id_leitura DESC
            LIMIT 1
        ) AS valor_ultima_leitura,
        (
            SELECT l.unidade
            FROM leituras l
            WHERE l.id_sensor = s.id_sensores
            ORDER BY l.data_hora DESC, l.id_leitura DESC
            LIMIT 1
        ) AS unidade_ultima_leitura
     FROM sensores s
     LEFT JOIN trem t ON t.id_trem = s.FK_id_trem
     ORDER BY s.codigo'
)->fetch_all(MYSQLI_ASSOC);

$titulo = 'Sensores';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina"><?= h($abas[$acao]) ?></h1>

<nav class="abas" aria-label="Ações de sensores">
    <?php foreach ($abas as $chave => $rotulo): ?>
        <a href="?acao=<?= $chave ?>" class="aba" <?= $acao === $chave ? 'aria-current="page"' : '' ?>><?= h($rotulo) ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($mensagem !== ''): ?>
    <p class="mensagem sucesso" role="status"><?= h($mensagem) ?></p>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <p class="mensagem erro" role="alert"><?= h($erro) ?></p>
<?php endif; ?>

<?php if ($acao === 'adicionar'): ?>

    <form method="POST" class="painel">
        <div class="form-grade">
            <div class="campo">
                <label for="codigo">Código do sensor</label>
                <input type="text" name="codigo" id="codigo" placeholder="Ex: S-TEMP-001" required>
            </div>

            <div class="campo">
                <label for="tipo">Tipo de sensor</label>
                <select name="tipo" id="tipo" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($tiposSensor as $valor => $rotulo): ?>
                        <option value="<?= h($valor) ?>"><?= h($rotulo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="FK_id_trem">Trem</label>
                <select name="FK_id_trem" id="FK_id_trem" required>
                    <option value="">Selecione o trem...</option>
                    <?php foreach ($trens as $trem): ?>
                        <option value="<?= (int) $trem['id_trem'] ?>"><?= h($trem['prefixo'] . ' - ' . $trem['modelo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="FK_id_trilho">Trilho</label>
                <select name="FK_id_trilho" id="FK_id_trilho" required>
                    <option value="">Selecione o trilho...</option>
                    <?php foreach ($trilhos as $trilho): ?>
                        <option value="<?= (int) $trilho['id_trilho'] ?>"><?= h($trilho['localizacao']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="localizacao">Localização do sensor</label>
                <input type="text" name="localizacao" id="localizacao" placeholder="Ex: Trilho 2" required>
            </div>

            <div class="campo">
                <label for="segmento">Segmento</label>
                <input type="text" name="segmento" id="segmento" placeholder="Informe o segmento">
            </div>
        </div>

        <div class="acoes">
            <button type="submit" name="cadastrar_sensor" class="botao">
                <i class="fas fa-check"></i> Salvar
            </button>
        </div>
    </form>

<?php elseif ($acao === 'remover'): ?>

    <form method="POST" class="painel painel-estreito">
        <div class="campo">
            <label for="id_sensor">Selecione o sensor</label>
            <select name="id_sensor" id="id_sensor" required>
                <option value="">Selecione...</option>
                <?php foreach ($sensores as $sensor): ?>
                    <option value="<?= (int) $sensor['id_sensores'] ?>"><?= h($sensor['codigo'] . ' - ' . $sensor['tipo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="acoes">
            <button
                type="submit"
                name="remover_sensor"
                class="botao-perigo"
                onclick="return confirm('Deseja realmente remover este sensor?')">
                <i class="fas fa-trash"></i> Remover
            </button>
        </div>
    </form>

<?php elseif (count($sensores) === 0): ?>

    <div class="vazio-bloco">
        <i class="fas fa-microchip"></i>
        <p>Nenhum sensor cadastrado.</p>
    </div>

<?php else: ?>

    <section class="cartoes">
        <?php foreach ($sensores as $sensor): ?>
            <article class="cartao">
                <div class="cartao-topo">
                    <h3><?= h($sensor['codigo']) ?></h3>
                    <span class="cartao-icone"><i class="fas fa-microchip"></i></span>
                </div>

                <div class="dados">
                    <p><strong>Tipo:</strong> <?= h($sensor['tipo']) ?></p>
                    <p><strong>Trem:</strong> <?= h(($sensor['prefixo'] ?? 'Não informado') . ' - ' . ($sensor['modelo'] ?? '')) ?></p>
                    <p><strong>Localização:</strong> <?= h($sensor['estacao_sensores']) ?></p>
                    <p><strong>Segmento:</strong> <?= h($sensor['segmento']) ?></p>
                    <p>
                        <strong>Última leitura:</strong>
                        <?= $sensor['valor_ultima_leitura'] !== null
                            ? h($sensor['valor_ultima_leitura'] . ' ' . $sensor['unidade_ultima_leitura'])
                            : 'Nenhuma leitura registrada' ?>
                    </p>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

<?php endif; ?>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
