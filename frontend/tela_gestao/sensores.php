<?php

require '../../data_base/conexao.php';

$tipo = $_GET['tipo'] ?? '';

$sql = "SELECT 
            sensores.*,
            trem.nome AS nome_trem,
            trem.modelo AS modelo_trem
        FROM sensores
        INNER JOIN trem 
            ON trem.id_trem = sensores.FK_id_trem";

if ($tipo != '') {
    $sql .= " WHERE sensores.tipo = ?";
}

$sql .= " ORDER BY trem.nome, sensores.codigo";

if ($tipo != '') {

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $tipo);
    $stmt->execute();

    $sensores = $stmt->get_result();

} else {

    $sensores = $conexao->query($sql);
}

$tipos = $conexao->query(
    "SELECT DISTINCT tipo 
     FROM sensores 
     ORDER BY tipo"
);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sensores - Ferrorama Gauge</title>

    <link rel="stylesheet" href="sensores_style.css">

</head>

<body>

<header>

    <div class="logo">
        <img src="../assets/logo_gauge_menor.png" alt="Gauge">
    </div>

</header>


<main class="fluxo-conteudo">

    <div class="tela-container">

        <h1 class="titulo-tela">Sensores</h1>

        <div class="conteudo-flex">

            <div class="painel-formulario">

                <h2>Filtros</h2>

                <form method="GET">

                    <label for="tipo">
                        Tipo de sensor
                    </label>

                    <select 
                        name="tipo" 
                        id="tipo"
                        class="select-filtro"
                    >

                        <option value="">
                            Todos os tipos
                        </option>

                        <?php while ($tipo_sensor = $tipos->fetch_assoc()): ?>

                            <option 
                                value="<?= htmlspecialchars($tipo_sensor['tipo']) ?>"
                                <?= $tipo == $tipo_sensor['tipo'] ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars($tipo_sensor['tipo']) ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                    <button type="submit" class="btn-salvar">
                        Filtrar
                    </button>

                </form>

                <br>

                <a href="formulario_sensor.php" class="btn-salvar">
                    Cadastrar Sensor
                </a>

            </div>


            <div class="painel-formulario">

                <h2>Lista de Sensores</h2>

                <table class="tabela-sensores">

                    <thead>

                        <tr>

                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Trem</th>
                            <th>Localização</th>
                            <th>Segmento</th>
                            <th>Última leitura</th>
                            <th>Ações</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php while ($sensor = $sensores->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($sensor['codigo']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($sensor['tipo']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($sensor['nome_trem']) ?>
                                    -
                                    <?= htmlspecialchars($sensor['modelo_trem']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($sensor['estacao_sensores'] ?? '') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($sensor['segmento'] ?? '') ?>
                                </td>

                                <td>

                                    <?php
                                    $status = strtolower(
                                        $sensor['ultima_leitura'] ?? ''
                                    );
                                    ?>

                                    <?php if ($status == 'normal'): ?>

                                        <span class="status-ativo">
                                            Normal
                                        </span>

                                    <?php elseif ($status == 'attention'): ?>

                                        <span class="status-alerta">
                                            Atenção
                                        </span>

                                    <?php elseif ($status == 'critical'): ?>

                                        <span class="status-falha">
                                            Crítico
                                        </span>

                                    <?php else: ?>

                                        <span>
                                            Não informado
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <a href="formulario_sensor.php?id=<?= $sensor['id_sensores'] ?>">
                                        Editar
                                    </a>

                                    <br>

                                    <a href="excluir_sensor.php?id=<?= $sensor['id_sensores'] ?>"
                                       onclick="return confirm('Deseja realmente excluir este sensor?');">
                                        Excluir
                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>

</body>

</html>