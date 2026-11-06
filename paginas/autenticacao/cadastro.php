<?php
// Cadastro de novo usuário. O formulário envia os dados para esta mesma página.

require_once __DIR__ . '/../../includes/funcoes.php';

$erro = '';
$dados = [
    'nome'            => '',
    'data_nascimento' => '',
    'email'           => '',
    'telefone'        => '',
    'FK_id_perfil'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/conexao.php';

    foreach ($dados as $campo => $valor) {
        $dados[$campo] = trim($_POST[$campo] ?? '');
    }

    $senha           = $_POST['senha'] ?? '';
    $confirmarSenha  = $_POST['confirmar_senha'] ?? '';
    $perfil          = (int) $dados['FK_id_perfil'];

    if ($dados['nome'] === '' || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe o nome e um e-mail válido.';
    } elseif (!in_array($perfil, [PERFIL_CLIENTE, PERFIL_GESTOR, PERFIL_MAQUINISTA], true)) {
        $erro = 'Selecione um perfil.';
    } elseif ($senha === '' || $senha !== $confirmarSenha) {
        $erro = 'As senhas não coincidem.';
    } else {
        // Protege a senha antes de salvar
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $conexao->prepare(
            'INSERT INTO pessoa (nome, data_nascimento, email, telefone, senha, FK_id_perfil)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $dataNascimento = $dados['data_nascimento'] !== '' ? $dados['data_nascimento'] : null;

        $stmt->bind_param(
            'sssssi',
            $dados['nome'],
            $dataNascimento,
            $dados['email'],
            $dados['telefone'],
            $senhaHash,
            $perfil
        );

        try {
            $stmt->execute();
            $stmt->close();
            redirecionar('index.php');
        } catch (mysqli_sql_exception $e) {
            $erro = 'Não foi possível concluir o cadastro. Verifique os dados e tente novamente.';
        }
    }
}

$titulo  = 'Cadastro';
$estilos = ['autenticacao'];
require __DIR__ . '/../../includes/head.php';
?>

<body class="tela-auth">

    <main class="cartao-auth cartao-auth-largo">
        <h1>Cadastre-se</h1>

        <i class="fas fa-circle-user avatar"></i>

        <?php if ($erro !== ''): ?>
            <p class="mensagem erro" role="alert"><?= h($erro) ?></p>
        <?php endif; ?>

        <form method="POST" class="form-grade">
            <div class="campo campo-inteiro">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome" value="<?= h($dados['nome']) ?>" required>
            </div>

            <div class="campo">
                <label for="data_nascimento">Data de nascimento</label>
                <input type="date" id="data_nascimento" name="data_nascimento" value="<?= h($dados['data_nascimento']) ?>">
            </div>

            <div class="campo">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone" value="<?= h($dados['telefone']) ?>">
            </div>

            <div class="campo campo-inteiro">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?= h($dados['email']) ?>" required>
            </div>

            <div class="campo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" autocomplete="new-password" required>
            </div>

            <div class="campo">
                <label for="confirmar_senha">Confirmar senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" autocomplete="new-password" required>
            </div>

            <div class="campo campo-inteiro">
                <label for="FK_id_perfil">Perfil</label>
                <select id="FK_id_perfil" name="FK_id_perfil" required>
                    <option value="">Selecione o perfil</option>
                    <?php foreach ([PERFIL_CLIENTE => 'Cliente', PERFIL_GESTOR => 'Gestor', PERFIL_MAQUINISTA => 'Maquinista'] as $id => $nome): ?>
                        <option value="<?= $id ?>" <?= (int) $dados['FK_id_perfil'] === $id ? 'selected' : '' ?>>
                            <?= $nome ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="acoes acoes-centro campo-inteiro">
                <button type="submit" class="botao">Salvar</button>
            </div>
        </form>

        <a href="<?= url('index.php') ?>" class="link-auth">Voltar ao login</a>
    </main>

</body>

</html>
