<?php

session_start();

require '../../data_base/conexao.php';


// Verifica se recebeu o ID
if (!isset($_GET['id']) || empty($_GET['id'])) {

    header('Location: trem_cadastrados.php');
    exit;
}


$id_trem = intval($_GET['id']);


// Exclui o trem
$sql = "DELETE FROM trem WHERE id_trem = ?";

$stmt = $conexao->prepare($sql);


// Verifica se conseguiu preparar
if (!$stmt) {

    die(
        "Erro ao preparar exclusão: " .
        $conexao->error
    );
}


$stmt->bind_param(
    "i",
    $id_trem
);


// Executa
if ($stmt->execute()) {

    $stmt->close();

    // Volta para a lista
    header('Location: trem_cadastrados.php');
    exit;

} else {

    $erro = $stmt->error;

    $stmt->close();

    echo "<h2>Não foi possível excluir o trem.</h2>";
    echo "<p>Erro: " . htmlspecialchars($erro) . "</p>";
    echo "<br>";
    echo '<a href="trem_cadastrados.php">Voltar para a lista</a>';
}