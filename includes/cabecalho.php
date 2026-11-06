<?php
// Início das páginas internas: <head>, barra superior e menu lateral.
// Abre o <main>, que é fechado em includes/rodape.php.
//
// Variáveis opcionais (além das de head.php):
//   $menuAtivo -> caminho do item do menu a destacar
//                 (por padrão, a própria página)

require __DIR__ . '/head.php';
require_once __DIR__ . '/menu.php';
?>

<body>

    <script>
        // Aplica antes de desenhar a página a escolha de menu recolhido
        // (evita que o menu "pisque" ao carregar). Ver assets/js/menu.js.
        try {
            if (localStorage.getItem('menuRecolhido') === 'sim') {
                document.body.classList.add('menu-recolhido');
            }
        } catch (erro) {}
    </script>

    <header class="topo">
        <button
            type="button"
            class="botao-menu"
            id="botaoMenu"
            aria-controls="menuLateral"
            aria-expanded="false"
            aria-label="Abrir menu">
            <i class="fas fa-bars"></i>
        </button>

        <span class="topo-titulo"><?= h($titulo) ?></span>

        <a href="<?= url(painelDoUsuario()) ?>" class="topo-logo" title="Ir para o painel">
            <img src="<?= url('assets/img/logo_gauge_menor.png') ?>" alt="Gauge">
        </a>
    </header>

    <div class="layout">

        <aside class="menu-lateral" id="menuLateral" aria-label="Menu principal">
            <nav class="menu-links">
                <?php foreach (itensDoMenu() as $item): ?>
                    <a
                        href="<?= url($item['url']) ?>"
                        class="menu-item"
                        <?= menuEstaAtivo($item['url'], $menuAtivo ?? null) ? 'aria-current="page"' : '' ?>>
                        <i class="fas <?= h($item['icone']) ?>"></i>
                        <?= h($item['texto']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="fundo-menu" id="fundoMenu" hidden></div>

        <main class="conteudo">
