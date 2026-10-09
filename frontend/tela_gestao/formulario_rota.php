
<?php
session_start();

require '../../data_base/conexao.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $estacao_trem = trim($_POST['estacao_trem'] ?? '');
    $data_hora_chegada = $_POST['data_hora_chegada'] ?? '';
    $data_hora_saida = $_POST['data_hora_saida'] ?? '';
    $status_trem = $_POST['status_trem'] ?? '';
    $origem = trim($_POST['origem'] ?? '');
    $destino = trim($_POST['destino'] ?? '');
    $velocidade_km_h = (float) ($_POST['velocidade_km_h'] ?? 0);
    $previsao_chegada = $_POST['previsao_chegada'] ?? '';
    $historico_descricao = trim($_POST['historico_descricao'] ?? '');
    $id_trem = (int) ($_POST['FK_id_trem'] ?? 0);
    $id_trilho = (int) ($_POST['FK_id_trilho'] ?? 0);

    // Troca o T do datetime-local por espaço
    $data_hora_chegada = str_replace('T', ' ', $data_hora_chegada);
    $data_hora_saida = str_replace('T', ' ', $data_hora_saida);
    $previsao_chegada = str_replace('T', ' ', $previsao_chegada);

    // Cadastra a viagem
    $sql = "
        INSERT INTO viagem (
            estacao_trem,
            data_hora_chegada,
            data_hora_saida,
            status_trem,
            origem,
            destino,
            velocidade_km_h,
            previsao_chegada,
            historico_descricao,
            FK_id_trem,
            FK_id_trilho
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conexao->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "ssssssdssii",
            $estacao_trem,
            $data_hora_chegada,
            $data_hora_saida,
            $status_trem,
            $origem,
            $destino,
            $velocidade_km_h,
            $previsao_chegada,
            $historico_descricao,
            $id_trem,
            $id_trilho
        );

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: rotas.php');
            exit;
        } else {
            $mensagem = 'Erro ao cadastrar a viagem: ' . $stmt->error;
        }

        $stmt->close();

    } else {
        $mensagem = 'Erro na preparação da consulta: ' . $conexao->error;
    }
}

// =====================================
// BUSCAR TRENS CADASTRADOS
// =====================================

$sql_trens = "
    SELECT
        id_trem,
        prefixo,
        modelo
    FROM trem
    ORDER BY prefixo
";

$resultado_trens = $conexao->query($sql_trens);

if (!$resultado_trens) {
    die('Erro ao buscar os trens: ' . $conexao->error);
}

// =====================================
// BUSCAR TRILHOS CADASTRADOS
// =====================================

$sql_trilhos = "
    SELECT
        id_trilho,
        localizacao,
        distancia
    FROM trilho
    ORDER BY localizacao
";

$resultado_trilhos = $conexao->query($sql_trilhos);

if (!$resultado_trilhos) {
    die('Erro ao buscar os trilhos: ' . $conexao->error);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nova Viagem - Gauge</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >

    <link rel="stylesheet" href="formulario_rota.css">
</head>

<body>

<header>

    <a href="rotas.php" class="menu">
        <i class="fas fa-arrow-left"></i>
    </a>

    <img src="../assets/logo_gauge_menor.png" alt="Gauge">

    <i class="fas fa-search"></i>

</header>

<main>

    <h1>Nova viagem</h1>

    <p class="descricao">
        Cadastre uma nova viagem para o sistema Ferrorama Gauge.
    </p>

    <?php if ($mensagem !== ''): ?>
        <div class="mensagem">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="formulario">

            <!-- TREM -->
            <div class="campo">

                <label for="FK_id_trem">Trem</label>

                <select name="FK_id_trem" id="FK_id_trem" required>

                    <option value="">Selecione um trem</option>

                    <?php while ($trem = $resultado_trens->fetch_assoc()): ?>

                        <option value="<?= (int) $trem['id_trem'] ?>">

                            <?= htmlspecialchars($trem['prefixo']) ?>
                            -
                            <?= htmlspecialchars($trem['modelo']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <!-- TRILHO -->
            <div class="campo">

                <label for="FK_id_trilho">Trilho</label>

                <select name="FK_id_trilho" id="FK_id_trilho" required>

                    <option value="">Selecione um trilho</option>

                    <?php while ($trilho = $resultado_trilhos->fetch_assoc()): ?>

                        <option value="<?= (int) $trilho['id_trilho'] ?>">

                            <?= htmlspecialchars($trilho['localizacao']) ?>
                            -
                            <?= htmlspecialchars((string) $trilho['distancia']) ?>
                            km

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <!-- ESTAÇÃO -->
            <div class="campo">

                <label for="estacao_trem">Estação atual</label>

                <input
                    type="text"
                    name="estacao_trem"
                    id="estacao_trem"
                    placeholder="Ex: Estação Central"
                    maxlength="45"
                    required
                >

            </div>

            <!-- ORIGEM -->
            <div class="campo">

                <label for="origem">Origem</label>

                <input
                    type="text"
                    name="origem"
                    id="origem"
                    placeholder="Ex: Joinville"
                    maxlength="45"
                    required
                >

            </div>

            <!-- DESTINO -->
            <div class="campo">

                <label for="destino">Destino</label>

                <input
                    type="text"
                    name="destino"
                    id="destino"
                    placeholder="Ex: Curitiba"
                    maxlength="45"
                    required
                >

            </div>

            <!-- SAÍDA -->
            <div class="campo">

                <label for="data_hora_saida">Data e hora de saída</label>

                <input
                    type="datetime-local"
                    name="data_hora_saida"
                    id="data_hora_saida"
                    required
                >

            </div>

            <!-- CHEGADA -->
            <div class="campo">

                <label for="data_hora_chegada">Data e hora de chegada</label>

                <input
                    type="datetime-local"
                    name="data_hora_chegada"
                    id="data_hora_chegada"
                    required
                >

            </div>

            <!-- PREVISÃO -->
            <div class="campo">

                <label for="previsao_chegada">Previsão de chegada</label>

                <input
                    type="datetime-local"
                    name="previsao_chegada"
                    id="previsao_chegada"
                    required
                >

            </div>

            <!-- STATUS -->
            <div class="campo">

                <label for="status_trem">Status</label>

                <select name="status_trem" id="status_trem" required>

                    <option value="">Selecione o status</option>

                    <option value="programada">Programada</option>

                    <option value="em andamento">Em andamento</option>

                    <option value="concluida">Concluída</option>

                    <option value="cancelada">Cancelada</option>

                </select>

            </div>

            <!-- VELOCIDADE -->
            <div class="campo">

                <label for="velocidade_km_h">Velocidade (km/h)</label>

                <input
                    type="number"
                    name="velocidade_km_h"
                    id="velocidade_km_h"
                    placeholder="Ex: 80"
                    min="0"
                    step="0.01"
                    required
                >

            </div>

            <!-- HISTÓRICO -->
            <div class="campo campo-grande">

                <label for="historico_descricao">Descrição</label>

                <textarea
                    name="historico_descricao"
                    id="historico_descricao"
                    maxlength="45"
                    placeholder="Ex: Viagem iniciada normalmente"
                    required
                ></textarea>

            </div>

        </div>

        <!-- BOTÕES -->
        <div class="botoes">

            <a href="rotas.php" class="botao cancelar">
                Cancelar
            </a>

            <button type="submit" class="botao salvar">
                <i class="fas fa-check"></i>
                Cadastrar viagem
            </button>

        </div>

    </form>

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

            <img
                src="../assets/logo_gauge_menor.png"
                alt="Gauge Logo"
                class="footer-logo"
            >

            <p class="footer-copyright">
                &copy; 2026 Gauge. Todos os direitos reservados.
            </p>

        </div>

        <div class="footer-bloco bloco-direita">

            <span class="footer-label">Precisa de Suporte?</span>

            <a
                href="mailto:contato@gauge.com.br"
                class="footer-link link-sublinhado"
            >
                contato@gauge.com.br
            </a>

        </div>

    </div>

</footer>

</body>
</html>