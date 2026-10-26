<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_pessoa'])) {
    header('Location: ../../data_base/autenticacaodb/login.php');
    exit;
}

// Somente o gestor pode acessar a gestão de usuários
if ((int) ($_SESSION['FK_id_perfil'] ?? 0) !== 2) {
    http_response_code(403);
    exit('Acesso negado. Somente gestores podem acessar esta página.');
}

require '../../data_base/conexao.php';
require '../../data_base/includes/proteger.php';

// Token de segurança dos formulários
if (empty($_SESSION['token_usuarios'])) {
    $_SESSION['token_usuarios'] = bin2hex(random_bytes(32));
}

// Busca todos os usuários e seus perfis
$sql = "SELECT
            p.id_pessoa,
            p.nome,
            p.data_nascimento,
            p.telefone,
            p.email,
            p.FK_id_perfil,
            p.ativo,
            f.nome_perfil
        FROM PESSOA p
        INNER JOIN PERFIL f ON p.FK_id_perfil = f.id_perfil
        ORDER BY p.ativo DESC, p.nome ASC";

$resultado = $conexao->query($sql);

if (!$resultado) {
    exit('Erro ao carregar os usuários.');
}

$usuarios = $resultado->fetch_all(MYSQLI_ASSOC);

// Identifica o usuário selecionado
$id_selecionado = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_selecionado) {
    $id_selecionado = (int) ($_SESSION['id_pessoa']);
}

// Busca os dados do usuário selecionado
$sql_usuario = "SELECT
                    p.id_pessoa,
                    p.nome,
                    p.data_nascimento,
                    p.telefone,
                    p.email,
                    p.FK_id_perfil,
                    p.ativo,
                    f.nome_perfil
                FROM PESSOA p
                INNER JOIN PERFIL f ON p.FK_id_perfil = f.id_perfil
                WHERE p.id_pessoa = ?";

$stmt = $conexao->prepare($sql_usuario);
$stmt->bind_param('i', $id_selecionado);
$stmt->execute();

$resultado_usuario = $stmt->get_result();
$usuario = $resultado_usuario->fetch_assoc();

$stmt->close();

// Formata a data para exibição
$data_nascimento = '';

if (!empty($usuario['data_nascimento'])) {
    $data_nascimento = date(
        'Y-m-d',
        strtotime($usuario['data_nascimento'])
    );
}

// Mensagens de retorno das operações
$mensagem = '';

if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] === 'editar') {
        $mensagem = 'Usuário atualizado com sucesso!';
    } elseif ($_GET['sucesso'] === 'desativar') {
        $mensagem = 'Conta desativada com sucesso!';
    } elseif ($_GET['sucesso'] === 'ativar') {
        $mensagem = 'Conta ativada com sucesso!';
    }
}

$erro = '';

if (isset($_GET['erro'])) {
    $erro = 'Não foi possível realizar a operação. Verifique as informações e tente novamente.';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="usuarios.css">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Fjalla+One&family=Roboto:wght@400;500;700&display=swap"
          rel="stylesheet">

    <title>Usuários | Gauge</title>
</head>

<body>

    <header class="main-navbar">
        <div class="left">
            <i class="fas fa-bars" title="Menu"></i>
            <i class="fas fa-search" title="Pesquisar"></i>
        </div>

        <div class="logo">
            <img src="../assets/logo_gauge_menor.png" alt="Logo Gauge">
        </div>
    </header>

    <div class="container">

        <header class="header">
            <h1 class="titulo-pagina">Usuários</h1>
        </header>

        <?php if ($mensagem !== ''): ?>
            <p role="status">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <p role="alert">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <main class="lista-usuarios">

            <!-- Lista dinâmica de usuários -->
            <section aria-label="Lista de usuários">

                <?php if (count($usuarios) > 0): ?>

                    <?php foreach ($usuarios as $item): ?>

                        <a
                            href="usuarios.php?id=<?= (int) $item['id_pessoa'] ?>"
                            class="linha-usuario"
                            style="text-decoration: none; color: inherit;"
                        >

                            <div class="bloco-info">

                                <div class="usuario-detalhes">

                                    <i class="bi bi-person-circle icone-user"></i>

                                    <span class="nome-user">
                                        <?= htmlspecialchars(
                                            $item['nome'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                </div>

                                <i
                                    class="bi bi-circle-fill status
                                    <?= (int) $item['ativo'] === 1
                                        ? 'status-online'
                                        : 'status-offline' ?>"
                                    title="<?= (int) $item['ativo'] === 1
                                        ? 'Conta ativa'
                                        : 'Conta desativada' ?>"
                                ></i>

                            </div>

                            <div class="bloco-cargo">
                                <?= htmlspecialchars(
                                    $item['nome_perfil'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                <?php if ((int) $item['ativo'] !== 1): ?>
                                    (Desativado)
                                <?php endif; ?>
                            </div>

                        </a>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p>Nenhum usuário cadastrado.</p>

                <?php endif; ?>

            </section>

            <!-- Perfil do usuário selecionado -->
            <section class="secao-perfil">

                <?php if ($usuario): ?>

                    <header class="header">
                        <h1 class="titulo-pagina">
                            <?= htmlspecialchars(
                                $usuario['nome'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h1>
                    </header>

                    <div class="perfil-centro">
                        <i class="bi bi-person-circle perfil-grande"></i>
                    </div>

                    <form action="editar_usuario.php" method="POST">

                        <input
                            type="hidden"
                            name="token"
                            value="<?= htmlspecialchars(
                                $_SESSION['token_usuarios'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                        <input
                            type="hidden"
                            name="id_pessoa"
                            value="<?= (int) $usuario['id_pessoa'] ?>"
                        >

                        <div class="grupo-campo">
                            <label for="nome">Nome completo</label>

                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                class="input-perfil-campo"
                                maxlength="100"
                                value="<?= htmlspecialchars(
                                    $usuario['nome'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >
                        </div>

                        <div class="grupo-campo">
                            <label for="nascimento">Data de nascimento</label>

                            <input
                                type="date"
                                id="nascimento"
                                name="data_nascimento"
                                class="input-perfil-campo"
                                value="<?= htmlspecialchars(
                                    $data_nascimento,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="grupo-campo">
                            <label for="telefone">Telefone</label>

                            <input
                                type="tel"
                                id="telefone"
                                name="telefone"
                                class="input-perfil-campo"
                                maxlength="20"
                                value="<?= htmlspecialchars(
                                    $usuario['telefone'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="grupo-campo">
                            <label for="email">E-mail</label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="input-perfil-campo"
                                maxlength="255"
                                value="<?= htmlspecialchars(
                                    $usuario['email'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >
                        </div>

                        <div class="grupo-campo">
                            <label for="perfil">Perfil de acesso</label>

                            <select
                                id="perfil"
                                name="FK_id_perfil"
                                class="input-perfil-campo"
                                required
                            >

                                <?php foreach ([1 => 'Cliente', 2 => 'Gestor', 3 => 'Maquinista'] as $id_perfil => $nome_perfil): ?>

                                    <option
                                        value="<?= $id_perfil ?>"
                                        <?= (int) $usuario['FK_id_perfil'] === $id_perfil
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= $nome_perfil ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>

                        <p>
                            Situação da conta:
                            <strong>
                                <?= (int) $usuario['ativo'] === 1
                                    ? 'Ativa'
                                    : 'Desativada' ?>
                            </strong>
                        </p>

                        <div class="centro-botao-excluir">

                            <button
                                type="submit"
                                class="botao-excluir"
                            >
                                Salvar alterações
                            </button>

                        </div>

                    </form>

                    <!-- Formulário separado para ativar ou desativar -->
                    <form action="desativar_usuario.php" method="POST">

                        <input
                            type="hidden"
                            name="token"
                            value="<?= htmlspecialchars(
                                $_SESSION['token_usuarios'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                        <input
                            type="hidden"
                            name="id_pessoa"
                            value="<?= (int) $usuario['id_pessoa'] ?>"
                        >

                        <div class="centro-botao-excluir">

                            <?php if ((int) $usuario['ativo'] === 1): ?>

                                <button
                                    type="submit"
                                    class="botao-excluir"
                                    onclick="return confirm('Deseja realmente desativar esta conta?');"
                                >
                                    Desativar conta
                                </button>

                            <?php else: ?>

                                <button
                                    type="submit"
                                    name="acao"
                                    value="ativar"
                                    class="botao-excluir"
                                    onclick="return confirm('Deseja ativar esta conta novamente?');"
                                >
                                    Ativar conta
                                </button>

                            <?php endif; ?>

                        </div>

                    </form>

                <?php else: ?>

                    <header class="header">
                        <h1 class="titulo-pagina">Usuário não encontrado</h1>
                    </header>

                    <p>Selecione um usuário da lista para visualizar seus dados.</p>

                <?php endif; ?>

            </section>

        </main>

    </div>

    <footer class="footer-gauge">

        <div class="footer-container">

            <div class="footer-bloco bloco-esquerda">
                <span class="footer-label">Entre em contato</span>
                <a href="tel:47999174896" class="footer-link">
                    (47) 99917-4896
                </a>
            </div>

            <div class="footer-bloco bloco-centro">
                <img
                    src="../assets/logo_gauge_menor.png"
                    alt="Gauge Logo"
                    class="footer-logo"
                >

                <p class="footer-copyright">
                    &copy; 2026 Gauge. Todos os direitos reservados.
                </p>
            </div>

            <div class="footer-bloco bloco-direita">
                <span class="footer-label">Precisa de Suporte?</span>

                <a
                    href="mailto:contato@gauge.com.br"
                    class="footer-link link-sublinhado"
                >
                    contato@gauge.com.br
                </a>
            </div>

        </div>

    </footer>

</body>
</html>
