<?php

session_start();

if (!isset($_SESSION['id_pessoa'])) {
    header('Location: login.php');
    exit;
}