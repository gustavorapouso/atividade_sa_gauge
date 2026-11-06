<?php
// Lista dos trens de passageiros, com pesquisa por prefixo ou modelo.

require __DIR__ . '/../../includes/proteger.php';

$trens = $conexao->query(
    "SELECT id_trem, prefixo, modelo, ano, status, capacidade,
            ultima_inspecao, quantidade_assentos
     FROM trem
     WHERE tipo_trem = 'Passageiro'
     ORDER BY prefixo ASC"
)->fetch_all(MYSQLI_ASSOC);

$titulo = 'Trens de passageiros';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina">Trens de passageiros</h1>
<p class="subtitulo">Consulte os trens de passageiros cadastrados no sistema.</p>

<?php if (count($trens) === 0): ?>

    <div class="vazio-bloco">
        <i class="fas fa-train"></i>
        <h2>Nenhum trem encontrado</h2>
        <p>Não há trens cadastrados com o tipo Passageiro.</p>
    </div>

<?php else: ?>

    <div class="campo-pesquisa">
        <i class="fas fa-magnifying-glass"></i>
        <input type="search" id="pesquisa" placeholder="Pesquise por prefixo ou modelo" aria-label="Pesquisar trens">
    </div>

    <section class="cartoes">
        <?php foreach ($trens as $trem): ?>
            <article class="cartao" data-pesquisa="<?= h($trem['prefixo'] . ' ' . $trem['modelo']) ?>">
                <div class="cartao-topo">
                    <span class="cartao-icone"><i class="fas fa-train"></i></span>
                    <span class="status status-<?= h($trem['status']) ?>"><?= h($trem['status']) ?></span>
                </div>

                <h3><?= h($trem['prefixo']) ?></h3>

                <div class="dados">
                    <p><strong>Modelo:</strong> <?= h($trem['modelo']) ?></p>
                    <p><strong>Ano:</strong> <?= (int) $trem['ano'] ?></p>
                    <p><strong>Capacidade:</strong> <?= h($trem['capacidade']) ?></p>
                    <?php if ($trem['quantidade_assentos'] !== null): ?>
                        <p><strong>Assentos:</strong> <?= (int) $trem['quantidade_assentos'] ?></p>
                    <?php endif; ?>
                    <p><strong>Última inspeção:</strong> <?= h(formatarData($trem['ultima_inspecao'], 'd/m/Y')) ?></p>
                    <p><strong>ID do trem:</strong> <?= (int) $trem['id_trem'] ?></p>
                </div>

                <div class="cartao-rodape">
                    <a href="detalhes_trem.php?id_trem=<?= (int) $trem['id_trem'] ?>" class="botao">
                        Ver viagens <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <script>
        // Filtra os cartões enquanto o usuário digita
        document.getElementById('pesquisa').addEventListener('input', function () {
            const termo = this.value.toLowerCase().trim();

            document.querySelectorAll('[data-pesquisa]').forEach(function (cartao) {
                cartao.hidden = !cartao.dataset.pesquisa.toLowerCase().includes(termo);
            });
        });
    </script>

<?php endif; ?>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
