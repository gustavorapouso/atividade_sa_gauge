<?php
require __DIR__ . '/../../includes/proteger.php';

$stmt = $conexao->prepare(
    'SELECT nome, data_nascimento, telefone, email
     FROM pessoa
     WHERE id_pessoa = ?'
);
$stmt->bind_param('i', $_SESSION['id_pessoa']);
$stmt->execute();

$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    exit('Não foi possível encontrar os dados do usuário.');
}

$campos = [
    'Nome completo'      => $usuario['nome'],
    'Data de nascimento' => formatarData($usuario['data_nascimento'], 'd/m/Y', ''),
    'Telefone'           => $usuario['telefone'],
    'E-mail'             => $usuario['email'],
];

$titulo = 'Seu perfil';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina">Seu perfil</h1>

<section class="painel painel-estreito">
    <i class="fas fa-circle-user avatar"></i>

    <div class="form-grade">
        <?php foreach ($campos as $rotulo => $valor): ?>
            <div class="campo campo-inteiro">
                <label><?= h($rotulo) ?></label>
                <input type="text" value="<?= h($valor) ?>" readonly>
            </div>
        <?php endforeach; ?>

        <div class="campo campo-inteiro">
            <label>Senha</label>
            <input type="password" placeholder="Senha protegida" readonly>
        </div>
    </div>

    <div class="acoes acoes-centro">
        <a href="<?= url('paginas/autenticacao/recuperar_senha.php') ?>" class="botao-secundario">
            <i class="fas fa-key"></i> Alterar senha
        </a>
        <a href="<?= url('paginas/autenticacao/sair.php') ?>" class="botao">
            <i class="fas fa-right-from-bracket"></i> Sair
        </a>
    </div>
</section>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
