<?php
require __DIR__ . '/../../includes/proteger.php';
require __DIR__ . '/../../includes/paineis.php';

exigirPapel([PERFIL_GESTOR]);

$titulo  = 'Painel do gestor';
$estilos = ['paineis'];
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina">Painel de disponibilidade</h1>
<p class="subtitulo">Olá, <?= h($_SESSION['nome'] ?? '') ?>! Veja a situação atual da frota.</p>

<div class="painel-inicial">
    <?php exibirIndicadoresSensores($conexao); ?>
    <?php exibirResumoTrens($conexao); ?>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
