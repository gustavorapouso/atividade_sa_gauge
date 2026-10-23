<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../conexao.php';

if (!isset($_SESSION['id_pessoa'])) {
    header('Location: ../../data_base/autenticacaodb/login.php');
    exit;
}

// Verifica se a conta continua ativa no banco
$sql = "SELECT ativo, FK_id_perfil
        FROM PESSOA
        WHERE id_pessoa = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param('i', $_SESSION['id_pessoa']);
$stmt->execute();

$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (
    !$usuario ||
    (int) $usuario['ativo'] !== 1 ||
    (int) $usuario['FK_id_perfil'] !== (int) $_SESSION['FK_id_perfil']
) {
    session_unset();
    session_destroy();

    header('Location: ../../data_base/autenticacaodb/login.php');
    exit;
}
?>

