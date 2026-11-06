<?php
// Tela de login. O formulário envia os dados para esta mesma página.

require_once __DIR__ . '/includes/funcoes.php';

// Usuário já logado vai direto para o painel
if (isset($_SESSION['id_pessoa'])) {
    redirecionar(painelDoUsuario());
}

$erro = '';
$nome = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/conexao.php';

    $nome  = trim($_POST['nome'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $conexao->prepare(
        'SELECT id_pessoa, nome, email, senha, FK_id_perfil, ativo
         FROM PESSOA
         WHERE nome = ?'
    );
    $stmt->bind_param('s', $nome);
    $stmt->execute();

    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        $erro = 'Nome ou senha incorretos.';
    } elseif ((int) $usuario['ativo'] !== 1) {
        $erro = 'Esta conta está desativada.';
    } else {
        // Proteção da sessão
        session_regenerate_id(true);

        $_SESSION['id_pessoa']    = $usuario['id_pessoa'];
        $_SESSION['nome']         = $usuario['nome'];
        $_SESSION['email']        = $usuario['email'];
        $_SESSION['FK_id_perfil'] = $usuario['FK_id_perfil'];

        $painel = painelDoUsuario();

        if ($painel === 'index.php') {
            session_unset();
            session_destroy();
            $erro = 'Perfil de usuário inválido.';
        } else {
            redirecionar($painel);
        }
    }
}

$titulo  = 'Login';
$estilos = ['autenticacao'];
require __DIR__ . '/includes/head.php';
?>

<body class="tela-login">

    <div class="login-imagem">
        <img src="<?= url('assets/img/tela_cadastrar_img.png') ?>" alt="">
    </div>

    <main class="cartao-auth">
        <h1>Login</h1>
        <p class="auth-subtitulo">Bem-vindo!</p>

        <img src="<?= url('assets/img/logo_gauge_menor.png') ?>" alt="Gauge" class="auth-logo">

        <?php if ($erro !== ''): ?>
            <p class="mensagem erro" role="alert"><?= h($erro) ?></p>
        <?php endif; ?>

        <form method="POST" class="form-auth">
            <label class="campo-icone">
                <i class="fas fa-user"></i>
                <input type="text" name="nome" placeholder="Nome" value="<?= h($nome) ?>" autocomplete="username" required>
            </label>

            <label class="campo-icone">
                <i class="fas fa-lock"></i>
                <input type="password" name="senha" placeholder="Senha" autocomplete="current-password" required>
            </label>

            <a href="<?= url('paginas/autenticacao/recuperar_senha.php') ?>" class="link-auth">
                Esqueceu sua senha?
            </a>

            <button type="submit" class="botao">Entrar</button>
        </form>

        <div class="auth-rodape">
            <a href="<?= url('paginas/autenticacao/cadastro.php') ?>" class="link-auth">Cadastrar-se</a>

            <div class="redes" aria-hidden="true">
                <i class="fab fa-google"></i>
                <i class="fab fa-facebook"></i>
                <i class="fab fa-linkedin"></i>
            </div>
        </div>
    </main>

</body>

</html>
