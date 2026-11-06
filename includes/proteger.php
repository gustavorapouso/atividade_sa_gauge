<?php
// Protege as páginas que exigem login.
// Deve ser incluído no início de toda página restrita.

require_once __DIR__ . '/funcoes.php';
require_once __DIR__ . '/conexao.php';

if (!isset($_SESSION['id_pessoa'])) {
    redirecionar('index.php');
}

// Verifica se a conta continua ativa e com o mesmo perfil no banco
$stmt = $conexao->prepare(
    'SELECT ativo, FK_id_perfil
     FROM pessoa
     WHERE id_pessoa = ?'
);
$stmt->bind_param('i', $_SESSION['id_pessoa']);
$stmt->execute();

$usuarioLogado = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (
    !$usuarioLogado ||
    (int) $usuarioLogado['ativo'] !== 1 ||
    (int) $usuarioLogado['FK_id_perfil'] !== (int) $_SESSION['FK_id_perfil']
) {
    session_unset();
    session_destroy();

    redirecionar('index.php');
}
