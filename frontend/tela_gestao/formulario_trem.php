<?php

session_start();

require '../../data_base/conexao.php';

$mensagem = '';


// =====================================
// VERIFICAR SE É EDIÇÃO
// =====================================

$id_trem = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;

$modo_edicao = ($id_trem > 0);


// =====================================
// VALORES INICIAIS
// =====================================

$prefixo = '';
$modelo = '';
$ano = '';
$status = '';
$capacidade = '';
$ultima_inspecao = '';


// =====================================
// BUSCAR TREM PARA EDITAR
// =====================================

if ($modo_edicao) {

    $sql = "
        SELECT
            id_trem,
            prefixo,
            modelo,
            ano,
            status,
            capacidade,
            ultima_inspecao
        FROM trem
        WHERE id_trem = ?
    ";

    $stmt = $conexao->prepare($sql);

    if (!$stmt) {

        die(
            "Erro ao preparar consulta: " .
            $conexao->error
        );
    }

    $stmt->bind_param(
        "i",
        $id_trem
    );

    $stmt->execute();

    $resultado = $stmt->get_result();


    // =====================================
    // VERIFICAR SE O TREM EXISTE
    // =====================================

    if ($resultado->num_rows === 0) {

        header('Location: trem_cadastrados.php');
        exit;
    }


    // =====================================
    // PEGAR DADOS DO TREM
    // =====================================

    $trem = $resultado->fetch_assoc();


    $prefixo = $trem['prefixo'];
    $modelo = $trem['modelo'];
    $ano = $trem['ano'];
    $status = $trem['status'];
    $capacidade = $trem['capacidade'];
    $ultima_inspecao = $trem['ultima_inspecao'];


    $stmt->close();
}


// =====================================
// CADASTRAR OU EDITAR
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    // =====================================
    // RECEBER DADOS
    // =====================================

    $prefixo = trim($_POST['prefixo'] ?? '');

    $modelo = trim($_POST['modelo'] ?? '');

    $ano = intval($_POST['ano'] ?? 0);

    $status = $_POST['status'] ?? '';

    $capacidade = floatval(
        $_POST['capacidade'] ?? 0
    );


    // =====================================
    // ÚLTIMA INSPEÇÃO
    // =====================================

    $ultima_inspecao = !empty(
        $_POST['ultima_inspecao'] ?? ''
    )
        ? $_POST['ultima_inspecao']
        : null;


    // =====================================
    // VALIDAÇÃO
    // =====================================

    if (
        $prefixo === '' ||
        $modelo === '' ||
        $ano <= 0 ||
        $status === '' ||
        $capacidade < 0
    ) {

        $mensagem = 'Preencha todos os campos obrigatórios.';

    } else {


        // =====================================
        // EDITAR TREM
        // =====================================

        if ($modo_edicao) {


            $sql = "
                UPDATE trem
                SET
                    prefixo = ?,
                    modelo = ?,
                    ano = ?,
                    status = ?,
                    capacidade = ?,
                    ultima_inspecao = ?
                WHERE id_trem = ?
            ";


            $stmt = $conexao->prepare($sql);


            if (!$stmt) {

                $mensagem =
                    'Erro ao preparar atualização: ' .
                    $conexao->error;

            } else {


                // s = prefixo
                // s = modelo
                // i = ano
                // s = status
                // d = capacidade
                // s = última inspeção
                // i = id do trem

                $stmt->bind_param(
                    "ssisdsi",
                    $prefixo,
                    $modelo,
                    $ano,
                    $status,
                    $capacidade,
                    $ultima_inspecao,
                    $id_trem
                );


                if ($stmt->execute()) {

                    $stmt->close();

                    header(
                        'Location: trem_cadastrados.php'
                    );

                    exit;

                } else {

                    $mensagem =
                        'Erro ao atualizar o trem: ' .
                        $stmt->error;

                    $stmt->close();
                }
            }


        // =====================================
        // CADASTRAR TREM
        // =====================================

        } else {


            $sql = "
                INSERT INTO trem (
                    prefixo,
                    modelo,
                    ano,
                    status,
                    capacidade,
                    ultima_inspecao
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ";


            $stmt = $conexao->prepare($sql);


            if (!$stmt) {

                $mensagem =
                    'Erro ao preparar cadastro: ' .
                    $conexao->error;

            } else {


                // s = prefixo
                // s = modelo
                // i = ano
                // s = status
                // d = capacidade
                // s = última inspeção

                $stmt->bind_param(
                    "ssisds",
                    $prefixo,
                    $modelo,
                    $ano,
                    $status,
                    $capacidade,
                    $ultima_inspecao
                );


                if ($stmt->execute()) {

                    $stmt->close();

                    header(
                        'Location: trem_cadastrados.php'
                    );

                    exit;

                } else {

                    $mensagem =
                        'Erro ao cadastrar o trem: ' .
                        $stmt->error;

                    $stmt->close();
                }
            }
        }
    }
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

    <title>

        <?= $modo_edicao
            ? 'Editar Trem - Gauge'
            : 'Novo Trem - Gauge'
        ?>

    </title>


    <!-- =====================================
         FONT AWESOME
    ====================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >


    <!-- =====================================
         CSS
    ====================================== -->

    <link
        rel="stylesheet"
        href="formulario_rota.css"
    >

</head>


<body>


<!-- =====================================
     NAVBAR
===================================== -->

<header>


    <!-- VOLTAR -->

    <a
        href="trem_cadastrados.php"
        class="menu"
        title="Voltar"
    >

        <i class="fas fa-arrow-left"></i>

    </a>


    <!-- LOGO -->

    <img
        src="../assets/logo_gauge_menor.png"
        alt="Gauge"
    >


    <!-- PESQUISA -->

    <i
        class="fas fa-search"
        title="Pesquisar"
    ></i>


</header>



<!-- =====================================
     CONTEÚDO
===================================== -->

<main>


    <!-- =====================================
         TÍTULO
    ====================================== -->

    <h1>

        <?= $modo_edicao
            ? 'Editar trem'
            : 'Novo trem'
        ?>

    </h1>


    <!-- =====================================
         DESCRIÇÃO
    ====================================== -->

    <p class="descricao">

        <?= $modo_edicao
            ? 'Altere os dados do trem cadastrado.'
            : 'Cadastre um novo trem para o sistema Ferrorama Gauge.'
        ?>

    </p>



    <!-- =====================================
         MENSAGEM
    ====================================== -->

    <?php if ($mensagem !== ''): ?>

        <div class="mensagem">

            <?= htmlspecialchars($mensagem) ?>

        </div>

    <?php endif; ?>



    <!-- =====================================
         FORMULÁRIO
    ====================================== -->

    <form method="POST">


        <div class="formulario">


            <!-- =================================
                 PREFIXO
            ================================== -->

            <div class="campo">

                <label for="prefixo">

                    Prefixo

                </label>


                <input
                    type="text"
                    name="prefixo"
                    id="prefixo"
                    placeholder="Ex: TR-204"
                    maxlength="20"
                    value="<?= htmlspecialchars($prefixo) ?>"
                    required
                >

            </div>



            <!-- =================================
                 MODELO
            ================================== -->

            <div class="campo">

                <label for="modelo">

                    Modelo

                </label>


                <input
                    type="text"
                    name="modelo"
                    id="modelo"
                    placeholder="Ex: Vectron"
                    maxlength="45"
                    value="<?= htmlspecialchars($modelo) ?>"
                    required
                >

            </div>



            <!-- =================================
                 ANO
            ================================== -->

            <div class="campo">

                <label for="ano">

                    Ano

                </label>


                <input
                    type="number"
                    name="ano"
                    id="ano"
                    placeholder="Ex: 2024"
                    min="1900"
                    max="2100"
                    value="<?= htmlspecialchars($ano) ?>"
                    required
                >

            </div>



            <!-- =================================
                 STATUS
            ================================== -->

            <div class="campo">

                <label for="status">

                    Status

                </label>


                <select
                    name="status"
                    id="status"
                    required
                >

                    <option value="">

                        Selecione o status

                    </option>


                    <option
                        value="ativo"
                        <?= $status === 'ativo'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Ativo

                    </option>


                    <option
                        value="manutencao"
                        <?= $status === 'manutencao'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Em manutenção

                    </option>


                    <option
                        value="inativo"
                        <?= $status === 'inativo'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Inativo

                    </option>

                </select>

            </div>



            <!-- =================================
                 CAPACIDADE
            ================================== -->

            <div class="campo">

                <label for="capacidade">

                    Capacidade (toneladas)

                </label>


                <input
                    type="number"
                    name="capacidade"
                    id="capacidade"
                    placeholder="Ex: 6200.50"
                    step="0.01"
                    min="0"
                    value="<?= htmlspecialchars($capacidade) ?>"
                    required
                >

            </div>



            <!-- =================================
                 ÚLTIMA INSPEÇÃO
            ================================== -->

            <div class="campo">

                <label for="ultima_inspecao">

                    Última inspeção

                </label>


                <input
                    type="date"
                    name="ultima_inspecao"
                    id="ultima_inspecao"
                    value="<?= htmlspecialchars($ultima_inspecao ?? '') ?>"
                >

            </div>


        </div>



        <!-- =====================================
             BOTÕES
        ====================================== -->

        <div class="botoes">


            <!-- CANCELAR -->

            <a
                href="trem_cadastrados.php"
                class="botao cancelar"
            >

                Cancelar

            </a>



            <!-- SALVAR -->

            <button
                type="submit"
                class="botao salvar"
            >

                <i class="fas fa-check"></i>


                <?= $modo_edicao
                    ? 'Salvar alterações'
                    : 'Cadastrar trem'
                ?>

            </button>


        </div>


    </form>


</main>



<!-- =====================================
     FOOTER
===================================== -->

<footer class="footer-gauge">

    <div class="footer-container">


        <!-- CONTATO -->

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



        <!-- LOGO -->

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



        <!-- SUPORTE -->

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