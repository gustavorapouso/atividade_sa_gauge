<?php
// Ativa ou desativa a conta de um usuário (formulário de usuarios.php).

require __DIR__ . '/../../includes/proteger.php';

exigirPapel([PERFIL_GESTOR]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('paginas/usuarios/usuarios.php');
}

// Valida o token de segurança
$token = $_POST['token'] ?? '';

if (empty($_SESSION['token_usuarios']) || !is_string($token) || !hash_equals($_SESSION['token_usuarios'], $token)) {
    exit('Solicitação inválida. Atualize a página e tente novamente.');
}

$idPessoa = filter_input(INPUT_POST, 'id_pessoa', FILTER_VALIDATE_INT);
$acao = $_POST['acao'] ?? 'desativar';

if (!$idPessoa || !in_array($acao, ['ativar', 'desativar'], true)) {
    redirecionar('paginas/usuarios/usuarios.php?erro=operacao');
}

// O gestor não pode desativar a própria conta
if ($acao === 'desativar' && $idPessoa === (int) $_SESSION['id_pessoa']) {
    exit('Você não pode desativar sua própria conta.');
}

// Erros que podem ser mostrados ao usuário
$errosConhecidos = [
    'Não é possível desativar o último gestor ativo.',
    'Usuário não encontrado.',
];

try {
    $conexao->begin_transaction();

    // Busca o usuário e bloqueia o registro durante a operação
    $stmt = $conexao->prepare('SELECT FK_id_perfil, ativo FROM pessoa WHERE id_pessoa = ? FOR UPDATE');
    $stmt->bind_param('i', $idPessoa);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        throw new Exception('Usuário não encontrado.');
    }

    $novoStatus = $acao === 'ativar' ? 1 : 0;

    // Se a conta já estiver no estado pedido, não há o que alterar
    if ((int) $usuario['ativo'] === $novoStatus) {
        $conexao->commit();
        redirecionar('paginas/usuarios/usuarios.php?id=' . $idPessoa);
    }

    // Impede desativar o último gestor ativo
    if ($acao === 'desativar' && (int) $usuario['FK_id_perfil'] === PERFIL_GESTOR) {
        $totalGestores = (int) $conexao
            ->query('SELECT COUNT(*) AS total FROM pessoa WHERE FK_id_perfil = 2 AND ativo = 1')
            ->fetch_assoc()['total'];

        if ($totalGestores <= 1) {
            throw new Exception('Não é possível desativar o último gestor ativo.');
        }
    }

    $stmt = $conexao->prepare('UPDATE pessoa SET ativo = ? WHERE id_pessoa = ?');
    $stmt->bind_param('ii', $novoStatus, $idPessoa);
    $stmt->execute();
    $stmt->close();

    $conexao->commit();
} catch (Throwable $e) {
    $conexao->rollback();

    // Registra o erro no log do PHP, sem exibir detalhes técnicos
    error_log('Erro na gestão de usuários: ' . $e->getMessage());

    if (in_array($e->getMessage(), $errosConhecidos, true)) {
        exit(h($e->getMessage()));
    }

    redirecionar('paginas/usuarios/usuarios.php?id=' . $idPessoa . '&erro=operacao');
}

redirecionar('paginas/usuarios/usuarios.php?id=' . $idPessoa . '&sucesso=' . $acao);
