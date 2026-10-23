<?php

session_start();

require '../../data_base/conexao.php';

$mensagem = '';
$erro = '';
$codigo_teste = '';

// Etapa 1: solicitar o código de recuperação
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['acao'] ?? '') === 'solicitar') {

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {

        // Busca o usuário pelo e-mail cadastrado
        $sql = "SELECT id_pessoa, email, ativo
                FROM PESSOA
                WHERE email = ?";

        $stmt = $conexao->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$usuario || (int) $usuario['ativo'] !== 1) {
            $erro = 'Não foi possível solicitar a recuperação para este e-mail.';
        } else {

            // Gera um código numérico de seis dígitos
            $codigo = (string) random_int(100000, 999999);

            // Guarda o código protegido na sessão por 10 minutos
            $_SESSION['recuperacao_id'] = (int) $usuario['id_pessoa'];
            $_SESSION['recuperacao_email'] = $usuario['email'];
            $_SESSION['recuperacao_codigo_hash'] = password_hash(
                $codigo,
                PASSWORD_DEFAULT
            );
            $_SESSION['recuperacao_expira'] = time() + 600;
            $_SESSION['recuperacao_tentativas'] = 0;

            // SIMULAÇÃO ACADÊMICA: o código é exibido na tela
            $codigo_teste = $codigo;

            $mensagem = 'Código gerado! Ele é válido por 10 minutos.';
        }
    }
}

// Etapa 2: conferir o código e redefinir a senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['acao'] ?? '') === 'redefinir') {

    $codigo_informado = trim($_POST['codigo'] ?? '');
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (
        !isset(
            $_SESSION['recuperacao_id'],
            $_SESSION['recuperacao_codigo_hash'],
            $_SESSION['recuperacao_expira'],
            $_SESSION['recuperacao_tentativas']
        )
    ) {
        $erro = 'Solicite um novo código de recuperação.';
    } elseif (time() > $_SESSION['recuperacao_expira']) {
        $erro = 'O código expirou. Solicite outro código.';
        unset(
            $_SESSION['recuperacao_id'],
            $_SESSION['recuperacao_email'],
            $_SESSION['recuperacao_codigo_hash'],
            $_SESSION['recuperacao_expira'],
            $_SESSION['recuperacao_tentativas']
        );
    } elseif ($_SESSION['recuperacao_tentativas'] >= 5) {
        $erro = 'Limite de tentativas atingido. Solicite outro código.';
        unset(
            $_SESSION['recuperacao_id'],
            $_SESSION['recuperacao_email'],
            $_SESSION['recuperacao_codigo_hash'],
            $_SESSION['recuperacao_expira'],
            $_SESSION['recuperacao_tentativas']
        );
    } else {

        $_SESSION['recuperacao_tentativas']++;

        if (
            !preg_match('/^[0-9]{6}$/', $codigo_informado) ||
            !password_verify(
                $codigo_informado,
                $_SESSION['recuperacao_codigo_hash']
            )
        ) {
            $erro = 'Código incorreto. Confira e tente novamente.';
        } elseif (strlen($nova_senha) < 8) {
            $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
        } elseif ($nova_senha !== $confirmar_senha) {
            $erro = 'As senhas não coincidem.';
        } else {

            // Confere novamente se a conta continua ativa
            $id_pessoa = (int) $_SESSION['recuperacao_id'];

            $sql = "SELECT ativo
                    FROM PESSOA
                    WHERE id_pessoa = ?";

            $stmt = $conexao->prepare($sql);
            $stmt->bind_param('i', $id_pessoa);
            $stmt->execute();

            $usuario = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$usuario || (int) $usuario['ativo'] !== 1) {
                $erro = 'Não foi possível redefinir a senha desta conta.';
            } else {

                // Protege a nova senha antes de salvar
                $senha_hash = password_hash(
                    $nova_senha,
                    PASSWORD_DEFAULT
                );

                $sql = "UPDATE PESSOA
                        SET senha = ?
                        WHERE id_pessoa = ? AND ativo = 1";

                $stmt = $conexao->prepare($sql);
                $stmt->bind_param('si', $senha_hash, $id_pessoa);
                $stmt->execute();

                if ($stmt->affected_rows === 1) {
                    $stmt->close();

                    unset(
                        $_SESSION['recuperacao_id'],
                        $_SESSION['recuperacao_email'],
                        $_SESSION['recuperacao_codigo_hash'],
                        $_SESSION['recuperacao_expira'],
                        $_SESSION['recuperacao_tentativas']
                    );

                    $mensagem = 'Senha redefinida com sucesso! Você já pode entrar com a nova senha.';
                } else {
                    $stmt->close();
                    $erro = 'Não foi possível alterar a senha. Tente novamente.';
                }
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
    <title>Recuperação de senha | Gauge</title>

    <link rel="stylesheet" href="usuarios.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap"
          rel="stylesheet">
</head>

<body>

    <div class="container">

        <header class="header">
            <h1 class="titulo-pagina">Recuperar senha</h1>
        </header>

        <?php if ($mensagem !== ''): ?>
            <p role="status">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <p role="alert">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if ($codigo_teste !== ''): ?>
            <section>
                <h2>Código de teste</h2>
                <p>
                    Em uma aplicação real, este código seria enviado ao e-mail.
                    Nesta simulação, use o código abaixo:
                </p>

                <p>
                    <strong>
                        <?= htmlspecialchars($codigo_teste, ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                </p>
            </section>
        <?php endif; ?>

        <?php if (
            isset($_SESSION['recuperacao_codigo_hash']) &&
            !isset($_POST['acao']) ||
            (
                ($_POST['acao'] ?? '') === 'solicitar' &&
                $codigo_teste !== ''
            ) ||
            (
                ($_POST['acao'] ?? '') === 'redefinir' &&
                isset($_SESSION['recuperacao_codigo_hash'])
            )
        ): ?>

            <form method="POST" action="recupera_senha.php">

                <input type="hidden" name="acao" value="redefinir">

                <div class="grupo-campo">
                    <label for="codigo">Código recebido</label>
                    <input
                        type="text"
                        id="codigo"
                        name="codigo"
                        class="input-perfil-campo"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        required
                    >
                </div>

                <div class="grupo-campo">
                    <label for="nova_senha">Nova senha</label>
                    <input
                        type="password"
                        id="nova_senha"
                        name="nova_senha"
                        class="input-perfil-campo"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="grupo-campo">
                    <label for="confirmar_senha">Confirmar nova senha</label>
                    <input
                        type="password"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        class="input-perfil-campo"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="centro-botao-excluir">
                    <button type="submit" class="botao-excluir">
                        Redefinir senha
                    </button>
                </div>

            </form>

        <?php elseif ($mensagem !== 'Senha redefinida com sucesso! Você já pode entrar com a nova senha.'): ?>

            <form method="POST" action="recupera_senha.php">

                <input type="hidden" name="acao" value="solicitar">

                <div class="grupo-campo">
                    <label for="email">E-mail cadastrado</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="input-perfil-campo"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="centro-botao-excluir">
                    <button type="submit" class="botao-excluir">
                        Gerar código
                    </button>
                </div>

            </form>

        <?php endif; ?>

        <p>
            <a href="../../data_base/autenticacaodb/login.php">
                Voltar para o login
            </a>
        </p>

    </div>

</body>
</html>
