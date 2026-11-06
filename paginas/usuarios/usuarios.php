<?php
// Gestão de usuários: lista à esquerda e dados do usuário selecionado à direita.

require __DIR__ . '/../../includes/proteger.php';

exigirPapel([PERFIL_GESTOR]);

// Token de segurança dos formulários
if (empty($_SESSION['token_usuarios'])) {
    $_SESSION['token_usuarios'] = bin2hex(random_bytes(32));
}

$sqlUsuarios = 'SELECT
                    p.id_pessoa,
                    p.nome,
                    p.data_nascimento,
                    p.telefone,
                    p.email,
                    p.FK_id_perfil,
                    p.ativo,
                    f.nome_perfil
                FROM PESSOA p
                INNER JOIN PERFIL f ON p.FK_id_perfil = f.id_perfil';

// Todos os usuários
$usuarios = $conexao
    ->query($sqlUsuarios . ' ORDER BY p.ativo DESC, p.nome ASC')
    ->fetch_all(MYSQLI_ASSOC);

// Usuário selecionado (por padrão, o próprio gestor)
$idSelecionado = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int) $_SESSION['id_pessoa'];

$stmt = $conexao->prepare($sqlUsuarios . ' WHERE p.id_pessoa = ?');
$stmt->bind_param('i', $idSelecionado);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Mensagens de retorno das operações
$mensagens = [
    'editar'    => 'Usuário atualizado com sucesso!',
    'desativar' => 'Conta desativada com sucesso!',
    'ativar'    => 'Conta ativada com sucesso!',
];

$mensagem = $mensagens[$_GET['sucesso'] ?? ''] ?? '';
$erro = isset($_GET['erro'])
    ? 'Não foi possível realizar a operação. Verifique as informações e tente novamente.'
    : '';

$perfis = [PERFIL_CLIENTE => 'Cliente', PERFIL_GESTOR => 'Gestor', PERFIL_MAQUINISTA => 'Maquinista'];

$titulo  = 'Usuários';
$estilos = ['usuarios'];
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina">Usuários</h1>
<p class="subtitulo">Selecione um usuário para ver e editar seus dados.</p>

<?php if ($mensagem !== ''): ?>
    <p class="mensagem sucesso" role="status"><?= h($mensagem) ?></p>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <p class="mensagem erro" role="alert"><?= h($erro) ?></p>
<?php endif; ?>

<div class="duas-colunas">

    <!-- Lista de usuários -->
    <section class="painel lista-usuarios" aria-label="Lista de usuários">
        <?php if (count($usuarios) === 0): ?>
            <p>Nenhum usuário cadastrado.</p>
        <?php endif; ?>

        <?php foreach ($usuarios as $item): ?>
            <?php $ativo = (int) $item['ativo'] === 1; ?>
            <a
                href="?id=<?= (int) $item['id_pessoa'] ?>"
                class="usuario-linha"
                <?= (int) $item['id_pessoa'] === $idSelecionado ? 'aria-current="true"' : '' ?>>
                <i class="fas fa-circle-user usuario-icone"></i>
                <span class="usuario-nome"><?= h($item['nome']) ?></span>
                <span class="usuario-perfil">
                    <?= h($item['nome_perfil']) ?><?= $ativo ? '' : ' (desativado)' ?>
                </span>
                <i
                    class="fas fa-circle usuario-situacao <?= $ativo ? 'ativo' : '' ?>"
                    title="<?= $ativo ? 'Conta ativa' : 'Conta desativada' ?>"></i>
            </a>
        <?php endforeach; ?>
    </section>

    <!-- Dados do usuário selecionado -->
    <section class="painel">
        <?php if (!$usuario): ?>

            <h2>Usuário não encontrado</h2>
            <p>Selecione um usuário da lista para visualizar seus dados.</p>

        <?php else: ?>

            <i class="fas fa-circle-user avatar"></i>
            <h2 class="usuario-titulo"><?= h($usuario['nome']) ?></h2>

            <form action="editar_usuario.php" method="POST" class="form-grade">
                <input type="hidden" name="token" value="<?= h($_SESSION['token_usuarios']) ?>">
                <input type="hidden" name="id_pessoa" value="<?= (int) $usuario['id_pessoa'] ?>">

                <div class="campo campo-inteiro">
                    <label for="nome">Nome completo</label>
                    <input type="text" id="nome" name="nome" maxlength="100" value="<?= h($usuario['nome']) ?>" required>
                </div>

                <div class="campo">
                    <label for="nascimento">Data de nascimento</label>
                    <input type="date" id="nascimento" name="data_nascimento" value="<?= h(formatarData($usuario['data_nascimento'], 'Y-m-d', '')) ?>">
                </div>

                <div class="campo">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone" maxlength="20" value="<?= h($usuario['telefone']) ?>">
                </div>

                <div class="campo campo-inteiro">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" maxlength="255" value="<?= h($usuario['email']) ?>" required>
                </div>

                <div class="campo">
                    <label for="perfil">Perfil de acesso</label>
                    <select id="perfil" name="FK_id_perfil" required>
                        <?php foreach ($perfis as $idPerfil => $nomePerfil): ?>
                            <option value="<?= $idPerfil ?>" <?= (int) $usuario['FK_id_perfil'] === $idPerfil ? 'selected' : '' ?>>
                                <?= $nomePerfil ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label>Situação da conta</label>
                    <input type="text" value="<?= (int) $usuario['ativo'] === 1 ? 'Ativa' : 'Desativada' ?>" readonly>
                </div>

                <div class="acoes campo-inteiro">
                    <button type="submit" class="botao">
                        <i class="fas fa-check"></i> Salvar alterações
                    </button>
                </div>
            </form>

            <!-- Formulário separado para ativar ou desativar -->
            <form action="desativar_usuario.php" method="POST" class="acoes">
                <input type="hidden" name="token" value="<?= h($_SESSION['token_usuarios']) ?>">
                <input type="hidden" name="id_pessoa" value="<?= (int) $usuario['id_pessoa'] ?>">

                <?php if ((int) $usuario['ativo'] === 1): ?>
                    <button
                        type="submit"
                        name="acao"
                        value="desativar"
                        class="botao-perigo"
                        onclick="return confirm('Deseja realmente desativar esta conta?');">
                        <i class="fas fa-user-slash"></i> Desativar conta
                    </button>
                <?php else: ?>
                    <button
                        type="submit"
                        name="acao"
                        value="ativar"
                        class="botao-secundario"
                        onclick="return confirm('Deseja ativar esta conta novamente?');">
                        <i class="fas fa-user-check"></i> Ativar conta
                    </button>
                <?php endif; ?>
            </form>

        <?php endif; ?>
    </section>

</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
