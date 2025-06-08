<?php


function limpiar($texto) {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function resumen($texto, $limite = 100) {
    if (strlen($texto) <= $limite) return $texto;
    return substr($texto, 0, $limite) . '...';
}

function formatear_fecha($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}
