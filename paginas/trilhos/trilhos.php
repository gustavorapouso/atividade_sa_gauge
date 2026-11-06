<?php
// Cadastro e listagem de trilhos (trechos ferroviários).

require __DIR__ . '/../../includes/proteger.php';

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $localizacao = trim($_POST['localizacao'] ?? '');
    $distancia = filter_input(INPUT_POST, 'distancia', FILTER_VALIDATE_FLOAT);

    if ($localizacao === '') {
        $mensagem = 'Informe a localização do trilho.';
        $tipoMensagem = 'erro';
    } elseif (!$distancia || $distancia <= 0) {
        $mensagem = 'Informe uma distância válida, maior que zero.';
        $tipoMensagem = 'erro';
    } else {
        $stmt = $conexao->prepare('INSERT INTO trilho (localizacao, distancia) VALUES (?, ?)');
        $stmt->bind_param('sd', $localizacao, $distancia);

        if ($stmt->execute()) {
            $mensagem = 'Trilho cadastrado com sucesso!';
            $tipoMensagem = 'sucesso';
        } else {
            $mensagem = 'Erro ao cadastrar o trilho.';
            $tipoMensagem = 'erro';
        }

        $stmt->close();
    }
}

$trilhos = $conexao
    ->query('SELECT id_trilho, localizacao, distancia FROM trilho ORDER BY id_trilho DESC')
    ->fetch_all(MYSQLI_ASSOC);

$titulo = 'Trilhos';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<h1 class="titulo-pagina">Cadastro de trilhos</h1>
<p class="subtitulo">Cadastre e consulte os trechos ferroviários do sistema.</p>

<?php if ($mensagem !== ''): ?>
    <p class="mensagem <?= $tipoMensagem ?>" role="status"><?= h($mensagem) ?></p>
<?php endif; ?>

<section class="painel">
    <h2><i class="fas fa-plus-circle"></i> Novo trilho</h2>

    <form method="POST" class="form-grade">
        <div class="campo">
            <label for="localizacao">Localização</label>
            <input type="text" id="localizacao" name="localizacao" maxlength="100" placeholder="Ex.: Joinville - SC" required>
        </div>

        <div class="campo">
            <label for="distancia">Distância (km)</label>
            <input type="number" id="distancia" name="distancia" min="0.01" step="any" placeholder="Ex.: 25.5" required>
        </div>

        <div class="acoes campo-inteiro">
            <button type="submit" class="botao">
                <i class="fas fa-save"></i> Cadastrar trilho
            </button>
        </div>
    </form>
</section>

<section class="painel">
    <h2><i class="fas fa-list"></i> Trilhos cadastrados</h2>

    <div class="tabela-responsiva">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Localização</th>
                    <th>Distância (km)</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($trilhos as $trilho): ?>
                    <tr>
                        <td><?= (int) $trilho['id_trilho'] ?></td>
                        <td><?= h($trilho['localizacao']) ?></td>
                        <td><?= number_format((float) $trilho['distancia'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (count($trilhos) === 0): ?>
                    <tr>
                        <td colspan="3" class="vazio">Nenhum trilho cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
