<?php

require '../../data_base/conexao.php';

$id = $_GET['id'] ?? null;

$codigo = '';
$tipo = '';
$localizacao = '';
$segmento = '';
$ultima_leitura = '';
$FK_id_trem = '';
$FK_id_trilho = '';

if ($id) {

    $stmt = $conexao->prepare(
        "SELECT * FROM sensores WHERE id_sensores = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $sensor = $resultado->fetch_assoc();

    if (!$sensor) {
        die("Sensor não encontrado.");
    }

    $codigo = $sensor['codigo'];
    $tipo = $sensor['tipo'];
    $localizacao = $sensor['estacao_sensores'];
    $segmento = $sensor['segmento'];
    $ultima_leitura = $sensor['ultima_leitura'];
    $FK_id_trem = $sensor['FK_id_trem'];
    $FK_id_trilho = $sensor['FK_id_trilho'];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $codigo = $_POST['codigo'];
    $tipo = $_POST['tipo'];
    $localizacao = $_POST['localizacao'];
    $segmento = $_POST['segmento'];
    $ultima_leitura = $_POST['ultima_leitura'];
    $FK_id_trem = $_POST['FK_id_trem'];
    $FK_id_trilho = $_POST['FK_id_trilho'];

    // Verifica se o trem realmente existe
    $stmt = $conexao->prepare(
        "SELECT id_trem FROM trem WHERE id_trem = ?"
    );

    $stmt->bind_param("i", $FK_id_trem);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {

        die("O trem selecionado não existe.");

    }


    if ($id) {

        $stmt = $conexao->prepare(
            "UPDATE sensores
             SET codigo = ?,
                 tipo = ?,
                 estacao_sensores = ?,
                 segmento = ?,
                 ultima_leitura = ?,
                 FK_id_trem = ?,
                 FK_id_trilho = ?
             WHERE id_sensores = ?"
        );

        $stmt->bind_param(
            "sssssiii",
            $codigo,
            $tipo,
            $localizacao,
            $segmento,
            $ultima_leitura,
            $FK_id_trem,
            $FK_id_trilho,
            $id
        );

    } else {

        $data_adicao = date('Y-m-d H:i:s');

        $stmt = $conexao->prepare(
            "INSERT INTO sensores
            (
                codigo,
                nome,
                tipo,
                estacao_sensores,
                segmento,
                ultima_leitura,
                data_adiçao,
                FK_id_trem,
                FK_id_trilho
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $nome = $codigo;

        $stmt->bind_param(
            "sssssssii",
            $codigo,
            $nome,
            $tipo,
            $localizacao,
            $segmento,
            $ultima_leitura,
            $data_adicao,
            $FK_id_trem,
            $FK_id_trilho
        );
    }


    if ($stmt->execute()) {

        header("Location: sensores.php");
        exit;

    } else {

        echo "Erro ao salvar sensor: " . $stmt->error;

    }
}


// Busca os trens
$trens = $conexao->query(
    "SELECT id_trem, nome, modelo
     FROM trem
     ORDER BY nome"
);


// Busca os trilhos
$trilhos = $conexao->query(
    "SELECT id_trilho, localizacao
     FROM trilho
     ORDER BY localizacao"
);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <title>
        <?= $id ? 'Editar Sensor' : 'Cadastrar Sensor' ?>
    </title>

    <link rel="stylesheet" href="sensores_style.css">

</head>

<body>

<main class="fluxo-conteudo">

    <div class="tela-container">

        <h1 class="titulo-tela">
            <?= $id ? 'Editar Sensor' : 'Cadastrar Sensor' ?>
        </h1>


        <div class="painel-formulario">

            <form method="POST">

                <div class="campo-grupo">

                    <label for="codigo">
                        Código do Sensor
                    </label>

                    <input
                        type="text"
                        name="codigo"
                        id="codigo"
                        class="input-form"
                        placeholder="Ex: S-TEMP-001"
                        value="<?= htmlspecialchars($codigo) ?>"
                        required
                    >

                </div>


                <div class="campo-grupo">

                    <label for="tipo">
                        Tipo do Sensor
                    </label>

                    <select
                        name="tipo"
                        id="tipo"
                        class="select-filtro"
                        required
                    >

                        <option value="">
                            Selecione
                        </option>

                        <option value="velocidade"
                            <?= $tipo == 'velocidade' ? 'selected' : '' ?>>
                            Velocidade
                        </option>

                        <option value="temperatura do motor"
                            <?= $tipo == 'temperatura do motor' ? 'selected' : '' ?>>
                            Temperatura do motor
                        </option>

                        <option value="consumo de energia"
                            <?= $tipo == 'consumo de energia' ? 'selected' : '' ?>>
                            Consumo de energia
                        </option>

                        <option value="localização"
                            <?= $tipo == 'localização' ? 'selected' : '' ?>>
                            Localização
                        </option>

                    </select>

                </div>


                <div class="campo-grupo">

                    <label for="FK_id_trem">
                        Trem
                    </label>

                    <select
                        name="FK_id_trem"
                        id="FK_id_trem"
                        class="select-filtro"
                        required
                    >

                        <option value="">
                            Selecione o trem
                        </option>

                        <?php while ($trem = $trens->fetch_assoc()): ?>

                            <option
                                value="<?= $trem['id_trem'] ?>"
                                <?= $FK_id_trem == $trem['id_trem'] ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars($trem['nome']) ?>
                                -
                                <?= htmlspecialchars($trem['modelo']) ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <div class="campo-grupo">

                    <label for="localizacao">
                        Localização
                    </label>

                    <input
                        type="text"
                        name="localizacao"
                        id="localizacao"
                        class="input-form"
                        placeholder="Ex: Motor, cabine, eixo dianteiro"
                        value="<?= htmlspecialchars($localizacao) ?>"
                    >

                </div>


                <div class="campo-grupo">

                    <label for="segmento">
                        Segmento
                    </label>

                    <input
                        type="text"
                        name="segmento"
                        id="segmento"
                        class="input-form"
                        placeholder="Ex: Trecho 01"
                        value="<?= htmlspecialchars($segmento) ?>"
                    >

                </div>


                <div class="campo-grupo">

                    <label for="ultima_leitura">
                        Última leitura
                    </label>

                    <select
                        name="ultima_leitura"
                        id="ultima_leitura"
                        class="select-filtro"
                        required
                    >

                        <option value="">
                            Selecione
                        </option>

                        <option value="normal"
                            <?= $ultima_leitura == 'normal' ? 'selected' : '' ?>>
                            Normal
                        </option>

                        <option value="attention"
                            <?= $ultima_leitura == 'attention' ? 'selected' : '' ?>>
                            Atenção
                        </option>

                        <option value="critical"
                            <?= $ultima_leitura == 'critical' ? 'selected' : '' ?>>
                            Crítico
                        </option>

                    </select>

                </div>


                <div class="campo-grupo">

                    <label for="FK_id_trilho">
                        Trilho
                    </label>

                    <select
                        name="FK_id_trilho"
                        id="FK_id_trilho"
                        class="select-filtro"
                        required
                    >

                        <option value="">
                            Selecione o trilho
                        </option>

                        <?php while ($trilho = $trilhos->fetch_assoc()): ?>

                            <option
                                value="<?= $trilho['id_trilho'] ?>"
                                <?= $FK_id_trilho == $trilho['id_trilho'] ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars($trilho['localizacao']) ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <button type="submit" class="btn-salvar">
                    Salvar Sensor
                </button>

                <a href="sensores.php">
                    Voltar
                </a>

            </form>

        </div>

    </div>

</main>

</body>

</html>