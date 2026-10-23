<?php

require '../../data_base/includes/proteger.php';
require '../../data_base/includes/permissao.php';
require '../../data_base/conexao.php';

if (!temPapel([2])) {
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
        content="width=device-width, initial-scale=1.0">

    <title>Painel Gestor</title>


    <link
        rel="stylesheet"
        href="painel.css?v=1.0">

    <link
        rel="stylesheet"
        href="menu.css">

    <link rel="stylesheet" href="dashboard.css">

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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>


<body>


    <!-- CABEÇALHO -->

    <header class="header">

        <div class="header-left">

            <button
                type="button"
                class="menu-button"
                id="menuBtn"
                aria-label="Abrir menu">

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
                src="../../frontend/assets/logo_gauge_menor.png"
                alt="Gauge Logo">

        </div>

    </header>



    <!-- ÁREA QUE DIVIDE MENU E CONTEÚDO -->

    <div class="layout-wrapper">


        <!-- MENU LATERAL -->

        <aside
            class="sidebar"
            id="sidebar">

            <nav class="nav-links">


                <a
                    href="../tela_gestao/seu_perfil.php"
                    class="btn-menu-item">
                    PERFIL
                </a>


                <a
                    href="../tela_gestao/sensores.php"
                    class="btn-menu-item">
                    CADASTRAR SENSOR
                </a>


                <?php if (temPapel([2])): ?>

                    <a
                        href="../tela_gestao/usuarios.php"
                        class="btn-menu-item">
                        USUÁRIOS
                    </a>

                <?php endif; ?>


                <a
                    href="#"
                    class="btn-menu-item">
                    RELATÓRIOS
                </a>


                <a
                    href="../tela_gestao/trens/trem.php"
                    class="btn-menu-item">
                    TREM
                </a>

                <a
                    href="../tela_gestao/trens_carga/trem_carga.php"
                    class="btn-menu-item">
                    TREM DE CARGA
                </a>

                <a
                    href="#"
                    class="btn-menu-item">
                    ALERTAS
                </a>

            </nav>

        </aside>

        <?php
            $velocidade = $conexao->query("SELECT ROUND(AVG(leituras.valor)) AS velocidade
                FROM leituras
                INNER JOIN sensores
                    ON leituras.id_sensor = sensores.id_sensores
                WHERE sensores.tipo = 'Velocidade'");

            $temperatura = $conexao->query("SELECT ROUND(AVG(leituras.valor)) AS temperatura
                FROM leituras
                INNER JOIN sensores
                ON leituras.id_sensor = sensores.id_sensores
                WHERE sensores.tipo = 'Temperatura'");

            $consumo = $conexao->query("SELECT ROUND(AVG(leituras.valor)) AS consumo
                FROM leituras
                INNER JOIN sensores
                ON leituras.id_sensor = sensores.id_sensores
                WHERE sensores.tipo = 'Consumo'");

            $vibracao = $conexao->query("SELECT ROUND(AVG(leituras.valor)) AS vibracao
                FROM leituras
                INNER JOIN sensores
                ON leituras.id_sensor = sensores.id_sensores
                WHERE sensores.tipo = 'Vibração'");
        ?>

        <!-- CONTEÚDO PRINCIPAL -->


        <!-- CONTEÚDO PRINCIPAL -->
        <main class="main-content">

            <div class="title">
                <h1 class="titulo-dashboard">Painel de Disponibilidade</h1>
            </div>

            <div class="dashboard-layout">

                <!-- QUATRO LACUNAS À ESQUERDA -->
                <section class="lacunas-grid">

                    <div class="lacuna">
                        <i class="fa-solid fa-bolt"></i>
                        <h2>Velocidade</h2>
                        <p>
                            <?php
                            $resultado = $velocidade->fetch_assoc();
                            echo $resultado['velocidade']
                            ?>
                        </p>
                    </div>

                    <div class="lacuna">
                        <i class="fas fa-temperature-high"></i>
                        <h2>Temperatura</h2>
                        <p>
                            <?php
                            $resultado = $temperatura->fetch_assoc();
                            echo $resultado['temperatura']
                            ?>
                        </p>
                    </div>

                    <div class="lacuna">
                        <i class="fas fa-gas-pump"></i>
                        <h2>Consumo</h2>
                        <p>
                            <?php
                            $resultado = $consumo->fetch_assoc();
                            echo $resultado['consumo']
                            ?>
                        </p>
                    </div>

                    <div class="lacuna">
                        <i class="fas fa-water fa-shake"></i>
                        <h2>Vibração</h2>
                        <p>
                            <?php
                            $resultado = $vibracao->fetch_assoc();
                            echo $resultado['vibracao']
                            ?>
                        </p>
                    </div>

                    <div class="lacuna">
                        <i class="fas fa-chart-column"></i>
                        <h2>Indicador 4</h2>
                        <p>Conteúdo do painel</p>
                    </div>

                    <div class="lacuna">
                        <i class="fas fa-chart-column"></i>
                        <h2>Indicador 4</h2>
                        <p>Conteúdo do painel</p>
                    </div>

                </section>

                <!-- QUATRO CARDS MARRONS À DIREITA -->
                <!-- Arrumar -->
                <?php

                    $trem_passageiro_status = $conexao->query("
                        SELECT COUNT(*) AS trem
                        FROM trem
                        WHERE status = 'ativo'
                        AND tipo_trem = 'Passageiro'
                    ");

                    $trem_carga_status = $conexao->query("
                        SELECT COUNT(*) AS trem
                        FROM trem
                        WHERE status = 'ativo'
                        AND tipo_trem = 'Carga'
                    ");

                    $trem_status = $conexao->query("
                        SELECT COUNT(*) AS trem
                        FROM trem
                        WHERE status = 'inativo'
                        AND tipo_trem = 'Passageiro'
                    ");

                    $trem_status = $conexao->query("
                        SELECT COUNT(*) AS trem
                        FROM trem
                        WHERE status = 'inativo'
                        AND tipo_trem = 'Carga'
                    ");

                ?>

                <aside class="container">

                    <div class="card">
                        <div class="icon">
                            <i class="fas fa-train"></i>
                        </div>
                        <div class="info">
                            <h2>TRENS DE PASSAGEIRO</h2>
                            <p>Ativos: 
                                <?php
                                $resultado = $trem_passageiro_status->fetch_assoc();
                                echo $resultado['trem']
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="info">
                            <h2>TRENS DE CARGA</h2>
                            <p>Ativos: 
                                <?php
                                $resultado = $trem_carga_status->fetch_assoc();
                                echo $resultado['trem']
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="icon">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <div class="info">
                            <h2>EM MANUTENÇÃO</h2>
                            <p>Quantidade: <?php
                                $resultado = $trem_carga_status->fetch_assoc();
                                echo $resultado['trem']
                                ?></p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="info">
                            <h2>TRENS INATIVOS</h2>
                            <p>Quantidade: 2</p>
                        </div>
                    </div>
                <!--Arrumar -->
                </aside>

            </div>

        </main>




    </div>

    <footer class="footer-gauge">
        <div class="footer-container">

            <div class="footer-bloco bloco-esquerda">
                <span class="footer-label">Entre em contato</span>
                <a href="tel:47999174896" class="footer-link">(47) 99917-4896</a>
            </div>

            <div class="footer-bloco bloco-centro">
                <img src="../assets/logo_gauge_menor.png" alt="Gauge Logo" class="footer-logo">
                <p class="footer-copyright">&copy; 2026 Gauge. Todos os direitos reservados.</p>
            </div>

            <div class="footer-bloco bloco-direita">
                <span class="footer-label">Precisa de Suporte?</span>
                <a href="mailto:contato@gauge.com.br" class="footer-link link-sublinhado">contato@gauge.com.br</a>
            </div>

        </div>
    </footer>



    <script src="../js/menu.js"></script>

</body>

</html>