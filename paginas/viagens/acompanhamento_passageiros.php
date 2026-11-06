<?php
// Acompanhamento das viagens dos trens de passageiros (mapa + lista).

require __DIR__ . '/../../includes/proteger.php';

$trens = $conexao->query(
    "SELECT
        t.prefixo, t.modelo,
        v.id_viagem, v.origem, v.destino,
        v.data_hora_saida, v.previsao_chegada,
        v.status_trem, v.historico_descricao, v.velocidade_km_h
     FROM trem t
     LEFT JOIN viagem v ON v.FK_id_trem = t.id_trem
     WHERE t.tipo_trem = 'Passageiro'
     ORDER BY v.data_hora_saida DESC"
)->fetch_all(MYSQLI_ASSOC);

// Viagens que podem aparecer no mapa
$viagensMapa = [];

foreach ($trens as $trem) {
    if (!empty($trem['origem']) && !empty($trem['destino'])) {
        $viagensMapa[] = [
            'origem'  => $trem['origem'],
            'destino' => $trem['destino'],
            'titulo'  => 'Trem ' . $trem['prefixo'],
            'linhas'  => [
                'Status: ' . ($trem['status_trem'] ?? 'Sem viagem'),
                'Saída: ' . formatarData($trem['data_hora_saida']),
                'Previsão: ' . formatarData($trem['previsao_chegada']),
            ],
        ];
    }
}

$titulo    = 'Trens de passageiros';
$usarMapa  = true;
$scripts   = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', url('assets/js/mapa.js')];
$menuAtivo = 'paginas/viagens/acompanhamento_carga.php';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<nav class="abas" aria-label="Tipo de trem">
    <a href="acompanhamento_carga.php" class="aba">Cargas</a>
    <a href="acompanhamento_passageiros.php" class="aba" aria-current="page">Passageiros</a>
</nav>

<h1 class="titulo-pagina">Trens de passageiros</h1>
<p class="subtitulo">Consulte as viagens cadastradas para trens de passageiros.</p>

<section class="painel">
    <div class="legenda-mapa">
        <span><i class="fas fa-location-dot"></i> Origem e destino</span>
        <span><i class="fas fa-minus"></i> Percurso ilustrativo</span>
    </div>

    <div class="mapa" data-viagens="<?= h(json_encode($viagensMapa, JSON_UNESCAPED_UNICODE)) ?>" aria-label="Mapa das viagens de passageiros"></div>

    <p class="nota">O mapa apresenta as viagens cadastradas. Os marcadores não representam a posição GPS atual dos trens.</p>
</section>

<h2 class="titulo-secao">Viagens de passageiros</h2>

<?php if (count($trens) === 0): ?>

    <div class="vazio-bloco">
        <i class="fas fa-train"></i>
        <p>Nenhum trem de passageiros foi encontrado. Confira o tipo dos trens cadastrados.</p>
    </div>

<?php else: ?>

    <section class="cartoes">
        <?php foreach ($trens as $trem): ?>
            <article class="cartao">
                <div class="cartao-topo">
                    <h3>Trem <?= h($trem['prefixo']) ?></h3>
                    <span class="status"><?= h($trem['status_trem'] ?? 'Sem viagem cadastrada') ?></span>
                </div>

                <div class="dados">
                    <p><strong>Modelo:</strong> <?= h($trem['modelo']) ?></p>
                    <p><strong>Viagem:</strong> <?= h($trem['id_viagem'] ?? 'Não cadastrada') ?></p>
                    <p><strong>Origem:</strong> <?= h($trem['origem'] ?? 'Não informada') ?></p>
                    <p><strong>Destino:</strong> <?= h($trem['destino'] ?? 'Não informado') ?></p>
                    <p><strong>Saída programada:</strong> <?= h(formatarData($trem['data_hora_saida'])) ?></p>
                    <p><strong>Previsão de chegada:</strong> <?= h(formatarData($trem['previsao_chegada'])) ?></p>
                    <p><strong>Velocidade cadastrada:</strong> <?= h($trem['velocidade_km_h'] ?? 'Não informada') ?> km/h</p>
                    <p><strong>Histórico:</strong> <?= h($trem['historico_descricao'] ?? 'Sem histórico') ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

<?php endif; ?>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
