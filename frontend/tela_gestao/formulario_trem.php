<?php

session_start();

require '../../data_base/conexao.php';

$mensagem = '';


// =====================================
// CADASTRAR TREM
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = $_POST['nome'];
    $modelo = $_POST['modelo'];
    $tipo = $_POST['tipo'];


    // =====================================
    // INSERIR NO BANCO
    // =====================================

    $sql = "
        INSERT INTO trem (
            nome,
            modelo,
            tipo
        )
        VALUES (?, ?, ?)
    ";


    $stmt = $conexao->prepare($sql);


    if ($stmt) {

        $stmt->bind_param(
            "sss",
            $nome,
            $modelo,
            $tipo
        );


        if ($stmt->execute()) {

            header('Location: trens.php');
            exit;
        } else {

            $mensagem =
                'Erro ao cadastrar o trem: ' .
                $stmt->error;
        }


        $stmt->close();
    } else {

        $mensagem =
            'Erro na preparação da consulta: ' .
            $conexao->error;
    }
}

?>


<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Novo Trem - Gauge</title>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="formulario_rota.css">

</head>


<body>


    <!-- =====================================
     NAVBAR
===================================== -->

    <header>

        <a
            href="trens.php"
            class="menu"
            title="Voltar">

            <i class="fas fa-arrow-left"></i>

        </a>


        <img
            src="../assets/logo_gauge_menor.png"
            alt="Gauge">


        <i
            class="fas fa-search"
            title="Pesquisar"></i>

    </header>



    <!-- =====================================
     CONTEÚDO
===================================== -->

    <main>

        <h1>Novo trem</h1>


        <p class="descricao">

            Cadastre um novo trem para o sistema
            Ferrorama Gauge.

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


                <!-- NOME -->

                <div class="campo">

                    <label for="nome">

                        Nome do trem

                    </label>


                    <input
                        type="text"
                        name="nome"
                        id="nome"
                        placeholder="Ex: Trem Gauge 01"
                        maxlength="45"
                        required>

                </div>



                <!-- MODELO -->

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
                        required>

                </div>



                <!-- TIPO -->

                <div class="campo">

                    <label for="tipo">

                        Tipo de trem

                    </label>


                    <select
                        name="tipo"
                        id="tipo"
                        required>

                        <option value="">

                            Selecione o tipo

                        </option>


                        <option value="passageiro">

                            Passageiro

                        </option>


                        <option value="carga">

                            Carga

                        </option>


                    </select>

                </div>


            </div>



            <!-- =====================================
             BOTÕES
        ====================================== -->

            <div class="botoes">


                <a
                    href="trens.php"
                    class="botao cancelar">

                    Cancelar

                </a>


                <button
                    type="submit"
                    class="botao salvar">

                    <i class="fas fa-check"></i>

                    Cadastrar trem

                </button>


            </div>


        </form>

    </main>



    <!-- =====================================
     FOOTER
===================================== -->

    <footer class="footer-gauge">

        <div class="footer-container">


            <div class="footer-bloco bloco-esquerda">

                <span class="footer-label">

                    Entre em contato

                </span>


                <a
                    href="tel:47999174896"
                    class="footer-link">

                    (47) 99917-4896

                </a>

            </div>



            <div class="footer-bloco bloco-centro">

                <img
                    src="../assets/logo_gauge_menor.png"
                    alt="Gauge Logo"
                    class="footer-logo">


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
                    class="footer-link link-sublinhado">

                    contato@gauge.com.br

                </a>

            </div>


        </div>

    </footer>


</body>

</html>