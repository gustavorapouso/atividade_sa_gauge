
<?php
require '../../data_base/conexao.php';

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $localizacao = trim($_POST['localizacao'] ?? '');
    $distancia = filter_input(INPUT_POST, 'distancia', FILTER_VALIDATE_FLOAT);

    if ($localizacao === '') {
        $mensagem = 'Informe a localização do trilho.';
        $tipoMensagem = 'erro';
    } elseif (
        $distancia === false ||
        $distancia === null ||
        $distancia <= 0
    ) {
        $mensagem = 'Informe uma distância válida, maior que zero.';
        $tipoMensagem = 'erro';
    } else {
        $sql = "INSERT INTO trilho (localizacao, distancia)
                VALUES (?, ?)";

        $stmt = $conexao->prepare($sql);

        if (!$stmt) {
            $mensagem = 'Erro ao preparar o cadastro.';
            $tipoMensagem = 'erro';
        } else {
            $stmt->bind_param('sd', $localizacao, $distancia);

            if ($stmt->execute()) {
                $mensagem = 'Trilho cadastrado com sucesso!';
                $tipoMensagem = 'sucesso';
            } else {
                $mensagem = 'Erro ao cadastrar o trilho.';
                $tipoMensagem = 'erro';
            }

            $stmt->close();
        }
    }
}

$sql = "SELECT id_trilho, localizacao, distancia
        FROM trilho
        ORDER BY id_trilho DESC";

$resultado = $conexao->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastro de Trilhos | Gauge</title>

    <link rel="stylesheet" href="trilhos_style.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<div class="pagina">

    
<!-- NAVBAR ORIGINAL DO PROJETO -->
<header class="header">
    <div class="left">
        <i class="fas fa-bars" title="Menu"></i>
        <i class="fas fa-search" title="Pesquisar"></i>
    </div>

    <div class="logo">
        <img src="../assets/logo_gauge_menor.png" alt="Gauge Logo">
    </div>
</header>


    <!-- CONTEÚDO -->
    <main class="conteudo">

        <h1>CADASTRO DE TRILHOS</h1>

        <p class="subtitulo">
            Cadastre e consulte os trechos ferroviários do sistema.
        </p>

        <?php if ($mensagem !== ''): ?>
            <div class="mensagem <?= htmlspecialchars($tipoMensagem) ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- FORMULÁRIO DE CADASTRO -->
        <section class="painel">

            <h2>
                <i class="fas fa-plus-circle"></i>
                Novo trilho
            </h2>

            <form method="POST" action="trilhos.php" class="formulario">

                <div class="campo">
                    <label for="localizacao">Localização</label>

                    <input
                        type="text"
                        id="localizacao"
                        name="localizacao"
                        maxlength="100"
                        placeholder="Ex.: Joinville - SC"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="distancia">Distância (km)</label>

                    <input
                        type="number"
                        id="distancia"
                        name="distancia"
                        min="0.01"
                        step="any"
                        placeholder="Ex.: 25.5"
                        required
                    >
                </div>

                <button type="submit" class="botao">
                    <i class="fas fa-save"></i>
                    Cadastrar trilho
                </button>

            </form>

        </section>

        <!-- LISTAGEM DE TRILHOS -->
        <section class="painel">

            <h2>
                <i class="fas fa-list"></i>
                Trilhos cadastrados
            </h2>

            <div class="tabela-responsiva">

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Localização</th>
                            <th>Distância (km)</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($resultado && $resultado->num_rows > 0): ?>

                        <?php while ($trilho = $resultado->fetch_assoc()): ?>

                            <tr>
                                <td>
                                    <?= (int) $trilho['id_trilho'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($trilho['localizacao']) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float) $trilho['distancia'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="3" class="vazio">
                                Nenhum trilho cadastrado.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>

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

                <a href="mailto:contato@gauge.com.br"
                   class="footer-link">
                    contato@gauge.com.br
                </a>
            </div>

        </div>

    </footer>

</div>

</body>
</html>

