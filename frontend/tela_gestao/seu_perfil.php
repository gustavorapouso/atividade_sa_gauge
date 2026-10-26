
<?php

session_start();

require '../../data_base/conexao.php';
require '../../data_base/includes/permissao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_pessoa'])) {
    header("Location: ../autenticacao/login.html");
    exit;
}

// Busca os dados do usuário
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
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Seu Perfil</title>

    <!-- CSS DO PERFIL -->
    <link
        rel="stylesheet"
        href="seu_perfil.css?v=1.0">

    <!-- CSS DO MENU LATERAL -->
    <link
        rel="stylesheet"
        href="menu.css">

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Fontes -->
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">

</head>

<body>

    <!-- CABEÇALHO -->

    <header class="main-navbar">

        <div class="left">

            <!-- BOTÃO DO MENU LATERAL -->

            <button
                type="button"
                class="menu-button"
                id="menuBtn"
                aria-label="Abrir menu">

                <span></span>
                <span></span>
                <span></span>

            </button>

            <!-- PESQUISA -->

            <i
                class="fas fa-search"
                title="Pesquisar">
            </i>

        </div>

        <!-- LOGO -->

        <div class="logo">

            <img
                src="../assets/logo_gauge_menor.png"
                alt="Logo Gauge">

        </div>

    </header>


    <!-- ÁREA QUE DIVIDE MENU E CONTEÚDO -->

    <div class="layout-wrapper">


        <!-- MENU LATERAL -->

        <aside
            class="sidebar"
            id="sidebar">

            <nav class="nav-links">


                <!-- PERFIL -->

                <a
                    href="seu_perfil.php"
                    class="btn-menu-item">

                    PERFIL

                </a>


                <!-- CADASTRAR SENSOR -->

                <a
                    href="sensores.php"
                    class="btn-menu-item">

                    CADASTRAR SENSOR

                </a>


                <!-- USUÁRIOS -->

                <?php if (temPapel([2])): ?>

                    <a
                        href="usuarios.php"
                        class="btn-menu-item">

                        USUÁRIOS

                    </a>

                <?php endif; ?>


                <!-- RELATÓRIOS -->

                <a
                    href="#"
                    class="btn-menu-item">

                    RELATÓRIOS

                </a>


                <!-- TREM DE PASSAGEIRO -->

                <a
                    href="trens/trem.php"
                    class="btn-menu-item">

                    TREM

                </a>


                <!-- TREM DE CARGA -->

                <a
                    href="trens_carga/trem_carga.php"
                    class="btn-menu-item">

                    TREM DE CARGA

                </a>


                <!-- ALERTAS -->

                <a
                    href="#"
                    class="btn-menu-item">

                    ALERTAS

                </a>


            </nav>

        </aside>


        <!-- CONTEÚDO PRINCIPAL -->

        <main class="main-content">


            <!-- TÍTULO -->

            <header class="header-perfil">

                <h1 class="titulo-pagina">

                    Seu Perfil

                </h1>

            </header>


            <!-- CONTEÚDO DO PERFIL -->

            <div class="container-perfil">


                <!-- ÍCONE DO USUÁRIO -->

                <div class="perfil-centro">

                    <i class="bi bi-person-circle perfil-grande"></i>

                </div>


                <!-- FORMULÁRIO -->

                <form class="formulario-perfil">


                    <!-- NOME -->

                    <div class="grupo-campo">

                        <label for="nome-perfil">

                            Nome Completo

                        </label>

                        <input
                            type="text"
                            id="nome-perfil"
                            class="input-perfil-campo"
                            value="<?= htmlspecialchars($usuario['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            readonly>

                    </div>


                    <!-- DATA DE NASCIMENTO -->

                    <div class="grupo-campo">

                        <label for="nascimento-perfil">

                            Data de Nascimento

                        </label>

                        <input
                            type="text"
                            id="nascimento-perfil"
                            class="input-perfil-campo"
                            value="<?= htmlspecialchars($data_nascimento, ENT_QUOTES, 'UTF-8') ?>"
                            readonly>

                    </div>


                    <!-- TELEFONE -->

                    <div class="grupo-campo">

                        <label for="telefone-perfil">

                            Telefone

                        </label>

                        <input
                            type="text"
                            id="telefone-perfil"
                            class="input-perfil-campo"
                            value="<?= htmlspecialchars($usuario['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            readonly>

                    </div>


                    <!-- EMAIL -->

                    <div class="grupo-campo">

                        <label for="email-perfil">

                            Email

                        </label>

                        <input
                            type="email"
                            id="email-perfil"
                            class="input-perfil-campo"
                            value="<?= htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            readonly>

                    </div>


                    <!-- SENHA -->

                    <div class="grupo-campo">

                        <label for="senha">

                            Senha

                        </label>

                        <input
                            type="password"
                            id="senha"
                            class="input-perfil-campo"
                            value=""
                            placeholder="Senha protegida"
                            readonly>

                    </div>


                    <!-- BOTÕES -->

                    <div class="centro-botao-excluir">

                        <button
                            type="button"
                            class="botao-excluir">

                            Excluir Conta

                        </button>

                        <a
                            href="logout.php"
                            class="botao-logout">

                            Sair

                        </a>

                    </div>


                </form>


            </div>


        </main>


    </div>


    <!-- RODAPÉ -->

    <footer class="footer-gauge">

        <div class="footer-container">


            <!-- CONTATO -->

            <div class="footer-bloco bloco-esquerda">

                <span class="footer-label">

                    Entre em contato

                </span>

                <a
                    href="tel:47999174896"
                    class="footer-link">

                    (47) 99917-4896

                </a>

            </div>


            <!-- LOGO -->

            <div class="footer-bloco bloco-centro">

                <img
                    src="../assets/logo_gauge_menor.png"
                    alt="Gauge Logo"
                    class="footer-logo">

                <p class="footer-copyright">

                    &copy; 2026 Gauge. Todos os direitos reservados.

                </p>

            </div>


            <!-- SUPORTE -->

            <div class="footer-bloco bloco-direita">

                <span class="footer-label">

                    Precisa de Suporte?

                </span>

                <a
                    href="mailto:contato@gauge.com.br"
                    class="footer-link link-sublinhado">

                    contato@gauge.com.br

                </a>
alice e vitória legais
            </div>


        </div>

    </footer>


    <!-- JAVASCRIPT DO MENU -->

    <script src="../js/menu.js"></script>


</body>

</html>