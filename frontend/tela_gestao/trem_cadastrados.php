<?php

session_start();

require '../../data_base/conexao.php';


// =====================================
// BUSCAR TRENS CADASTRADOS
// =====================================

$sql = "
    SELECT
        id_trem,
        nome,
        modelo,
        tipo
    FROM trem
    ORDER BY nome
";

$resultado = $conexao->query($sql);


// Verificar se houve erro
if (!$resultado) {
    die(
        "Erro ao buscar os trens: " .
        $conexao->error
    );
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

    <title>Trens - Ferrorama Gauge</title>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="rotas.css"
    >

</head>


<body>


<!-- =====================================
     NAVBAR
===================================== -->

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
            alt="Gauge Logo"
        >

    </div>

</header>



<!-- =====================================
     CONTEÚDO
===================================== -->

<main>


    <h1>Trens</h1>


    <!-- =====================================
         BOTÃO CADASTRAR
    ====================================== -->

    <a
        href="formulario_trem.php.php"
        class="botao botao-nova"
    >

        <i class="fas fa-plus"></i>

        Cadastrar trem

    </a>



    <!-- =====================================
         TABELA DE TRENS
    ====================================== -->

    <table>

        <thead>

            <tr>

                <th>
                    ID
                </th>

                <th>
                    Nome
                </th>

                <th>
                    Modelo
                </th>

                <th>
                    Tipo
                </th>

                <th>
                    Ações
                </th>

            </tr>

        </thead>


        <tbody>


            <?php if ($resultado->num_rows > 0): ?>


                <?php while ($trem = $resultado->fetch_assoc()): ?>

                    <tr>


                        <!-- ID -->

                        <td>

                            <?= htmlspecialchars(
                                $trem['id_trem']
                            ) ?>

                        </td>


                        <!-- NOME -->

                        <td>

                            <?= htmlspecialchars(
                                $trem['nome']
                            ) ?>

                        </td>


                        <!-- MODELO -->

                        <td>

                            <?= htmlspecialchars(
                                $trem['modelo']
                            ) ?>

                        </td>


                        <!-- TIPO -->

                        <td>

                            <?= htmlspecialchars(
                                $trem['tipo']
                            ) ?>

                        </td>


                        <!-- AÇÕES -->

                        <td class="acoes">


                            <!-- EDITAR -->

                            <a
                                href="formulario_trem.php?id=<?= $trem['id_trem'] ?>"
                                class="editar"
                                title="Editar"
                            >

                                <i class="fas fa-pen"></i>

                            </a>


                            <!-- EXCLUIR -->

                            <a
                                href="excluir_trem.php?id=<?= $trem['id_trem'] ?>"
                                class="excluir"
                                title="Excluir"
                                onclick="return confirm('Deseja realmente excluir este trem?')"
                            >

                                <i class="fas fa-trash"></i>

                            </a>


                        </td>


                    </tr>

                <?php endwhile; ?>


            <?php else: ?>


                <tr>

                    <td colspan="5">

                        Nenhum trem cadastrado.

                    </td>

                </tr>


            <?php endif; ?>


        </tbody>

    </table>


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
                &copy; 2026 Gauge. Todos os direitos reservados.
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
