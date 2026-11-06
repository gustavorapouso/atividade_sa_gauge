<?php
// Recuperação de senha em duas etapas:
// 1. o usuário informa o e-mail e recebe um código;
// 2. informa o código e a nova senha.
//
// SIMULAÇÃO ACADÊMICA: o código é exibido na tela em vez de ser enviado por e-mail.

require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../includes/conexao.php';

$mensagem = '';
$erro = '';
$codigoTeste = '';
$senhaRedefinida = false;
$acao = $_POST['acao'] ?? '';

// Remove os dados da recuperação guardados na sessão
function limparRecuperacao(): void
{
    unset(
        $_SESSION['recuperacao_id'],
        $_SESSION['recuperacao_email'],
        $_SESSION['recuperacao_codigo_hash'],
        $_SESSION['recuperacao_expira'],
        $_SESSION['recuperacao_tentativas']
    );
}

// Etapa 1: solicitar o código de recuperação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'solicitar') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        $stmt = $conexao->prepare('SELECT id_pessoa, email, ativo FROM pessoa WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$usuario || (int) $usuario['ativo'] !== 1) {
            $erro = 'Não foi possível solicitar a recuperação para este e-mail.';
        } else {
            // Código numérico de seis dígitos, guardado protegido na sessão por 10 minutos
            $codigo = (string) random_int(100000, 999999);

            $_SESSION['recuperacao_id']          = (int) $usuario['id_pessoa'];
            $_SESSION['recuperacao_email']       = $usuario['email'];
            $_SESSION['recuperacao_codigo_hash'] = password_hash($codigo, PASSWORD_DEFAULT);
            $_SESSION['recuperacao_expira']      = time() + 600;
            $_SESSION['recuperacao_tentativas']  = 0;

            $codigoTeste = $codigo;
            $mensagem = 'Código gerado! Ele é válido por 10 minutos.';
        }
    }
}

// Etapa 2: conferir o código e redefinir a senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'redefinir') {
    $codigoInformado = trim($_POST['codigo'] ?? '');
    $novaSenha       = $_POST['nova_senha'] ?? '';
    $confirmarSenha  = $_POST['confirmar_senha'] ?? '';

    if (!isset($_SESSION['recuperacao_id'], $_SESSION['recuperacao_codigo_hash'], $_SESSION['recuperacao_expira'], $_SESSION['recuperacao_tentativas'])) {
        $erro = 'Solicite um novo código de recuperação.';
    } elseif (time() > $_SESSION['recuperacao_expira']) {
        $erro = 'O código expirou. Solicite outro código.';
        limparRecuperacao();
    } elseif ($_SESSION['recuperacao_tentativas'] >= 5) {
        $erro = 'Limite de tentativas atingido. Solicite outro código.';
        limparRecuperacao();
    } else {
        $_SESSION['recuperacao_tentativas']++;

        if (!preg_match('/^[0-9]{6}$/', $codigoInformado) || !password_verify($codigoInformado, $_SESSION['recuperacao_codigo_hash'])) {
            $erro = 'Código incorreto. Confira e tente novamente.';
        } elseif (strlen($novaSenha) < 8) {
            $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
        } elseif ($novaSenha !== $confirmarSenha) {
            $erro = 'As senhas não coincidem.';
        } else {
            $idPessoa  = (int) $_SESSION['recuperacao_id'];
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

            // Só altera se a conta continuar ativa
            $stmt = $conexao->prepare('UPDATE pessoa SET senha = ? WHERE id_pessoa = ? AND ativo = 1');
            $stmt->bind_param('si', $senhaHash, $idPessoa);
            $stmt->execute();
            $alterou = $stmt->affected_rows === 1;
            $stmt->close();

            if ($alterou) {
                limparRecuperacao();
                $senhaRedefinida = true;
                $mensagem = 'Senha redefinida com sucesso! Você já pode entrar com a nova senha.';
            } else {
                $erro = 'Não foi possível redefinir a senha desta conta.';
            }
        }
    }
}

// Mostra a etapa 2 quando há um código válido na sessão
// (exceto quando a solicitação de um novo código acabou de falhar).
$mostrarRedefinicao = isset($_SESSION['recuperacao_codigo_hash'])
    && ($acao !== 'solicitar' || $codigoTeste !== '');

$titulo  = 'Recuperar senha';
$estilos = ['autenticacao'];
require __DIR__ . '/../../includes/head.php';
?>

<body class="tela-auth">

    <main class="cartao-auth">
        <img src="<?= url('assets/img/logo_arredondada.png') ?>" alt="Gauge" class="auth-logo">

        <h1>Recuperar senha</h1>

        <?php if ($mensagem !== ''): ?>
            <p class="mensagem sucesso" role="status"><?= h($mensagem) ?></p>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <p class="mensagem erro" role="alert"><?= h($erro) ?></p>
        <?php endif; ?>

        <?php if ($codigoTeste !== ''): ?>
            <div class="codigo-teste">
                <p>Em uma aplicação real, este código seria enviado ao e-mail. Nesta simulação, use:</p>
                <strong><?= h($codigoTeste) ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($mostrarRedefinicao): ?>

            <form method="POST" class="form-grade">
                <input type="hidden" name="acao" value="redefinir">

                <div class="campo campo-inteiro">
                    <label for="codigo">Código recebido</label>
                    <input type="text" id="codigo" name="codigo" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required>
                </div>

                <div class="campo campo-inteiro">
                    <label for="nova_senha">Nova senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" minlength="8" autocomplete="new-password" required>
                </div>

                <div class="campo campo-inteiro">
                    <label for="confirmar_senha">Confirmar nova senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="8" autocomplete="new-password" required>
                </div>

                <div class="acoes acoes-centro campo-inteiro">
                    <button type="submit" class="botao">Redefinir senha</button>
                </div>
            </form>

        <?php elseif (!$senhaRedefinida): ?>

            <p class="auth-subtitulo">Informe o e-mail cadastrado na sua conta para gerar um código de recuperação.</p>

            <form method="POST" class="form-grade">
                <input type="hidden" name="acao" value="solicitar">

                <div class="campo campo-inteiro">
                    <label for="email">E-mail cadastrado</label>
                    <input type="email" id="email" name="email" autocomplete="email" required>
                </div>

                <div class="acoes acoes-centro campo-inteiro">
                    <button type="submit" class="botao">Gerar código</button>
                </div>
            </form>

        <?php endif; ?>

        <a href="<?= url('index.php') ?>" class="link-auth">Voltar para o login</a>
    </main>

</body>

</html>
