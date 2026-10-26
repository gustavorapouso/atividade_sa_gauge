<?php

require '../../data_base/conexao.php';
require_once '../../data_base/includes/limites.php';
require '../../data_base/includes/proteger.php';

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
// A última leitura agora vem da tabela leituras.
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['cadastrar_sensor'])
) {
    $codigo = trim($_POST['codigo'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $localizacao = trim($_POST['localizacao'] ?? '');
    $segmento = trim($_POST['segmento'] ?? '');
    $FK_id_trem = filter_input(
        INPUT_POST,
        'FK_id_trem',
        FILTER_VALIDATE_INT
    );
    $FK_id_trilho = filter_input(
        INPUT_POST,
        'FK_id_trilho',
        FILTER_VALIDATE_INT
    );

    if (
        $codigo === '' ||
        $tipo === '' ||
        $localizacao === '' ||
        !$FK_id_trem ||
        !$FK_id_trilho
    ) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        // Verifica se o trem existe
        $stmt = $conexao->prepare(
            "SELECT id_trem
             FROM trem
             WHERE id_trem = ?"
        );

        $stmt->bind_param('i', $FK_id_trem);
        $stmt->execute();
        $resultado_trem = $stmt->get_result();
        $stmt->close();

        if ($resultado_trem->num_rows === 0) {
            $erro = 'O trem selecionado não existe.';
        } else {
            // Verifica se o trilho existe
            $stmt = $conexao->prepare(
                "SELECT id_trilho
                 FROM trilho
                 WHERE id_trilho = ?"
            );

            $stmt->bind_param('i', $FK_id_trilho);
            $stmt->execute();
            $resultado_trilho = $stmt->get_result();
            $stmt->close();

            if ($resultado_trilho->num_rows === 0) {
                $erro = 'O trilho selecionado não existe.';
            } else {
                // Verifica se o código já existe
                $stmt = $conexao->prepare(
                    "SELECT id_sensores
                     FROM sensores
                     WHERE codigo = ?"
                );

                $stmt->bind_param('s', $codigo);
                $stmt->execute();
                $resultado_codigo = $stmt->get_result();
                $stmt->close();

                if ($resultado_codigo->num_rows > 0) {
                    $erro = 'Já existe um sensor com esse código.';
                } else {
                    // A coluna nome continua obrigatória no banco.
                    $nome = $codigo;
                    $data_adicao = date('Y-m-d H:i:s');

                    // Não gravamos mais ultima_leitura manualmente.
                    $stmt = $conexao->prepare(
                        "INSERT INTO sensores
                        (
                            nome,
                            codigo,
                            tipo,
                            estacao_sensores,
                            segmento,
                            data_adiçao,
                            FK_id_trem,
                            FK_id_trilho
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->bind_param(
                        'ssssssii',
                        $nome,
                        $codigo,
                        $tipo,
                        $localizacao,
                        $segmento,
                        $data_adicao,
                        $FK_id_trem,
                        $FK_id_trilho
                    );

                    if ($stmt->execute()) {
                        $mensagem = 'Sensor cadastrado com sucesso!';
                        $acao = 'adicionar';
                    } else {
                        $erro = 'Erro ao cadastrar sensor: ' . $stmt->error;
                    }

                    $stmt->close();
                }
            }
        }
    }
}

// =====================================================
// BUSCAR TRENS
// =====================================================

$trens = $conexao->query(
    "SELECT id_trem, prefixo, modelo
     FROM trem
     ORDER BY prefixo"
);

// =====================================================
// BUSCAR TRILHOS
// =====================================================

$trilhos = $conexao->query(
    "SELECT id_trilho, localizacao
     FROM trilho
     ORDER BY localizacao"
);

// =====================================================
// BUSCAR SENSORES E SUAS LEITURAS MAIS RECENTES
// A subconsulta encontra a última leitura de cada sensor.
// O LEFT JOIN mantém os sensores sem leitura na listagem.
// =====================================================

$sensores = $conexao->query(
    "SELECT
        sensores.*,
        trem.prefixo,
        trem.modelo,

        (
            SELECT l.id_leitura
            FROM leituras l
            WHERE l.id_sensor = sensores.id_sensores
            ORDER BY l.data_hora DESC, l.id_leitura DESC
            LIMIT 1
        ) AS id_ultima_leitura,

        (
            SELECT l.valor
            FROM leituras l
            WHERE l.id_sensor = sensores.id_sensores
            ORDER BY l.data_hora DESC, l.id_leitura DESC
            LIMIT 1
        ) AS valor_ultima_leitura,

        (
            SELECT l.unidade
            FROM leituras l
            WHERE l.id_sensor = sensores.id_sensores
            ORDER BY l.data_hora DESC, l.id_leitura DESC
            LIMIT 1
        ) AS unidade_ultima_leitura,

        (
            SELECT l.data_hora
            FROM leituras l
            WHERE l.id_sensor = sensores.id_sensores
            ORDER BY l.data_hora DESC, l.id_leitura DESC
            LIMIT 1
        ) AS data_ultima_leitura

    FROM sensores

    LEFT JOIN trem
        ON trem.id_trem = sensores.FK_id_trem

    ORDER BY trem.prefixo, sensores.codigo"
);

if (!$sensores) {
    die('Erro ao buscar sensores: ' . $conexao->error);
}

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

// =====================================================
// FUNÇÃO PARA EXIBIR TEXTO COM SEGURANÇA
// =====================================================

function escapar($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        'UTF-8'
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

    <!-- CSS ORIGINAL -->
    <link
        rel="stylesheet"
        href="sensores_style.css"
    >
</head>

<body>

    <!-- =====================================================
         HEADER ORIGINAL
    ====================================================== -->

    <header class="header">

        <div class="left">
            <i class="fas fa-bars" title="Menu"></i>
            <i class="fas fa-search" title="Pesquisar"></i>
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
    ====================================================== -->

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

                <!-- MENU LATERAL -->

                <form class="menu-radios">

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
                                <?= escapar($mensagem) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($erro): ?>
                            <div class="mensagem-erro">
                                <?= escapar($erro) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">

                            <!-- CÓDIGO -->

                            <div class="campo-grupo">
                                <label for="codigo">
                                    Código do Sensor:
                                </label>

                                <input
                                    type="text"
                                    name="codigo"
                                    id="codigo"
                                    class="input-form"
                                    placeholder="Ex: S-TEMP-001"
                                    required
                                >
                            </div>

                            <!-- TIPO -->

                            <div class="campo-grupo">
                                <label for="tipo">
                                    Tipo de Sensor:
                                </label>

                                <div class="select-wrapper">
                                    <select
                                        name="tipo"
                                        id="tipo"
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
                                <label for="FK_id_trem">
                                    Trem:
                                </label>

                                <div class="select-wrapper">
                                    <select
                                        name="FK_id_trem"
                                        id="FK_id_trem"
                                        class="select-filtro"
                                        required
                                    >
                                        <option value="">
                                            Selecione o trem...
                                        </option>

                                        <?php while ($trem = $trens->fetch_assoc()): ?>
                                            <option
                                                value="<?= escapar($trem['id_trem']) ?>"
                                            >
                                                <?= escapar($trem['prefixo']) ?>
                                                -
                                                <?= escapar($trem['modelo']) ?>
                                            </option>
                                        <?php endwhile; ?>

                                    </select>
                                </div>
                            </div>

                            <!-- LOCALIZAÇÃO -->

                            <div class="campo-grupo">
                                <label for="localizacao">
                                    Localização:
                                </label>

                                <input
                                    type="text"
                                    name="localizacao"
                                    id="localizacao"
                                    class="input-form"
                                    placeholder="Ex: Motor, cabine..."
                                    required
                                >
                            </div>

                            <!-- SEGMENTO -->

                            <div class="campo-grupo">
                                <label for="segmento">
                                    Segmento:
                                </label>

                                <input
                                    type="text"
                                    name="segmento"
                                    id="segmento"
                                    class="input-form"
                                    placeholder="Ex: Trecho 01"
                                >
                            </div>

                            <!-- TRILHO -->

                            <div class="campo-grupo">
                                <label for="FK_id_trilho">
                                    Trilho:
                                </label>

                                <div class="select-wrapper">
                                    <select
                                        name="FK_id_trilho"
                                        id="FK_id_trilho"
                                        class="select-filtro"
                                        required
                                    >
                                        <option value="">
                                            Selecione o trilho...
                                        </option>

                                        <?php while ($trilho = $trilhos->fetch_assoc()): ?>
                                            <option
                                                value="<?= escapar($trilho['id_trilho']) ?>"
                                            >
                                                <?= escapar($trilho['localizacao']) ?>
                                            </option>
                                        <?php endwhile; ?>

                                    </select>
                                </div>
                            </div>

                            <!--
                                O campo manual "Última leitura" foi removido.
                                O valor agora vem da tabela leituras.
                            -->

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
                                <label for="sensor-remover">
                                    Selecione o Sensor:
                                </label>

                                <div class="select-wrapper">
                                    <select
                                        name="id"
                                        id="sensor-remover"
                                        class="select-filtro"
                                        required
                                    >
                                        <option value="">
                                            Selecione...
                                        </option>

                                        <?php while ($sensor = $sensores_remover->fetch_assoc()): ?>
                                            <option
                                                value="<?= escapar($sensor['id_sensores']) ?>"
                                            >
                                                <?= escapar($sensor['codigo']) ?>
                                                -
                                                <?= escapar($sensor['prefixo']) ?>
                                            </option>
                                        <?php endwhile; ?>

                                    </select>
                                </div>
                            </div>

                            <div class="campo-grupo">
                                <label for="data-remocao">
                                    Data de remoção:
                                </label>

                                <input
                                    type="text"
                                    id="data-remocao"
                                    class="input-form"
                                    value="<?= date('d/m/Y') ?>"
                                    readonly
                                >
                            </div>

                            <div class="campo-grupo">
                                <label for="observacoes">
                                    Observações:
                                </label>

                                <input
                                    type="text"
                                    id="observacoes"
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
                                    <th>Sensor</th>
                                    <th>Vínculo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if ($sensores->num_rows > 0): ?>

                                    <?php while ($sensor = $sensores->fetch_assoc()): ?>

                                        <tr>

                                            <!-- SENSOR -->

                                            <td>
                                                <?= escapar($sensor['codigo']) ?>
                                            </td>

                                            <!-- VÍNCULO -->

                                            <td>
                                                <?= escapar($sensor['prefixo'] ?? 'Sem trem') ?>
                                            </td>

                                            <!-- STATUS CALCULADO -->

                                            <td>

                                                <?php if ($sensor['id_ultima_leitura'] === null): ?>

                                                    <span class="status-sem-leitura">
                                                        Sem leitura
                                                    </span>

                                                <?php else: ?>

                                                    <?php
                                                    $valorAtual = (float) $sensor['valor_ultima_leitura'];

                                                    $severidade = classificar(
                                                        $sensor['tipo'],
                                                        $valorAtual
                                                    );

                                                    $classesStatus = [
                                                        'normal' => 'status-ativo',
                                                        'atencao' => 'status-alerta',
                                                        'critico' => 'status-falha'
                                                    ];

                                                    $rotulosStatus = [
                                                        'normal' => 'Normal',
                                                        'atencao' => 'Atenção',
                                                        'critico' => 'Crítico'
                                                    ];
                                                    ?>

                                                    <span class="<?= escapar($classesStatus[$severidade]) ?>">
                                                        <?= escapar($rotulosStatus[$severidade]) ?>
                                                    </span>

                                                    <div class="valor-leitura">
                                                        <?= escapar(
                                                            number_format(
                                                                $valorAtual,
                                                                2,
                                                                ',',
                                                                '.'
                                                            )
                                                        ) ?>
                                                        <?= escapar($sensor['unidade_ultima_leitura'] ?? '') ?>
                                                    </div>

                                                    <small class="data-leitura">
                                                        <?= escapar(
                                                            date(
                                                                'd/m/Y H:i',
                                                                strtotime($sensor['data_ultima_leitura'])
                                                            )
                                                        ) ?>
                                                    </small>

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
         JAVASCRIPT DO MENU LATERAL
    ====================================================== -->

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

    <!-- =====================================================
         FOOTER ORIGINAL
    ====================================================== -->

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

