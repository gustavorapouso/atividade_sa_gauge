<?php

session_start();

require '../../data_base/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_pessoa'])) {
    header("Location: ../autenticacao/login.html");
    exit;
}

// Busca os dados do usuário que realizou o login
    $id_pessoa = $_SESSION['id_pessoa'];

    $sql = "SELECT nome, data_nascimento, telefone, email
            FROM PESSOA
            WHERE id_pessoa = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_pessoa);
$stmt->execute();

$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

$stmt->close();

if (!$usuario) {
    echo "Não foi possível encontrar os dados do usuário.";
    exit;
}

// Formata a data para o padrão brasileiro
$data_nascimento = !empty($usuario['data_nascimento'])
    ? date('d/m/Y', strtotime($usuario['data_nascimento']))
    : '';
?>

<!DOCTYPE html>
    <html lang="pt-br">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">

            <link rel="stylesheet" href="seu_perfil.css">

            <link rel="stylesheet"
                href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

            <link rel="stylesheet"
                href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

            <link href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap"
                rel="stylesheet">

            <title>Seu perfil</title>
        </head>

    <body>

    <header class="main-navbar">
        <div class="left">
            <i class="fas fa-bars" title="Menu"></i>
            <i class="fas fa-search" title="Pesquisar"></i>
        </div>

        <div class="logo">
            <img src="../assets/logo_gauge_menor.png" alt="Logo">
        </div>
    </header>

    <div class="container-perfil">

        <header class="header-perfil">
            <h1 class="titulo-pagina">Seu Perfil</h1>
        </header>

        <div class="perfil-centro">
            <i class="bi bi-person-circle perfil-grande"></i>
        </div>

        <form class="formulario-perfil">

            <div class="grupo-campo">
                <label for="nome-perfil">Nome Completo</label>

                <input
                    type="text"
                    id="nome-perfil"
                    class="input-perfil-campo"
                    value="<?= htmlspecialchars($usuario['nome']) ?>"
                    readonly>
            </div>

            <div class="grupo-campo">
                <label for="nascimento-perfil">Data de Nascimento</label>

                <input
                    type="text"
                    id="nascimento-perfil"
                    class="input-perfil-campo"
                    value="<?= htmlspecialchars($data_nascimento) ?>"
                    readonly>
            </div>

            <div class="grupo-campo">
                <label for="telefone-perfil">Telefone</label>

                <input
                    type="text"
                    id="telefone-perfil"
                    class="input-perfil-campo"
                    value="<?= htmlspecialchars($usuario['telefone']) ?>"
                    readonly>
            </div>

            <div class="grupo-campo">
                <label for="email-perfil">Email</label>

                <input
                    type="email"
                    id="email-perfil"
                    class="input-perfil-campo"
                    value="<?= htmlspecialchars($usuario['email']) ?>"
                    readonly>
            </div>

            <div class="grupo-campo">
                <label for="senha">Senha</label>

                <input
                    type="password"
                    id="senha"
                    class="input-perfil-campo"
                    value=""
                    placeholder="Senha protegida"
                    readonly>
            </div>

            <div class="centro-botao-excluir">
                <button type="button" class="botao-excluir">
                    Excluir Conta
                </button>

                <a href="logout.php" class="botao-logout">Sair</a>
            </div>

        </form>
    </div>

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
                <span class="footer-label">Precisa de Suporte?</span>

                <a href="mailto:contato@gauge.com.br"
                   class="footer-link link-sublinhado">
                    contato@gauge.com.br
                </a>
            </div>

        </div>
    </footer>

</body>
</html>

