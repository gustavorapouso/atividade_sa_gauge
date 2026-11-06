<?php
// Alertas gerados pelas leituras dos sensores, separados por severidade.

require __DIR__ . '/../../includes/proteger.php';

// Filtros
$filtroSeveridade = $_GET['severidade'] ?? '';
$filtroTipo = $_GET['tipo'] ?? '';

if (!in_array($filtroSeveridade, ['', 'atencao', 'critico'], true)) {
    $filtroSeveridade = '';
}

$condicoes = [];
$parametros = [];
$tipos = '';

if ($filtroSeveridade !== '') {
    $condicoes[] = 'a.severidade = ?';
    $parametros[] = $filtroSeveridade;
    $tipos .= 's';
}

if ($filtroTipo !== '') {
    $condicoes[] = 's.tipo = ?';
    $parametros[] = $filtroTipo;
    $tipos .= 's';
}

$where = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';

$stmt = $conexao->prepare(
    "SELECT
        a.severidade, a.mensagem, a.data_hora,
        l.valor, l.unidade,
        s.codigo,
        t.prefixo
     FROM alertas a
     INNER JOIN leituras l ON l.id_leitura = a.id_leitura
     INNER JOIN sensores s ON s.id_sensores = l.id_sensor
     INNER JOIN trem t ON t.id_trem = s.FK_id_trem
     $where
     ORDER BY
        CASE
            WHEN a.severidade = 'critico' THEN 1
            WHEN a.severidade = 'atencao' THEN 2
            ELSE 3
        END,
        a.data_hora DESC
     LIMIT 200"
);

if ($parametros) {
    $stmt->bind_param($tipos, ...$parametros);
}

$stmt->execute();
$alertas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Tipos de sensores para o filtro
$tiposSensor = $conexao->query(
    "SELECT DISTINCT tipo FROM sensores WHERE tipo IS NOT NULL AND tipo <> '' ORDER BY tipo"
)->fetch_all(MYSQLI_ASSOC);

// Separa os alertas em dois cartões
$grupos = [
    'critico' => ['titulo' => 'Críticos', 'vazio' => 'Nenhum alerta crítico encontrado.',   'alertas' => []],
    'atencao' => ['titulo' => 'Alertas',  'vazio' => 'Nenhum alerta de atenção encontrado.', 'alertas' => []],
];

foreach ($alertas as $alerta) {
    $grupo = $alerta['severidade'] === 'critico' ? 'critico' : 'atencao';
    $grupos[$grupo]['alertas'][] = $alerta;
}

$titulo  = 'Alertas';
$estilos = ['alertas'];
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina">Alertas e notificações</h1>
<p class="subtitulo">Acompanhe os alertas identificados pelos sensores dos trens.</p>

<form method="GET" class="painel form-grade">
    <div class="campo">
        <label for="severidade">Severidade</label>
        <select name="severidade" id="severidade">
            <option value="">Todas</option>
            <option value="critico" <?= $filtroSeveridade === 'critico' ? 'selected' : '' ?>>Crítico</option>
            <option value="atencao" <?= $filtroSeveridade === 'atencao' ? 'selected' : '' ?>>Atenção</option>
        </select>
    </div>

    <div class="campo">
        <label for="tipo">Tipo de sensor</label>
        <select name="tipo" id="tipo">
            <option value="">Todos os tipos</option>
            <?php foreach ($tiposSensor as $tipo): ?>
                <option value="<?= h($tipo['tipo']) ?>" <?= $filtroTipo === $tipo['tipo'] ? 'selected' : '' ?>><?= h($tipo['tipo']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="acoes campo-inteiro">
        <a href="alertas.php" class="botao-secundario">Limpar</a>
        <button type="submit" class="botao">
            <i class="fas fa-filter"></i> Filtrar
        </button>
    </div>
</form>

<section class="grade-alertas">
    <?php foreach ($grupos as $severidade => $grupo): ?>
        <article class="cartao-alertas <?= $severidade ?>">
            <h2>
                <i class="fas fa-triangle-exclamation"></i>
                <?= h($grupo['titulo']) ?>
                <span class="contador"><?= count($grupo['alertas']) ?></span>
            </h2>

            <?php if (count($grupo['alertas']) === 0): ?>
                <p class="nota"><?= h($grupo['vazio']) ?></p>
            <?php else: ?>
                <ul class="lista-alertas">
                    <?php foreach ($grupo['alertas'] as $alerta): ?>
                        <li>
                            <strong>Trem <?= h($alerta['prefixo']) ?>:</strong>
                            <?= h($alerta['mensagem']) ?>
                            <span class="nota">Sensor <?= h($alerta['codigo']) ?> · <?= h($alerta['valor'] . ' ' . $alerta['unidade']) ?></span>
                            <time><?= h(formatarData($alerta['data_hora'])) ?></time>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>

<p class="nota">Exibindo até 200 alertas. Os alertas críticos aparecem primeiro.</p>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
