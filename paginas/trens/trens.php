<?php
// Lista de todos os trens cadastrados, com opções de editar e excluir.

require __DIR__ . '/../../includes/proteger.php';

$trens = $conexao->query(
    'SELECT id_trem, prefixo, modelo, ano, status, capacidade,
            ultima_inspecao, tipo_trem, quantidade_assentos, tipo_carga
     FROM trem
     ORDER BY prefixo'
)->fetch_all(MYSQLI_ASSOC);

$statusTexto = [
    'ativo'      => 'Ativo',
    'manutencao' => 'Em manutenção',
    'inativo'    => 'Inativo',
];

$erro = isset($_GET['erro'])
    ? 'Não foi possível excluir o trem. Verifique se há sensores ou viagens vinculados a ele.'
    : '';

$titulo = 'Trens';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="cabecalho-pagina">
    <h1 class="titulo-pagina">Trens cadastrados</h1>

    <a href="formulario_trem.php" class="botao">
        <i class="fas fa-plus"></i> Novo trem
    </a>
</div>

<?php if ($erro !== ''): ?>
    <p class="mensagem erro" role="alert"><?= h($erro) ?></p>
<?php endif; ?>

<div class="tabela-responsiva">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Prefixo</th>
                <th>Modelo</th>
                <th>Ano</th>
                <th>Status</th>
                <th>Capacidade (t)</th>
                <th>Última inspeção</th>
                <th>Tipo</th>
                <th>Assentos / Carga</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($trens as $trem): ?>
                <tr>
                    <td><?= (int) $trem['id_trem'] ?></td>
                    <td><?= h($trem['prefixo']) ?></td>
                    <td><?= h($trem['modelo']) ?></td>
                    <td><?= h($trem['ano']) ?></td>
                    <td>
                        <span class="status status-<?= h($trem['status']) ?>">
                            <?= h($statusTexto[$trem['status']] ?? $trem['status']) ?>
                        </span>
                    </td>
                    <td><?= number_format((float) $trem['capacidade'], 2, ',', '.') ?></td>
                    <td><?= h(formatarData($trem['ultima_inspecao'], 'd/m/Y')) ?></td>
                    <td><?= h($trem['tipo_trem']) ?></td>
                    <td>
                        <?php if ($trem['tipo_trem'] === 'Passageiro'): ?>
                            <?= $trem['quantidade_assentos'] !== null ? (int) $trem['quantidade_assentos'] . ' assentos' : 'Não informado' ?>
                        <?php else: ?>
                            <?= h($trem['tipo_carga'] ?? 'Não informada') ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="acoes-tabela">
                            <a href="formulario_trem.php?id=<?= (int) $trem['id_trem'] ?>" class="icone-acao editar" title="Editar" aria-label="Editar trem">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a
                                href="excluir_trem.php?id=<?= (int) $trem['id_trem'] ?>"
                                class="icone-acao excluir"
                                title="Excluir"
                                aria-label="Excluir trem"
                                onclick="return confirm('Deseja realmente excluir este trem?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (count($trens) === 0): ?>
                <tr>
                    <td colspan="10" class="vazio">Nenhum trem cadastrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
