
<?php

session_start();

require '../../data_base/conexao.php';

// =====================================
// FILTROS
// =====================================

$status = $_GET['status'] ?? '';

$id_trem = isset($_GET['id_trem'])
    ? (int) $_GET['id_trem']
    : 0;

$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$busca = $_GET['busca'] ?? '';

// =====================================
// CONDIÇÕES DOS FILTROS
// =====================================

$condicoes = [];
$valores = [];
$tipos = '';

// Filtro por status
if ($status !== '') {
    $condicoes[] = 'viagem.status_trem = ?';
    $valores[] = $status;
    $tipos .= 's';
}

// Filtro por trem
if ($id_trem > 0) {
    $condicoes[] = 'viagem.FK_id_trem = ?';
    $valores[] = $id_trem;
    $tipos .= 'i';
}

// Filtro por período
if ($data_inicio !== '' && $data_fim !== '') {
    $condicoes[] = 'DATE(viagem.data_hora_saida) BETWEEN ? AND ?';
    $valores[] = $data_inicio;
    $valores[] = $data_fim;
    $tipos .= 'ss';
}

// Filtro por origem ou destino
if ($busca !== '') {
    $condicoes[] = '(viagem.origem LIKE ? OR viagem.destino LIKE ?)';

    $termo = '%' . $busca . '%';

    $valores[] = $termo;
    $valores[] = $termo;
    $tipos .= 'ss';
}

// =====================================
// WHERE
// =====================================

$where = '';

if (!empty($condicoes)) {
    $where = 'WHERE ' . implode(' AND ', $condicoes);
}

// =====================================
// CONSULTA DAS VIAGENS
// =====================================

$sql = "
    SELECT
        viagem.*,
        trem.prefixo AS nome_trem,
        trem.modelo
    FROM viagem
    INNER JOIN trem
        ON trem.id_trem = viagem.FK_id_trem
    $where
    ORDER BY viagem.data_hora_saida DESC
";

$stmt = $conexao->prepare($sql);

if (!$stmt) {
    die('Erro na consulta: ' . $conexao->error);
}

if (!empty($valores)) {
    $stmt->bind_param($tipos, ...$valores);
}

if (!$stmt->execute()) {
    die('Erro ao executar a consulta: ' . $stmt->error);
}

$resultado = $stmt->get_result();

// =====================================
// BUSCAR TRENS PARA O FILTRO
// =====================================

$sql_trens = "
    SELECT
        id_trem,
        prefixo,
        modelo
    FROM trem
    ORDER BY prefixo
";

$resultado_trens = $conexao->query($sql_trens);

if (!$resultado_trens) {
    die('Erro ao buscar os trens: ' . $conexao->error);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Viagens - Ferrorama Gauge</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >

    <!-- CSS -->
    <link rel="stylesheet" href="rotas.css">
</head>

<body>

    <!-- NAVBAR -->
    <header class="header">
        <div class="left">
            <i class="fas fa-bars" title="Menu"></i>
            <i class="fas fa-search" title="Pesquisar"></i>
        </div>

        <div class="logo">
            <img src="../assets/logo_gauge_menor.png" alt="Gauge Logo">
        </div>
    </header>

    <!-- CONTEÚDO -->
    <main>

        <h1>Viagens</h1>

        <!-- NOVA VIAGEM -->
        <a href="formulario_rota.php" class="botao botao-nova">
            <i class="fas fa-plus"></i>
            Nova viagem
        </a>

        <!-- FILTROS -->
        <div class="filtros">

            <form method="GET">

                <!-- STATUS -->
                <div class="campo">
                    <label for="status">Status</label>

                    <select name="status" id="status">
                        <option value="">Todos</option>

                        <option
                            value="programada"
                            <?= $status === 'programada' ? 'selected' : '' ?>
                        >
                            Programada
                        </option>

                        <option
                            value="em andamento"
                            <?= $status === 'em andamento' ? 'selected' : '' ?>
                        >
                            Em andamento
                        </option>

                        <option
                            value="concluída"
                            <?= $status === 'concluída' ? 'selected' : '' ?>
                        >
                            Concluída
                        </option>

                        <option
                            value="cancelada"
                            <?= $status === 'cancelada' ? 'selected' : '' ?>
                        >
                            Cancelada
                        </option>
                    </select>
                </div>

                <!-- TREM -->
                <div class="campo">
                    <label for="id_trem">Trem</label>

                    <select name="id_trem" id="id_trem">
                        <option value="0">Todos</option>

                        <?php while ($trem = $resultado_trens->fetch_assoc()): ?>
                            <option
                                value="<?= (int) $trem['id_trem'] ?>"
                                <?= $id_trem == $trem['id_trem'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($trem['prefixo']) ?>
                                -
                                <?= htmlspecialchars($trem['modelo']) ?>
                            </option>
                        <?php endwhile; ?>

                    </select>
                </div>

                <!-- DATA INICIAL -->
                <div class="campo">
                    <label for="data_inicio">Data inicial</label>

                    <input
                        type="date"
                        name="data_inicio"
                        id="data_inicio"
                        value="<?= htmlspecialchars($data_inicio) ?>"
                    >
                </div>

                <!-- DATA FINAL -->
                <div class="campo">
                    <label for="data_fim">Data final</label>

                    <input
                        type="date"
                        name="data_fim"
                        id="data_fim"
                        value="<?= htmlspecialchars($data_fim) ?>"
                    >
                </div>

                <!-- ORIGEM / DESTINO -->
                <div class="campo">
                    <label for="busca">Origem ou destino</label>

                    <input
                        type="text"
                        name="busca"
                        id="busca"
                        placeholder="Ex: Joinville"
                        value="<?= htmlspecialchars($busca) ?>"
                    >
                </div>

                <!-- FILTRAR -->
                <button type="submit">
                    <i class="fas fa-filter"></i>
                    Filtrar
                </button>

            </form>
        </div>

        <!-- TABELA DE VIAGENS -->
        <table>

            <thead>
                <tr>
                    <th>Trem</th>
                    <th>Modelo</th>
                    <th>Origem</th>
                    <th>Destino</th>
                    <th>Saída</th>
                    <th>Chegada</th>
                    <th>Status</th>
                    <th>Velocidade</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($resultado->num_rows > 0): ?>

                    <?php while ($viagem = $resultado->fetch_assoc()): ?>

                        <tr>

                            <!-- TREM -->
                            <td>
                                <?= htmlspecialchars($viagem['nome_trem']) ?>
                            </td>

                            <!-- MODELO -->
                            <td>
                                <?= htmlspecialchars($viagem['modelo']) ?>
                            </td>

                            <!-- ORIGEM -->
                            <td>
                                <?= htmlspecialchars($viagem['origem']) ?>
                            </td>

                            <!-- DESTINO -->
                            <td>
                                <?= htmlspecialchars($viagem['destino']) ?>
                            </td>

                            <!-- SAÍDA -->
                            <td>
                                <?= !empty($viagem['data_hora_saida'])
                                    ? date(
                                        'd/m/Y H:i',
                                        strtotime($viagem['data_hora_saida'])
                                    )
                                    : '-' ?>
                            </td>

                            <!-- CHEGADA -->
                            <td>
                                <?= !empty($viagem['data_hora_chegada'])
                                    ? date(
                                        'd/m/Y H:i',
                                        strtotime($viagem['data_hora_chegada'])
                                    )
                                    : '-' ?>
                            </td>

                            <!-- STATUS -->
                            <td>
                                <?= htmlspecialchars($viagem['status_trem']) ?>
                            </td>

                            <!-- VELOCIDADE -->
                            <td>
                                <?= htmlspecialchars((string) $viagem['velocidade_km_h']) ?>
                                km/h
                            </td>

                            <!-- AÇÕES -->
                            <td class="acoes">

                                <!-- EDITAR -->
                                <a
                                    href="formulario_viagem.php?id=<?= (int) $viagem['id_viagem'] ?>"
                                    class="editar"
                                    title="Editar"
                                >
                                    <i class="fas fa-pen"></i>
                                </a>

                                <!-- EXCLUIR -->
                                <a
                                    href="excluir_viagem.php?id=<?= (int) $viagem['id_viagem'] ?>"
                                    class="excluir"
                                    title="Excluir"
                                    onclick="return confirm('Deseja realmente excluir esta viagem?')"
                                >
                                    <i class="fas fa-trash"></i>
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="9">
                            Nenhuma viagem encontrada.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>

    </main>

    <!-- RODAPÉ -->
    <footer class="footer-gauge">

        <div class="footer-container">

            <div class="footer-bloco bloco-esquerda">
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

            <div class="footer-bloco bloco-direita">
                <span class="footer-label">Precisa de Suporte?</span>

                <a
                    href="mailto:contato@gauge.com.br"
                    class="footer-link link-sublinhado"
                >
                    contato@gauge.com.br
                </a>
            </div>

        </div>

    </footer>

</body>
</html>