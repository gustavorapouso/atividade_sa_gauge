<?php

require '../../data_base/conexao.php';


// =====================================================
// OPÇÃO SELECIONADA
// =====================================================

$acao = $_GET['acao'] ?? 'adicionar';


// =====================================================
// MENSAGENS
// =====================================================

$mensagem = '';
$erro = '';


// =====================================================
// CADASTRAR SENSOR
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_sensor'])) {

    $codigo = trim($_POST['codigo']);
    $tipo = trim($_POST['tipo']);
    $localizacao = trim($_POST['localizacao']);
    $segmento = trim($_POST['segmento']);
    $ultima_leitura = trim($_POST['ultima_leitura']);
    $FK_id_trem = $_POST['FK_id_trem'];
    $FK_id_trilho = $_POST['FK_id_trilho'];

    // Verifica se o trem existe
    $stmt = $conexao->prepare(
        "SELECT id_trem
         FROM trem
         WHERE id_trem = ?"
    );

    $stmt->bind_param("i", $FK_id_trem);
    $stmt->execute();

    $resultado_trem = $stmt->get_result();

    if ($resultado_trem->num_rows === 0) {

        $erro = "O trem selecionado não existe.";

    } else {

        // Verifica se o trilho existe
        $stmt = $conexao->prepare(
            "SELECT id_trilho
             FROM trilho
             WHERE id_trilho = ?"
        );

        $stmt->bind_param("i", $FK_id_trilho);
        $stmt->execute();

        $resultado_trilho = $stmt->get_result();

        if ($resultado_trilho->num_rows === 0) {

            $erro = "O trilho selecionado não existe.";

        } else {

            // Verifica se o código já existe
            $stmt = $conexao->prepare(
                "SELECT id_sensores
                 FROM sensores
                 WHERE codigo = ?"
            );

            $stmt->bind_param("s", $codigo);
            $stmt->execute();

            $resultado_codigo = $stmt->get_result();

            if ($resultado_codigo->num_rows > 0) {

                $erro = "Já existe um sensor com esse código.";

            } else {

                /*
                 * O campo nome continua sendo preenchido porque
                 * ele já existe na sua tabela sensores atual.
                 *
                 * Usaremos o próprio código como nome do sensor.
                 */

                $nome = $codigo;

                $data_adicao = date('Y-m-d H:i:s');

                $stmt = $conexao->prepare(
                    "INSERT INTO sensores
                    (
                        nome,
                        codigo,
                        tipo,
                        estacao_sensores,
                        segmento,
                        ultima_leitura,
                        data_adição,
                        FK_id_trem,
                        FK_id_trilho
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssssssii",
                    $nome,
                    $codigo,
                    $tipo,
                    $localizacao,
                    $segmento,
                    $ultima_leitura,
                    $data_adicao,
                    $FK_id_trem,
                    $FK_id_trilho
                );

                if ($stmt->execute()) {

                    $mensagem = "Sensor cadastrado com sucesso!";

                    $acao = 'adicionar';

                } else {

                    $erro = "Erro ao cadastrar sensor: " . $stmt->error;
                }
            }
        }
    }
}


// =====================================================
// BUSCAR TRENS
// =====================================================

$trens = $conexao->query(
    "SELECT
        id_trem,
        prefixo,
        modelo
     FROM trem
     ORDER BY prefixo"
);


// =====================================================
// BUSCAR TRILHOS
// =====================================================

$trilhos = $conexao->query(
    "SELECT
        id_trilho,
        localizacao
     FROM trilho
     ORDER BY localizacao"
);


// =====================================================
// BUSCAR SENSORES
// =====================================================

$sensores = $conexao->query(
    "SELECT
        sensores.*,
        trem.prefixo,
        trem.modelo
     FROM sensores
     INNER JOIN trem
        ON trem.id_trem = sensores.FK_id_trem
     ORDER BY trem.prefixo, sensores.codigo"
);


// =====================================================
// BUSCAR SENSORES PARA REMOÇÃO
// =====================================================

$sensores_remover = $conexao->query(
    "SELECT
        sensores.id_sensores,
        sensores.codigo,
        sensores.tipo,
        trem.prefixo,
        trem.modelo
     FROM sensores
     INNER JOIN trem
        ON trem.id_trem = sensores.FK_id_trem
     ORDER BY sensores.codigo"
);

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Gestão de Sensores</title>


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >


    <!-- FONTES -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet"
    >


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="sensores_style.css"
    >

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">

    <div class="left">

        <i
            class="fas fa-bars"
            title="Menu"
        ></i>

        <i
            class="fas fa-search"
            title="Pesquisar"
        ></i>

    </div>


    <div class="logo">

        <img
            src="../assets/logo_gauge_menor.png"
            alt="Gauge"
        >

    </div>

</header>


<!-- =====================================================
     CONTEÚDO
===================================================== -->

<main class="fluxo-conteudo">


    <section class="tela-container">


        <h1 class="titulo-tela">

            <?php

            if ($acao === 'remover') {

                echo 'Remover Sensor';

            } elseif ($acao === 'lista') {

                echo 'Lista de Sensores';

            } else {

                echo 'Cadastrar Sensor';

            }

            ?>

        </h1>


        <div class="conteudo-flex">


            <!-- =================================================
                 MENU LATERAL
            ================================================== -->

            <form class="menu-radios">


                <!-- ADICIONAR -->

                <div class="radio-group">

                    <input
                        type="radio"
                        value="adicionar"
                        id="add"
                        name="acao"
                        <?= $acao === 'adicionar' ? 'checked' : '' ?>
                    >

                    <label for="add">
                        Adicionar Sensor
                    </label>

                </div>


                <!-- REMOVER -->

                <div class="radio-group">

                    <input
                        type="radio"
                        value="remover"
                        id="rem"
                        name="acao"
                        <?= $acao === 'remover' ? 'checked' : '' ?>
                    >

                    <label for="rem">
                        Remover Sensor
                    </label>

                </div>


                <!-- LISTA -->

                <div class="radio-group">

                    <input
                        type="radio"
                        value="lista"
                        id="list"
                        name="acao"
                        <?= $acao === 'lista' ? 'checked' : '' ?>
                    >

                    <label for="list">
                        Lista de Sensores
                    </label>

                </div>


            </form>


            <!-- =================================================
                 PAINEL ADICIONAR
            ================================================== -->

            <?php if ($acao === 'adicionar'): ?>


                <div class="painel-lateral painel-formulario">


                    <?php if ($mensagem): ?>

                        <div class="mensagem-sucesso">
                            <?= htmlspecialchars($mensagem) ?>
                        </div>

                    <?php endif; ?>


                    <?php if ($erro): ?>

                        <div class="mensagem-erro">
                            <?= htmlspecialchars($erro) ?>
                        </div>

                    <?php endif; ?>


                    <form method="POST">


                        <!-- CÓDIGO -->

                        <div class="campo-grupo">

                            <label>
                                Código do Sensor:
                            </label>

                            <input
                                type="text"
                                name="codigo"
                                class="input-form"
                                placeholder="Ex: S-TEMP-001"
                                required
                            >

                        </div>


                        <!-- TIPO -->

                        <div class="campo-grupo">

                            <label>
                                Tipo de Sensor:
                            </label>

                            <div class="select-wrapper">

                                <select
                                    name="tipo"
                                    class="select-filtro"
                                    required
                                >

                                    <option value="">
                                        Selecione...
                                    </option>

                                    <option value="velocidade">
                                        Velocidade
                                    </option>

                                    <option value="temperatura do motor">
                                        Temperatura do motor
                                    </option>

                                    <option value="consumo de energia">
                                        Consumo de energia
                                    </option>

                                    <option value="localização">
                                        Localização
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- TREM -->

                        <div class="campo-grupo">

                            <label>
                                Trem:
                            </label>

                            <div class="select-wrapper">

                                <select
                                    name="FK_id_trem"
                                    class="select-filtro"
                                    required
                                >

                                    <option value="">
                                        Selecione o trem...
                                    </option>


                                    <?php while ($trem = $trens->fetch_assoc()): ?>

                                        <option
                                            value="<?= $trem['id_trem'] ?>"
                                        >

                                            <?= htmlspecialchars($trem['prefixo']) ?>
                                            -
                                            <?= htmlspecialchars($trem['modelo']) ?>

                                        </option>

                                    <?php endwhile; ?>


                                </select>

                            </div>

                        </div>


                        <!-- LOCALIZAÇÃO -->

                        <div class="campo-grupo">

                            <label>
                                Localização:
                            </label>

                            <input
                                type="text"
                                name="localizacao"
                                class="input-form"
                                placeholder="Ex: Motor, cabine..."
                                required
                            >

                        </div>


                        <!-- SEGMENTO -->

                        <div class="campo-grupo">

                            <label>
                                Segmento:
                            </label>

                            <input
                                type="text"
                                name="segmento"
                                class="input-form"
                                placeholder="Ex: Trecho 01"
                            >

                        </div>


                        <!-- TRILHO -->

                        <div class="campo-grupo">

                            <label>
                                Trilho:
                            </label>

                            <div class="select-wrapper">

                                <select
                                    name="FK_id_trilho"
                                    class="select-filtro"
                                    required
                                >

                                    <option value="">
                                        Selecione o trilho...
                                    </option>


                                    <?php while ($trilho = $trilhos->fetch_assoc()): ?>

                                        <option
                                            value="<?= $trilho['id_trilho'] ?>"
                                        >

                                            <?= htmlspecialchars($trilho['localizacao']) ?>

                                        </option>

                                    <?php endwhile; ?>


                                </select>

                            </div>

                        </div>


                        <!-- ÚLTIMA LEITURA -->

                        <div class="campo-grupo">

                            <label>
                                Última leitura:
                            </label>

                            <div class="select-wrapper">

                                <select
                                    name="ultima_leitura"
                                    class="select-filtro"
                                    required
                                >

                                    <option value="">
                                        Selecione...
                                    </option>

                                    <option value="normal">
                                        Normal
                                    </option>

                                    <option value="attention">
                                        Atenção
                                    </option>

                                    <option value="critical">
                                        Crítico
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- BOTÃO -->

                        <button
                            type="submit"
                            name="cadastrar_sensor"
                            class="btn-salvar"
                        >

                            Salvar

                        </button>


                    </form>


                </div>


            <!-- =================================================
                 PAINEL REMOVER
            ================================================== -->

            <?php elseif ($acao === 'remover'): ?>


                <div class="painel-lateral painel-formulario">


                    <form
                        method="GET"
                        action="excluir_sensor.php"
                    >


                        <div class="campo-grupo">

                            <label>
                                Selecione o Sensor:
                            </label>


                            <div class="select-wrapper">

                                <select
                                    name="id"
                                    class="select-filtro"
                                    required
                                >

                                    <option value="">
                                        Selecione...
                                    </option>


                                    <?php while ($sensor = $sensores_remover->fetch_assoc()): ?>

                                        <option
                                            value="<?= $sensor['id_sensores'] ?>"
                                        >

                                            <?= htmlspecialchars($sensor['codigo']) ?>

                                            -

                                            <?= htmlspecialchars($sensor['prefixo']) ?>

                                        </option>

                                    <?php endwhile; ?>


                                </select>

                            </div>

                        </div>


                        <div class="campo-grupo">

                            <label>
                                Data de remoção:
                            </label>

                            <input
                                type="text"
                                class="input-form"
                                value="<?= date('d/m/Y') ?>"
                                readonly
                            >

                        </div>


                        <div class="campo-grupo">

                            <label>
                                Observações:
                            </label>

                            <input
                                type="text"
                                class="input-form"
                                placeholder="Escreva aqui..."
                            >

                        </div>


                        <button
                            type="submit"
                            class="btn-salvar"
                            onclick="return confirm('Deseja realmente remover este sensor?');"
                        >

                            Remover

                        </button>


                    </form>


                </div>


            <!-- =================================================
                 PAINEL LISTA
            ================================================== -->

            <?php else: ?>


                <div class="painel-lateral painel-formulario">


                    <table class="tabela-sensores">


                        <thead>

                            <tr>

                                <th>
                                    Sensor
                                </th>

                                <th>
                                    Vínculo
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if ($sensores->num_rows > 0): ?>


                                <?php while ($sensor = $sensores->fetch_assoc()): ?>


                                    <tr>


                                        <!-- SENSOR -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $sensor['codigo']
                                            ) ?>

                                        </td>


                                        <!-- VÍNCULO -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $sensor['prefixo']
                                            ) ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>


                                            <?php

                                            $status = strtolower(
                                                $sensor['ultima_leitura'] ?? ''
                                            );

                                            ?>


                                            <?php if ($status === 'normal'): ?>

                                                <span class="status-ativo">
                                                    Normal
                                                </span>


                                            <?php elseif ($status === 'attention'): ?>

                                                <span class="status-alerta">
                                                    Atenção
                                                </span>


                                            <?php elseif ($status === 'critical'): ?>

                                                <span class="status-falha">
                                                    Crítico
                                                </span>


                                            <?php else: ?>

                                                <span>
                                                    Não informado
                                                </span>

                                            <?php endif; ?>


                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            <?php else: ?>


                                <tr>

                                    <td colspan="3">

                                        Nenhum sensor cadastrado.

                                    </td>

                                </tr>


                            <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </div>


    </section>


</main>


<!-- =====================================================
     JAVASCRIPT DOS RADIOS
===================================================== -->

<script>

const radios = document.querySelectorAll(
    'input[name="acao"]'
);

radios.forEach(function(radio) {

    radio.addEventListener('change', function() {

        window.location.href =
            'sensores.php?acao=' + this.value;

    });

});

</script>

<footer class="footer-gauge">

    <div class="footer-container">


        <div class="footer-bloco bloco-esquerda">

            <span class="footer-label">
                Entre em contato
            </span>


            <a
                href="tel:47999174896"
                class="footer-link"
            >

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

                &copy; 2026 Gauge.
                Todos os direitos reservados.

            </p>

        </div>



        <div class="footer-bloco bloco-direita">

            <span class="footer-label">

                Precisa de Suporte?

            </span>


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