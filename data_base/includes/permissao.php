<?php

function temPapel($papeis_permitidos)
{
    return in_array($_SESSION['FK_id_perfil'], $papeis_permitidos);
} 
