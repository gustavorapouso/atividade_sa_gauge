<?php

session_start();

require '../../data_base/conexao.php';


// ===============================
// FILTROS
// ===============================

$status = $_GET['status'] ?? '';

$id_trem = isset($_GET['id_trem'])
    ? (int) $_GET['id_trem']
    : 0;

$data_inicio = $_GET['data_inicio'] ?? '';

$data_fim = $_GET['data_fim'] ?? '';

$busca = $_GET['busca'] ?? '';


// ===============================
// MONTAGEM DOS FILTROS
// ===============================

$condicoes = [];

$valores = [];

$tipos = '';


// Filtro por status
if ($status !== '') {

    $condicoes[] = 'rotas.status = ?';

    $valores[] = $status;

    $tipos .= 's';
}


// Filtro por trem
if ($id_trem > 0) {

    $condicoes[] = 'rotas.id_trem = ?';

    $valores[] = $id_trem;

    $tipos .= 'i';
}


// Filtro por período
if ($data_inicio !== '' && $data_fim !== '') {

    $condicoes[] = 'rotas.data_viagem BETWEEN ? AND ?';

    $valores[] = $data_inicio;

    $valores[] = $data_fim;

    $tipos .= 'ss';
}


// Filtro por origem ou destino
if ($busca !== '') {

    $condicoes[] = '(rotas.origem LIKE ? OR rotas.destino LIKE ?)';

    $termo = '%' . $busca . '%';

    $valores[] = $termo;

    $valores[] = $termo;

    $tipos .= 'ss';
}


// ===============================
// WHERE
// ===============================

$where = '';

if (!empty($condicoes)) {

    $where = 'WHERE ' . implode(' AND ', $condicoes);
}


// ===============================
// CONSULTA DAS ROTAS
// ===============================

$sql = "
    SELECT
        rotas.*,
        trem.nome,
        trem.modelo,
        trem.tipo
    FROM rotas
    INNER JOIN trem
        ON trem.id_trem = rotas.id_trem
    $where
    ORDER BY
        rotas.data_viagem DESC,
        rotas.hora_partida
";


// Prepara a consulta
$stmt = $conexao->prepare($sql);


// Verifica se conseguiu preparar
if (!$stmt) {

    die('Erro na consulta: ' . $conexao->error);
}


// Adiciona os valores dos filtros
if (!empty($valores)) {

    $stmt->bind_param($tipos, ...$valores);
}


// Executa
$stmt->execute();


// Pega os resultados
$resultado = $stmt->get_result();


// ===============================
// BUSCAR TRENS PARA O FILTRO
// ===============================

$sql_trens = "
    SELECT
        id_trem,
        nome,
        modelo
    FROM trem
    ORDER BY nome
";

$resultado_trens = $conexao->query($sql_trens);


// Verifica erro
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

    <title>Rotas - Ferrorama Gauge</title>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >


    <!-- CSS da página -->

    <link
        rel="stylesheet"
        href="rotas.css"
    >

</head>


<body>


<!-- ===============================
     NAVBAR
=============================== -->

<header>

    <a href="dashboard.php">

        <i class="fas fa-bars"></i>

    </a>


    <img
        src="../assets/logo_gauge_menor.png"
        alt="Gauge"
    >


    <i class="fas fa-search"></i>

</header>


<!-- ===============================
     CONTEÚDO
=============================== -->

<main>


    <h1>Rotas</h1>


    <!-- BOTÃO NOVA ROTA -->

    <a
        href="formulario_rota.php"
        class="botao botao-nova"
    >

        <i class="fas fa-plus"></i>

        Nova rota

    </a>


    <!-- ===============================
         FILTROS
    ================================ -->

    <div class="filtros">


        <form method="GET">


            <!-- STATUS -->

            <div class="campo">

                <label for="status">
                    Status
                </label>


                <select
                    name="status"
                    id="status"
                >

                    <option value="">
                        Todos
                    </option>


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

                <label for="id_trem">
                    Trem
                </label>


                <select
                    name="id_trem"
                    id="id_trem"
                >

                    <option value="0">
                        Todos
                    </option>


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


            <!-- DATA INICIAL -->

            <div class="campo">

                <label for="data_inicio">
                    Data inicial
                </label>


                <input
                    type="date"
                    name="data_inicio"
                    id="data_inicio"
                    value="<?= htmlspecialchars($data_inicio) ?>"
                >

            </div>


            <!-- DATA FINAL -->

            <div class="campo">

                <label for="data_fim">
                    Data final
                </label>


                <input
                    type="date"
                    name="data_fim"
                    id="data_fim"
                    value="<?= htmlspecialchars($data_fim) ?>"
                >

            </div>


            <!-- BUSCA -->

            <div class="campo">

                <label for="busca">
                    Origem ou destino
                </label>


                <input
                    type="text"
                    name="busca"
                    id="busca"
                    placeholder="Ex: Joinville"
                    value="<?= htmlspecialchars($busca) ?>"
                >

            </div>


            <!-- BOTÃO FILTRAR -->

            <button type="submit">

                <i class="fas fa-filter"></i>

                Filtrar

            </button>


        </form>

    </div>


    <!-- ===============================
         TABELA DE ROTAS
    ================================ -->

    <table>


        <thead>

            <tr>

                <th>
                    Trem
                </th>

                <th>
                    Modelo
                </th>

                <th>
                    Origem
                </th>

                <th>
                    Destino
                </th>

                <th>
                    Data
                </th>

                <th>
                    Saída
                </th>

                <th>
                    Chegada
                </th>

                <th>
                    Status
                </th>

                <th>
                    Ações
                </th>

            </tr>

        </thead>


        <tbody>


        <?php if ($resultado->num_rows > 0): ?>


            <?php while ($rota = $resultado->fetch_assoc()): ?>


                <tr>


                    <!-- TREM -->

                    <td>

                        <?= htmlspecialchars($rota['nome']) ?>

                    </td>


                    <!-- MODELO -->

                    <td>

                        <?= htmlspecialchars($rota['modelo']) ?>

                    </td>


                    <!-- ORIGEM -->

                    <td>

                        <?= htmlspecialchars($rota['origem']) ?>

                    </td>


                    <!-- DESTINO -->

                    <td>

                        <?= htmlspecialchars($rota['destino']) ?>

                    </td>


                    <!-- DATA -->

                    <td>

                        <?= date(
                            'd/m/Y',
                            strtotime($rota['data_viagem'])
                        ) ?>

                    </td>


                    <!-- HORA DE PARTIDA -->

                    <td>

                        <?= htmlspecialchars(
                            $rota['hora_partida']
                        ) ?>

                    </td>


                    <!-- HORA DE CHEGADA -->

                    <td>

                        <?php if (!empty($rota['hora_chegada'])): ?>

                            <?= htmlspecialchars(
                                $rota['hora_chegada']
                            ) ?>

                        <?php else: ?>

                            -

                        <?php endif; ?>

                    </td>


                    <!-- STATUS -->

                    <td>

                        <?= htmlspecialchars(
                            $rota['status']
                        ) ?>

                    </td>


                    <!-- AÇÕES -->

                    <td class="acoes">


                        <!-- EDITAR -->

                        <a
                            href="formulario_rota.php?id=<?= $rota['id_rota'] ?>"
                            class="editar"
                            title="Editar"
                        >

                            <i class="fas fa-pen"></i>

                        </a>


                        <!-- EXCLUIR -->

                        <a
                            href="excluir_rota.php?id=<?= $rota['id_rota'] ?>"
                            class="excluir"
                            title="Excluir"
                            onclick="return confirm('Deseja realmente excluir esta rota?')"
                        >

                            <i class="fas fa-trash"></i>

                        </a>


                    </td>


                </tr>


            <?php endwhile; ?>


        <?php else: ?>


            <tr>

                <td colspan="9">

                    Nenhuma rota encontrada.

                </td>

            </tr>


        <?php endif; ?>


        </tbody>


    </table>


</main>


</body>

</html>