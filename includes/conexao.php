<?php
// Conexão com o banco de dados (MySQLi orientado a objetos).
// Ajuste os dados abaixo conforme a configuração do MySQL no computador.

$servidor = 'localhost';
$usuario  = 'root';
$senha    = '';
$banco    = 'ferrorama_gauge';

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die('Falha na conexão: ' . $conexao->connect_error);
}

$conexao->set_charset('utf8mb4');

// Remove as variáveis de configuração para não conflitarem
// com variáveis de mesmo nome usadas nas páginas (ex.: $senha).
unset($servidor, $usuario, $senha, $banco);
