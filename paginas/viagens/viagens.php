<?php
// Lista de viagens com filtros por status, trem, período e cidade.

require __DIR__ . '/../../includes/proteger.php';

$statusViagem = [
    'programada'   => 'Programada',
    'em andamento' => 'Em andamento',
    'concluida'    => 'Concluída',
    'cancelada'    => 'Cancelada',
];

// Filtros
$status     = $_GET['status'] ?? '';
$idTrem     = (int) ($_GET['id_trem'] ?? 0);
$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim    = $_GET['data_fim'] ?? '';
$busca      = trim($_GET['busca'] ?? '');

$condicoes = [];
$valores = [];
$tipos = '';

if ($status !== '') {
    $condicoes[] = 'viagem.status_trem = ?';
    $valores[] = $status;
    $tipos .= 's';
}

if ($idTrem > 0) {
    $condicoes[] = 'viagem.FK_id_trem = ?';
    $valores[] = $idTrem;
    $tipos .= 'i';
}

if ($dataInicio !== '' && $dataFim !== '') {
    $condicoes[] = 'DATE(viagem.data_hora_saida) BETWEEN ? AND ?';
    array_push($valores, $dataInicio, $dataFim);
    $tipos .= 'ss';
}

if ($busca !== '') {
    $condicoes[] = '(viagem.origem LIKE ? OR viagem.destino LIKE ?)';
    $termo = '%' . $busca . '%';
    array_push($valores, $termo, $termo);
    $tipos .= 'ss';
}

$where = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';

$stmt = $conexao->prepare(
    "SELECT viagem.*, trem.prefixo AS nome_trem, trem.modelo
     FROM viagem
     INNER JOIN trem ON trem.id_trem = viagem.FK_id_trem
     $where
     ORDER BY viagem.data_hora_saida DESC"
);

if ($valores) {
    $stmt->bind_param($tipos, ...$valores);
}

$stmt->execute();
$viagens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Trens para o filtro
$trens = $conexao
    ->query('SELECT id_trem, prefixo, modelo FROM trem ORDER BY prefixo')
    ->fetch_all(MYSQLI_ASSOC);

$titulo = 'Viagens';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="cabecalho-pagina">
    <h1 class="titulo-pagina">Viagens</h1>

    <a href="formulario_viagem.php" class="botao">
        <i class="fas fa-plus"></i> Nova viagem
    </a>
</div>

<form method="GET" class="painel form-grade">
    <div class="campo">
        <label for="status">Status</label>
        <select name="status" id="status">
            <option value="">Todos</option>
            <?php foreach ($statusViagem as $valor => $rotulo): ?>
                <option value="<?= h($valor) ?>" <?= $status === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="id_trem">Trem</label>
        <select name="id_trem" id="id_trem">
            <option value="0">Todos</option>
            <?php foreach ($trens as $trem): ?>
                <option value="<?= (int) $trem['id_trem'] ?>" <?= $idTrem === (int) $trem['id_trem'] ? 'selected' : '' ?>>
                    <?= h($trem['prefixo'] . ' - ' . $trem['modelo']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="data_inicio">Data inicial</label>
        <input type="date" name="data_inicio" id="data_inicio" value="<?= h($dataInicio) ?>">
    </div>

    <div class="campo">
        <label for="data_fim">Data final</label>
        <input type="date" name="data_fim" id="data_fim" value="<?= h($dataFim) ?>">
    </div>

    <div class="campo">
        <label for="busca">Origem ou destino</label>
        <input type="text" name="busca" id="busca" placeholder="Ex: Joinville" value="<?= h($busca) ?>">
    </div>

    <div class="acoes campo-inteiro">
        <a href="viagens.php" class="botao-secundario">Limpar</a>
        <button type="submit" class="botao">
            <i class="fas fa-filter"></i> Filtrar
        </button>
    </div>
</form>

<div class="tabela-responsiva">
    <table>
        <thead>
            <tr>
                <th>Trem</th>
                <th>Modelo</th>
                <th>Origem</th>
                <th>Destino</th>
                <th>Saída</th>
                <th>Chegada</th>
                <th>Status</th>
                <th>Velocidade</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($viagens as $viagem): ?>
                <tr>
                    <td><?= h($viagem['nome_trem']) ?></td>
                    <td><?= h($viagem['modelo']) ?></td>
                    <td><?= h($viagem['origem']) ?></td>
                    <td><?= h($viagem['destino']) ?></td>
                    <td><?= h(formatarData($viagem['data_hora_saida'], 'd/m/Y H:i', '-')) ?></td>
                    <td><?= h(formatarData($viagem['data_hora_chegada'], 'd/m/Y H:i', '-')) ?></td>
                    <td><span class="status"><?= h($viagem['status_trem']) ?></span></td>
                    <td><?= h($viagem['velocidade_km_h']) ?> km/h</td>
                    <td>
                        <div class="acoes-tabela">
                            <a href="formulario_viagem.php?id=<?= (int) $viagem['id_viagem'] ?>" class="icone-acao editar" title="Editar" aria-label="Editar viagem">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a
                                href="excluir_viagem.php?id=<?= (int) $viagem['id_viagem'] ?>"
                                class="icone-acao excluir"
                                title="Excluir"
                                aria-label="Excluir viagem"
                                onclick="return confirm('Deseja realmente excluir esta viagem?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (count($viagens) === 0): ?>
                <tr>
                    <td colspan="9" class="vazio">Nenhuma viagem encontrada.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
