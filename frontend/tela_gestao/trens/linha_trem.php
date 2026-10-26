
<?php
require '../../../data_base/includes/proteger.php';
require '../../../data_base/includes/permissao.php';
require '../../../data_base/conexao.php';

if (!temPapel([1, 2, 3])) {
    http_response_code(403);
    exit('Acesso negado.');
}

$idViagem = filter_input(INPUT_GET, 'id_viagem', FILTER_VALIDATE_INT);

if (!$idViagem || $idViagem < 1) {
    exit('Viagem não informada. Volte ao calendário e selecione uma viagem.');
}

$sql = "SELECT
            v.id_viagem,
            v.origem,
            v.destino,
            v.data_hora_saida,
            v.data_hora_chegada,
            v.previsao_chegada,
            v.status_trem,
            v.historico_descricao,
            v.velocidade_km_h,
            t.id_trem,
            t.prefixo,
            t.modelo,
            t.situacao_trem,
            t.capacidade_toneladas
        FROM viagem v
        INNER JOIN trem t
            ON t.id_trem = v.FK_id_trem
        WHERE v.id_viagem = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param('i', $idViagem);
$stmt->execute();

$viagem = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$viagem) {
    exit('Viagem não encontrada.');
}

function h($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function dataHora($valor) {
    if (empty($valor)) {
        return 'Não informada';
    }

    $timestamp = strtotime($valor);

    return $timestamp ? date('d/m/Y H:i', $timestamp) : 'Não informada';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da viagem</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="linha_trem_style.css?v=1.1">
</head>
<body>

<header class="header">
    <div class="left">
        <a href="calendario_trem.php?id_trem=<?= (int)$viagem['id_trem'] ?>"
           aria-label="Voltar ao calendário">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <div class="logo">
        <img src="../../assets/logo_gauge_menor.png" alt="Gauge Logo">
    </div>
</header>

<main>
    <h1 class="titulo">
        Viagem #<?= (int)$viagem['id_viagem'] ?>
    </h1>

    <h3 class="sub-titulo">
        <?= h($viagem['prefixo']) ?> — <?= h($viagem['modelo']) ?>
    </h3>

    <section>
        <div class="conteudo-principal">

            <div class="info">
                <p><strong>Origem:</strong> <?= h($viagem['origem']) ?></p>
                <p><strong>Destino:</strong> <?= h($viagem['destino']) ?></p>
                <p><strong>Saída:</strong> <?= h(dataHora($viagem['data_hora_saida'])) ?></p>
                <p><strong>Chegada registrada:</strong> <?= h(dataHora($viagem['data_hora_chegada'])) ?></p>
                <p><strong>Previsão de chegada:</strong> <?= h(dataHora($viagem['previsao_chegada'])) ?></p>
                <p><strong>Status da viagem:</strong> <?= h($viagem['status_trem']) ?></p>
                <p><strong>Status do trem:</strong> <?= h($viagem['situacao_trem']) ?></p>
                <p><strong>Capacidade cadastrada:</strong>
                    <?= $viagem['capacidade_toneladas'] !== null
                        ? h($viagem['capacidade_toneladas'])
                        : 'Não informada' ?>
                </p>
                <p><strong>Velocidade atual:</strong>
                    <?= $viagem['velocidade_km_h'] !== null
                        ? h($viagem['velocidade_km_h']) . ' km/h'
                        : 'Não informada' ?>
                </p>
            </div>

            <div class="mapa">
                <div id="mapa-viagem"></div>
            </div>

        </div>

        <div class="historico">
            <h3>Histórico da viagem</h3>

            <div class="registro">
                <div class="descricao">
                    <p><?= h($viagem['historico_descricao'] ?: 'Nenhum histórico cadastrado para esta viagem.') ?></p>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="footer-gauge">
    <div class="footer-container">
        <div class="footer-bloco bloco-esquerda">
            <span class="footer-label">Entre em contato</span>
            <a href="tel:47999174896" class="footer-link">(47) 99917-4896</a>
        </div>

        <div class="footer-bloco bloco-centro">
            <img src="../../assets/logo_gauge_menor.png"
                 alt="Gauge Logo" class="footer-logo">
            <p class="footer-copyright">
                &copy; 2026 Gauge. Todos os direitos reservados.
            </p>
        </div>

        <div class="footer-bloco bloco-direita">
            <span class="footer-label">Precisa de suporte?</span>
            <a href="mailto:contato@gauge.com.br"
               class="footer-link link-sublinhado">contato@gauge.com.br</a>
        </div>
    </div>
</footer>

</body>
</html>
