<?php

require '../../data_base/includes/proteger.php';
require '../../data_base/includes/permissao.php';

if (!temPapel([1])) {
    echo "Acesso negado.";
    exit;
}


?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Painel Gestor</title>


    <link
        rel="stylesheet"
        href="painel.css"
    >

    <link
        rel="stylesheet"
        href="menu.css"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet"
    >

</head>


<body>


<!-- CABEÇALHO -->

<header class="header">

    <div class="header-left">

        <button
            type="button"
            class="menu-button"
            id="menuBtn"
            aria-label="Abrir menu"
        >

            <span></span>
            <span></span>
            <span></span>

        </button>


        <span class="pagina-nome">
            PAINEL DO GESTOR
        </span>

    </div>


    <div class="logo">

        <img
            src="../../assets/logo_gauge_menor.png"
            alt="Gauge Logo"
        >

    </div>

</header>



<!-- ÁREA QUE DIVIDE MENU E CONTEÚDO -->

<div class="layout-wrapper">


    <!-- MENU LATERAL -->

    <aside
        class="sidebar"
        id="sidebar"
    >

        <nav class="nav-links">


            <a
                href="#"
                class="btn-menu-item"
            >
                PERFIL
            </a>


            <a
                href="#"
                class="btn-menu-item"
            >
                CADASTRAR SENSOR
            </a>


            <?php if (temPapel([2])): ?>

                <a
                    href="usuarios.php"
                    class="btn-menu-item"
                >
                    USUÁRIOS
                </a>

            <?php endif; ?>


            <a
                href="#"
                class="btn-menu-item"
            >
                RELATÓRIOS
            </a>


            <a
                href="#"
                class="btn-menu-item"
            >
                ALERTAS
            </a>


        </nav>

    </aside>



    <!-- CONTEÚDO PRINCIPAL -->

    <main class="main-content">

        <h1>
            Painel de Disponibilidade
        </h1>


        <div class="container">


            <div class="card">

                <div class="icon">
                    🚆
                </div>

                <div class="info">

                    <h2>
                        TRENS DE PASSAGEIRO
                    </h2>

                    <p>
                        Ativos: 3
                    </p>

                </div>

            </div>



            <div class="card">

                <div class="icon">
                    📦
                </div>

                <div class="info">

                    <h2>
                        TRENS DE CARGA
                    </h2>

                    <p>
                        Ativos: 4
                    </p>

                </div>

            </div>



            <div class="card">

                <div class="icon">
                    🔧
                </div>

                <div class="info">

                    <h2>
                        EM MANUTENÇÃO
                    </h2>

                    <p>
                        Quantidade: 2
                    </p>

                </div>

            </div>



            <div class="card">

                <div class="icon">
                    ⛔
                </div>

                <div class="info">

                    <h2>
                        TRENS INATIVOS
                    </h2>

                    <p>
                        Quantidade: 2
                    </p>

                </div>

            </div>


        </div>

    </main>


</div>



<script src="../js/menu.js"></script>

</body>

</html>