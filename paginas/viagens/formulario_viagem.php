<?php
// Cadastro e edição de viagens.
// Sem "id" na URL: cadastra uma nova viagem. Com "id": edita a viagem existente.

require __DIR__ . '/../../includes/proteger.php';

$statusViagem = [
    'programada'   => 'Programada',
    'em andamento' => 'Em andamento',
    'concluida'    => 'Concluída',
    'cancelada'    => 'Cancelada',
];

$mensagem = '';
$idViagem = (int) ($_POST['id_viagem'] ?? $_GET['id'] ?? 0);
$modoEdicao = $idViagem > 0;

$viagem = [
    'FK_id_trem'          => '',
    'FK_id_trilho'        => '',
    'estacao_trem'        => '',
    'origem'              => '',
    'destino'             => '',
    'data_hora_saida'     => '',
    'data_hora_chegada'   => '',
    'previsao_chegada'    => '',
    'status_trem'         => '',
    'velocidade_km_h'     => '',
    'historico_descricao' => '',
];

// Carrega a viagem para edição
if ($modoEdicao && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conexao->prepare(
        'SELECT ' . implode(', ', array_keys($viagem)) . ' FROM viagem WHERE id_viagem = ?'
    );
    $stmt->bind_param('i', $idViagem);
    $stmt->execute();
    $encontrada = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($encontrada) {
        $viagem = $encontrada;
    } else {
        $mensagem = 'Viagem não encontrada.';
        $modoEdicao = false;
        $idViagem = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($viagem as $campo => $valor) {
        $viagem[$campo] = trim($_POST[$campo] ?? '');
    }

    // O campo datetime-local envia "2026-01-31T08:00"; o banco espera "2026-01-31 08:00"
    foreach (['data_hora_saida', 'data_hora_chegada', 'previsao_chegada'] as $campo) {
        $viagem[$campo] = str_replace('T', ' ', $viagem[$campo]);
    }

    $valores = [
        $viagem['estacao_trem'],
        $viagem['data_hora_chegada'],
        $viagem['data_hora_saida'],
        $viagem['status_trem'],
        $viagem['origem'],
        $viagem['destino'],
        (float) $viagem['velocidade_km_h'],
        $viagem['previsao_chegada'],
        $viagem['historico_descricao'],
        (int) $viagem['FK_id_trem'],
        (int) $viagem['FK_id_trilho'],
    ];

    if ($modoEdicao) {
        $sql = 'UPDATE viagem
                SET estacao_trem = ?, data_hora_chegada = ?, data_hora_saida = ?, status_trem = ?,
                    origem = ?, destino = ?, velocidade_km_h = ?, previsao_chegada = ?,
                    historico_descricao = ?, FK_id_trem = ?, FK_id_trilho = ?
                WHERE id_viagem = ?';
        $tipos = 'ssssssdssiii';
        $valores[] = $idViagem;
    } else {
        $sql = 'INSERT INTO viagem
                    (estacao_trem, data_hora_chegada, data_hora_saida, status_trem, origem, destino,
                     velocidade_km_h, previsao_chegada, historico_descricao, FK_id_trem, FK_id_trilho)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $tipos = 'ssssssdssii';
    }

    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param($tipos, ...$valores);
        $stmt->execute();
        $stmt->close();

        redirecionar('paginas/viagens/viagens.php');
    } catch (mysqli_sql_exception $e) {
        $mensagem = 'Erro ao salvar a viagem: ' . $e->getMessage();
    }
}

// Converte "2026-01-31 08:00:00" para o formato do campo datetime-local
function valorDataHora($valor): string
{
    return formatarData($valor, 'Y-m-d\TH:i', '');
}

$trens = $conexao
    ->query('SELECT id_trem, prefixo, modelo FROM trem ORDER BY prefixo')
    ->fetch_all(MYSQLI_ASSOC);

$trilhos = $conexao
    ->query('SELECT id_trilho, localizacao, distancia FROM trilho ORDER BY localizacao')
    ->fetch_all(MYSQLI_ASSOC);

$titulo = $modoEdicao ? 'Editar viagem' : 'Nova viagem';
$menuAtivo = 'paginas/viagens/viagens.php';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina"><?= h($titulo) ?></h1>
<p class="subtitulo">Informe os dados da viagem para o sistema Ferrorama Gauge.</p>

<?php if ($mensagem !== ''): ?>
    <p class="mensagem erro" role="alert"><?= h($mensagem) ?></p>
<?php endif; ?>

<form method="POST" class="painel">
    <input type="hidden" name="id_viagem" value="<?= $idViagem ?>">

    <div class="form-grade">
        <div class="campo">
            <label for="FK_id_trem">Trem</label>
            <select name="FK_id_trem" id="FK_id_trem" required>
                <option value="">Selecione um trem</option>
                <?php foreach ($trens as $trem): ?>
                    <option value="<?= (int) $trem['id_trem'] ?>" <?= (int) $viagem['FK_id_trem'] === (int) $trem['id_trem'] ? 'selected' : '' ?>>
                        <?= h($trem['prefixo'] . ' - ' . $trem['modelo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="FK_id_trilho">Trilho</label>
            <select name="FK_id_trilho" id="FK_id_trilho" required>
                <option value="">Selecione um trilho</option>
                <?php foreach ($trilhos as $trilho): ?>
                    <option value="<?= (int) $trilho['id_trilho'] ?>" <?= (int) $viagem['FK_id_trilho'] === (int) $trilho['id_trilho'] ? 'selected' : '' ?>>
                        <?= h($trilho['localizacao'] . ' - ' . $trilho['distancia'] . ' km') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="estacao_trem">Estação atual</label>
            <input type="text" name="estacao_trem" id="estacao_trem" maxlength="45" placeholder="Ex: Estação Central" value="<?= h($viagem['estacao_trem']) ?>" required>
        </div>

        <div class="campo">
            <label for="origem">Origem</label>
            <input type="text" name="origem" id="origem" maxlength="45" placeholder="Ex: Joinville" value="<?= h($viagem['origem']) ?>" required>
        </div>

        <div class="campo">
            <label for="destino">Destino</label>
            <input type="text" name="destino" id="destino" maxlength="45" placeholder="Ex: Curitiba" value="<?= h($viagem['destino']) ?>" required>
        </div>

        <div class="campo">
            <label for="status_trem">Status</label>
            <select name="status_trem" id="status_trem" required>
                <option value="">Selecione o status</option>
                <?php foreach ($statusViagem as $valor => $rotulo): ?>
                    <option value="<?= h($valor) ?>" <?= $viagem['status_trem'] === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="data_hora_saida">Data e hora de saída</label>
            <input type="datetime-local" name="data_hora_saida" id="data_hora_saida" value="<?= h(valorDataHora($viagem['data_hora_saida'])) ?>" required>
        </div>

        <div class="campo">
            <label for="data_hora_chegada">Data e hora de chegada</label>
            <input type="datetime-local" name="data_hora_chegada" id="data_hora_chegada" value="<?= h(valorDataHora($viagem['data_hora_chegada'])) ?>" required>
        </div>

        <div class="campo">
            <label for="previsao_chegada">Previsão de chegada</label>
            <input type="datetime-local" name="previsao_chegada" id="previsao_chegada" value="<?= h(valorDataHora($viagem['previsao_chegada'])) ?>" required>
        </div>

        <div class="campo">
            <label for="velocidade_km_h">Velocidade (km/h)</label>
            <input type="number" name="velocidade_km_h" id="velocidade_km_h" min="0" step="0.01" placeholder="Ex: 80" value="<?= h($viagem['velocidade_km_h']) ?>" required>
        </div>

        <div class="campo campo-inteiro">
            <label for="historico_descricao">Descrição</label>
            <textarea name="historico_descricao" id="historico_descricao" maxlength="45" placeholder="Ex: Viagem iniciada normalmente" required><?= h($viagem['historico_descricao']) ?></textarea>
        </div>
    </div>

    <div class="acoes">
        <a href="viagens.php" class="botao-secundario">Cancelar</a>
        <button type="submit" class="botao">
            <i class="fas fa-check"></i>
            <?= $modoEdicao ? 'Salvar alterações' : 'Cadastrar viagem' ?>
        </button>
    </div>
</form>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
