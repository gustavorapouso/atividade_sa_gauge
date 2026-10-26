<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_pessoa'])) {
    header('Location: ../../data_base/autenticacaodb/login.php');
    exit;
}

// Somente o gestor pode editar usuários
if ((int) ($_SESSION['FK_id_perfil'] ?? 0) !== 2) {
    http_response_code(403);
    exit('Acesso negado. Somente gestores podem editar usuários.');
}

require '../../data_base/conexao.php';
require '../../data_base/includes/proteger.php';

// Verifica o envio do formulário
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

// Valida o token de segurança
$token = $_POST['token'] ?? '';

if (
    empty($_SESSION['token_usuarios']) ||
    !is_string($token) ||
    !hash_equals($_SESSION['token_usuarios'], $token)
) {
    exit('Solicitação inválida. Atualize a página e tente novamente.');
}

// Recebe os dados do formulário
$id_pessoa = filter_input(INPUT_POST, 'id_pessoa', FILTER_VALIDATE_INT);
$nome = trim($_POST['nome'] ?? '');
$data_nascimento = trim($_POST['data_nascimento'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');
$perfil = filter_input(INPUT_POST, 'FK_id_perfil', FILTER_VALIDATE_INT);

// Verifica os campos obrigatórios
if (
    !$id_pessoa ||
    $nome === '' ||
    $email === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !in_array($perfil, [1, 2, 3], true)
) {
    header('Location: usuarios.php?id=' . (int) $id_pessoa . '&erro=editar');
    exit;
}

// Valida a data de nascimento
if ($data_nascimento !== '') {
    $data = DateTime::createFromFormat('!Y-m-d', $data_nascimento);

    if (!$data || $data->format('Y-m-d') !== $data_nascimento) {
        header('Location: usuarios.php?id=' . $id_pessoa . '&erro=editar');
        exit;
    }
}

// Confere se o usuário existe
$sql_verifica = "SELECT id_pessoa FROM PESSOA WHERE id_pessoa = ?";
$stmt = $conexao->prepare($sql_verifica);
$stmt->bind_param('i', $id_pessoa);
$stmt->execute();

$existe = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$existe) {
    header('Location: usuarios.php?erro=editar');
    exit;
}

// Confere se o e-mail já pertence a outra pessoa
$sql_email = "SELECT id_pessoa
              FROM PESSOA
              WHERE email = ? AND id_pessoa <> ?";

$stmt = $conexao->prepare($sql_email);
$stmt->bind_param('si', $email, $id_pessoa);
$stmt->execute();

$email_existe = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($email_existe) {
    exit('Este e-mail já está sendo utilizado por outro usuário.');
}

// Confere se o perfil atual está sendo alterado
$sql_perfil = "SELECT FK_id_perfil
               FROM PESSOA
               WHERE id_pessoa = ?";

$stmt = $conexao->prepare($sql_perfil);
$stmt->bind_param('i', $id_pessoa);
$stmt->execute();

$usuario_atual = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario_atual) {
    header('Location: usuarios.php?erro=editar');
    exit;
}

// Se o usuário for gestor e estiver alterando o perfil,
// impede retirar o perfil do último gestor ativo.
if (
    (int) $usuario_atual['FK_id_perfil'] === 2 &&
    $perfil !== 2
) {
    $sql_gestores = "SELECT COUNT(*) AS total
                     FROM PESSOA
                     WHERE FK_id_perfil = 2 AND ativo = 1";

    $resultado_gestores = $conexao->query($sql_gestores);
    $total_gestores = (int) $resultado_gestores->fetch_assoc()['total'];

    $sql_ativo = "SELECT ativo FROM PESSOA WHERE id_pessoa = ?";
    $stmt = $conexao->prepare($sql_ativo);
    $stmt->bind_param('i', $id_pessoa);
    $stmt->execute();

    $ativo_atual = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (
        $ativo_atual &&
        (int) $ativo_atual['ativo'] === 1 &&
        $total_gestores <= 1
    ) {
        exit('Não é possível retirar o perfil do último gestor ativo.');
    }
}

// Prepara os valores opcionais para salvar como NULL
if ($data_nascimento === '') {
    $data_nascimento = null;
}

if ($telefone === '') {
    $telefone = null;
}

// Atualiza os dados do usuário
$sql_update = "UPDATE PESSOA
               SET nome = ?,
                   data_nascimento = ?,
                   telefone = ?,
                   email = ?,
                   FK_id_perfil = ?
               WHERE id_pessoa = ?";

$stmt = $conexao->prepare($sql_update);
$stmt->bind_param(
    'ssssii',
    $nome,
    $data_nascimento,
    $telefone,
    $email,
    $perfil,
    $id_pessoa
);

if ($stmt->execute()) {
    $stmt->close();

    header('Location: usuarios.php?id=' . $id_pessoa . '&sucesso=editar');
    exit;
}

$stmt->close();

header('Location: usuarios.php?id=' . $id_pessoa . '&erro=editar');
exit;
?>
