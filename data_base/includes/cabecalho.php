<?php

session_start();

require 'includes/proteger.php';
require 'conexao.php';

include('permissao.php');

$perfil = $_SESSION['fk_id_perfil'] ?? null;


?>


