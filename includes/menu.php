<?php
// Itens do menu lateral de acordo com o perfil do usuário.

require_once __DIR__ . '/funcoes.php';

function itensDoMenu(): array
{
    $todos = [PERFIL_CLIENTE, PERFIL_GESTOR, PERFIL_MAQUINISTA];
    $equipe = [PERFIL_GESTOR, PERFIL_MAQUINISTA];

    // [texto, endereço, ícone, perfis que podem ver]
    $itens = [
        ['Painel',               painelDoUsuario(),                          'fa-house',               $todos],
        ['Perfil',               'paginas/usuarios/perfil.php',               'fa-user',                $todos],

        ['Trens',                'paginas/trens/trens.php',                   'fa-train',               $equipe],
        ['Trilhos',              'paginas/trilhos/trilhos.php',               'fa-road',                $equipe],
        ['Viagens',              'paginas/viagens/viagens.php',               'fa-route',               $equipe],
        ['Sensores',             'paginas/sensores/sensores.php',             'fa-microchip',           $equipe],
        ['Leituras',             'paginas/sensores/leituras.php',             'fa-gauge-high',          $equipe],
        ['Simulador',            'paginas/sensores/simulador.php',            'fa-flask',               $equipe],
        ['Alertas',              'paginas/sensores/alertas.php',              'fa-triangle-exclamation', $equipe],
        ['Acompanhamento',       'paginas/viagens/acompanhamento_carga.php',  'fa-map-location-dot',    $equipe],
        ['Relatórios',           'paginas/relatorios/gerar.php',              'fa-chart-column',        $equipe],
        ['Usuários',             'paginas/usuarios/usuarios.php',             'fa-users',               [PERFIL_GESTOR]],

        ['Trens de carga',       'paginas/trens/trens_carga.php',             'fa-box',                 [PERFIL_CLIENTE]],
        ['Trens de passageiros', 'paginas/trens/trens_passageiro.php',        'fa-train-subway',        [PERFIL_CLIENTE]],

        ['Suporte',              'paginas/suporte/suporte.php',               'fa-headset',             $todos],
        ['Sair',                 'paginas/autenticacao/sair.php',             'fa-right-from-bracket',  $todos],
    ];

    $visiveis = [];

    foreach ($itens as [$texto, $endereco, $icone, $perfis]) {
        if (temPapel($perfis)) {
            $visiveis[] = ['texto' => $texto, 'url' => $endereco, 'icone' => $icone];
        }
    }

    return $visiveis;
}

// Indica se o item do menu corresponde à página aberta.
function menuEstaAtivo(string $endereco, ?string $menuAtivo): bool
{
    $paginaAtual = $menuAtivo ?? $_SERVER['SCRIPT_NAME'] ?? '';

    return str_ends_with($paginaAtual, $endereco);
}
