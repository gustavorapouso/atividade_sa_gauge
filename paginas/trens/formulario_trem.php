<?php
// Cadastro e edição de trens.
// Sem "id" na URL: cadastra um novo trem. Com "id": edita o trem existente.

require __DIR__ . '/../../includes/proteger.php';

$tiposCarga = ['Minério', 'Grãos', 'Combustíveis', 'Produtos químicos', 'Contêineres', 'Madeira', 'Outros'];

$mensagem = '';
$idTrem = (int) ($_POST['id_trem'] ?? $_GET['id'] ?? 0);
$modoEdicao = $idTrem > 0;

// Valores iniciais do formulário
$trem = [
    'prefixo'             => '',
    'modelo'              => '',
    'ano'                 => '',
    'status'              => 'ativo',
    'capacidade'          => '',
    'ultima_inspecao'     => '',
    'tipo_trem'           => 'Passageiro',
    'quantidade_assentos' => '',
    'tipo_carga'          => '',
];

// Carrega o trem para edição
if ($modoEdicao && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conexao->prepare(
        'SELECT prefixo, modelo, ano, status, capacidade, ultima_inspecao,
                tipo_trem, quantidade_assentos, tipo_carga
         FROM trem
         WHERE id_trem = ?'
    );
    $stmt->bind_param('i', $idTrem);
    $stmt->execute();
    $encontrado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($encontrado) {
        $trem = $encontrado;
    } else {
        $mensagem = 'Trem não encontrado.';
        $modoEdicao = false;
        $idTrem = 0;
    }
}

// Cadastra ou atualiza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trem = [
        'prefixo'             => trim($_POST['prefixo'] ?? ''),
        'modelo'              => trim($_POST['modelo'] ?? ''),
        'ano'                 => (int) ($_POST['ano'] ?? 0),
        'status'              => $_POST['status'] ?? '',
        'capacidade'          => ($_POST['capacidade'] ?? '') === '' ? 0 : (float) $_POST['capacidade'],
        'ultima_inspecao'     => ($_POST['ultima_inspecao'] ?? '') ?: null,
        'tipo_trem'           => $_POST['tipo_trem'] ?? '',
        'quantidade_assentos' => null,
        'tipo_carga'          => null,
    ];

    // Cada tipo de trem usa apenas um dos dois campos
    if ($trem['tipo_trem'] === 'Passageiro') {
        $assentos = $_POST['quantidade_assentos'] ?? '';
        $trem['quantidade_assentos'] = $assentos === '' ? null : (int) $assentos;
    } else {
        $trem['tipo_carga'] = trim($_POST['tipo_carga'] ?? '') ?: null;
    }

    if ($trem['prefixo'] === '' || $trem['modelo'] === '' || $trem['ano'] < 1 || !in_array($trem['status'], ['ativo', 'manutencao', 'inativo'], true)) {
        $mensagem = 'Preencha corretamente os campos obrigatórios.';
    } elseif (!in_array($trem['tipo_trem'], ['Passageiro', 'Carga'], true)) {
        $mensagem = 'Selecione um tipo de trem válido.';
    } elseif ($trem['capacidade'] < 0) {
        $mensagem = 'A capacidade não pode ser negativa.';
    } elseif ($trem['tipo_trem'] === 'Passageiro' && ($trem['quantidade_assentos'] === null || $trem['quantidade_assentos'] < 1)) {
        $mensagem = 'Informe uma quantidade de assentos válida.';
    } elseif ($trem['tipo_trem'] === 'Carga' && $trem['tipo_carga'] === null) {
        $mensagem = 'Selecione o tipo de carga.';
    } else {
        $valores = [
            $trem['prefixo'],
            $trem['modelo'],
            $trem['ano'],
            $trem['status'],
            $trem['capacidade'],
            $trem['ultima_inspecao'],
            $trem['tipo_trem'],
            $trem['quantidade_assentos'],
            $trem['tipo_carga'],
        ];

        if ($modoEdicao) {
            $sql = 'UPDATE trem
                    SET prefixo = ?, modelo = ?, ano = ?, status = ?, capacidade = ?,
                        ultima_inspecao = ?, tipo_trem = ?, quantidade_assentos = ?, tipo_carga = ?
                    WHERE id_trem = ?';
            $tipos = 'ssisdssisi';
            $valores[] = $idTrem;
        } else {
            $sql = 'INSERT INTO trem
                        (prefixo, modelo, ano, status, capacidade,
                         ultima_inspecao, tipo_trem, quantidade_assentos, tipo_carga)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $tipos = 'ssisdssis';
        }

        try {
            $stmt = $conexao->prepare($sql);
            $stmt->bind_param($tipos, ...$valores);
            $stmt->execute();
            $stmt->close();

            redirecionar('paginas/trens/trens.php');
        } catch (mysqli_sql_exception $e) {
            $mensagem = $e->getCode() == 1062
                ? 'Esse prefixo já está cadastrado.'
                : 'Erro ao salvar o trem. Verifique os dados e tente novamente.';
        }
    }
}

$titulo = $modoEdicao ? 'Editar trem' : 'Cadastrar trem';
$menuAtivo = 'paginas/trens/trens.php';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina"><?= h($titulo) ?></h1>
<p class="subtitulo">Preencha os dados do trem e selecione seu tipo para completar o cadastro.</p>

<?php if ($mensagem !== ''): ?>
    <p class="mensagem erro" role="alert"><?= h($mensagem) ?></p>
<?php endif; ?>

<form method="POST" class="painel">
    <input type="hidden" name="id_trem" value="<?= $idTrem ?>">

    <div class="form-grade">
        <div class="campo">
            <label for="prefixo">Prefixo</label>
            <input type="text" id="prefixo" name="prefixo" value="<?= h($trem['prefixo']) ?>" required>
        </div>

        <div class="campo">
            <label for="modelo">Modelo</label>
            <input type="text" id="modelo" name="modelo" value="<?= h($trem['modelo']) ?>" required>
        </div>

        <div class="campo">
            <label for="ano">Ano de fabricação</label>
            <input type="number" id="ano" name="ano" min="1" value="<?= h($trem['ano']) ?>" required>
        </div>

        <div class="campo">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <?php foreach (['ativo' => 'Ativo', 'manutencao' => 'Em manutenção', 'inativo' => 'Inativo'] as $valor => $rotulo): ?>
                    <option value="<?= $valor ?>" <?= $trem['status'] === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="capacidade">Capacidade (toneladas)</label>
            <input type="number" id="capacidade" name="capacidade" min="0" step="0.01" value="<?= h($trem['capacidade']) ?>" required>
        </div>

        <div class="campo">
            <label for="ultima_inspecao">Última inspeção</label>
            <input type="date" id="ultima_inspecao" name="ultima_inspecao" value="<?= h($trem['ultima_inspecao']) ?>">
        </div>

        <div class="campo">
            <label for="tipo_trem">Tipo de trem</label>
            <select id="tipo_trem" name="tipo_trem" required>
                <option value="Passageiro" <?= $trem['tipo_trem'] === 'Passageiro' ? 'selected' : '' ?>>Trem de passageiros</option>
                <option value="Carga" <?= $trem['tipo_trem'] === 'Carga' ? 'selected' : '' ?>>Trem de carga</option>
            </select>
        </div>

        <div class="campo" id="campo-assentos">
            <label for="quantidade_assentos">Quantidade de assentos</label>
            <input type="number" id="quantidade_assentos" name="quantidade_assentos" min="1" value="<?= h($trem['quantidade_assentos']) ?>">
        </div>

        <div class="campo" id="campo-carga">
            <label for="tipo_carga">Tipo de carga</label>
            <select id="tipo_carga" name="tipo_carga">
                <option value="">Selecione o tipo de carga</option>
                <?php foreach ($tiposCarga as $tipo): ?>
                    <option value="<?= h($tipo) ?>" <?= $trem['tipo_carga'] === $tipo ? 'selected' : '' ?>><?= h($tipo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="acoes">
        <a href="trens.php" class="botao-secundario">Cancelar</a>
        <button type="submit" class="botao">
            <i class="fas fa-check"></i>
            <?= $modoEdicao ? 'Salvar alterações' : 'Cadastrar trem' ?>
        </button>
    </div>
</form>

<script>
    // Mostra o campo de assentos (passageiros) ou de tipo de carga (carga)
    const tipoTrem = document.getElementById('tipo_trem');
    const campoAssentos = document.getElementById('campo-assentos');
    const campoCarga = document.getElementById('campo-carga');
    const quantidadeAssentos = document.getElementById('quantidade_assentos');
    const tipoCarga = document.getElementById('tipo_carga');

    function atualizarCampos() {
        const passageiro = tipoTrem.value === 'Passageiro';

        campoAssentos.hidden = !passageiro;
        campoCarga.hidden = passageiro;

        quantidadeAssentos.required = passageiro;
        tipoCarga.required = !passageiro;
    }

    tipoTrem.addEventListener('change', atualizarCampos);
    atualizarCampos();
</script>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
