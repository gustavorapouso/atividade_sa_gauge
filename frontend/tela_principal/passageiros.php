<?php
session_start();
require '../../data_base/conexao.php';

// Buscar viagens associadas a trens de passageiros
$sql = "SELECT
            t.id_trem,
            t.prefixo,
            t.modelo,
            t.status AS status_cadastro,
            v.id_viagem,
            v.origem,
            v.destino,
            v.data_hora_saida,
            v.previsao_chegada,
            v.status_trem,
            v.historico_descricao,
            v.velocidade_km_h,
            tr.localizacao AS localizacao_trilho,
            tr.distancia
        FROM trem t
        LEFT JOIN viagem v
            ON v.FK_id_trem = t.id_trem
        LEFT JOIN trilho tr
            ON tr.id_trilho = v.FK_id_trilho
        WHERE t.tipo_trem = 'Passageiro'
        ORDER BY v.data_hora_saida DESC";

$resultado = $conexao->query($sql);

$trens = [];

while ($linha = $resultado->fetch_assoc()) {
    $trens[] = $linha;
}

function h($valor) {
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function formatarData($data) {
    if (empty($data)) {
        return 'Não informada';
    }

    return date('d/m/Y H:i', strtotime($data));
}

// Enviar ao JavaScript somente os dados necessários para o mapa
$dadosMapa = [];

foreach ($trens as $trem) {
    if (
        !empty($trem['origem']) &&
        !empty($trem['destino'])
    ) {
        $dadosMapa[] = [
            'prefixo' => $trem['prefixo'],
            'modelo' => $trem['modelo'],
            'origem' => $trem['origem'],
            'destino' => $trem['destino'],
            'status' => $trem['status_trem'] ?? 'Sem viagem',
            'saida' => $trem['data_hora_saida'],
            'chegada' => $trem['previsao_chegada'],
            'velocidade' => $trem['velocidade_km_h'],
            'historico' => $trem['historico_descricao']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Trens de passageiros | Ferrorama Gauge</title>

    <link rel="stylesheet" href="passageiros.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>

<body>

<header class="header">
    <div class="left">
        <a href="../tela_principal/dashboard.php" title="Voltar ao painel">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <div class="logo">
        <img src="../assets/logo_gauge_menor.png"
             alt="Ferrorama Gauge">
    </div>
</header>

<main class="content-container">

    <section class="screen-section">

        <div class="select-wrapper">
            <select class="select-badge"
                    onchange="if(this.value) window.location.href=this.value">
                <option value="carga.php">CARGA</option>
                <option value="passageiros.php" selected>PASSAGEIROS</option>
            </select>
        </div>

        <h1 class="titulo-pagina">Trens de passageiros</h1>

        <p class="descricao-passageiros">
            Consulte as viagens cadastradas para trens de passageiros.
        </p>

        <div class="legenda-mapa">
            <span><i class="fas fa-circle origem-cor"></i> Origem</span>
            <span><i class="fas fa-location-dot destino-cor"></i> Destino</span>
            <span><i class="fas fa-minus percurso-cor"></i> Percurso ilustrativo</span>
        </div>

        <div id="mapa-passageiros"></div>

        <p class="aviso-passageiros">
            O mapa apresenta as viagens cadastradas. Os marcadores não
            representam a posição GPS atual dos trens.
        </p>

    </section>

    <section class="screen-section lista-cargas">

        <h2 class="titulo-lista">Viagens de passageiros</h2>

        <?php if (count($trens) === 0): ?>

            <div class="mapa-sem-rota">
                <i class="fas fa-train"></i>
                <p>
                    Nenhum trem de passageiros foi encontrado.
                    Confira o tipo dos trens cadastrados.
                </p>
            </div>

        <?php else: ?>

            <?php foreach ($trens as $trem): ?>

                <article class="card-carga">

                    <div class="card-carga-titulo">
                        <h3>
                            Trem <?= h($trem['prefixo']) ?>
                        </h3>

                        <span class="status">
                            <?= h($trem['status_trem'] ?? 'Sem viagem cadastrada') ?>
                        </span>
                    </div>

                    <div class="dados-carga">

                        <p>
                            <strong>Modelo:</strong>
                            <?= h($trem['modelo']) ?>
                        </p>

                        <p>
                            <strong>Viagem:</strong>
                            <?= h($trem['id_viagem'] ?? 'Não cadastrada') ?>
                        </p>

                        <p>
                            <strong>Origem:</strong>
                            <?= h($trem['origem'] ?? 'Não informada') ?>
                        </p>

                        <p>
                            <strong>Destino:</strong>
                            <?= h($trem['destino'] ?? 'Não informado') ?>
                        </p>

                        <p>
                            <strong>Saída programada:</strong>
                            <?= h(formatarData($trem['data_hora_saida'])) ?>
                        </p>

                        <p>
                            <strong>Previsão de chegada:</strong>
                            <?= h(formatarData($trem['previsao_chegada'])) ?>
                        </p>

                        <p>
                            <strong>Velocidade cadastrada:</strong>
                            <?= h($trem['velocidade_km_h'] ?? 'Não informada') ?>
                            km/h
                        </p>

                        <p>
                            <strong>Histórico:</strong>
                            <?= h($trem['historico_descricao'] ?? 'Sem histórico') ?>
                        </p>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>

<footer class="footer-gauge">
    <div class="footer-container">

        <div class="footer-bloco bloco-esquerda">
            <span class="footer-label">Entre em contato</span>
            <a href="tel:47999174896" class="footer-link">
                (47) 99917-4896
            </a>
        </div>

        <div class="footer-bloco bloco-centro">
            <img src="../assets/logo_gauge_menor.png"
                 alt="Gauge Logo"
                 class="footer-logo">

            <p class="footer-copyright">
                &copy; 2026 Gauge. Todos os direitos reservados.
            </p>
        </div>

        <div class="footer-bloco bloco-direita">
            <span class="footer-label">Precisa de suporte?</span>
            <a href="mailto:contato@gauge.com.br"
               class="footer-link link-sublinhado">
                contato@gauge.com.br
            </a>
        </div>

    </div>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const viagens = <?= json_encode(
    $dadosMapa,
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
) ?>;

// Coordenadas aproximadas para as cidades já presentes no banco.
// Acrescente outras cidades quando houver viagens para elas.
const cidades = {
    'joinville': [-26.3044, -48.8487],
    'curitiba': [-25.4284, -49.2733],
    'são francisco do sul': [-26.2433, -48.6380],
    'sao francisco do sul': [-26.2433, -48.6380]
};

function coordenadas(nome) {
    const chave = nome.trim().toLocaleLowerCase('pt-BR');
    return cidades[chave] || null;
}

const mapa = L.map('mapa-passageiros');

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
}).addTo(mapa);

const pontos = [];
let rotasDesenhadas = 0;

viagens.forEach(viagem => {
    const origem = coordenadas(viagem.origem);
    const destino = coordenadas(viagem.destino);

    if (!origem || !destino) {
        return;
    }

    const marcadorOrigem = L.marker(origem).addTo(mapa);

    marcadorOrigem.bindPopup(`
        <strong>Trem ${viagem.prefixo}</strong><br>
        Origem: ${viagem.origem}<br>
        Saída: ${viagem.saida || 'Não informada'}
    `);

    const marcadorDestino = L.marker(destino).addTo(mapa);

    marcadorDestino.bindPopup(`
        <strong>Trem ${viagem.prefixo}</strong><br>
        Destino: ${viagem.destino}<br>
        Previsão: ${viagem.chegada || 'Não informada'}
    `);

    const percurso = L.polyline(
        [origem, destino],
        {
            color: '#6B4732',
            weight: 4,
            dashArray: '8, 8'
        }
    ).addTo(mapa);

    percurso.bindPopup(`
        <strong>Trem ${viagem.prefixo}</strong><br>
        ${viagem.origem} → ${viagem.destino}<br>
        Status: ${viagem.status}
    `);

    pontos.push(origem, destino);
    rotasDesenhadas++;
});

if (pontos.length > 0) {
    mapa.fitBounds(L.latLngBounds(pontos), {
        padding: [30, 30]
    });
} else {
    mapa.setView([-26.3, -48.8], 7);

    L.popup()
        .setLatLng([-26.3, -48.8])
        .setContent(
            'Nenhuma viagem possui origem e destino reconhecidos ' +
            'para exibição no mapa.'
        )
        .openOn(mapa);
}
</script>

</body>
</html>
