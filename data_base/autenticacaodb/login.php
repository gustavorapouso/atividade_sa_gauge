<?php

session_start();

require '../conexao.php';

$nome = trim($_POST['nome'] ?? '');
$senha = $_POST['senha'] ?? '';

// Verificação do Login com ajuda da IA
$sql = "SELECT
            id_pessoa,
            nome,
            email,
            senha,
            FK_id_perfil,
            ativo
        FROM PESSOA
        WHERE nome = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("s", $nome);

$stmt->execute();

$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

// Verifica se o usuário existe e se a senha está correta
if ($usuario && password_verify($senha, $usuario['senha'])) {

    // Verifica se o usuário está ativo
    if ((int) $usuario['ativo'] !== 1) {
        $stmt->close();
        $conexao->close();

        echo "Esta conta está desativada.";
        exit;
    }

    // Proteção da sessão
    session_regenerate_id(true);

    $_SESSION['id_pessoa'] = $usuario['id_pessoa'];
    $_SESSION['nome'] = $usuario['nome'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['FK_id_perfil'] = $usuario['FK_id_perfil'];

    $perfil = (int) $usuario['FK_id_perfil'];

    $stmt->close();
    $conexao->close();

    // CLIENTE
    if ($perfil === 1) {

        header("Location: ../../frontend/tela_principal/painel_cliente.php");
        exit;

    }

    // GESTOR
    elseif ($perfil === 2) {

        header("Location: ../../frontend/tela_principal/painel_gestor.php");
        exit;

    }

    // MAQUINISTA
    elseif ($perfil === 3) {

        header("Location: ../../frontend/tela_principal/painel_maquinista.php");
        exit;

    } else {

        session_unset();
        session_destroy();

        echo "Perfil de usuário inválido.";
        exit;

    }

} else {

    echo "E-mail ou senha incorretos.";

}

$stmt->close();
$conexao->close();

?>
