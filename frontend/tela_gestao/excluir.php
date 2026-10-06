<?php

require '../../data_base/conexao.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Sensor não informado.");
}

$stmt = $conexao->prepare(
    "DELETE FROM sensores WHERE id_sensores = ?"
);

$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    header("Location: sensores.php");
    exit;

} else {

    echo "Erro ao excluir sensor: " . $stmt->error;

}

?>