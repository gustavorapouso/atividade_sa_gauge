<?php

    require 'conexao.php';

    $nome = $_POST['nome'];
    $nascimento = $_POST['nascimento'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Proteger a senha -> IA

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "INSERT INTO PESSOA
        (nome, nascimento, email, telefone, senha, confirmar_senha, id_perfil)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

?>