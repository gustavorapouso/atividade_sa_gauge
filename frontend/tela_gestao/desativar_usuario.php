<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_pessoa'])) {
    header('Location: ../../data_base/autenticacaodb/login.php');
    exit;
}

// Somente gestores podem ativar ou desativar contas
if ((int) ($_SESSION['FK_id_perfil'] ?? 0) !== 2) {
    http_response_code(403);
    exit('Acesso negado. Somente gestores podem gerenciar contas.');
}

require '../../data_base/conexao.php';
require '../../data_base/includes/proteger.php';

// Aceita somente requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

// Valida o token de segurança
$token = $_POST['token'] ?? '';

if (
    !isset($_SESSION['token_usuarios']) ||
    !is_string($token) ||
    !hash_equals($_SESSION['token_usuarios'], $token)
) {
    exit('Solicitação inválida. Atualize a página e tente novamente.');
}

// Recebe o ID do usuário e a ação
$id_pessoa = filter_input(INPUT_POST, 'id_pessoa', FILTER_VALIDATE_INT);
$acao = $_POST['acao'] ?? 'desativar';

if (!$id_pessoa || !in_array($acao, ['ativar', 'desativar'], true)) {
    header('Location: usuarios.php?erro=operacao');
    exit;
}

// Impede que o gestor desative ou reative a própria conta
if (
    $acao === 'desativar' &&
    (int) $id_pessoa === (int) $_SESSION['id_pessoa']
) {
    exit('Você não pode desativar sua própria conta.');
}

try {
    $conexao->begin_transaction();

    // Busca o usuário e bloqueia seu registro durante a operação
    $sql = "SELECT id_pessoa, FK_id_perfil, ativo
            FROM PESSOA
            WHERE id_pessoa = ?
            FOR UPDATE";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('i', $id_pessoa);
    $stmt->execute();

    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        throw new Exception('Usuário não encontrado.');
    }

    // Se a conta já estiver no estado solicitado, não precisa alterar
    if (
        ($acao === 'desativar' && (int) $usuario['ativo'] === 0) ||
        ($acao === 'ativar' && (int) $usuario['ativo'] === 1)
    ) {
        $conexao->commit();

        header('Location: usuarios.php?id=' . $id_pessoa);
        exit;
    }

    // Impede desativar o último gestor ativo
    if (
        $acao === 'desativar' &&
        (int) $usuario['FK_id_perfil'] === 2 &&
        (int) $usuario['ativo'] === 1
    ) {
        $sql_gestores = "SELECT COUNT(*) AS total
                         FROM PESSOA
                         WHERE FK_id_perfil = 2
                         AND ativo = 1";

        $resultado = $conexao->query($sql_gestores);
        $total_gestores = (int) $resultado->fetch_assoc()['total'];

        if ($total_gestores <= 1) {
            throw new Exception(
                'Não é possível desativar o último gestor ativo.'
            );
        }
    }

    // Define o novo estado da conta
    $novo_status = ($acao === 'ativar') ? 1 : 0;

    $sql_update = "UPDATE PESSOA
                   SET ativo = ?
                   WHERE id_pessoa = ?";

    $stmt = $conexao->prepare($sql_update);
    $stmt->bind_param('ii', $novo_status, $id_pessoa);
    $stmt->execute();
    $stmt->close();

    $conexao->commit();

    // Retorna à página de usuários com mensagem de sucesso
    header(
        'Location: usuarios.php?id=' . $id_pessoa .
        '&sucesso=' . $acao
    );
    exit;

} catch (Throwable $e) {
    $conexao->rollback();

    // Registra o erro no log do PHP, sem exibir detalhes ao usuário
    error_log('Erro na gestão de usuários: ' . $e->getMessage());

    if (
        $e instanceof Exception &&
        in_array(
            $e->getMessage(),
            [
                'Não é possível desativar o último gestor ativo.',
                'Não é possível desativar sua própria conta.',
                'Usuário não encontrado.'
            ],
            true
        )
    ) {
        exit(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }

    header('Location: usuarios.php?id=' . $id_pessoa . '&erro=operacao');
    exit;
}
?>
