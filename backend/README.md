<?php

$servidor = 'localhost';
$usuario = 'root';
$senha = 'root';
$banco = 'frota_ferroviaria';

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die('Falha na conexão: ' . $conexao->connect_error);
}

$conexao->set_charset('utf8mb4');
