
<?php
require '../../data_base/conexao.php';

$permitidos = [
    'desempenho',
    'consumo',
    'eficiencia',
    'manutencao'
];

$tipo = $_GET['tipo'] ?? 'desempenho';
$inicio = trim($_GET['data_inicio'] ?? '');
$fim = trim($_GET['data_fim'] ?? '');

$idTrem = filter_input(INPUT_GET, 'id_trem', FILTER_VALIDATE_INT) ?: 0;
$status = trim($_GET['status'] ?? '');

if (!in_array($tipo, $permitidos, true)) {
    exit('Tipo de relatório inválido. <a href="gera_relatorio.php">Voltar</a>');
}

function dataOk($valor) {
    if ($valor === '') {
        return true;
    }

    $data = DateTime::createFromFormat('!Y-m-d', $valor);

    return $data && $data->format('Y-m-d') === $valor;
}

if (
    !dataOk($inicio) ||
    !dataOk($fim) ||
    ($inicio !== '' && $fim !== '' && $inicio > $fim)
) {
    exit('Período inválido. <a href="gera_relatorio.php">Voltar</a>');
}

$titulos = [
    'desempenho' => 'Desempenho dos trens',
    'consumo' => 'Consumo de energia',
    'eficiencia' => 'Eficiência operacional',
    'manutencao' => 'Manutenção'
];

$titulo = $titulos[$tipo];

$linhas = [];
$erro = '';
$cond = [];
$vals = [];
$bind = '';
$cols = [];

/* Adiciona apenas as datas que foram preenchidas. */
function datas(&$cond, &$vals, &$bind, $campo, $inicio, $fim) {
    if ($inicio !== '') {
        $cond[] = "$campo >= ?";
        $vals[] = $inicio . ' 00:00:00';
        $bind .= 's';
    }

    if ($fim !== '') {
        $cond[] = "$campo < DATE_ADD(?, INTERVAL 1 DAY)";
        $vals[] = $fim;
        $bind .= 's';
    }
}

/* Executa consultas preparadas. */
function consulta($db, $sql, $bind, $vals) {
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new Exception($db->error);
    }

    if ($bind !== '') {
        $stmt->bind_param($bind, ...$vals);
    }

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $resultado = $stmt->get_result();
    $dados = [];

    while ($linha = $resultado->fetch_assoc()) {
        $dados[] = $linha;
    }

    $stmt->close();

    return $dados;
}

try {

    /*
     * RELATÓRIOS DE DESEMPENHO E CONSUMO
     */
    if ($tipo === 'desempenho' || $tipo === 'consumo') {

        datas(
            $cond,
            $vals,
            $bind,
            'l.data_hora',
            $inicio,
            $fim
        );

        if ($idTrem > 0) {
            $cond[] = 't.id_trem = ?';
            $vals[] = $idTrem;
            $bind .= 'i';
        }

        if ($tipo === 'desempenho') {

            $cond[] = "LOWER(s.tipo) LIKE '%velocidade%'";

            $medidas = '
                ROUND(AVG(l.valor), 2) AS media,
                MAX(l.valor) AS maximo
            ';

            $cols = [
                'Trem',
                'Modelo',
                'Velocidade média',
                'Velocidade máxima'
            ];

        } else {

            $cond[] = "(
                LOWER(s.tipo) LIKE '%consumo%'
                OR LOWER(s.tipo) LIKE '%energia%'
            )";

            $medidas = '
                ROUND(SUM(l.valor), 2) AS total,
                ROUND(AVG(l.valor), 2) AS media
            ';

            $cols = [
                'Trem',
                'Modelo',
                'Consumo total',
                'Consumo médio'
            ];
        }

        $sql = "
            SELECT
                t.id_trem,
                t.prefixo,
                t.modelo,
                $medidas
            FROM leituras l
            INNER JOIN sensores s
                ON s.id_sensores = l.id_sensor
            INNER JOIN trem t
                ON t.id_trem = s.FK_id_trem
            WHERE " . implode(' AND ', $cond) . "
            GROUP BY
                t.id_trem,
                t.prefixo,
                t.modelo
            ORDER BY t.prefixo
        ";

        $linhas = consulta($conexao, $sql, $bind, $vals);

        if ($status !== '') {
            $erro = 'O filtro de status não se aplica aos relatórios de desempenho e consumo. Selecione Todos.';
            $linhas = [];
        }
    }

    /*
     * RELATÓRIO DE MANUTENÇÃO
     */
    if ($tipo === 'manutencao') {

        $cond = [];
        $vals = [];
        $bind = '';

        datas(
            $cond,
            $vals,
            $bind,
            'a.data_hora',
            $inicio,
            $fim
        );

        if ($idTrem > 0) {
            $cond[] = 't.id_trem = ?';
            $vals[] = $idTrem;
            $bind .= 'i';
        }

        if (in_array($status, ['atencao', 'critico'], true)) {
            $cond[] = 'LOWER(a.severidade) = ?';
            $vals[] = $status;
            $bind .= 's';

        } elseif ($status !== '') {
            $erro = 'No relatório de manutenção, o status deve ser Todos, Atenção ou Crítico.';
        }

        if ($erro === '') {

            $sql = "
                SELECT
                    t.id_trem,
                    t.prefixo,
                    t.modelo,
                    COUNT(a.id_alerta) AS total,
                    SUM(
                        CASE
                            WHEN LOWER(a.severidade) = 'critico'
                            THEN 1 ELSE 0
                        END
                    ) AS criticos,
                    SUM(
                        CASE
                            WHEN LOWER(a.severidade) = 'atencao'
                            THEN 1 ELSE 0
                        END
                    ) AS atencoes,
                    MAX(t.ultima_inspecao) AS ultima_inspecao
                FROM alertas a
                INNER JOIN leituras l
                    ON l.id_leitura = a.id_leitura
                INNER JOIN sensores s
                    ON s.id_sensores = l.id_sensor
                INNER JOIN trem t
                    ON t.id_trem = s.FK_id_trem
                " . ($cond ? 'WHERE ' . implode(' AND ', $cond) : '') . "
                GROUP BY
                    t.id_trem,
                    t.prefixo,
                    t.modelo
                ORDER BY total DESC, t.prefixo
            ";

            $linhas = consulta($conexao, $sql, $bind, $vals);
        }

        $cols = [
            'Trem',
            'Modelo',
            'Total de alertas',
            'Atenção',
            'Críticos',
            'Última inspeção'
        ];
    }

    /*
     * RELATÓRIO DE EFICIÊNCIA OPERACIONAL
     */
    if ($tipo === 'eficiencia') {

        $cond = [];
        $vals = [];
        $bind = '';

        datas(
            $cond,
            $vals,
            $bind,
            'v.data_hora_saida',
            $inicio,
            $fim
        );

        if ($idTrem > 0) {
            $cond[] = 't.id_trem = ?';
            $vals[] = $idTrem;
            $bind .= 'i';
        }

        if ($status !== '') {
            $cond[] = 'LOWER(v.status_trem) = ?';
            $vals[] = mb_strtolower($status, 'UTF-8');
            $bind .= 's';
        }

        $sql = "
            SELECT
                t.id_trem,
                t.prefixo,
                t.modelo,
                COUNT(v.id_viagem) AS total,
                SUM(
                    CASE
                        WHEN LOWER(v.status_trem) LIKE '%conclu%'
                        THEN 1 ELSE 0
                    END
                ) AS concluidas,
                SUM(
                    CASE
                        WHEN LOWER(v.status_trem) LIKE '%cancel%'
                        THEN 1 ELSE 0
                    END
                ) AS canceladas,
                SUM(
                    CASE
                        WHEN LOWER(v.status_trem) LIKE '%atras%'
                        THEN 1 ELSE 0
                    END
                ) AS atrasadas
            FROM viagem v
            INNER JOIN trem t
                ON t.id_trem = v.FK_id_trem
            " . ($cond ? 'WHERE ' . implode(' AND ', $cond) : '') . "
            GROUP BY
                t.id_trem,
                t.prefixo,
                t.modelo
            ORDER BY t.prefixo
        ";

        $linhas = consulta($conexao, $sql, $bind, $vals);

        $cols = [
            'Trem',
            'Modelo',
            'Total de viagens',
            'Concluídas',
            'Canceladas',
            'Atrasadas'
        ];
    }

} catch (Throwable $e) {
    $erro = 'Erro ao gerar relatório: ' . $e->getMessage();
}

/* Valores usados nos gráficos. */
function principal($linha, $tipo) {
    if ($tipo === 'desempenho') {
        return (float)$linha['media'];
    }

    return (float)$linha['total'];
}

function secundario($linha, $tipo) {
    if ($tipo === 'desempenho') {
        return (float)$linha['maximo'];
    }

    if ($tipo === 'consumo') {
        return (float)$linha['media'];
    }

    if ($tipo === 'eficiencia') {
        return (float)$linha['concluidas'];
    }

    return (float)$linha['criticos'];
}

function cor($tipo, $valor) {
    if ($tipo === 'desempenho') {
        if ($valor > 120) return 'critico';
        if ($valor > 100) return 'atencao';
    }

    if ($tipo === 'consumo') {
        if ($valor > 9) return 'critico';
        if ($valor > 8) return 'atencao';
    }

    return 'normal';
}

$max1 = 0;
$max2 = 0;

foreach ($linhas as $linha) {
    $max1 = max($max1, principal($linha, $tipo));
    $max2 = max($max2, secundario($linha, $tipo));
}

/* Exportação CSV. */
if (($_GET['exportar'] ?? '') === 'csv' && $erro === '') {

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="relatorio_gauge.csv"');

    $arquivo = fopen('php://output', 'w');

    fwrite($arquivo, "\xEF\xBB\xBF");

    fputcsv($arquivo, $cols, ';');

    foreach ($linhas as $linha) {

        if ($tipo === 'desempenho') {
            $dados = [
                $linha['prefixo'],
                $linha['modelo'],
                $linha['media'],
                $linha['maximo']
            ];

        } elseif ($tipo === 'consumo') {
            $dados = [
                $linha['prefixo'],
                $linha['modelo'],
                $linha['total'],
                $linha['media']
            ];

        } elseif ($tipo === 'eficiencia') {
            $dados = [
                $linha['prefixo'],
                $linha['modelo'],
                $linha['total'],
                $linha['concluidas'],
                $linha['canceladas'],
                $linha['atrasadas']
            ];

        } else {
            $dados = [
                $linha['prefixo'],
                $linha['modelo'],
                $linha['total'],
                $linha['atencoes'],
                $linha['criticos'],
                $linha['ultima_inspecao'] ?? 'Não informada'
            ];
        }

        fputcsv($arquivo, $dados, ';');
    }

    fclose($arquivo);
    exit;
}

$parametros = $_GET;
unset($parametros['exportar']);
$parametros['exportar'] = 'csv';

$urlCsv = 'visualiza_relatorio.php?' . http_build_query($parametros);

$periodo =
    ($inicio ? date('d/m/Y', strtotime($inicio)) : 'Início do histórico')
    . ' até ' .
    ($fim ? date('d/m/Y', strtotime($fim)) : 'fim do histórico');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($titulo) ?> | Gauge</title>

    <link rel="stylesheet" href="relatorios_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
<div class="pagina">

    <header class="header">
        <div class="left">
            <a href="gera_relatorio.php" title="Novo relatório">
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

        <section class="painel">

            <div class="titulo-painel">
                <h2><?= htmlspecialchars($titulo) ?></h2>

                <a class="botao-secundario" href="gera_relatorio.php">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>
            </div>

            <div class="filtros-aplicados">
                <strong>
                    <i class="fas fa-filter"></i>
                    Filtros aplicados:
                </strong>

                <span>Tipo: <?= htmlspecialchars($titulo) ?></span>
                <span>Período: <?= htmlspecialchars($periodo) ?></span>

                <span>
                    Trem:
                    <?= $idTrem ? 'ID ' . (int)$idTrem : 'Todos os trens' ?>
                </span>

                <span>
                    Status:
                    <?= htmlspecialchars($status ?: 'Todos') ?>
                </span>
            </div>

            <?php if ($erro): ?>

                <p class="mensagem">
                    <?= htmlspecialchars($erro) ?>
                </p>

            <?php elseif (!$linhas): ?>

                <p class="mensagem">
                    Nenhum dado encontrado para os filtros selecionados.
                </p>

            <?php else: ?>

                <div class="tabela-responsiva">
                    <table>
                        <thead>
                            <tr>
                                <?php foreach ($cols as $coluna): ?>
                                    <th><?= htmlspecialchars($coluna) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($linhas as $linha): ?>
                                <tr>
                                    <td><?= htmlspecialchars($linha['prefixo']) ?></td>
                                    <td><?= htmlspecialchars($linha['modelo']) ?></td>

                                    <?php if ($tipo === 'desempenho'): ?>

                                        <td>
                                            <?= number_format($linha['media'], 2, ',', '.') ?> km/h
                                        </td>

                                        <td>
                                            <span class="valor-status <?= cor($tipo, (float)$linha['maximo']) ?>">
                                                <span class="indicador-status <?= cor($tipo, (float)$linha['maximo']) ?>"></span>
                                                <?= number_format($linha['maximo'], 2, ',', '.') ?> km/h
                                            </span>
                                        </td>

                                    <?php elseif ($tipo === 'consumo'): ?>

                                        <td><?= number_format($linha['total'], 2, ',', '.') ?></td>
                                        <td><?= number_format($linha['media'], 2, ',', '.') ?></td>

                                    <?php elseif ($tipo === 'eficiencia'): ?>

                                        <td><?= (int)$linha['total'] ?></td>
                                        <td><?= (int)$linha['concluidas'] ?></td>
                                        <td><?= (int)$linha['canceladas'] ?></td>
                                        <td><?= (int)$linha['atrasadas'] ?></td>

                                    <?php else: ?>

                                        <td><?= (int)$linha['total'] ?></td>

                                        <td>
                                            <span class="valor-status atencao">
                                                <?= (int)$linha['atencoes'] ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="valor-status critico">
                                                <?= (int)$linha['criticos'] ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($linha['ultima_inspecao'] ?? 'Não informada') ?>
                                        </td>

                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="graficos">

                    <section class="grafico">
                        <h3>
                            <?= $tipo === 'desempenho'
                                ? 'Velocidade média por trem'
                                : ($tipo === 'consumo'
                                    ? 'Consumo total por trem'
                                    : ($tipo === 'eficiencia'
                                        ? 'Viagens por trem'
                                        : 'Alertas por trem')) ?>
                        </h3>

                        <div class="barras">
                            <?php foreach ($linhas as $linha):
                                $valor = principal($linha, $tipo);
                                $largura = $max1 ? ($valor / $max1) * 100 : 0;

                                $classeCor = $tipo === 'manutencao'
                                    ? ((int)$linha['criticos'] > 0
                                        ? 'critico'
                                        : ((int)$linha['atencoes'] > 0 ? 'atencao' : 'normal'))
                                    : cor($tipo, $valor);
                            ?>

                                <div class="barra-item">
                                    <span><?= htmlspecialchars($linha['prefixo']) ?></span>

                                    <div class="barra-fundo">
                                        <div
                                            class="barra-valor <?= $classeCor ?>"
                                            style="width: <?= $largura ?>%"
                                        ></div>
                                    </div>

                                    <strong><?= number_format($valor, 2, ',', '.') ?></strong>
                                </div>

                            <?php endforeach; ?>
                        </div>

                        <div class="legenda">
                            <span><i class="indicador-status normal"></i> Normal</span>
                            <span><i class="indicador-status atencao"></i> Atenção</span>
                            <span><i class="indicador-status critico"></i> Crítico</span>
                        </div>
                    </section>

                    <section class="grafico">
                        <h3>
                            <?= $tipo === 'desempenho'
                                ? 'Velocidade máxima por trem'
                                : ($tipo === 'consumo'
                                    ? 'Consumo médio por trem'
                                    : ($tipo === 'eficiencia'
                                        ? 'Viagens concluídas por trem'
                                        : 'Alertas críticos por trem')) ?>
                        </h3>

                        <div class="colunas">
                            <?php foreach ($linhas as $linha):
                                $valor = secundario($linha, $tipo);
                                $altura = $max2 ? ($valor / $max2) * 100 : 0;

                                $classeCor = $tipo === 'manutencao'
                                    ? ((int)$linha['criticos'] > 0 ? 'critico' : 'normal')
                                    : cor($tipo, $valor);
                            ?>

                                <div class="coluna-item">
                                    <strong><?= number_format($valor, 2, ',', '.') ?></strong>

                                    <div
                                        class="coluna <?= $classeCor ?>"
                                        style="height: <?= max(2, $altura) ?>%"
                                    ></div>

                                    <span><?= htmlspecialchars($linha['prefixo']) ?></span>
                                </div>

                            <?php endforeach; ?>
                        </div>

                        <div class="legenda">
                            <span><i class="indicador-status normal"></i> Normal</span>
                            <span><i class="indicador-status atencao"></i> Atenção</span>
                            <span><i class="indicador-status critico"></i> Crítico</span>
                        </div>
                    </section>

                </div>

            <?php endif; ?>

            <div class="acoes">
                <?php if (!$erro): ?>
                    <a class="botao" href="<?= htmlspecialchars($urlCsv) ?>">
                        <i class="fas fa-file-csv"></i>
                        Exportar CSV
                    </a>
                <?php endif; ?>

                <button
                    class="botao-secundario"
                    type="button"
                    onclick="window.print()"
                >
                    <i class="fas fa-print"></i>
                    Imprimir
                </button>
            </div>

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

