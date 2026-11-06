<?php
// Detalhes de um trem (passageiros ou carga) e da sua viagem mais recente.

require __DIR__ . '/../../includes/proteger.php';

$idTrem = filter_input(INPUT_GET, 'id_trem', FILTER_VALIDATE_INT);

if (!$idTrem) {
    exit('Trem não encontrado.');
}

// Dados do trem
$stmt = $conexao->prepare(
    'SELECT id_trem, prefixo, modelo, ano, status, capacidade,
            tipo_trem, quantidade_assentos, tipo_carga
     FROM trem
     WHERE id_trem = ?'
);
$stmt->bind_param('i', $idTrem);
$stmt->execute();
$trem = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trem) {
    exit('Trem não encontrado.');
}

// Viagem mais recente do trem
$stmt = $conexao->prepare(
    'SELECT origem, destino, data_hora_saida, data_hora_chegada, previsao_chegada,
            status_trem, historico_descricao, velocidade_km_h
     FROM viagem
     WHERE FK_id_trem = ?
     ORDER BY data_hora_saida DESC, id_viagem DESC
     LIMIT 1'
);
$stmt->bind_param('i', $idTrem);
$stmt->execute();
$viagem = $stmt->get_result()->fetch_assoc();
$stmt->close();

$ehCarga = $trem['tipo_trem'] === 'Carga';

$titulo   = 'Trem ' . $trem['prefixo'];
$usarMapa = (bool) $viagem;
$scripts  = $viagem ? ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', url('assets/js/mapa.js')] : [];
$menuAtivo = $ehCarga ? 'paginas/trens/trens_carga.php' : 'paginas/trens/trens_passageiro.php';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina"><?= h($trem['prefixo']) ?></h1>
<p class="subtitulo">
    <?= $ehCarga ? 'Trem de carga' : 'Trem de passageiros' ?>
    <?php if ($viagem): ?>
        · saída às <?= h(formatarData($viagem['data_hora_saida'], 'H:i', '--:--')) ?>
    <?php endif; ?>
</p>

<?php if (!$viagem): ?>

    <div class="vazio-bloco">
        <i class="fas fa-train"></i>
        <h2>Nenhuma viagem cadastrada</h2>
        <p>Este trem ainda não possui viagens registradas no sistema.</p>
    </div>

<?php else: ?>

    <section class="painel">
        <h2><i class="fas fa-circle-info"></i> Detalhes da viagem</h2>

        <div class="dados dados-colunas">
            <p><strong>Modelo:</strong> <?= h($trem['modelo']) ?></p>
            <p><strong>ID do trem:</strong> <?= (int) $trem['id_trem'] ?></p>
            <p><strong>Status:</strong> <?= h($viagem['status_trem']) ?></p>

            <?php if ($ehCarga): ?>
                <p><strong>Carga:</strong> <?= h($trem['tipo_carga'] ?: 'Não informada') ?></p>
            <?php else: ?>
                <p><strong>Assentos:</strong> <?= $trem['quantidade_assentos'] !== null ? (int) $trem['quantidade_assentos'] : 'Não informado' ?></p>
            <?php endif; ?>

            <p><strong>Origem:</strong> <?= h($viagem['origem']) ?></p>
            <p><strong>Destino:</strong> <?= h($viagem['destino']) ?></p>
            <p><strong>Horário de saída:</strong> <?= h(formatarData($viagem['data_hora_saida'], 'H:i', '--:--')) ?></p>
            <p><strong>Horário de chegada:</strong> <?= h(formatarData($viagem['data_hora_chegada'], 'H:i', '--:--')) ?></p>
            <p><strong>Previsão de chegada:</strong> <?= h(formatarData($viagem['previsao_chegada'], 'H:i', '--:--')) ?></p>
            <p><strong>Velocidade atual:</strong> <?= h($viagem['velocidade_km_h'] ?? 0) ?> km/h</p>
        </div>
    </section>

    <section class="painel">
        <h2><i class="fas fa-map-location-dot"></i> <?= h($viagem['origem']) ?> → <?= h($viagem['destino']) ?></h2>

        <div
            class="mapa"
            data-geocodificar="sim"
            data-viagens="<?= h(json_encode([[
                'origem'  => $viagem['origem'],
                'destino' => $viagem['destino'],
                'titulo'  => 'Trem ' . $trem['prefixo'],
                'linhas'  => ['Status: ' . $viagem['status_trem']],
            ]], JSON_UNESCAPED_UNICODE)) ?>"
            aria-label="Mapa da viagem"></div>

        <p class="nota"><i class="fas fa-circle-info"></i> O trajeto é ilustrativo e não representa necessariamente os trilhos reais.</p>
    </section>

    <section class="painel">
        <h2><i class="fas fa-clock-rotate-left"></i> Histórico recente</h2>

        <ul class="linha-tempo">
            <li>
                <time><?= h(formatarData($viagem['data_hora_saida'], 'H:i', '--:--')) ?></time>
                <span><?= h($viagem['historico_descricao'] ?: 'Viagem cadastrada no sistema') ?></span>
            </li>

            <?php if (!empty($viagem['data_hora_chegada'])): ?>
                <li>
                    <time><?= h(formatarData($viagem['data_hora_chegada'], 'H:i')) ?></time>
                    <span>Horário de chegada registrado</span>
                </li>
            <?php endif; ?>
        </ul>
    </section>

<?php endif; ?>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
