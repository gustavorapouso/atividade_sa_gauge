<?php
// Lista dos trens de carga.

require __DIR__ . '/../../includes/proteger.php';

$trens = $conexao->query(
    "SELECT id_trem, prefixo, modelo, ano, status, capacidade,
            ultima_inspecao, tipo_carga
     FROM trem
     WHERE tipo_trem = 'Carga'
     ORDER BY prefixo ASC"
)->fetch_all(MYSQLI_ASSOC);

$titulo = 'Trens de carga';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="cabecalho-pagina">
    <div>
        <h1 class="titulo-pagina">Trens de carga</h1>
        <p>Consulte os trens de carga cadastrados no sistema.</p>
    </div>

    <span class="status">
        <i class="fas fa-train"></i>
        <?= count($trens) ?> trem(ns) cadastrado(s)
    </span>
</div>

<?php if (count($trens) === 0): ?>

    <div class="vazio-bloco">
        <i class="fas fa-train"></i>
        <h2>Nenhum trem de carga encontrado</h2>
        <p>Não existem trens de carga cadastrados no momento.</p>
    </div>

<?php else: ?>

    <section class="cartoes">
        <?php foreach ($trens as $trem): ?>
            <article class="cartao">
                <div class="cartao-topo">
                    <span class="cartao-icone"><i class="fas fa-train"></i></span>
                    <span class="status status-<?= h($trem['status']) ?>"><?= h($trem['status']) ?></span>
                </div>

                <div>
                    <h3><?= h($trem['prefixo']) ?></h3>
                    <p class="nota"><?= h($trem['modelo']) ?></p>
                </div>

                <div class="dados">
                    <p><i class="fas fa-calendar"></i> <strong>Ano:</strong> <?= h($trem['ano']) ?></p>
                    <p><i class="fas fa-weight-hanging"></i> <strong>Capacidade:</strong> <?= h($trem['capacidade']) ?> toneladas</p>
                    <p><i class="fas fa-box"></i> <strong>Tipo de carga:</strong> <?= h($trem['tipo_carga'] ?: 'Não informado') ?></p>
                    <p><i class="fas fa-screwdriver-wrench"></i> <strong>Última inspeção:</strong> <?= h(formatarData($trem['ultima_inspecao'], 'd/m/Y')) ?></p>
                </div>

                <div class="cartao-rodape">
                    <a href="detalhes_trem.php?id_trem=<?= (int) $trem['id_trem'] ?>" class="botao">
                        Ver detalhes <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

<?php endif; ?>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
