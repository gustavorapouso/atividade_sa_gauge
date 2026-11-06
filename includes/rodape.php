<?php
// Fim das páginas internas: fecha o <main> aberto em cabecalho.php,
// mostra o rodapé e carrega os scripts.
//
// Variável opcional:
//   $scripts -> lista de endereços de scripts extras
?>
        </main>

    </div>

    <footer class="rodape">
        <div class="rodape-bloco">
            <span class="rodape-rotulo">Entre em contato</span>
            <a href="tel:+5547999174896">(47) 99917-4896</a>
        </div>

        <div class="rodape-bloco rodape-centro">
            <img src="<?= url('assets/img/logo_gauge_menor.png') ?>" alt="Gauge" class="rodape-logo">
            <small>&copy; <?= date('Y') ?> Gauge. Todos os direitos reservados.</small>
        </div>

        <div class="rodape-bloco">
            <span class="rodape-rotulo">Precisa de suporte?</span>
            <a href="mailto:contato@gauge.com.br">contato@gauge.com.br</a>
        </div>
    </footer>

    <script src="<?= url('assets/js/menu.js') ?>"></script>

    <?php foreach ($scripts ?? [] as $script): ?>
        <script src="<?= h($script) ?>"></script>
    <?php endforeach; ?>

</body>

</html>
