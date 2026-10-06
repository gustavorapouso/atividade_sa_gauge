<?php

session_start();

require '../../data_base/conexao.php';

$mensagem = '';


// =====================================
// CADASTRAR TREM
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $prefixo = $_POST['prefixo'];
    $modelo = $_POST['modelo'];
    $ano = $_POST['ano'];
    $status = $_POST['status'];
    $capacidade = $_POST['capacidade'];

    // Última inspeção pode ficar vazia
    $ultima_inspecao = !empty($_POST['ultima_inspecao'])
        ? $_POST['ultima_inspecao']
        : null;


    // =====================================
    // INSERIR NO BANCO
    // =====================================

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


    if ($stmt) {

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
    ====================================== -->

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
    ====================================== -->

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
                        required>

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
                        required>

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
                        required>

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
                        required>

                        <option value="">

                            Selecione o status

                        </option>


                        <option value="ativo">

                            Ativo

                        </option>


                        <option value="manutencao">

                            Em manutenção

                        </option>


                        <option value="inativo">

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
                        required>

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
                        id="ultima_inspecao">

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
    ====================================== -->

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