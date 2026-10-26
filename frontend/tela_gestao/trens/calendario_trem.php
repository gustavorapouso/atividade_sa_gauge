
<?php
require '../../../data_base/includes/proteger.php';
require '../../../data_base/includes/permissao.php';
require '../../../data_base/conexao.php';

if (!temPapel([1, 2, 3])) {
    http_response_code(403);
    exit('Acesso negado.');
}

$idTrem = filter_input(INPUT_GET, 'id_trem', FILTER_VALIDATE_INT);

if (!$idTrem || $idTrem < 1) {
    exit('Trem não informado. Volte à lista e selecione um trem.');
}

/* Busca o trem selecionado */
$sqlTrem = "SELECT id_trem, prefixo, modelo, situacao_trem
            FROM trem
            WHERE id_trem = ?";

$stmtTrem = $conexao->prepare($sqlTrem);
$stmtTrem->bind_param('i', $idTrem);
$stmtTrem->execute();

$trem = $stmtTrem->get_result()->fetch_assoc();
$stmtTrem->close();

if (!$trem) {
    exit('Trem não encontrado.');
}

/* Busca as viagens registradas para esse trem */
$sqlViagens = "SELECT
                   id_viagem,
                   origem,
                   destino,
                   data_hora_saida,
                   data_hora_chegada,
                   previsao_chegada,
                   status_trem
               FROM viagem
               WHERE FK_id_trem = ?
               ORDER BY data_hora_saida ASC, id_viagem ASC";

$stmt = $conexao->prepare($sqlViagens);
$stmt->bind_param('i', $idTrem);
$stmt->execute();

$resultado = $stmt->get_result();
$viagens = [];

while ($linha = $resultado->fetch_assoc()) {
    $viagens[] = $linha;
}

$stmt->close();

function h($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function dataFormatada($valor) {
    if (empty($valor)) {
        return 'Não informada';
    }

    $timestamp = strtotime($valor);

    return $timestamp ? date('d/m/Y', $timestamp) : 'Não informada';
}

function horaFormatada($valor) {
    if (empty($valor)) {
        return 'Não informada';
    }

    $timestamp = strtotime($valor);

    return $timestamp ? date('H:i', $timestamp) : 'Não informada';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário de viagens</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="calendario_trem_style.css?v=1.1">
</head>
<body>

<header class="header">
    <div class="left">
        <a href="trem_passageiro.php" aria-label="Voltar aos trens">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <div class="logo">
        <img src="../../assets/logo_gauge_menor.png" alt="Gauge Logo">
    </div>
</header>

<main>
    <section>
        <h1 id="titulo">Calendário de viagens</h1>

        <p class="subtitulo">
            Trem: <?= h($trem['prefixo']) ?> —
            <?= h($trem['modelo']) ?>
        </p>

        <div class="barra-pesquisa">
            <i class="fas fa-search lupa"></i>
            <input type="text"
                   placeholder="Filtrar por origem ou destino"
                   id="pesquisa_bar">
        </div>
    </section>

    <section>
        <?php if (!empty($viagens)): ?>

            <table class="calendario">
                <thead>
                    <tr>
                        <th>Origem</th>
                        <th>Destino</th>
                        <th>Data</th>
                        <th>Saída</th>
                        <th>Chegada prevista</th>
                        <th>Status</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($viagens as $viagem): ?>
                        <tr class="linha"
                            data-pesquisa="<?= h(($viagem['origem'] ?? '') . ' ' . ($viagem['destino'] ?? '')) ?>">

                            <td><?= h($viagem['origem'] ?? 'Não informada') ?></td>
                            <td><?= h($viagem['destino'] ?? 'Não informada') ?></td>
                            <td><?= h(dataFormatada($viagem['data_hora_saida'] ?? null)) ?></td>
                            <td><?= h(horaFormatada($viagem['data_hora_saida'] ?? null)) ?></td>
                            <td><?= h(horaFormatada(
                                $viagem['previsao_chegada']
                                ?? $viagem['data_hora_chegada']
                                ?? null
                            )) ?></td>
                            <td><?= h($viagem['status_trem'] ?? 'Não informado') ?></td>

                            <td>
                                <a href="linha_trem.php?id_viagem=<?= (int)$viagem['id_viagem'] ?>">
                                    Ver viagem
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p id="semResultado" hidden>
                Nenhuma viagem corresponde à pesquisa.
            </p>

        <?php else: ?>

            <p class="mensagem-vazia">
                Não há viagens cadastradas para este trem.
            </p>

        <?php endif; ?>
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

<script>
const campoPesquisa = document.getElementById('pesquisa_bar');
const linhas = document.querySelectorAll('.calendario tbody .linha');
const semResultado = document.getElementById('semResultado');

if (campoPesquisa) {
    campoPesquisa.addEventListener('input', function () {
        const termo = this.value.trim().toLocaleLowerCase('pt-BR');
        let encontrados = 0;

        linhas.forEach(function (linha) {
            const texto = (linha.dataset.pesquisa || '').toLocaleLowerCase('pt-BR');
            const corresponde = texto.includes(termo);

            linha.hidden = !corresponde;

            if (corresponde) encontrados++;
        });

        if (semResultado) {
            semResultado.hidden = encontrados !== 0;
        }
    });
}
</script>

</body>
</html>
