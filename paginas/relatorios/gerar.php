<?php
// Formulário para escolher o tipo de relatório e os filtros.
// Os resultados são exibidos em visualizar.php.

require __DIR__ . '/../../includes/proteger.php';

$trens = $conexao
    ->query('SELECT id_trem, prefixo, modelo FROM trem ORDER BY prefixo')
    ->fetch_all(MYSQLI_ASSOC);

$tiposRelatorio = [
    'desempenho' => 'Desempenho dos trens',
    'consumo'    => 'Consumo de energia',
    'eficiencia' => 'Eficiência operacional',
    'manutencao' => 'Manutenção',
];

$opcoesStatus = [
    ''             => 'Todos',
    'normal'       => 'Normal',
    'atencao'      => 'Atenção',
    'critico'      => 'Crítico',
    'programada'   => 'Programada',
    'em andamento' => 'Em andamento',
    'concluida'    => 'Concluída',
    'cancelada'    => 'Cancelada',
    'atrasada'     => 'Atrasada',
];

$tipoSelecionado   = $_GET['tipo'] ?? 'desempenho';
$inicio            = $_GET['data_inicio'] ?? '';
$fim               = $_GET['data_fim'] ?? '';
$tremSelecionado   = filter_input(INPUT_GET, 'id_trem', FILTER_VALIDATE_INT) ?: 0;
$statusSelecionado = $_GET['status'] ?? '';

$titulo = 'Relatórios';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina">Relatórios</h1>
<p class="subtitulo">Consulte e acompanhe os dados ferroviários.</p>

<form method="GET" action="visualizar.php" class="painel form-grade">
    <div class="campo">
        <label for="tipo">Tipo de relatório</label>
        <select name="tipo" id="tipo" required>
            <?php foreach ($tiposRelatorio as $valor => $rotulo): ?>
                <option value="<?= $valor ?>" <?= $tipoSelecionado === $valor ? 'selected' : '' ?>><?= h($rotulo) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="data_inicio">Data inicial (opcional)</label>
        <input type="date" name="data_inicio" id="data_inicio" value="<?= h($inicio) ?>">
    </div>

    <div class="campo">
        <label for="data_fim">Data final (opcional)</label>
        <input type="date" name="data_fim" id="data_fim" value="<?= h($fim) ?>">
    </div>

    <div class="campo">
        <label for="id_trem">Trem</label>
        <select name="id_trem" id="id_trem">
            <option value="0">Todos os trens</option>
            <?php foreach ($trens as $trem): ?>
                <option value="<?= (int) $trem['id_trem'] ?>" <?= $tremSelecionado === (int) $trem['id_trem'] ? 'selected' : '' ?>>
                    <?= h($trem['prefixo'] . ' - ' . $trem['modelo']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="status">Status / severidade</label>
        <select name="status" id="status">
            <?php foreach ($opcoesStatus as $valor => $rotulo): ?>
                <option value="<?= h($valor) ?>" <?= $statusSelecionado === $valor ? 'selected' : '' ?>><?= h($rotulo) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="acoes campo-inteiro">
        <button type="submit" class="botao">
            <i class="fas fa-chart-column"></i> Gerar relatório
        </button>
    </div>
</form>

<p class="mensagem">
    <i class="fas fa-circle-info"></i>
    As datas são opcionais. Deixe as duas vazias para consultar todo o histórico.
</p>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
