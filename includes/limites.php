<?php

function classificar(string $tipo, float $valor): string
{
    $tipo = mb_strtolower(trim($tipo), 'UTF-8');

    // Valores máximos de referência para o simulador.
    if (strpos($tipo, 'temperatura') !== false) {
        $limite = 100;
    } elseif (strpos($tipo, 'velocidade') !== false) {
        $limite = 120;
    } elseif (
        strpos($tipo, 'consumo') !== false ||
        strpos($tipo, 'energia') !== false
    ) {
        $limite = 9;
    } elseif (strpos($tipo, 'vibra') !== false) {
        $limite = 100;
    } else {
        // Tipos sem limite definido não geram alertas.
        return 'normal';
    }

    if ($valor <= $limite) {
        return 'normal';
    }

    if ($valor <= $limite * 1.10) {
        return 'atencao';
    }

    return 'critico';
}
