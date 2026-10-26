
<?php
session_start();

require '../../data_base/conexao.php';

/* CONSULTA TODAS AS VIAGENS DOS TRENS DE CARGA */

$sql = "SELECT
            c.id_carga,
            c.mercadoria,
            c.peso,
            t.id_trem,
            t.prefixo,
            t.modelo,
            t.tipo_carga,
            v.id_viagem,
            v.origem,
            v.destino,
            v.data_hora_saida,
            v.data_hora_chegada,
            v.previsao_chegada,
            v.status_trem,
            v.historico_descricao,
            v.velocidade_km_h,
            tr.localizacao AS localizacao_trilho,
            tr.distancia
        FROM viagem v
        INNER JOIN trem t
            ON t.id_trem = v.FK_id_trem
        LEFT JOIN carga c
            ON c.FK_id_trem = t.id_trem
        LEFT JOIN trilho tr
            ON tr.id_trilho = v.FK_id_trilho
        WHERE LOWER(t.tipo_trem) = 'carga'
        ORDER BY v.data_hora_saida DESC, v.id_viagem DESC";

$stmt = $conexao->prepare($sql);

if (!$stmt) {
    die('Erro ao preparar consulta: ' . $conexao->error);
}

$stmt->execute();
$resultado = $stmt->get_result();

$registros = [];

while ($linha = $resultado->fetch_assoc()) {
    $registros[] = $linha;
}

$stmt->close();

$mensagem = empty($registros)
    ? 'Nenhuma viagem de trem de carga cadastrada.'
    : '';

$viagensMapa = [];

foreach ($registros as $registro) {
    if (
        !empty($registro['origem']) &&
        !empty($registro['destino'])
    ) {
        $viagensMapa[] = $registro;
    }
}

function h($valor) {
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function dataHora($valor) {
    if (empty($valor)) {
        return 'Não informada';
    }

    $timestamp = strtotime($valor);

    return $timestamp
        ? date('d/m/Y H:i', $timestamp)
        : 'Não informada';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Acompanhamento de cargas | Ferrorama Gauge</title>

    <link rel="stylesheet" href="./carga.css?v=3">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>

<body>

<header class="header">

    <div class="left">
        <a href="dashboard.php" title="Voltar ao painel">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <div class="logo">
        <img src="../assets/logo_gauge_menor.png"
             alt="Ferrorama Gauge">
    </div>

</header>

<main class="content-container">

    <!-- SEÇÃO DO MAPA -->

    <section class="screen-section">

        <div class="select-wrapper">
            <select
                class="select-badge"
                onchange="if (this.value) window.location.href=this.value">

                <option value="carga.php" selected>
                    CARGAS
                </option>

                <option value="passageiros.php">
                    PASSAGEIROS
                </option>

            </select>
        </div>

        <h1 class="titulo-pagina">Acompanhar carga</h1>

        <?php if ($mensagem !== ''): ?>

            <p class="mensagem">
                <?= h($mensagem) ?>
            </p>

        <?php endif; ?>

        <?php if (!empty($viagensMapa)): ?>

            <div class="mapa-info">
                <p>
                    <strong>Viagens com origem e destino:</strong>
                    <?= count($viagensMapa) ?>
                </p>
            </div>

            <div id="mapa"
                 aria-label="Mapa de acompanhamento das cargas">
            </div>

            <p class="aviso-mapa">
                <i class="fas fa-circle-info"></i>
                As linhas são trajetos ilustrativos entre as cidades
                e não representam necessariamente os trilhos reais.
            </p>

        <?php else: ?>

            <div class="mapa-sem-rota">
                <i class="fas fa-map-location-dot"></i>

                <p>
                    Não há viagens com origem e destino disponíveis
                    para mostrar no mapa.
                </p>
            </div>

        <?php endif; ?>

    </section>

    <!-- LISTA DE CARGAS E VIAGENS -->

    <section class="lista-cargas">

        <h2 class="titulo-lista">
            Cargas e viagens encontradas
        </h2>

        <?php if (!empty($registros)): ?>

            <?php foreach ($registros as $registro): ?>

                <article class="card-carga">

                    <div class="card-carga-titulo">

                        <h3>
                            <?php if (!empty($registro['id_carga'])): ?>

                                Carga #<?= h($registro['id_carga']) ?>
                                —
                                <?= h(
                                    $registro['mercadoria']
                                    ?: ($registro['tipo_carga'] ?? 'Mercadoria não cadastrada')
                                ) ?>

                            <?php else: ?>

                                Viagem #<?= h($registro['id_viagem']) ?>
                                —
                                <?= h($registro['tipo_carga'] ?? 'Trem de carga') ?>

                            <?php endif; ?>
                        </h3>

                        <span class="status">
                            <?= h($registro['status_trem'] ?? 'Sem status') ?>
                        </span>

                    </div>

                    <div class="dados-carga">

                        <p>
                            <strong>Peso:</strong>

                            <?= $registro['peso'] !== null
                                && $registro['peso'] !== ''
                                ? h($registro['peso']) . ' toneladas'
                                : 'Não cadastrado' ?>
                        </p>

                        <p>
                            <strong>Trem:</strong>

                            <?= h($registro['prefixo'] ?? 'Não associado') ?>

                            <?php if (!empty($registro['modelo'])): ?>
                                — <?= h($registro['modelo']) ?>
                            <?php endif; ?>
                        </p>

                        <p>
                            <strong>Viagem:</strong>
                            <?= h($registro['id_viagem'] ?? 'Não programada') ?>
                        </p>

                        <p>
                            <strong>Origem:</strong>
                            <?= h($registro['origem'] ?? 'Não informada') ?>
                        </p>

                        <p>
                            <strong>Destino:</strong>
                            <?= h($registro['destino'] ?? 'Não informado') ?>
                        </p>

                        <p>
                            <strong>Saída programada:</strong>
                            <?= h(dataHora($registro['data_hora_saida'] ?? null)) ?>
                        </p>

                        <p>
                            <strong>Chegada prevista:</strong>
                            <?= h(dataHora($registro['previsao_chegada'] ?? null)) ?>
                        </p>

                        <p>
                            <strong>Chegada registrada:</strong>
                            <?= h(dataHora($registro['data_hora_chegada'] ?? null)) ?>
                        </p>

                        <p>
                            <strong>Velocidade:</strong>

                            <?= $registro['velocidade_km_h'] !== null
                                && $registro['velocidade_km_h'] !== ''
                                ? h($registro['velocidade_km_h']) . ' km/h'
                                : 'Não informada' ?>
                        </p>

                        <p class="historico-carga">
                            <strong>Histórico:</strong>
                            <?= h($registro['historico_descricao'] ?? 'Sem histórico') ?>
                        </p>

                    </div>

                    <?php if (
                        !empty($registro['origem']) &&
                        !empty($registro['destino'])
                    ): ?>

                        <a class="botao-ver-mapa" href="#mapa">
                            <i class="fas fa-map"></i>
                            Ver no mapa
                        </a>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="mensagem lista-vazia">
                Nenhuma viagem para exibir.
            </div>

        <?php endif; ?>

    </section>

</main>

<!-- RODAPÉ -->

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

<!-- MAPA -->

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php if (!empty($viagensMapa)): ?>

<script>
const viagens = <?= json_encode(
    array_map(function ($registro) {
        return [
            'origem' => $registro['origem'],
            'destino' => $registro['destino'],
            'prefixo' => $registro['prefixo'] ?? 'Sem prefixo',
            'mercadoria' => $registro['mercadoria']
                ?? ($registro['tipo_carga'] ?? 'Carga')
        ];
    }, $viagensMapa),
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
) ?>;

const cidades = {
    'joinville': [-26.3044, -48.8487],
    'itajai': [-26.9101, -48.6705],
    'curitiba': [-25.4284, -49.2733],
    'sao francisco do sul': [-26.2433, -48.6380],
    'blumenau': [-26.9194, -49.0661],
    'florianopolis': [-27.5954, -48.5480],
    'jaragua do sul': [-26.4851, -49.0713],
    'barra velha': [-26.6329, -48.6846],
    'sao jose': [-27.6136, -48.6366],
    'balneario camboriu': [-26.9906, -48.6352],
    'navegantes': [-26.8943, -48.6546]
};

function normalizarNome(nome) {
    return String(nome || '')
        .trim()
        .toLocaleLowerCase('pt-BR')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

function obterCoordenadas(nome) {
    return cidades[normalizarNome(nome)] || null;
}

function escaparHTML(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, function (caractere) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[caractere];
    });
}

const elementoMapa = document.getElementById('mapa');

if (elementoMapa && typeof L !== 'undefined') {

    const mapa = L.map('mapa');

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(mapa);

    const pontos = [];
    const coordenadasUsadas = new Set();

    viagens.forEach(function (viagem) {

        const origem = obterCoordenadas(viagem.origem);
        const destino = obterCoordenadas(viagem.destino);

        if (!origem || !destino) {
            console.warn(
                'Coordenadas não cadastradas:',
                viagem.origem,
                viagem.destino
            );
            return;
        }

        const percurso = L.polyline([origem, destino], {
            color: '#6B4732',
            weight: 4,
            opacity: 0.85,
            dashArray: '8, 8'
        }).addTo(mapa);

        percurso.bindPopup(
            '<strong>Carga:</strong> ' + escaparHTML(viagem.mercadoria) +
            '<br><strong>Trem:</strong> ' + escaparHTML(viagem.prefixo) +
            '<br><strong>Origem:</strong> ' + escaparHTML(viagem.origem) +
            '<br><strong>Destino:</strong> ' + escaparHTML(viagem.destino)
        );

        [
            { coordenadas: origem, cidade: viagem.origem },
            { coordenadas: destino, cidade: viagem.destino }
        ].forEach(function (ponto) {

            const chave = ponto.coordenadas.join(',');

            if (!coordenadasUsadas.has(chave)) {
                coordenadasUsadas.add(chave);

                L.marker(ponto.coordenadas)
                    .addTo(mapa)
                    .bindPopup(
                        '<strong>' + escaparHTML(ponto.cidade) + '</strong>'
                    );

                pontos.push(ponto.coordenadas);
            }
        });
    });

    if (pontos.length > 0) {
        mapa.fitBounds(L.latLngBounds(pontos), {
            padding: [35, 35]
        });
    } else {
        mapa.setView([-26.5, -48.7], 8);
    }

    setTimeout(function () {
        mapa.invalidateSize();
    }, 200);
}
</script>

<?php endif; ?>

</body>
</html>
