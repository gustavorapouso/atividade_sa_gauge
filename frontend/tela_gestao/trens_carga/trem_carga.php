
<?php
require '../../../data_base/includes/proteger.php';
require '../../../data_base/includes/permissao.php';
require '../../../data_base/conexao.php';

// Permite acesso aos perfis autorizados
if (!temPapel([1, 2, 3])) {
    header('Location: ../../tela_principal/dashboard.php');
    exit;
}

// Busca somente os trens de carga cadastrados
$sql = "SELECT
            id_trem,
            prefixo,
            modelo,
            ano,
            status,
            capacidade,
            ultima_inspecao,
            tipo_carga
        FROM trem
        WHERE tipo_trem = 'Carga'
        ORDER BY prefixo ASC";

$resultado = $conexao->query($sql);

if (!$resultado) {
    die("Erro ao buscar os trens: " . $conexao->error);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Trens de Carga | Ferrorama Gauge</title>

    <link rel="stylesheet" href="trem_carga_style.css?v=1">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

    <header class="navbar">
        <div class="navbar-esquerda">
            <i class="fa-solid fa-bars"></i>
            <h1>Trens de Carga</h1>
        </div>

        <a href="../../tela_principal/dashboard.php" class="logo">
            <img src="../../../assets/logo_gauge_menor.png" alt="Logo Gauge">
        </a>
    </header>

    <main class="container">

        <section class="cabecalho-pagina">
            <div>
                <h2>Gerenciamento de Trens de Carga</h2>
                <p>Consulte os trens de carga cadastrados no sistema.</p>
            </div>

            <div class="contador">
                <i class="fa-solid fa-train"></i>
                <span>
                    <?php echo $resultado->num_rows; ?>
                    trem(ns) cadastrado(s)
                </span>
            </div>
        </section>

        <section class="lista-trens">

            <?php if ($resultado->num_rows > 0): ?>

                <?php while ($trem = $resultado->fetch_assoc()): ?>

                    <a
                        href="linha_trem_carga.php?id_trem=<?php echo (int) $trem['id_trem']; ?>"
                        class="card-trem"
                    >

                        <div class="card-topo">
                            <div class="icone-trem">
                                <i class="fa-solid fa-train"></i>
                            </div>

                            <span class="status <?php echo strtolower(str_replace(' ', '-', $trem['status'])); ?>">
                                <?php echo htmlspecialchars($trem['status']); ?>
                            </span>
                        </div>

                        <div class="informacoes-trem">

                            <h3>
                                <?php echo htmlspecialchars($trem['prefixo']); ?>
                            </h3>

                            <p class="modelo">
                                <?php echo htmlspecialchars($trem['modelo']); ?>
                            </p>

                            <div class="detalhes-trem">

                                <div class="detalhe">
                                    <i class="fa-solid fa-calendar"></i>
                                    <span>
                                        Ano:
                                        <?php echo htmlspecialchars($trem['ano']); ?>
                                    </span>
                                </div>

                                <div class="detalhe">
                                    <i class="fa-solid fa-weight-hanging"></i>
                                    <span>
                                        Capacidade:
                                        <?php echo htmlspecialchars($trem['capacidade']); ?> toneladas
                                    </span>
                                </div>

                                <div class="detalhe">
                                    <i class="fa-solid fa-box"></i>
                                    <span>
                                        Tipo de carga:
                                        <?php
                                        echo !empty($trem['tipo_carga'])
                                            ? htmlspecialchars($trem['tipo_carga'])
                                            : 'Não informado';
                                        ?>
                                    </span>
                                </div>

                                <div class="detalhe">
                                    <i class="fa-solid fa-screwdriver-wrench"></i>
                                    <span>
                                        Última inspeção:
                                        <?php
                                        echo !empty($trem['ultima_inspecao'])
                                            ? date('d/m/Y', strtotime($trem['ultima_inspecao']))
                                            : 'Não informada';
                                        ?>
                                    </span>
                                </div>

                            </div>
                        </div>

                        <div class="card-rodape">
                            <span>Visualizar detalhes</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </div>

                    </a>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="mensagem-vazia">
                    <i class="fa-solid fa-train"></i>
                    <h3>Nenhum trem de carga encontrado</h3>
                    <p>Não existem trens de carga cadastrados no momento.</p>
                </div>

            <?php endif; ?>

        </section>

    </main>

    <footer class="footer">
        <div class="footer-contato">
            <span>Contato: suporte@ferroramagauge.com</span>
        </div>

        <div class="footer-centro">
            <img src="../../../assets/logo_gauge_menor.png" alt="Logo Gauge">
            <p>© <?php echo date('Y'); ?> Ferrorama Gauge. Todos os direitos reservados.</p>
        </div>

        <div class="footer-suporte">
            <span>Suporte</span>
        </div>
    </footer>

</body>
</html>

<?php
$resultado->free();
$conexao->close();
?>
