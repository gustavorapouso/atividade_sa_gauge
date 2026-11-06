<?php
// Funções auxiliares usadas em várias páginas do sistema.

// Perfis de acesso (tabela perfil)
const PERFIL_CLIENTE    = 1;
const PERFIL_GESTOR     = 2;
const PERFIL_MAQUINISTA = 3;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Endereço base do projeto (ex.: "/atividade_sa_gauge/").
// É calculado automaticamente para que os links funcionem
// mesmo que a pasta do projeto tenha outro nome.
if (!defined('BASE_URL')) {
    $raizProjeto = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $raizServidor = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');

    $base = ($raizServidor !== '' && stripos($raizProjeto, $raizServidor) === 0)
        ? substr($raizProjeto, strlen($raizServidor))
        : '';

    define('BASE_URL', rtrim($base, '/') . '/');
}

// Monta um endereço a partir da raiz do projeto.
// Ex.: url('paginas/trens/trens.php')
function url(string $caminho = ''): string
{
    return BASE_URL . ltrim($caminho, '/');
}

// Redireciona para outra página do projeto e encerra o script.
function redirecionar(string $caminho): void
{
    header('Location: ' . url($caminho));
    exit;
}

// Escapa um valor antes de exibi-lo no HTML.
function h($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

// Formata uma data do banco. Retorna $vazio quando não houver data.
function formatarData($valor, string $formato = 'd/m/Y H:i', string $vazio = 'Não informada'): string
{
    if (empty($valor)) {
        return $vazio;
    }

    $timestamp = strtotime($valor);

    return $timestamp ? date($formato, $timestamp) : $vazio;
}

// Verifica se o usuário logado possui um dos perfis informados.
function temPapel(array $papeisPermitidos): bool
{
    return in_array((int) ($_SESSION['FK_id_perfil'] ?? 0), $papeisPermitidos, true);
}

// Encerra a página caso o usuário não possua um dos perfis informados.
function exigirPapel(array $papeisPermitidos): void
{
    if (!temPapel($papeisPermitidos)) {
        http_response_code(403);
        exit('Acesso negado.');
    }
}

// Endereço do painel inicial de acordo com o perfil do usuário.
function painelDoUsuario(): string
{
    $paineis = [
        PERFIL_CLIENTE    => 'paginas/paineis/cliente.php',
        PERFIL_GESTOR     => 'paginas/paineis/gestor.php',
        PERFIL_MAQUINISTA => 'paginas/paineis/maquinista.php',
    ];

    return $paineis[(int) ($_SESSION['FK_id_perfil'] ?? 0)] ?? 'index.php';
}
