<?php

session_start();

require 'includes/proteger.php';
require 'conexao.php';

include('permissao.php');

$perfil = $_SESSION['fk_id_perfil'] ?? null;


?>


<body>

<div class="layout-wrapper">
        <aside class="sidebar">
            <div class="menu-trigger-container">
               <div class="icone-menu-custom">
        <div class="linha-menu"></div>
        <div class="linha-menu"></div>
        <div class="linha-menu"></div>
                </div>
            </div>
            
            <nav class="nav-links">
                <a href="#" class="btn-menu-item">PERFIL</a>
                <a href="#" class="btn-menu-item">CADASTRAR SENSOR</a>
                <a href="#" class="btn-menu-item">USUÁRIOS</a>
                <a href="#" class="btn-menu-item">RELÁTORIOS</a>
                <a href="#" class="btn-menu-item">ALERTAS</a>
            </nav>
        </aside>

        <main class="main-content-blank">
            </main>
    </div>

</body>
</html>