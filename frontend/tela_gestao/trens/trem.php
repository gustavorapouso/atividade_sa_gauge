<?php


require '../../../data_base/includes/proteger.php';
require '../../../data_base/includes/permissao.php';

if (!temPapel([1, 2, 3])) {
    echo "Acesso negado.";
    exit;
}

?>



<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trens</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap"
      rel="stylesheet"/>

     

    

    <link rel="stylesheet" href="trem_style.css?v=1.0">
</head>
<body>
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

        <i class="fas fa-search" title="Pesquisar"></i>

    </div>

    <div class="logo">

        <img
            src="../../assets/logo_gauge_menor.png"
            alt="Gauge Logo"
        >

    </div>

</header>

<div class="layout-wrapper">

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
                    href="trem.php"
                    class="btn-menu-item"
                >
                    TREM
                </a>

                <a
                    href="../trens_carga/trem_carga.php"
                    class="btn-menu-item"
                >
                    TREM DE CARGA
                </a>

                <a
                    href="#"
                    class="btn-menu-item"
                >
                    ALERTAS
                </a>

            </nav>

        </aside>

    <main>
    <section>
        <h1 class="titulo">Trem de Passageiro</h1>
        <div class="barra-pesquisa">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="16"
          height="16"
          fill="currentColor"
          class="lupa"
          viewBox="0 0 16 16"
        >
          <path
            d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"
          />
        </svg>
        <div class="procurar-trem">
            <input type="text" class="procurar-input" placeholder="Pesquise uma linha" id="pesquisa_bar">
        </div>
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="16"
          height="16"
          fill="currentColor"
          class="microfone"
          viewBox="0 0 16 16"
        >
          <path d="M5 3a3 3 0 0 1 6 0v5a3 3 0 0 1-6 0z" />
          <path
            d="M3.5 6.5A.5.5 0 0 1 4 7v1a4 4 0 0 0 8 0V7a.5.5 0 0 1 1 0v1a5 5 0 0 1-4.5 4.975V15h3a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1h3v-2.025A5 5 0 0 1 3 8V7a.5.5 0 0 1 .5-.5"
          />
        </svg>
      </div>
        
    </section>

    <section class="container-cards">
            
        <div class="card-trem">
            <h3>Linha Central</h3>
            <p><strong>ID: </strong>TR-1103</p>
            <p><strong>Status: </strong>Em operação</p>
            <p><strong>Localização Atual: </strong>Linha Central</p>
            <p><strong>Velocidade: </strong>63 km/h</p>
        </div>

        <div class="card-trem">
            <h3>Linha Norte</h3>
            <p><strong>ID: </strong>TR-0901</p>
            <p><strong>Status: </strong>Parado na Estação</p>
            <p><strong>Localização Atual: </strong>Estação Norte</p>
            <p><strong>Velocidade: </strong>0 km/h</p>
        </div>

        <div class="card-trem">
            <h3>Linha Sul</h3>
            <p><strong>ID: </strong>TR-0726</p>
            <p><strong>Status: </strong>Parado na Estação</p>
            <p><strong>Localização Atual: </strong>Estação Sul</p>
            <p><strong>Velocidade: </strong>0 km/h</p>
        </div>

        
    </section>

    </main>
</div>
        <footer class="footer-gauge">
    <div class="footer-container">
        
        <div class="footer-bloco bloco-esquerda">
            <span class="footer-label">Entre em contato</span>
            <a href="tel:47999174896" class="footer-link">(47) 99917-4896</a>
        </div>

        <div class="footer-bloco bloco-centro">
            <img src="../../assets/logo_gauge_menor.png" alt="Gauge Logo" class="footer-logo">
            <p class="footer-copyright">&copy; 2026 Gauge. Todos os direitos reservados.</p>
        </div>

        <div class="footer-bloco bloco-direita">
            <span class="footer-label">Precisa de Suporte?</span>
            <a href="mailto:contato@gauge.com.br" class="footer-link link-sublinhado">contato@gauge.com.br</a>
        </div>

    </div>
</footer>

    <script src="../../js/menu.js"></script>
</body>
</html>