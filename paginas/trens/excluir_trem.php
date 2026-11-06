<?php
// Exclui um trem. Trens com sensores vinculados não podem ser
// excluídos (regra RESTRICT do banco); nesse caso, mostra um aviso.

require __DIR__ . '/../../includes/proteger.php';

$idTrem = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idTrem) {
    redirecionar('paginas/trens/trens.php');
}

try {
    $stmt = $conexao->prepare('DELETE FROM trem WHERE id_trem = ?');
    $stmt->bind_param('i', $idTrem);
    $excluiu = $stmt->execute();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    $excluiu = false;
}

redirecionar($excluiu ? 'paginas/trens/trens.php' : 'paginas/trens/trens.php?erro=excluir');
