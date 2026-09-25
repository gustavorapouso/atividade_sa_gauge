<?php

    require 'conexao.php';

    $nome = $_POST['nome'];
    $nascimento = $_POST['nascimento'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if(isset($_POST['perfil'])){
        $perfil = $_POST['perfil'];
    } else{
        echo "Erro: nenhum perfil selecionado";
    }

   
    if($senha === $confirmar_senha){
         // Proteger a senha -> IA
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    } else {
        echo "Erro: senha incorreta.";
    }
    

    $sql = "INSERT INTO PESSOA
        (nome, nascimento, email, telefone, senha, perfil)
        VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conexao->prepare($sql);  

    $stmt->bind_param("ssssss",
        $nome,
        $nascimento,
        $email,
        $telefone,
        $senha,
        $perfil
    );

    if ($stmt->execute()){
        echo "Cadastro realizado com sucesso.";
    } else {
        echo "Erro ao cadastrar: " . $stmt->error;
    }

    $stmt->close();
    $conexao->close();
?>