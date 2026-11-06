<?php
require '../../../data_base/includes/proteger.php';
require '../../../data_base/includes/permissao.php';
require '../../../data_base/conexao.php';

if (!temPapel([1, 2, 3])) {
    header('Location: ../../tela_principal/dashboard.php');
    exit;
}

function h($valor): string {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatarHora($data): string {
    if (empty($data)) {
        return '--:--';
    }

    return date('H:i', strtotime($data));
}

// Recebe o ID do trem
$id_trem = filter_input(INPUT_GET, 'id_trem', FILTER_VALIDATE_INT);

if (!$id_trem) {
    die('Trem não encontrado.');
}

// Busca os dados do trem
$sqlTrem = "SELECT
                id_trem,
                prefixo,
                modelo,
                ano,
                status,
                capacidade,
                quantidade_assentos
            FROM trem
            WHERE id_trem = ?";

$stmtTrem = $conexao->prepare($sqlTrem);
$stmtTrem->bind_param('i', $id_trem);
$stmtTrem->execute();

$resultadoTrem = $stmtTrem->get_result();
$trem = $resultadoTrem->fetch_assoc();

$stmtTrem->close();

if (!$trem) {
    die('Trem não encontrado.');
}

// Busca a viagem mais recente do trem
$sqlViagem = "SELECT
                  id_viagem,
                  FK_id_trem,
                  FK_id_trilho,
                  origem,
                  destino,
                  data_hora_saida,
                  data_hora_chegada,
                  previsao_chegada,
                  status_trem,
                  historico_descricao,
                  velocidade_km_h
              FROM viagem
              WHERE FK_id_trem = ?
              ORDER BY data_hora_saida DESC, id_viagem DESC
              LIMIT 1";

$stmtViagem = $conexao->prepare($sqlViagem);
$stmtViagem->bind_param('i', $id_trem);
$stmtViagem->execute();

$resultadoViagem = $stmtViagem->get_result();
$viagem = $resultadoViagem->fetch_assoc();

$stmtViagem->close();

$temViagem = !empty($viagem);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Linha do Trem | Gauge</title>

    <link rel="stylesheet" href="linha_trem_style.css?v=3">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap"
          rel="stylesheet">

    <!-- Leaflet -->
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>

<body>

<!-- NAVBAR -->
<header class="header">
    <div class="header-left">

        <!-- Botão visual, sem abrir menu -->
        <button class="menu-button" type="button" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <i class="fa-solid fa-magnifying-glass"></i>
    </div>

    <a href="../../tela_principal/dashboard.php" class="logo">
        <img src="../../assets/logo_gauge_menor.png" alt="Logo Gauge">
    </a>
</header>

<main class="conteudo">

    <?php if ($temViagem): ?>

        <!-- TÍTULO -->
        <section class="titulo-pagina">
            <h1><?= h($trem['prefixo']) ?></h1>

            <p class="horario-titulo">
                <?= h(formatarHora($viagem['data_hora_saida'])) ?>
            </p>
        </section>

        <!-- DETALHES DA VIAGEM -->
        <section class="detalhes-container">

            <div class="informacoes-viagem">

                <p>
                    <strong>Modelo:</strong>
                    <?= h($trem['modelo']) ?>
                </p>

                <p>
                    <strong>Horário de saída:</strong>
                    <?= h(formatarHora($viagem['data_hora_saida'])) ?>
                </p>

                <p>
                    <strong>Horário de chegada:</strong>
                    <?= h(formatarHora($viagem['data_hora_chegada'])) ?>
                </p>

                <p>
                    <strong>ID do trem:</strong>
                    <?= (int)$trem['id_trem'] ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <?= h($viagem['status_trem']) ?>
                </p>

                <p>
                    <strong>Quantidade de assentos:</strong>
                    <?= $trem['quantidade_assentos'] !== null
                        ? h($trem['quantidade_assentos'])
                        : 'Não informado' ?>
                </p>

                <p>
                    <strong>Origem:</strong>
                    <?= h($viagem['origem']) ?>
                </p>

                <p>
                    <strong>Destino:</strong>
                    <?= h($viagem['destino']) ?>
                </p>

                <p>
                    <strong>Velocidade atual:</strong>
                    <?= h($viagem['velocidade_km_h'] ?? 0) ?> km/h
                </p>

                <p>
                    <strong>Previsão de chegada:</strong>
                    <?= h(formatarHora($viagem['previsao_chegada'])) ?>
                </p>

            </div>
        </section>

        <!-- MAPA -->
        <section class="secao-mapa">

            <div class="mapa-info">
                <p>
                    <strong>Viagem acompanhada:</strong>
                    <?= h($viagem['origem']) ?>

                    <i class="fa-solid fa-arrow-right"></i>

                    <?= h($viagem['destino']) ?>
                </p>
            </div>

            <div id="mapa"
                 aria-label="Mapa de acompanhamento da viagem">
            </div>

            <p class="aviso-mapa">
                <i class="fa-solid fa-circle-info"></i>
                O trajeto é ilustrativo e não representa necessariamente os trilhos reais.
            </p>

        </section>

        <!-- HISTÓRICO -->
        <section class="historico">

            <h2>Histórico Recente</h2>

            <div class="historico-item">

                <span class="historico-hora">
                    <?= h(formatarHora($viagem['data_hora_saida'])) ?>
                </span>

                <span class="historico-descricao">
                    <?= !empty($viagem['historico_descricao'])
                        ? h($viagem['historico_descricao'])
                        : 'Viagem cadastrada no sistema' ?>
                </span>

            </div>

            <?php if (!empty($viagem['data_hora_chegada'])): ?>

                <div class="historico-item">

                    <span class="historico-hora">
                        <?= h(formatarHora($viagem['data_hora_chegada'])) ?>
                    </span>

                    <span class="historico-descricao">
                        Horário de chegada registrado
                    </span>

                </div>

            <?php endif; ?>

        </section>

    <?php else: ?>

        <section class="titulo-pagina">
            <h1><?= h($trem['prefixo']) ?></h1>
            <p>Detalhes do trem de passageiros</p>
        </section>

        <section class="sem-viagem">
            <i class="fa-solid fa-train"></i>

            <h2>Nenhuma viagem cadastrada</h2>

            <p>
                Este trem ainda não possui viagens registradas no sistema.
            </p>
        </section>

    <?php endif; ?>

</main>

<!-- FOOTER -->
<footer class="rodape">

    <div class="rodape-container">

        <div class="rodape-contato">
            <p>Entre em contato</p>

            <a href="tel:+5547999174896">
                (47) 99917-4896
            </a>
        </div>

        <div class="rodape-centro">
            <img src="../../assets/logo_gauge_menor.png"
                 alt="Logo Gauge">

            <p>
                &copy; <?= date('Y') ?> Gauge.
                Todos os direitos reservados.
            </p>
        </div>

        <div class="rodape-suporte">
            <p>Precisa de Suporte?</p>

            <a href="mailto:contato@gauge.com.br">
                contato@gauge.com.br
            </a>
        </div>

    </div>

</footer>

<!-- LEAFLET -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php if ($temViagem): ?>
<script>
const origem = <?= json_encode($viagem['origem'], JSON_UNESCAPED_UNICODE) ?>;
const destino = <?= json_encode($viagem['destino'], JSON_UNESCAPED_UNICODE) ?>;

const mapa = L.map('mapa');

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(mapa);

// Localiza uma cidade usando o nome cadastrado no banco
async function localizarCidade(cidade) {

    const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q='
        + encodeURIComponent(cidade + ', Brasil');

    const resposta = await fetch(url);
    const dados = await resposta.json();

    if (!dados.length) {
        throw new Error('Cidade não encontrada: ' + cidade);
    }

    return [
        Number(dados[0].lat),
        Number(dados[0].lon)
    ];
}

async function carregarMapa() {

    const elementoMapa = document.getElementById('mapa');

    try {

        if (!origem || !destino) {
            throw new Error('Origem ou destino não informado.');
        }

        // Localiza origem e destino
        const coordenadasOrigem = await localizarCidade(origem);

        // Intervalo para respeitar o serviço de geocodificação
        await new Promise(resolve => setTimeout(resolve, 1100));

        const coordenadasDestino = await localizarCidade(destino);

        // Marcador da origem
        L.marker(coordenadasOrigem)
            .addTo(mapa)
            .bindPopup('<strong>Origem:</strong> ' + origem);

        // Marcador do destino
        L.marker(coordenadasDestino)
            .addTo(mapa)
            .bindPopup('<strong>Destino:</strong> ' + destino);

        // Linha ilustrativa entre as cidades
        const trajeto = L.polyline(
            [coordenadasOrigem, coordenadasDestino],
            {
                color: '#6B4732',
                weight: 5,
                opacity: 0.85
            }
        ).addTo(mapa);

        // Ajusta o zoom para mostrar os dois pontos
        mapa.fitBounds(trajeto.getBounds(), {
            padding: [40, 40]
        });

    } catch (erro) {

        console.error('Erro ao carregar o mapa:', erro);

        elementoMapa.innerHTML = `
            <div class="mapa-erro">
                <i class="fa-solid fa-map-location-dot"></i>
                <p>
                    Não foi possível localizar a origem ou o destino.
                    Verifique os nomes cadastrados na viagem.
                </p>
            </div>
        `;

    }
}

carregarMapa();
</script>
<?php endif; ?>

</body>
</html>
