<?php
require __DIR__ . '/../../includes/proteger.php';
require __DIR__ . '/../../includes/paineis.php';

exigirPapel([PERFIL_CLIENTE]);

$titulo  = 'Painel do cliente';
$estilos = ['paineis'];
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina">Painel de disponibilidade</h1>
<p class="subtitulo">Olá, <?= h($_SESSION['nome'] ?? '') ?>! Veja a situação atual dos trens.</p>

<?php exibirResumoTrens($conexao); ?>

<div class="acoes acoes-centro">
    <a href="<?= url('paginas/trens/trens_carga.php') ?>" class="botao">
        <i class="fas fa-box"></i> Trens de carga
    </a>
    <a href="<?= url('paginas/trens/trens_passageiro.php') ?>" class="botao">
        <i class="fas fa-train-subway"></i> Trens de passageiros
    </a>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
