<?php
require __DIR__ . '/../../includes/proteger.php';

$titulo = 'Suporte';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina">Suporte</h1>
<p class="subtitulo">Fale com a equipe Gauge pelos canais abaixo.</p>

<div class="cartoes painel-estreito">
    <a href="mailto:contato@gauge.com.br" class="cartao contato">
        <span class="cartao-icone"><i class="far fa-envelope"></i></span>
        <h3>Precisa de suporte?</h3>
        <p>contato@gauge.com.br</p>
    </a>

    <a href="tel:+5547999174896" class="cartao contato">
        <span class="cartao-icone"><i class="fas fa-phone"></i></span>
        <h3>Entre em contato</h3>
        <p>(47) 99917-4896</p>
    </a>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
