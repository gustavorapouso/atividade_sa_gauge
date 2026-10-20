
<?php
require '../../data_base/conexao.php';

$trens = $conexao->query(
    "SELECT id_trem, prefixo, modelo FROM trem ORDER BY prefixo"
);

if (!$trens) {
    exit('Erro ao consultar os trens: ' . htmlspecialchars($conexao->error));
}

$tipoSel = $_GET['tipo'] ?? 'desempenho';
$inicio = $_GET['data_inicio'] ?? '';
$fim = $_GET['data_fim'] ?? '';
$tremSel = filter_input(INPUT_GET, 'id_trem', FILTER_VALIDATE_INT) ?: 0;
$statusSel = $_GET['status'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gerar relatório | Gauge</title>

    <link rel="stylesheet" href="relatorios_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
<div class="pagina">

    <header class="header">
        <div class="left">
            <a href="gera_relatorio.php" title="Menu">
                <i class="fas fa-bars"></i>
            </a>

            <a href="gera_relatorio.php" title="Pesquisar">
                <i class="fas fa-search"></i>
            </a>
        </div>

        <div class="logo">
            <img src="../assets/logo_gauge_menor.png" alt="Gauge Logo">
        </div>
    </header>

    <main class="conteudo">
        <h1>RELATÓRIOS</h1>
        <p class="subtitulo">Consulte e acompanhe os dados ferroviários.</p>

        <form class="filtros" method="GET" action="visualiza_relatorio.php">

            <div class="campo">
                <label for="tipo">Tipo de relatório</label>

                <select name="tipo" id="tipo" required>
                    <option value="desempenho" <?= $tipoSel === 'desempenho' ? 'selected' : '' ?>>
                        Desempenho dos trens
                    </option>

                    <option value="consumo" <?= $tipoSel === 'consumo' ? 'selected' : '' ?>>
                        Consumo de energia
                    </option>

                    <option value="eficiencia" <?= $tipoSel === 'eficiencia' ? 'selected' : '' ?>>
                        Eficiência operacional
                    </option>

                    <option value="manutencao" <?= $tipoSel === 'manutencao' ? 'selected' : '' ?>>
                        Manutenção
                    </option>
                </select>
            </div>

            <div class="campo">
                <label for="data_inicio">Data inicial (opcional)</label>
                <input
                    type="date"
                    name="data_inicio"
                    id="data_inicio"
                    value="<?= htmlspecialchars($inicio) ?>"
                >
            </div>

            <div class="campo">
                <label for="data_fim">Data final (opcional)</label>
                <input
                    type="date"
                    name="data_fim"
                    id="data_fim"
                    value="<?= htmlspecialchars($fim) ?>"
                >
            </div>

            <div class="campo">
                <label for="id_trem">Trem</label>

                <select name="id_trem" id="id_trem">
                    <option value="0">Todos os trens</option>

                    <?php while ($t = $trens->fetch_assoc()): ?>
                        <option
                            value="<?= (int)$t['id_trem'] ?>"
                            <?= $tremSel === (int)$t['id_trem'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($t['prefixo'] . ' - ' . $t['modelo']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="campo">
                <label for="status">Status / severidade</label>

                <select name="status" id="status">
                    <?php
                    $opcoesStatus = [
                        '' => 'Todos',
                        'normal' => 'Normal',
                        'atencao' => 'Atenção',
                        'critico' => 'Crítico',
                        'programada' => 'Programada',
                        'em andamento' => 'Em andamento',
                        'concluida' => 'Concluída',
                        'cancelada' => 'Cancelada',
                        'atrasada' => 'Atrasada'
                    ];

                    foreach ($opcoesStatus as $valor => $rotulo):
                    ?>
                        <option
                            value="<?= htmlspecialchars($valor) ?>"
                            <?= $statusSel === $valor ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($rotulo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="botao" type="submit">
                <i class="fas fa-chart-column"></i>
                Gerar relatório
            </button>

        </form>

        <section class="orientacao">
            <i class="fas fa-circle-info"></i>
            As datas são opcionais. Deixe as duas vazias para consultar todo o histórico.
        </section>
    </main>

    <footer class="footer-gauge">
        <div class="footer-container">

            <div class="footer-bloco">
                <span class="footer-label">Entre em contato</span>
                <a href="tel:47999174896" class="footer-link">
                    (47) 99917-4896
                </a>
            </div>

            <div class="footer-bloco bloco-centro">
                <img
                    src="../assets/logo_gauge_menor.png"
                    alt="Gauge Logo"
                    class="footer-logo"
                >

                <p class="footer-copyright">
                    &copy; 2026 Gauge. Todos os direitos reservados.
                </p>
            </div>

            <div class="footer-bloco">
                <span class="footer-label">Precisa de Suporte?</span>
                <a href="mailto:contato@gauge.com.br" class="footer-link">
                    contato@gauge.com.br
                </a>
            </div>

        </div>
    </footer>

</div>
</body>
</html>

