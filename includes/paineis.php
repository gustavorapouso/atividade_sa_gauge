<?php
// Consultas e blocos usados pelos painéis iniciais (gestor, cliente e maquinista).

// Média das leituras de um tipo de sensor (ou null se não houver leituras).
function mediaSensor(mysqli $conexao, string $tipo): ?float
{
    $stmt = $conexao->prepare(
        'SELECT ROUND(AVG(leituras.valor)) AS media
         FROM leituras
         INNER JOIN sensores ON leituras.id_sensor = sensores.id_sensores
         WHERE sensores.tipo = ?'
    );
    $stmt->bind_param('s', $tipo);
    $stmt->execute();

    $media = $stmt->get_result()->fetch_assoc()['media'] ?? null;
    $stmt->close();

    return $media !== null ? (float) $media : null;
}

// Quantidade de trens com um status (e, opcionalmente, um tipo).
function contarTrens(mysqli $conexao, string $status, ?string $tipoTrem = null): int
{
    $sql = 'SELECT COUNT(*) AS total FROM trem WHERE status = ?';
    $tipos = 's';
    $valores = [$status];

    if ($tipoTrem !== null) {
        $sql .= ' AND tipo_trem = ?';
        $tipos .= 's';
        $valores[] = $tipoTrem;
    }

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param($tipos, ...$valores);
    $stmt->execute();

    $total = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    return $total;
}

// Mostra os indicadores das médias dos sensores.
function exibirIndicadoresSensores(mysqli $conexao): void
{
    $indicadores = [
        ['Velocidade',  'Velocidade',  'fa-bolt'],
        ['Temperatura', 'Temperatura', 'fa-temperature-high'],
        ['Consumo',     'Consumo',     'fa-gas-pump'],
        ['Vibração',    'Vibração',    'fa-water'],
    ];
    ?>
    <section class="indicadores" aria-label="Médias dos sensores">
        <?php foreach ($indicadores as [$rotulo, $tipo, $icone]): ?>
            <?php $media = mediaSensor($conexao, $tipo); ?>
            <div class="indicador">
                <i class="fas <?= $icone ?>"></i>
                <h2><?= h($rotulo) ?></h2>
                <p><?= $media !== null ? h($media) : '—' ?></p>
            </div>
        <?php endforeach; ?>
    </section>
    <?php
}

// Mostra os cartões com a situação dos trens.
function exibirResumoTrens(mysqli $conexao): void
{
    $cartoes = [
        ['Trens de passageiro', 'Ativos',     contarTrens($conexao, 'ativo', 'Passageiro'), 'fa-train'],
        ['Trens de carga',      'Ativos',     contarTrens($conexao, 'ativo', 'Carga'),      'fa-box'],
        ['Em manutenção',       'Quantidade', contarTrens($conexao, 'manutencao'),          'fa-wrench'],
        ['Trens inativos',      'Quantidade', contarTrens($conexao, 'inativo'),             'fa-ban'],
    ];
    ?>
    <section class="resumo-trens" aria-label="Situação dos trens">
        <?php foreach ($cartoes as [$titulo, $rotulo, $total, $icone]): ?>
            <div class="resumo-cartao">
                <i class="fas <?= $icone ?>"></i>
                <div>
                    <h2><?= h($titulo) ?></h2>
                    <p><?= h($rotulo) ?>: <?= $total ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
    <?php
}
