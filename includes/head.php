<?php
// Conteúdo do <head> comum a todas as páginas.
// Variáveis opcionais definidas pela página antes de incluir:
//   $titulo   -> título da aba do navegador
//   $estilos  -> lista de CSS extras de assets/css (sem ".css")
//   $usarMapa -> true para carregar o Leaflet (mapas)

require_once __DIR__ . '/funcoes.php';

$titulo   = $titulo ?? 'Gauge';
$estilos  = $estilos ?? [];
$usarMapa = $usarMapa ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= h($titulo) ?> | Gauge</title>

    <link rel="icon" href="<?= url('assets/img/logo_arredondada.png') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <?php if ($usarMapa): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <?php endif; ?>

    <link rel="stylesheet" href="<?= url('assets/css/base.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/layout.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/componentes.css') ?>">

    <?php foreach ($estilos as $estilo): ?>
        <link rel="stylesheet" href="<?= url('assets/css/' . $estilo . '.css') ?>">
    <?php endforeach; ?>
</head>
