<?php
// Exibe o relatório escolhido em gerar.php (tabela + gráficos),
// com opções de exportar em CSV e imprimir.

require __DIR__ . '/../../includes/proteger.php';

$titulos = [
    'desempenho' => 'Desempenho dos trens',
    'consumo'    => 'Consumo de energia',
    'eficiencia' => 'Eficiência operacional',
    'manutencao' => 'Manutenção',
];

$tipo   = $_GET['tipo'] ?? 'desempenho';
$inicio = trim($_GET['data_inicio'] ?? '');
$fim    = trim($_GET['data_fim'] ?? '');
$idTrem = filter_input(INPUT_GET, 'id_trem', FILTER_VALIDATE_INT) ?: 0;
$status = trim($_GET['status'] ?? '');

if (!isset($titulos[$tipo])) {
    exit('Tipo de relatório inválido. <a href="gerar.php">Voltar</a>');
}

// Aceita data vazia ou no formato AAAA-MM-DD
function dataValida(string $valor): bool
{
    if ($valor === '') {
        return true;
    }

    $data = DateTime::createFromFormat('!Y-m-d', $valor);

    return $data && $data->format('Y-m-d') === $valor;
}

if (!dataValida($inicio) || !dataValida($fim) || ($inicio !== '' && $fim !== '' && $inicio > $fim)) {
    exit('Período inválido. <a href="gerar.php">Voltar</a>');
}

$titulo = $titulos[$tipo];
$linhas = [];
$erro = '';
$cond = [];
$vals = [];
$bind = '';
$colunas = [];

// Adiciona as condições de período (apenas as datas preenchidas)
function filtrarPeriodo(array &$cond, array &$vals, string &$bind, string $campo, string $inicio, string $fim): void
{
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

// Executa uma consulta preparada e devolve todas as linhas
function consultar(mysqli $conexao, string $sql, string $bind, array $vals): array
{
    $stmt = $conexao->prepare($sql);

    if (!$stmt) {
        throw new Exception($conexao->error);
    }

    if ($bind !== '') {
        $stmt->bind_param($bind, ...$vals);
    }

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $dados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $dados;
}

try {
    filtrarPeriodo($cond, $vals, $bind, match ($tipo) {
        'manutencao' => 'a.data_hora',
        'eficiencia' => 'v.data_hora_saida',
        default      => 'l.data_hora',
    }, $inicio, $fim);

    if ($idTrem > 0) {
        $cond[] = 't.id_trem = ?';
        $vals[] = $idTrem;
        $bind .= 'i';
    }

    // Desempenho e consumo (leituras dos sensores)
    if ($tipo === 'desempenho' || $tipo === 'consumo') {
        if ($tipo === 'desempenho') {
            $cond[] = "LOWER(s.tipo) LIKE '%velocidade%'";
            $medidas = 'ROUND(AVG(l.valor), 2) AS media, MAX(l.valor) AS maximo';
            $colunas = ['Trem', 'Modelo', 'Velocidade média', 'Velocidade máxima'];
        } else {
            $cond[] = "(LOWER(s.tipo) LIKE '%consumo%' OR LOWER(s.tipo) LIKE '%energia%')";
            $medidas = 'ROUND(SUM(l.valor), 2) AS total, ROUND(AVG(l.valor), 2) AS media';
            $colunas = ['Trem', 'Modelo', 'Consumo total', 'Consumo médio'];
        }

        if ($status !== '') {
            $erro = 'O filtro de status não se aplica aos relatórios de desempenho e consumo. Selecione Todos.';
        } else {
            $linhas = consultar($conexao, "
                SELECT t.id_trem, t.prefixo, t.modelo, $medidas
                FROM leituras l
                INNER JOIN sensores s ON s.id_sensores = l.id_sensor
                INNER JOIN trem t ON t.id_trem = s.FK_id_trem
                WHERE " . implode(' AND ', $cond) . "
                GROUP BY t.id_trem, t.prefixo, t.modelo
                ORDER BY t.prefixo
            ", $bind, $vals);
        }
    }

    // Manutenção (alertas)
    if ($tipo === 'manutencao') {
        $colunas = ['Trem', 'Modelo', 'Total de alertas', 'Atenção', 'Críticos', 'Última inspeção'];

        if (in_array($status, ['atencao', 'critico'], true)) {
            $cond[] = 'LOWER(a.severidade) = ?';
            $vals[] = $status;
            $bind .= 's';
        } elseif ($status !== '') {
            $erro = 'No relatório de manutenção, o status deve ser Todos, Atenção ou Crítico.';
        }

        if ($erro === '') {
            $linhas = consultar($conexao, "
                SELECT
                    t.id_trem, t.prefixo, t.modelo,
                    COUNT(a.id_alerta) AS total,
                    SUM(CASE WHEN LOWER(a.severidade) = 'critico' THEN 1 ELSE 0 END) AS criticos,
                    SUM(CASE WHEN LOWER(a.severidade) = 'atencao' THEN 1 ELSE 0 END) AS atencoes,
                    MAX(t.ultima_inspecao) AS ultima_inspecao
                FROM alertas a
                INNER JOIN leituras l ON l.id_leitura = a.id_leitura
                INNER JOIN sensores s ON s.id_sensores = l.id_sensor
                INNER JOIN trem t ON t.id_trem = s.FK_id_trem
                " . ($cond ? 'WHERE ' . implode(' AND ', $cond) : '') . "
                GROUP BY t.id_trem, t.prefixo, t.modelo
                ORDER BY total DESC, t.prefixo
            ", $bind, $vals);
        }
    }

    // Eficiência operacional (viagens)
    if ($tipo === 'eficiencia') {
        $colunas = ['Trem', 'Modelo', 'Total de viagens', 'Concluídas', 'Canceladas', 'Atrasadas'];

        if ($status !== '') {
            $cond[] = 'LOWER(v.status_trem) = ?';
            $vals[] = mb_strtolower($status, 'UTF-8');
            $bind .= 's';
        }

        $linhas = consultar($conexao, "
            SELECT
                t.id_trem, t.prefixo, t.modelo,
                COUNT(v.id_viagem) AS total,
                SUM(CASE WHEN LOWER(v.status_trem) LIKE '%conclu%' THEN 1 ELSE 0 END) AS concluidas,
                SUM(CASE WHEN LOWER(v.status_trem) LIKE '%cancel%' THEN 1 ELSE 0 END) AS canceladas,
                SUM(CASE WHEN LOWER(v.status_trem) LIKE '%atras%' THEN 1 ELSE 0 END) AS atrasadas
            FROM viagem v
            INNER JOIN trem t ON t.id_trem = v.FK_id_trem
            " . ($cond ? 'WHERE ' . implode(' AND ', $cond) : '') . "
            GROUP BY t.id_trem, t.prefixo, t.modelo
            ORDER BY t.prefixo
        ", $bind, $vals);
    }
} catch (Throwable $e) {
    $erro = 'Erro ao gerar relatório: ' . $e->getMessage();
}

// Valores de cada linha mostrados na tabela/CSV (sem trem e modelo)
function valoresDaLinha(array $linha, string $tipo): array
{
    return match ($tipo) {
        'desempenho' => [$linha['media'], $linha['maximo']],
        'consumo'    => [$linha['total'], $linha['media']],
        'eficiencia' => [$linha['total'], $linha['concluidas'], $linha['canceladas'], $linha['atrasadas']],
        default      => [$linha['total'], $linha['atencoes'], $linha['criticos'], $linha['ultima_inspecao'] ?? 'Não informada'],
    };
}

// Valor usado no primeiro gráfico (barras)
function valorPrincipal(array $linha, string $tipo): float
{
    return (float) ($tipo === 'desempenho' ? $linha['media'] : $linha['total']);
}

// Valor usado no segundo gráfico (colunas)
function valorSecundario(array $linha, string $tipo): float
{
    return (float) match ($tipo) {
        'desempenho' => $linha['maximo'],
        'consumo'    => $linha['media'],
        'eficiencia' => $linha['concluidas'],
        default      => $linha['criticos'],
    };
}

// Classe de cor (normal, atencao, critico) de acordo com o valor
function corDoValor(string $tipo, float $valor): string
{
    if ($tipo === 'desempenho') {
        return $valor > 120 ? 'critico' : ($valor > 100 ? 'atencao' : 'normal');
    }

    if ($tipo === 'consumo') {
        return $valor > 9 ? 'critico' : ($valor > 8 ? 'atencao' : 'normal');
    }

    return 'normal';
}

function corDaBarra(array $linha, string $tipo, float $valor): string
{
    if ($tipo !== 'manutencao') {
        return corDoValor($tipo, $valor);
    }

    if ((int) $linha['criticos'] > 0) {
        return 'critico';
    }

    return (int) $linha['atencoes'] > 0 ? 'atencao' : 'normal';
}

function numero($valor): string
{
    return number_format((float) $valor, 2, ',', '.');
}

// Exportação CSV
if (($_GET['exportar'] ?? '') === 'csv' && $erro === '') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="relatorio_gauge.csv"');

    $arquivo = fopen('php://output', 'w');
    fwrite($arquivo, "\xEF\xBB\xBF"); // BOM para o Excel reconhecer os acentos
    fputcsv($arquivo, $colunas, ';', '"', '');

    foreach ($linhas as $linha) {
        fputcsv($arquivo, array_merge([$linha['prefixo'], $linha['modelo']], valoresDaLinha($linha, $tipo)), ';', '"', '');
    }

    fclose($arquivo);
    exit;
}

$maiorPrincipal = 0;
$maiorSecundario = 0;

foreach ($linhas as $linha) {
    $maiorPrincipal = max($maiorPrincipal, valorPrincipal($linha, $tipo));
    $maiorSecundario = max($maiorSecundario, valorSecundario($linha, $tipo));
}

$titulosGraficos = [
    'desempenho' => ['Velocidade média por trem', 'Velocidade máxima por trem'],
    'consumo'    => ['Consumo total por trem', 'Consumo médio por trem'],
    'eficiencia' => ['Viagens por trem', 'Viagens concluídas por trem'],
    'manutencao' => ['Alertas por trem', 'Alertas críticos por trem'],
];

$urlCsv = 'visualizar.php?' . http_build_query(array_merge($_GET, ['exportar' => 'csv']));

$periodo = ($inicio ? formatarData($inicio, 'd/m/Y') : 'Início do histórico')
    . ' até '
    . ($fim ? formatarData($fim, 'd/m/Y') : 'fim do histórico');

$estilos   = ['relatorios'];
$menuAtivo = 'paginas/relatorios/gerar.php';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="cabecalho-pagina">
    <h1 class="titulo-pagina"><?= h($titulo) ?></h1>

    <a class="botao-secundario" href="gerar.php">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<div class="filtros-aplicados">
    <strong><i class="fas fa-filter"></i> Filtros aplicados:</strong>
    <span>Tipo: <?= h($titulo) ?></span>
    <span>Período: <?= h($periodo) ?></span>
    <span>Trem: <?= $idTrem ? 'ID ' . $idTrem : 'Todos os trens' ?></span>
    <span>Status: <?= h($status ?: 'Todos') ?></span>
</div>

<?php if ($erro !== ''): ?>

    <p class="mensagem erro"><?= h($erro) ?></p>

<?php elseif (!$linhas): ?>

    <p class="mensagem">Nenhum dado encontrado para os filtros selecionados.</p>

<?php else: ?>

    <div class="tabela-responsiva">
        <table>
            <thead>
                <tr>
                    <?php foreach ($colunas as $coluna): ?>
                        <th><?= h($coluna) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($linhas as $linha): ?>
                    <tr>
                        <td><?= h($linha['prefixo']) ?></td>
                        <td><?= h($linha['modelo']) ?></td>

                        <?php if ($tipo === 'desempenho'): ?>
                            <?php $cor = corDoValor($tipo, (float) $linha['maximo']); ?>
                            <td><?= numero($linha['media']) ?> km/h</td>
                            <td>
                                <span class="valor-status">
                                    <span class="indicador <?= $cor ?>"></span>
                                    <?= numero($linha['maximo']) ?> km/h
                                </span>
                            </td>

                        <?php elseif ($tipo === 'consumo'): ?>
                            <td><?= numero($linha['total']) ?></td>
                            <td><?= numero($linha['media']) ?></td>

                        <?php elseif ($tipo === 'eficiencia'): ?>
                            <td><?= (int) $linha['total'] ?></td>
                            <td><?= (int) $linha['concluidas'] ?></td>
                            <td><?= (int) $linha['canceladas'] ?></td>
                            <td><?= (int) $linha['atrasadas'] ?></td>

                        <?php else: ?>
                            <td><?= (int) $linha['total'] ?></td>
                            <td><span class="status status-atencao"><?= (int) $linha['atencoes'] ?></span></td>
                            <td><span class="status status-critico"><?= (int) $linha['criticos'] ?></span></td>
                            <td><?= h($linha['ultima_inspecao'] ?? 'Não informada') ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="graficos">

        <section class="grafico">
            <h3><?= h($titulosGraficos[$tipo][0]) ?></h3>

            <div class="barras">
                <?php foreach ($linhas as $linha): ?>
                    <?php
                    $valor = valorPrincipal($linha, $tipo);
                    $largura = $maiorPrincipal ? ($valor / $maiorPrincipal) * 100 : 0;
                    ?>
                    <div class="barra-item">
                        <span><?= h($linha['prefixo']) ?></span>
                        <div class="barra-fundo">
                            <div class="barra-valor <?= corDaBarra($linha, $tipo, $valor) ?>" style="width: <?= $largura ?>%"></div>
                        </div>
                        <strong><?= numero($valor) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="grafico">
            <h3><?= h($titulosGraficos[$tipo][1]) ?></h3>

            <div class="colunas">
                <?php foreach ($linhas as $linha): ?>
                    <?php
                    $valor = valorSecundario($linha, $tipo);
                    $altura = $maiorSecundario ? ($valor / $maiorSecundario) * 100 : 0;
                    $cor = $tipo === 'manutencao'
                        ? ((int) $linha['criticos'] > 0 ? 'critico' : 'normal')
                        : corDoValor($tipo, $valor);
                    ?>
                    <div class="coluna-item">
                        <strong><?= numero($valor) ?></strong>
                        <div class="coluna <?= $cor ?>" style="height: <?= max(2, $altura) ?>%"></div>
                        <span><?= h($linha['prefixo']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </div>

    <div class="legenda">
        <span><i class="indicador normal"></i> Normal</span>
        <span><i class="indicador atencao"></i> Atenção</span>
        <span><i class="indicador critico"></i> Crítico</span>
    </div>

<?php endif; ?>

<div class="acoes">
    <?php if ($erro === ''): ?>
        <a class="botao" href="<?= h($urlCsv) ?>">
            <i class="fas fa-file-csv"></i> Exportar CSV
        </a>
    <?php endif; ?>

    <button class="botao-secundario" type="button" onclick="window.print()">
        <i class="fas fa-print"></i> Imprimir
    </button>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
