<?php
// Exclui uma viagem e volta para a lista.

require __DIR__ . '/../../includes/proteger.php';

$idViagem = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($idViagem) {
    $stmt = $conexao->prepare('DELETE FROM viagem WHERE id_viagem = ?');
    $stmt->bind_param('i', $idViagem);
    $stmt->execute();
    $stmt->close();
}

redirecionar('paginas/viagens/viagens.php');
