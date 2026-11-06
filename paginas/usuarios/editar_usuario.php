<?php
// Salva as alterações feitas em um usuário (formulário de usuarios.php).

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

// Dados do formulário
$idPessoa       = filter_input(INPUT_POST, 'id_pessoa', FILTER_VALIDATE_INT);
$nome           = trim($_POST['nome'] ?? '');
$dataNascimento = trim($_POST['data_nascimento'] ?? '');
$telefone       = trim($_POST['telefone'] ?? '');
$email          = trim($_POST['email'] ?? '');
$perfil         = filter_input(INPUT_POST, 'FK_id_perfil', FILTER_VALIDATE_INT);

$voltarComErro = 'paginas/usuarios/usuarios.php?id=' . (int) $idPessoa . '&erro=editar';

// Campos obrigatórios
if (
    !$idPessoa ||
    $nome === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !in_array($perfil, [PERFIL_CLIENTE, PERFIL_GESTOR, PERFIL_MAQUINISTA], true)
) {
    redirecionar($voltarComErro);
}

// Data de nascimento (opcional)
if ($dataNascimento !== '') {
    $data = DateTime::createFromFormat('!Y-m-d', $dataNascimento);

    if (!$data || $data->format('Y-m-d') !== $dataNascimento) {
        redirecionar($voltarComErro);
    }
}

// Busca o usuário que será alterado
$stmt = $conexao->prepare('SELECT FK_id_perfil, ativo FROM pessoa WHERE id_pessoa = ?');
$stmt->bind_param('i', $idPessoa);
$stmt->execute();
$usuarioAtual = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuarioAtual) {
    redirecionar('paginas/usuarios/usuarios.php?erro=editar');
}

// O e-mail não pode pertencer a outra pessoa
$stmt = $conexao->prepare('SELECT id_pessoa FROM pessoa WHERE email = ? AND id_pessoa <> ?');
$stmt->bind_param('si', $email, $idPessoa);
$stmt->execute();
$emailEmUso = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($emailEmUso) {
    exit('Este e-mail já está sendo utilizado por outro usuário.');
}

// Impede retirar o perfil do último gestor ativo
if ((int) $usuarioAtual['FK_id_perfil'] === PERFIL_GESTOR && $perfil !== PERFIL_GESTOR && (int) $usuarioAtual['ativo'] === 1) {
    $totalGestores = (int) $conexao
        ->query('SELECT COUNT(*) AS total FROM pessoa WHERE FK_id_perfil = 2 AND ativo = 1')
        ->fetch_assoc()['total'];

    if ($totalGestores <= 1) {
        exit('Não é possível retirar o perfil do último gestor ativo.');
    }
}

// Campos opcionais vazios são salvos como NULL
$dataNascimento = $dataNascimento !== '' ? $dataNascimento : null;
$telefone       = $telefone !== '' ? $telefone : null;

$stmt = $conexao->prepare(
    'UPDATE pessoa
     SET nome = ?, data_nascimento = ?, telefone = ?, email = ?, FK_id_perfil = ?
     WHERE id_pessoa = ?'
);
$stmt->bind_param('ssssii', $nome, $dataNascimento, $telefone, $email, $perfil, $idPessoa);
$salvou = $stmt->execute();
$stmt->close();

redirecionar($salvou
    ? 'paginas/usuarios/usuarios.php?id=' . $idPessoa . '&sucesso=editar'
    : $voltarComErro);
