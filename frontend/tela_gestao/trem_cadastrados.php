<?php
session_start();
require '../../data_base/conexao.php';

$sql = "SELECT
            id_trem,
            prefixo,
            modelo,
            ano,
            status,
            capacidade,
            ultima_inspecao,
            tipo_trem,
            quantidade_assentos,
            tipo_carga
        FROM trem
        ORDER BY prefixo";

$resultado = $conexao->query($sql);
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Trens cadastrados - Ferrorama Gauge</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="rotas.css">
</head>

<body>

    <!-- NAVBAR ORIGINAL -->
    <header class="header">
        <div class="left">
            <i class="fas fa-bars" title="Menu"></i>
            <i class="fas fa-search" title="Pesquisar"></i>
        </div>

        <div class="logo">
            <img src="../assets/logo_gauge_menor.png" alt="Gauge Logo">
        </div>
    </header>

    <main>

        <h1>Trens cadastrados</h1>

        <a href="formulario_trem.php" class="botao botao-nova">
            <i class="fas fa-plus"></i>
            Novo trem
        </a>

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
                    <th>Tipo de trem</th>
                    <th>Assentos / Tipo de carga</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>

                    <?php while ($trem = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$trem['id_trem'] ?></td>

                            <td><?= htmlspecialchars($trem['prefixo']) ?></td>

                            <td><?= htmlspecialchars($trem['modelo']) ?></td>

                            <td><?= htmlspecialchars((string)$trem['ano']) ?></td>

                            <td>
                                <?php
                                $status_texto = [
                                    'ativo' => 'Ativo',
                                    'manutencao' => 'Em manutenção',
                                    'inativo' => 'Inativo'
                                ];

                                echo htmlspecialchars(
                                    $status_texto[$trem['status']]
                                    ?? $trem['status']
                                );
                                ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float)$trem['capacidade'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                <?= !empty($trem['ultima_inspecao'])
                                    ? htmlspecialchars($trem['ultima_inspecao'])
                                    : 'Não informada' ?>
                            </td>

                            <td><?= htmlspecialchars($trem['tipo_trem']) ?></td>

                            <td>
                                <?php if ($trem['tipo_trem'] === 'Passageiro'): ?>
                                    <?= $trem['quantidade_assentos'] !== null
                                        ? (int)$trem['quantidade_assentos'] . ' assentos'
                                        : 'Não informado' ?>
                                <?php else: ?>
                                    <?= htmlspecialchars(
                                        $trem['tipo_carga'] ?? 'Não informada'
                                    ) ?>
                                <?php endif; ?>
                            </td>

                            <td class="acoes">
                                <a
                                    href="formulario_trem.php?id=<?= (int)$trem['id_trem'] ?>"
                                    class="editar"
                                    title="Editar"
                                >
                                    <i class="fas fa-pen"></i>
                                </a>

                                <a
                                    href="excluir_trem.php?id=<?= (int)$trem['id_trem'] ?>"
                                    class="excluir"
                                    title="Excluir"
                                    onclick="return confirm('Deseja realmente excluir este trem?')"
                                >
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="10">Nenhum trem cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </main>

    <!-- RODAPÉ ORIGINAL -->
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
                <a href="mailto:contato@gauge.com.br"
                   class="footer-link link-sublinhado">
                    contato@gauge.com.br
                </a>
            </div>

        </div>
    </footer>

</body>
</html>


