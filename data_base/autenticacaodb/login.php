<?php

session_start();

require 'conexao.php';

$nome = $_POST['nome'];
$senha = $_POST['senha'];

// Verificação do Login com ajuda da IA
$sql = "SELECT
            id_pessoa,
            nome,
            email,
            senha,
            fk_id_perfil
        FROM PESSOA
        WHERE nome = ?";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $nome);

    $stmt->execute();

    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc(); 
    
    if ($usuario && password_verify($senha, $usuario['senha'])) {
    
    $_SESSION['id_pessoa'] = $usuario['id_pessoa'];
    $_SESSION['nome'] = $usuario['nome'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['id_perfil'] = $usuario['fk_id_perfil'];

    header("Location: dashboard.php");
    exit;

    } else {

        echo "E-mail ou senha incorretos.";

    }


    $stmt->close();
    $conexao->close();



?>