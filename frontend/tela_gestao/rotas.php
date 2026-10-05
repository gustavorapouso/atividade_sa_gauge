<?php

session_start();
require 'conexao.php';

$status = $_GET['status'] ?? '';
$id_trem = isset($_GET['id_trem']) ? (int) $_GET['id_trem'] : 0;
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$busca = $_GET['busca'] ?? '';

$condicoes = [];
$valores = [];
$tipos = '';

if ($status !== '') {
    $condicoes[] = 'rotas.status = ?';
    $valores[] = $status;
    $tipos .= 's';
}

if ($id_trem > 0) {
    $condicoes[] = 'rotas.id_trem = ?';
    $valores[] = $id_trem;
    $tipos .= 'i';
}

if ($data_inicio !== '' && $data_fim !== '') {
    $condicoes[] = 'rotas.data_viagem BETWEEN ? AND ?';
    $valores[] = $data_inicio;
    $valores[] = $data_fim;
    $tipos .= 'ss';
}

if ($busca !== '') {
    $condicoes[] = '(rotas.origem LIKE ? OR rotas.destino LIKE ?)';

    $termo = '%' . $busca . '%';

    $valores[] = $termo;
    $valores[] = $termo;
    $tipos .= 'ss';
}

$where = '';

if (!empty($condicoes)) {
    $where = 'WHERE ' . implode(' AND ', $condicoes);
}

$sql = "
    SELECT 
        rotas.*,
        trem.nome,
        trem.modelo,
        trem.tipo
    FROM rotas
    INNER JOIN trem ON trem.id_trem = rotas.id_trem
    $where
    ORDER BY rotas.data_viagem DESC, rotas.hora_partida
";

$stmt = $conn->prepare($sql);

if (!empty($valores)) {
    $stmt->bind_param($tipos, ...$valores);
}

$stmt->execute();

$resultado = $stmt->get_result();

$sql_trens = "SELECT id_trem, nome, modelo FROM trem ORDER BY nome";
$resultado_trens = $conn->query($sql_trens);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rotas - Ferrorama Gauge</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="rotas.css">

</head>

<body>

<header>

    <a href="dashboard.php">
        <i class="fas fa-bars"></i>
    </a>

    <img src="../assets/logo_gauge_menor.png" alt="Gauge">

    <i class="fas fa-search"></i>

</header>

<main>

    <h1>Rotas</h1>

    <a href="formulario_rota.php" class="botao botao-nova">
        <i class="fas fa-plus"></i>
        Nova rota
    </a>

    <div class="filtros">

        <form method="GET">

            <div class="campo">

                <label>Status</label>

                <select name="status">

                    <option value="">Todos</option>

                    <option value="programada"
                        <?= $status === 'programada' ? 'selected' : '' ?>>
                        Programada
                    </option>

                    <option value="em andamento"
                        <?= $status === 'em andamento' ? 'selected' : '' ?>>
                        Em andamento
                    </option>

                    <option value="concluída"
                        <?= $status === 'concluída' ? 'selected' : '' ?>>
                        Concluída
                    </option>

                    <option value="cancelada"
                        <?= $status === 'cancelada' ? 'selected' : '' ?>>
                        Cancelada
                    </option>

                </select>

            </div>

            <div class="campo">

                <label>Trem</label>

                <select name="id_trem">

                    <option value="0">Todos</option>

                    <?php while ($trem = $resultado_trens->fetch_assoc()): ?>

                        <option
                            value="<?= $trem['id_trem'] ?>"
                            <?= $id_trem == $trem['id_trem'] ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars($trem['nome']) ?>
                            -
                            <?= htmlspecialchars($trem['modelo']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <div class="campo">

                <label>Data inicial</label>

                <input
                    type="date"
                    name="data_inicio"
                    value="<?= htmlspecialchars($data_inicio) ?>"
                >

            </div>

            <div class="campo">

                <label>Data final</label>

                <input
                    type="date"
                    name="data_fim"
                    value="<?= htmlspecialchars($data_fim) ?>"
                >

            </div>

            <div class="campo">

                <label>Origem ou destino</label>

                <input
                    type="text"
                    name="busca"
                    placeholder="Ex: Joinville"
                    value="<?= htmlspecialchars($busca) ?>"
                >

            </div>

            <button type="submit">
                <i class="fas fa-filter"></i>
                Filtrar
            </button>

        </form>

    </div>

    <table>

        <thead>

            <tr>
                <th>Trem</th>
                <th>Modelo</th>
                <th>Origem</th>
                <th>Destino</th>
                <th>Data</th>
                <th>Saída</th>
                <th>Chegada</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>

        </thead>

        <tbody>

        <?php if ($resultado->num_rows > 0): ?>

            <?php while ($rota = $resultado->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($rota['nome']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($rota['modelo']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($rota['origem']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($rota['destino']) ?>
                    </td>

                    <td>
                        <?= date('d/m/Y', strtotime($rota['data_viagem'])) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($rota['hora_partida']) ?>
                    </td>

                    <td>
                        <?= $rota['hora_chegada']
                            ? htmlspecialchars($rota['hora_chegada'])
                            : '-' ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($rota['status']) ?>
                    </td>

                    <td class="acoes">

                        <a
                            href="formulario_rota.php?id=<?= $rota['id_rota'] ?>"
                            class="editar"
                        >
                            <i class="fas fa-pen"></i>
                        </a>

                        <a
                            href="excluir_rota.php?id=<?= $rota['id_rota'] ?>"
                            class="excluir"
                            onclick="return confirm('Deseja realmente excluir esta rota?')"
                        >
                            <i class="fas fa-trash"></i>
                        </a>

                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="9">Nenhuma rota encontrada.</td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

</main>

</body>

</html>