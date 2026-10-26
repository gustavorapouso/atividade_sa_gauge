
<?php
session_start();
require '../../data_base/conexao.php';
require '../../data_base/includes/proteger.php';

$mensagem = '';
$id_trem = isset($_GET['id']) ? intval($_GET['id']) : 0;
$modo_edicao = $id_trem > 0;

$prefixo = '';
$modelo = '';
$ano = '';
$status = 'ativo';
$capacidade = '';
$ultima_inspecao = '';
$tipo_trem = 'Passageiro';
$quantidade_assentos = '';
$tipo_carga = '';

/* Carregar dados do trem para edição */
if ($modo_edicao) {
    $sql = "SELECT id_trem, prefixo, modelo, ano, status,
                   capacidade, ultima_inspecao, tipo_trem,
                   quantidade_assentos, tipo_carga
            FROM trem
            WHERE id_trem = ?";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id_trem);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $trem = $resultado->fetch_assoc();

        $prefixo = $trem['prefixo'];
        $modelo = $trem['modelo'];
        $ano = $trem['ano'];
        $status = $trem['status'];
        $capacidade = $trem['capacidade'];
        $ultima_inspecao = $trem['ultima_inspecao'];
        $tipo_trem = $trem['tipo_trem'];
        $quantidade_assentos = $trem['quantidade_assentos'];
        $tipo_carga = $trem['tipo_carga'];
    } else {
        $mensagem = 'Trem não encontrado.';
        $modo_edicao = false;
        $id_trem = 0;
    }

    $stmt->close();
}

/* Cadastrar ou atualizar */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_trem = isset($_POST['id_trem'])
        ? intval($_POST['id_trem'])
        : 0;

    $modo_edicao = $id_trem > 0;

    $prefixo = trim($_POST['prefixo'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $ano = intval($_POST['ano'] ?? 0);
    $status = $_POST['status'] ?? '';
    $capacidade = $_POST['capacidade'] ?? '';
    $ultima_inspecao = $_POST['ultima_inspecao'] ?? '';
    $tipo_trem = $_POST['tipo_trem'] ?? '';

    $quantidade_assentos = $_POST['quantidade_assentos'] ?? '';
    $tipo_carga = trim($_POST['tipo_carga'] ?? '');

    $capacidade = ($capacidade === '')
        ? 0
        : floatval($capacidade);

    $ultima_inspecao = ($ultima_inspecao === '')
        ? null
        : $ultima_inspecao;

    if ($tipo_trem === 'Passageiro') {
        $quantidade_assentos = ($quantidade_assentos === '')
            ? null
            : intval($quantidade_assentos);

        $tipo_carga = null;
    } else {
        $quantidade_assentos = null;
        $tipo_carga = ($tipo_carga === '')
            ? null
            : $tipo_carga;
    }

    /* Validações */
    if (
        $prefixo === '' ||
        $modelo === '' ||
        $ano < 1 ||
        !in_array($status, ['ativo', 'manutencao', 'inativo'], true)
    ) {
        $mensagem = 'Preencha corretamente os campos obrigatórios.';
    } elseif (!in_array($tipo_trem, ['Passageiro', 'Carga'], true)) {
        $mensagem = 'Selecione um tipo de trem válido.';
    } elseif ($capacidade < 0) {
        $mensagem = 'A capacidade não pode ser negativa.';
    } elseif (
        $tipo_trem === 'Passageiro' &&
        ($quantidade_assentos === null || $quantidade_assentos < 1)
    ) {
        $mensagem = 'Informe uma quantidade de assentos válida.';
    } elseif (
        $tipo_trem === 'Carga' &&
        ($tipo_carga === null || $tipo_carga === '')
    ) {
        $mensagem = 'Selecione o tipo de carga.';
    } else {
        try {
            if ($modo_edicao) {
                $sql = "UPDATE trem
                        SET prefixo = ?,
                            modelo = ?,
                            ano = ?,
                            status = ?,
                            capacidade = ?,
                            ultima_inspecao = ?,
                            tipo_trem = ?,
                            quantidade_assentos = ?,
                            tipo_carga = ?
                        WHERE id_trem = ?";

                $stmt = $conexao->prepare($sql);

                $stmt->bind_param(
                    "ssisdssisi",
                    $prefixo,
                    $modelo,
                    $ano,
                    $status,
                    $capacidade,
                    $ultima_inspecao,
                    $tipo_trem,
                    $quantidade_assentos,
                    $tipo_carga,
                    $id_trem
                );

                $stmt->execute();
                $stmt->close();

                header('Location: trem_cadastrados.php');
                exit;
            } else {
                $sql = "INSERT INTO trem (
                            prefixo,
                            modelo,
                            ano,
                            status,
                            capacidade,
                            ultima_inspecao,
                            tipo_trem,
                            quantidade_assentos,
                            tipo_carga
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conexao->prepare($sql);

                $stmt->bind_param(
                    "ssisdssis",
                    $prefixo,
                    $modelo,
                    $ano,
                    $status,
                    $capacidade,
                    $ultima_inspecao,
                    $tipo_trem,
                    $quantidade_assentos,
                    $tipo_carga
                );

                $stmt->execute();
                $stmt->close();

                header('Location: trem_cadastrados.php');
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $mensagem = 'Esse prefixo já está cadastrado.';
            } else {
                $mensagem = 'Erro ao salvar o trem. Verifique os dados e tente novamente.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $modo_edicao ? 'Editar trem' : 'Cadastrar trem' ?>
        | Ferrorama Gauge
    </title>

    <link rel="stylesheet" href="formulario_rota.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<header>
    <a href="../tela_principal/dashboard.php">
        <img src="../assets/logo_gauge_menor.png" alt="Ferrorama Gauge">
    </a>

    <a href="trem_cadastrados.php" class="menu">
        <i class="fa-solid fa-arrow-left"></i>
        Trens cadastrados
    </a>
</header>

<main>
    <h1>
        <?= $modo_edicao ? 'Editar trem' : 'Cadastrar trem' ?>
    </h1>

    <p class="descricao">
        Preencha os dados do trem e selecione seu tipo para completar o cadastro.
    </p>

    <?php if ($mensagem !== ''): ?>
        <p class="mensagem">
            <?= htmlspecialchars($mensagem) ?>
        </p>
    <?php endif; ?>

    <form
        method="POST"
        action="formulario_trem.php<?= $modo_edicao ? '?id=' . $id_trem : '' ?>"
    >

        <input
            type="hidden"
            name="id_trem"
            value="<?= $id_trem ?>"
        >

        <div class="formulario">

            <div class="campo">
                <label for="prefixo">Prefixo</label>
                <input
                    type="text"
                    id="prefixo"
                    name="prefixo"
                    value="<?= htmlspecialchars($prefixo) ?>"
                    required
                >
            </div>

            <div class="campo">
                <label for="modelo">Modelo</label>
                <input
                    type="text"
                    id="modelo"
                    name="modelo"
                    value="<?= htmlspecialchars($modelo) ?>"
                    required
                >
            </div>

            <div class="campo">
                <label for="ano">Ano de fabricação</label>
                <input
                    type="number"
                    id="ano"
                    name="ano"
                    min="1"
                    value="<?= htmlspecialchars((string)$ano) ?>"
                    required
                >
            </div>

            <div class="campo">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="ativo"
                        <?= $status === 'ativo' ? 'selected' : '' ?>>
                        Ativo
                    </option>

                    <option value="manutencao"
                        <?= $status === 'manutencao' ? 'selected' : '' ?>>
                        Em manutenção
                    </option>

                    <option value="inativo"
                        <?= $status === 'inativo' ? 'selected' : '' ?>>
                        Inativo
                    </option>
                </select>
            </div>

            <div class="campo">
                <label for="capacidade">Capacidade (toneladas)</label>
                <input
                    type="number"
                    id="capacidade"
                    name="capacidade"
                    min="0"
                    step="0.01"
                    value="<?= htmlspecialchars((string)$capacidade) ?>"
                    required
                >
            </div>

            <div class="campo">
                <label for="ultima_inspecao">Última inspeção</label>
                <input
                    type="date"
                    id="ultima_inspecao"
                    name="ultima_inspecao"
                    value="<?= htmlspecialchars((string)$ultima_inspecao) ?>"
                >
            </div>

            <div class="campo">
                <label for="tipo_trem">Tipo de trem</label>
                <select id="tipo_trem" name="tipo_trem" required>
                    <option value="Passageiro"
                        <?= $tipo_trem === 'Passageiro' ? 'selected' : '' ?>>
                        Trem de passageiros
                    </option>

                    <option value="Carga"
                        <?= $tipo_trem === 'Carga' ? 'selected' : '' ?>>
                        Trem de carga
                    </option>
                </select>
            </div>

            <div class="campo" id="campo-assentos">
                <label for="quantidade_assentos">
                    Quantidade de assentos
                </label>

                <input
                    type="number"
                    id="quantidade_assentos"
                    name="quantidade_assentos"
                    min="1"
                    value="<?= htmlspecialchars((string)$quantidade_assentos) ?>"
                >
            </div>

            <div class="campo" id="campo-carga">
                <label for="tipo_carga">Tipo de carga</label>

                <select id="tipo_carga" name="tipo_carga">
                    <option value="">Selecione o tipo de carga</option>

                    <?php
                    $tipos_carga = [
                        'Minério',
                        'Grãos',
                        'Combustíveis',
                        'Produtos químicos',
                        'Contêineres',
                        'Madeira',
                        'Outros'
                    ];

                    foreach ($tipos_carga as $tipo):
                    ?>
                        <option
                            value="<?= htmlspecialchars($tipo) ?>"
                            <?= $tipo_carga === $tipo ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($tipo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <div class="botoes">
            <a href="trem_cadastrados.php" class="botao cancelar">
                Cancelar
            </a>

            <button type="submit" class="botao salvar">
                <?= $modo_edicao ? 'Salvar alterações' : 'Cadastrar trem' ?>
            </button>
        </div>

    </form>
</main>

<script>
const tipoTrem = document.getElementById('tipo_trem');
const campoAssentos = document.getElementById('campo-assentos');
const campoCarga = document.getElementById('campo-carga');

const quantidadeAssentos =
    document.getElementById('quantidade_assentos');

const tipoCarga = document.getElementById('tipo_carga');

function atualizarCampos() {
    const passageiro = tipoTrem.value === 'Passageiro';

    campoAssentos.style.display = passageiro ? '' : 'none';
    campoCarga.style.display = passageiro ? 'none' : '';

    quantidadeAssentos.required = passageiro;
    tipoCarga.required = !passageiro;

    if (passageiro) {
        tipoCarga.value = '';
    } else {
        quantidadeAssentos.value = '';
    }
}

tipoTrem.addEventListener('change', atualizarCampos);
atualizarCampos();
</script>

</body>
</html>

