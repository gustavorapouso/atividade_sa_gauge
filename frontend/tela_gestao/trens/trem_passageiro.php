
<?php
require '../../../data_base/includes/proteger.php';
require '../../../data_base/includes/permissao.php';
require '../../../data_base/conexao.php';

if (!temPapel([1, 2, 3])) {
    header('Location: ../../tela_principal/dashboard.php');
    exit;
}

// Buscar somente os trens de passageiros cadastrados no banco
$sql = "SELECT
            id_trem,
            prefixo,
            modelo,
            ano,
            status,
            capacidade,
            ultima_inspecao,
            tipo_trem,
            quantidade_assentos
        FROM trem
        WHERE tipo_trem = 'Passageiro'
        ORDER BY prefixo ASC";

$resultado = $conexao->query($sql);

if (!$resultado) {
    die('Erro ao consultar os trens: ' . $conexao->error);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Trens de Passageiros | Gauge</title>

    <link rel="stylesheet" href="trem_style.css?v=99">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<header class="navbar">
    <button id="menuBtn" type="button" aria-label="Abrir menu">
        <i class="fa-solid fa-bars"></i>
    </button>

    <a href="../../tela_principal/dashboard.php">
        <img
            src="../../assets/logo_gauge_menor.png"
            alt="Logo Gauge"
            class="logo"
        >
    </a>
</header>

<aside class="sidebar" id="sidebar">
    <nav>
        <a href="../perfil/perfil.php">
            <i class="fa-solid fa-user"></i>
            Perfil
        </a>

        <a href="../sensores/sensores.php">
            <i class="fa-solid fa-gauge-high"></i>
            Sensores
        </a>

        <?php if (temPapel([2])): ?>
            <a href="../usuarios/usuarios.php">
                <i class="fa-solid fa-users"></i>
                Usuários
            </a>
        <?php endif; ?>

        <a href="../relatorios/relatorios.php">
            <i class="fa-solid fa-chart-column"></i>
            Relatórios
        </a>

        <a href="../trens_carga/trem_carga.php">
            <i class="fa-solid fa-train"></i>
            Trens de Carga
        </a>

        <a href="../alertas/alertas.php">
            <i class="fa-solid fa-bell"></i>
            Alertas
        </a>
    </nav>
</aside>

<main class="conteudo">

    <section class="titulo-pagina">
        <h1>Trens de Passageiros</h1>
        <p>Consulte os trens de passageiros cadastrados no sistema.</p>
    </section>

    <section class="pesquisa">
        <div class="campo-pesquisa">
            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                id="pesquisa_bar"
                placeholder="Pesquise por prefixo ou modelo"
                aria-label="Pesquisar trens"
            >
        </div>
    </section>

    <section class="lista-trens" id="listaTrens">

        <?php if ($resultado->num_rows > 0): ?>

            <?php while ($trem = $resultado->fetch_assoc()): ?>

                <?php
                $prefixo = $trem['prefixo'];
                $modelo = $trem['modelo'];
                $status = $trem['status'];
                ?>

                <article
                    class="card-trem"
                    data-pesquisa="<?= htmlspecialchars(
                        $prefixo . ' ' . $modelo,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                    <div class="card-trem-topo">
                        <div class="icone-trem">
                            <i class="fa-solid fa-train"></i>
                        </div>

                        <span class="status-trem">
                            <?= htmlspecialchars(
                                $status,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                    </div>

                    <div class="card-trem-conteudo">
                        <h2>
                            <?= htmlspecialchars(
                                $prefixo,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h2>

                        <p>
                            <strong>Modelo:</strong>
                            <?= htmlspecialchars(
                                $modelo,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <p>
                            <strong>Ano:</strong>
                            <?= (int) $trem['ano'] ?>
                        </p>

                        <p>
                            <strong>Capacidade:</strong>
                            <?= htmlspecialchars(
                                (string) $trem['capacidade'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <?php if ($trem['quantidade_assentos'] !== null): ?>
                            <p>
                                <strong>Assentos:</strong>
                                <?= (int) $trem['quantidade_assentos'] ?>
                            </p>
                        <?php endif; ?>

                        <p>
                            <strong>Última inspeção:</strong>
                            <?php
                            if (!empty($trem['ultima_inspecao'])) {
                                echo date(
                                    'd/m/Y',
                                    strtotime($trem['ultima_inspecao'])
                                );
                            } else {
                                echo 'Não informada';
                            }
                            ?>
                        </p>

                        <p>
                            <strong>ID do trem:</strong>
                            <?= (int) $trem['id_trem'] ?>
                        </p>
                    </div>

                    <div class="card-trem-acoes">
                        <a
                            href="calendario_trem.php?id_trem=<?= (int) $trem['id_trem'] ?>"
                            class="btn-detalhes"
                        >
                            Ver viagens
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                </article>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="mensagem-vazia">
                <i class="fa-solid fa-train"></i>
                <h2>Nenhum trem encontrado</h2>
                <p>
                    Não há trens cadastrados com o tipo Passageiro.
                </p>
            </div>

        <?php endif; ?>

    </section>

</main>

<footer class="rodape">
    <div class="rodape-contato">
        <p>
            <i class="fa-solid fa-phone"></i>
            (47) 99917-4896
        </p>

        <p>
            <i class="fa-solid fa-envelope"></i>
            contato@gauge.com.br
        </p>
    </div>

    <div class="rodape-logo">
        <img
            src="../../assets/logo_gauge_menor.png"
            alt="Logo Gauge"
        >
    </div>

    <p class="copyright">
        &copy; <?= date('Y') ?> Gauge. Todos os direitos reservados.
    </p>
</footer>

<script src="../../js/menu.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const campoPesquisa = document.getElementById('pesquisa_bar');
    const cards = document.querySelectorAll('.card-trem');

    campoPesquisa.addEventListener('input', function () {
        const termo = this.value.toLowerCase().trim();

        cards.forEach(function (card) {
            const texto = card.dataset.pesquisa.toLowerCase();

            card.style.display = texto.includes(termo) ? '' : 'none';
        });
    });
});
</script>

</body>
</html>

<?php
$resultado->free();
?>
