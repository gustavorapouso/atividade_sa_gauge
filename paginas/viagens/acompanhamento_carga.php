<?php
// Acompanhamento das viagens dos trens de carga (mapa + lista).

require __DIR__ . '/../../includes/proteger.php';

$registros = $conexao->query(
    "SELECT
        c.id_carga, c.mercadoria, c.peso,
        t.prefixo, t.modelo, t.tipo_carga,
        v.id_viagem, v.origem, v.destino,
        v.data_hora_saida, v.data_hora_chegada, v.previsao_chegada,
        v.status_trem, v.historico_descricao, v.velocidade_km_h
     FROM viagem v
     INNER JOIN trem t ON t.id_trem = v.FK_id_trem
     LEFT JOIN carga c ON c.FK_id_trem = t.id_trem
     WHERE LOWER(t.tipo_trem) = 'carga'
     ORDER BY v.data_hora_saida DESC, v.id_viagem DESC"
)->fetch_all(MYSQLI_ASSOC);

// Viagens que podem aparecer no mapa
$viagensMapa = [];

foreach ($registros as $registro) {
    if (!empty($registro['origem']) && !empty($registro['destino'])) {
        $viagensMapa[] = [
            'origem'  => $registro['origem'],
            'destino' => $registro['destino'],
            'titulo'  => 'Carga: ' . ($registro['mercadoria'] ?? $registro['tipo_carga'] ?? 'Carga'),
            'linhas'  => ['Trem: ' . ($registro['prefixo'] ?? 'Sem prefixo')],
        ];
    }
}

$titulo   = 'Acompanhamento de cargas';
$usarMapa = count($viagensMapa) > 0;
$scripts  = $usarMapa ? ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', url('assets/js/mapa.js')] : [];
require __DIR__ . '/../../includes/cabecalho.php';
?>

<nav class="abas" aria-label="Tipo de trem">
    <a href="acompanhamento_carga.php" class="aba" aria-current="page">Cargas</a>
    <a href="acompanhamento_passageiros.php" class="aba">Passageiros</a>
</nav>

<h1 class="titulo-pagina">Acompanhar carga</h1>

<section class="painel">
    <?php if ($usarMapa): ?>
        <p class="legenda-mapa"><strong>Viagens com origem e destino:</strong> <?= count($viagensMapa) ?></p>

        <div class="mapa" data-viagens="<?= h(json_encode($viagensMapa, JSON_UNESCAPED_UNICODE)) ?>" aria-label="Mapa de acompanhamento das cargas"></div>

        <p class="nota">
            <i class="fas fa-circle-info"></i>
            As linhas são trajetos ilustrativos entre as cidades e não representam necessariamente os trilhos reais.
        </p>
    <?php else: ?>
        <div class="vazio-bloco">
            <i class="fas fa-map-location-dot"></i>
            <p>Não há viagens com origem e destino disponíveis para mostrar no mapa.</p>
        </div>
    <?php endif; ?>
</section>

<h2 class="titulo-secao">Cargas e viagens encontradas</h2>

<?php if (count($registros) === 0): ?>

    <div class="vazio-bloco">
        <i class="fas fa-box"></i>
        <p>Nenhuma viagem de trem de carga cadastrada.</p>
    </div>

<?php else: ?>

    <section class="cartoes">
        <?php foreach ($registros as $registro): ?>
            <article class="cartao">
                <div class="cartao-topo">
                    <h3>
                        <?php if (!empty($registro['id_carga'])): ?>
                            Carga #<?= h($registro['id_carga']) ?> — <?= h($registro['mercadoria'] ?: ($registro['tipo_carga'] ?? 'Mercadoria não cadastrada')) ?>
                        <?php else: ?>
                            Viagem #<?= h($registro['id_viagem']) ?> — <?= h($registro['tipo_carga'] ?? 'Trem de carga') ?>
                        <?php endif; ?>
                    </h3>
                    <span class="status"><?= h($registro['status_trem'] ?? 'Sem status') ?></span>
                </div>

                <div class="dados">
                    <p><strong>Peso:</strong> <?= ($registro['peso'] ?? '') !== '' ? h($registro['peso']) . ' toneladas' : 'Não cadastrado' ?></p>
                    <p>
                        <strong>Trem:</strong> <?= h($registro['prefixo'] ?? 'Não associado') ?>
                        <?= !empty($registro['modelo']) ? '— ' . h($registro['modelo']) : '' ?>
                    </p>
                    <p><strong>Viagem:</strong> <?= h($registro['id_viagem'] ?? 'Não programada') ?></p>
                    <p><strong>Origem:</strong> <?= h($registro['origem'] ?? 'Não informada') ?></p>
                    <p><strong>Destino:</strong> <?= h($registro['destino'] ?? 'Não informado') ?></p>
                    <p><strong>Saída programada:</strong> <?= h(formatarData($registro['data_hora_saida'])) ?></p>
                    <p><strong>Chegada prevista:</strong> <?= h(formatarData($registro['previsao_chegada'])) ?></p>
                    <p><strong>Chegada registrada:</strong> <?= h(formatarData($registro['data_hora_chegada'])) ?></p>
                    <p><strong>Velocidade:</strong> <?= ($registro['velocidade_km_h'] ?? '') !== '' ? h($registro['velocidade_km_h']) . ' km/h' : 'Não informada' ?></p>
                    <p><strong>Histórico:</strong> <?= h($registro['historico_descricao'] ?? 'Sem histórico') ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

<?php endif; ?>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
