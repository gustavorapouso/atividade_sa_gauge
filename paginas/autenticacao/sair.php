<?php
// Encerra a sessão e volta para o login.

require_once __DIR__ . '/../../includes/funcoes.php';

session_unset();
session_destroy();

redirecionar('index.php');
