<?php
require '../../data_base/conexao.php';
require_once '../../data_base/includes/limites.php';
require '../../data_base/includes/proteger.php';

$acao = $_GET['acao'] ?? 'adicionar';

if (!in_array($acao, ['adicionar', 'remover', 'lista'], true)) {
    $acao = 'adicionar';
}

$mensagem = '';
$erro = '';

function escapar($valor): string
{
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =====================================================
   CADASTRAR SENSOR
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['cadastrar_sensor'])
) {
    $codigo = trim($_POST['codigo'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $localizacao = trim($_POST['localizacao'] ?? '');
    $segmento = trim($_POST['segmento'] ?? '');

    $id_trem = filter_input(
        INPUT_POST,
        'FK_id_trem',
        FILTER_VALIDATE_INT
    );

    $id_trilho = filter_input(
        INPUT_POST,
        'FK_id_trilho',
        FILTER_VALIDATE_INT
    );

    if (
        $codigo === ''
        || $tipo === ''
        || $localizacao === ''
        || !$id_trem
        || !$id_trilho
    ) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {

        /* Verifica se o código já existe */
        $stmt = $conexao->prepare(
            "SELECT id_sensores
             FROM sensores
             WHERE codigo = ?"
        );

        $stmt->bind_param('s', $codigo);
        $stmt->execute();

        $duplicado = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if ($duplicado) {
            $erro = 'Já existe um sensor com esse código.';
        } else {

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
                    `data_adição`,
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
                $id_trem,
                $id_trilho
            );

            if ($stmt->execute()) {
                $stmt->close();

                header(
                    'Location: sensores.php?acao=adicionar&sucesso=1'
                );

                exit;
            }

            $erro = 'Erro ao cadastrar sensor: ' . $stmt->error;

            $stmt->close();
        }
    }
}

/* Mensagem de cadastro */
if (isset($_GET['sucesso'])) {
    $mensagem = 'Sensor cadastrado com sucesso!';
}

/* =====================================================
   REMOVER SENSOR
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['remover_sensor'])
) {
    $id_sensor = filter_input(
        INPUT_POST,
        'id_sensor',
        FILTER_VALIDATE_INT
    );

    if (!$id_sensor) {
        $erro = 'Selecione um sensor válido.';
    } else {

        $stmt = $conexao->prepare(
            "DELETE FROM sensores
             WHERE id_sensores = ?"
        );

        $stmt->bind_param('i', $id_sensor);

        if (
            $stmt->execute()
            && $stmt->affected_rows > 0
        ) {
            $stmt->close();

            header(
                'Location: sensores.php?acao=remover&removido=1'
            );

            exit;
        }

        $erro = 'Não foi possível remover o sensor.';

        $stmt->close();
    }
}

/* Mensagem de remoção */
if (isset($_GET['removido'])) {
    $mensagem = 'Sensor removido com sucesso!';
}

/* =====================================================
   BUSCAR TRENS
===================================================== */

$trens = $conexao->query(
    "SELECT id_trem, prefixo, modelo
     FROM trem
     ORDER BY prefixo"
);

if (!$trens) {
    die('Erro ao buscar trens: ' . $conexao->error);
}

$listaTrens = $trens->fetch_all(MYSQLI_ASSOC);

/* =====================================================
   BUSCAR TRILHOS
===================================================== */

$trilhos = $conexao->query(
    "SELECT id_trilho, localizacao
     FROM trilho
     ORDER BY localizacao"
);

if (!$trilhos) {
    die('Erro ao buscar trilhos: ' . $conexao->error);
}

$listaTrilhos = $trilhos->fetch_all(MYSQLI_ASSOC);

/* =====================================================
   BUSCAR SENSORES E ÚLTIMAS LEITURAS
===================================================== */

$resultadoSensores = $conexao->query(
    "SELECT
        s.*,
        t.prefixo,
        t.modelo,

        (
            SELECT l.valor
            FROM leituras l
            WHERE l.id_sensor = s.id_sensores
            ORDER BY l.data_hora DESC, l.id_leitura DESC
            LIMIT 1
        ) AS valor_ultima_leitura,

        (
            SELECT l.unidade
            FROM leituras l
            WHERE l.id_sensor = s.id_sensores
            ORDER BY l.data_hora DESC, l.id_leitura DESC
            LIMIT 1
        ) AS unidade_ultima_leitura

    FROM sensores s

    LEFT JOIN trem t
        ON t.id_trem = s.FK_id_trem

    ORDER BY s.codigo"
);

if (!$resultadoSensores) {
    die('Erro ao buscar sensores: ' . $conexao->error);
}

$listaSensores = $resultadoSensores->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Gestão de Sensores | Gauge</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">

    <!-- CSS específico da página -->
    <link
        rel="stylesheet"
        href="sensores_style.css?v=12">

    <!-- CSS compartilhado do menu: mantido sem alterações -->
    <link
        rel="stylesheet"
        href="../tela_principal/menu.css?v=2.0">
</head>

<body>

    <!-- =================================================
         HEADER
    ================================================== -->

    <header class="header">

        <div class="header-left">

            <button
                class="menu-button"
                id="menuBtn"
                type="button"
                aria-label="Abrir ou fechar menu"
                aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <i class="fa-solid fa-magnifying-glass"></i>

        </div>

        <a
            href="../tela_principal/dashboard.php"
            class="logo">
            <img
                src="../../assets/logo_gauge_menor.png"
                alt="Gauge">
        </a>

    </header>

    <!-- =================================================
         LAYOUT PRINCIPAL: ASIDE + MAIN
    ================================================== -->

    <div class="layout-wrapper">

        <!-- MENU LATERAL -->

        <aside
            class="sidebar"
            id="sidebar">

            <nav class="nav-links">

                <a
                    href="seu_perfil.php"
                    class="btn-menu-item">
                    PERFIL
                </a>

                <a
                    href="rotas.php"
                    class="btn-menu-item">
                    CADASTRAR VIAGEM
                </a>

                <a
                    href="trilhos.php"
                    class="btn-menu-item">
                    CADASTRAR TRILHO
                </a>

                <a
                    href="trem_cadastrados.php"
                    class="btn-menu-item">
                    CADASTRAR TREM
                </a>

                <a
                    href="../relatorio/gera_relatorio.php"
                    class="btn-menu-item">
                    RELATÓRIOS
                </a>

                <a
                    href="notificacoes_alertas.php"
                    class="btn-menu-item">
                    ALERTAS
                </a>

                <a
                    href="../tela_principal/carga.php"
                    class="btn-menu-item">
                    ACOMPANHAMENTO
                </a>

                <a
                    href="leituras_sensores.php"
                    class="btn-menu-item">
                    LER SENSOR
                </a>

            </nav>

        </aside>

        <!-- CONTEÚDO PRINCIPAL -->

        <main class="fluxo-conteudo">

            <section class="tela-container">

                <h1 class="titulo-tela">

                    <?=
                    $acao === 'remover'
                        ? 'Remover Sensor'
                        : (
                            $acao === 'lista'
                            ? 'Lista de Sensores'
                            : 'Cadastrar Sensor'
                        )
                    ?>

                </h1>

                <div class="conteudo-flex">

                    <!-- OPÇÕES DE AÇÃO -->

                    <form
                        class="menu-radios"
                        method="GET"
                        action="sensores.php">

                        <div class="radio-group">

                            <input
                                type="radio"
                                name="acao"
                                value="adicionar"
                                id="add"
                                <?= $acao === 'adicionar' ? 'checked' : '' ?>>

                            <label for="add">
                                Adicionar Sensor
                            </label>

                        </div>

                        <div class="radio-group">

                            <input
                                type="radio"
                                name="acao"
                                value="remover"
                                id="rem"
                                <?= $acao === 'remover' ? 'checked' : '' ?>>

                            <label for="rem">
                                Remover Sensor
                            </label>

                        </div>

                        <div class="radio-group">

                            <input
                                type="radio"
                                name="acao"
                                value="lista"
                                id="list"
                                <?= $acao === 'lista' ? 'checked' : '' ?>>

                            <label for="list">
                                Lista de Sensores
                            </label>

                        </div>

                    </form>

                    <!-- =====================================
                         ADICIONAR SENSOR
                    ====================================== -->

                    <?php if ($acao === 'adicionar'): ?>

                        <div class="painel-lateral">

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
                                        required>

                                </div>

                                <div class="campo-grupo">

                                    <label for="tipo">
                                        Tipo de Sensor:
                                    </label>

                                    <select
                                        name="tipo"
                                        id="tipo"
                                        class="select-filtro"
                                        required>

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

                                <div class="campo-grupo">

                                    <label for="FK_id_trem">
                                        Trem:
                                    </label>

                                    <select
                                        name="FK_id_trem"
                                        id="FK_id_trem"
                                        class="select-filtro"
                                        required>

                                        <option value="">
                                            Selecione o trem...
                                        </option>

                                        <?php foreach ($listaTrens as $trem): ?>

                                            <option
                                                value="<?= (int)$trem['id_trem'] ?>">
                                                <?= escapar($trem['prefixo']) ?>
                                                -
                                                <?= escapar($trem['modelo']) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                                <div class="campo-grupo">

                                    <label for="FK_id_trilho">
                                        Trilho:
                                    </label>

                                    <select
                                        name="FK_id_trilho"
                                        id="FK_id_trilho"
                                        class="select-filtro"
                                        required>

                                        <option value="">
                                            Selecione o trilho...
                                        </option>

                                        <?php foreach ($listaTrilhos as $trilho): ?>

                                            <option
                                                value="<?= (int)$trilho['id_trilho'] ?>">
                                                <?= escapar($trilho['localizacao']) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                                <div class="campo-grupo">

                                    <label for="localizacao">
                                        Localização do Sensor:
                                    </label>

                                    <input
                                        type="text"
                                        name="localizacao"
                                        id="localizacao"
                                        class="input-form"
                                        placeholder="Ex: Trilho 2"
                                        required>

                                </div>

                                <div class="campo-grupo">

                                    <label for="segmento">
                                        Segmento:
                                    </label>

                                    <input
                                        type="text"
                                        name="segmento"
                                        id="segmento"
                                        class="input-form"
                                        placeholder="Informe o segmento">

                                </div>

                                <button
                                    type="submit"
                                    name="cadastrar_sensor"
                                    class="btn-salvar">
                                    Salvar
                                </button>

                            </form>

                        </div>

                        <!-- =====================================
                         REMOVER SENSOR
                    ====================================== -->

                    <?php elseif ($acao === 'remover'): ?>

                        <div class="painel-lateral">

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

                                <div class="campo-grupo">

                                    <label for="id_sensor">
                                        Selecione o sensor:
                                    </label>

                                    <select
                                        name="id_sensor"
                                        id="id_sensor"
                                        class="select-filtro"
                                        required>

                                        <option value="">
                                            Selecione...
                                        </option>

                                        <?php foreach ($listaSensores as $sensor): ?>

                                            <option
                                                value="<?= (int)$sensor['id_sensores'] ?>">
                                                <?= escapar($sensor['codigo']) ?>
                                                -
                                                <?= escapar($sensor['tipo']) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                                <button
                                    type="submit"
                                    name="remover_sensor"
                                    class="btn-remover"
                                    onclick="return confirm('Deseja realmente remover este sensor?')">
                                    Remover
                                </button>

                            </form>

                        </div>

                        <!-- =====================================
                         LISTA DE SENSORES
                    ====================================== -->

                    <?php else: ?>

                        <div class="painel-lista">

                            <?php if (count($listaSensores) > 0): ?>

                                <?php foreach ($listaSensores as $sensor): ?>

                                    <article class="sensor-item">

                                        <h3>
                                            <?= escapar($sensor['codigo']) ?>
                                        </h3>

                                        <p>
                                            <strong>Tipo:</strong>
                                            <?= escapar($sensor['tipo']) ?>
                                        </p>

                                        <p>
                                            <strong>Trem:</strong>

                                            <?= escapar(
                                                $sensor['prefixo'] ?? 'Não informado'
                                            ) ?>

                                            -

                                            <?= escapar(
                                                $sensor['modelo'] ?? ''
                                            ) ?>
                                        </p>

                                        <p>
                                            <strong>Localização:</strong>

                                            <?= escapar(
                                                $sensor['estacao_sensores']
                                            ) ?>
                                        </p>

                                        <p>
                                            <strong>Segmento:</strong>

                                            <?= escapar(
                                                $sensor['segmento']
                                            ) ?>
                                        </p>

                                        <p>
                                            <strong>Última leitura:</strong>

                                            <?php if (
                                                $sensor['valor_ultima_leitura'] !== null
                                            ): ?>

                                                <?= escapar(
                                                    $sensor['valor_ultima_leitura']
                                                ) ?>

                                                <?= escapar(
                                                    $sensor['unidade_ultima_leitura']
                                                ) ?>

                                            <?php else: ?>

                                                Nenhuma leitura registrada

                                            <?php endif; ?>

                                        </p>

                                    </article>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <p>
                                    Nenhum sensor cadastrado.
                                </p>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </main>

    </div>

    <!-- =================================================
         RODAPÉ
    ================================================== -->

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
                    src="../../assets/logo_gauge_menor.png"
                    alt="Gauge Logo"
                    class="footer-logo">

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
                    class="footer-link link-sublinhado">
                    contato@gauge.com.br
                </a>

            </div>

        </div>

    </footer>

    <!-- =================================================
         JAVASCRIPT DOS RADIOS
    ================================================== -->

    <script>
        document
            .querySelectorAll('.menu-radios input[type="radio"]')
            .forEach(function(radio) {

                radio.addEventListener('change', function() {
                    this.form.submit();
                });

            });
    </script>

    <!-- JavaScript compartilhado do menu -->
    <script src="../js/menu.js"></script>

</body>

</html>