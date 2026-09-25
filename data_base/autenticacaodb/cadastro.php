<?php

    require '../conexao.php';

    $nome = $_POST['nome'];
    $data_nascimento = $_POST['data_nascimento'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $FK_id_perfil = $_POST['FK_id_perfil'];


    if(isset($_POST['FK_id_perfil'])){
        $FK_id_perfil = $_POST['FK_id_perfil'];
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
        (nome, data_nascimento, email, telefone, senha, FK_id_perfil)
        VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conexao->prepare($sql);  

    $stmt->bind_param("ssssss",
        $nome,
        $data_nascimento,
        $email,
        $telefone,
        $senha_hash,
        $FK_id_perfil
    );

    if ($stmt->execute()){
        echo "Cadastro realizado com sucesso.";

        header("Location: login.html");
    } else {
        echo "Erro ao cadastrar: " . $stmt->error;
    }

    $stmt->close();
    $conexao->close();
?>