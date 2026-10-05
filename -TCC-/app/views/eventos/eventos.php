<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/_regras-equipes.php';

$eventos = [];
$erro = false;

try {
    $stmt = $conn->query("
        SELECT ev.id_evento, ev.nome_evento, ev.data_evento, ev.horario_evento,
               ev.status_evento, e.nome_esporte, l.nome_local, l.endereco_local,
               u.nome_user AS criador,
               (SELECT COUNT(*) FROM Lista_Evento le WHERE le.id_evento = ev.id_evento) AS confirmados,
               (SELECT COUNT(*) FROM Solicitacao_Vaga_Evento s
                    WHERE s.id_evento = ev.id_evento AND s.status_solicitacao = 'pendente') AS pendentes,
               (SELECT COUNT(*) FROM Solicitacao_Vaga_Evento s
                    WHERE s.id_evento = ev.id_evento AND s.status_solicitacao = 'aprovada' AND s.time_num = 1) AS time_1,
               (SELECT COUNT(*) FROM Solicitacao_Vaga_Evento s
                    WHERE s.id_evento = ev.id_evento AND s.status_solicitacao = 'aprovada' AND s.time_num = 2) AS time_2
        FROM Evento ev
        INNER JOIN Esporte e ON e.id_esporte = ev.id_esporte
        INNER JOIN LocalEsp l ON l.id_local = ev.id_local
        LEFT JOIN Usuario u ON u.id_user = ev.id_criador
        WHERE ev.status_evento <> 'removido'
        ORDER BY
            (ev.status_evento = 'ativo' AND TIMESTAMP(ev.data_evento, ev.horario_evento) >= NOW()) DESC,
            CASE
                WHEN TIMESTAMP(ev.data_evento, ev.horario_evento) >= NOW()
                    THEN TIMESTAMP(ev.data_evento, ev.horario_evento)
            END ASC,
            ev.data_evento DESC,
            ev.horario_evento DESC,
            ev.id_evento DESC
    ");
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao listar eventos: ' . $e->getMessage());
    $erro = true;
}

function escaparEvento($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos | Zubbo</title>
    <script>
        if (localStorage.getItem('zubbo-tema') === 'escuro') {
            document.documentElement.classList.add('tema-escuro');
        }
    </script>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/eventos.css">
</head>
<body>
<main class="eventos-container">
    <header class="eventos-topo">
        <div>
            <p class="eventos-marca">ZUBBO</p>
            <h1>Eventos</h1>
            <p class="eventos-subtitulo">Encontre a galera, escolha sua vaga e entre em jogo.</p>
        </div>
        <a class="eventos-criar" href="criar-evento.php">+ Criar evento</a>
    </header>

    <?php if ($erro): ?>
        <section class="eventos-vazio" role="alert">
            <h2>Não foi possível carregar os eventos</h2>
            <p>Confirme se as migrations desta branch foram executadas e tente novamente.</p>
            <a class="eventos-criar" href="eventos.php">Tentar novamente</a>
        </section>
    <?php elseif (!$eventos): ?>
        <section class="eventos-vazio">
            <span class="eventos-vazio-icone" aria-hidden="true">🏆</span>
            <h2>O próximo encontro começa com você</h2>
            <p>Ainda não há eventos cadastrados. Crie o primeiro para reunir a galera!</p>
            <a class="eventos-criar" href="criar-evento.php">Criar meu primeiro evento</a>
        </section>
    <?php else: ?>
        <p class="eventos-contagem"><?= count($eventos) ?> evento(s) cadastrado(s)</p>

        <section class="eventos-grid" aria-label="Eventos cadastrados">
            <?php foreach ($eventos as $evento): ?>
                <?php
                    $limite = evento_vagas_por_time((string) $evento['nome_esporte']);
                    $dataHoraEvento = strtotime($evento['data_evento'] . ' ' . $evento['horario_evento']);
                    $aberto = $evento['status_evento'] === 'ativo' && $dataHoraEvento >= time();

                    if ($evento['status_evento'] === 'cancelado') {
                        $statusRotulo = 'Cancelado';
                        $statusClasse = 'eventos-status-cancelado';
                    } elseif (!$aberto) {
                        $statusRotulo = 'Encerrado';
                        $statusClasse = 'eventos-status-encerrado';
                    } else {
                        $statusRotulo = 'Aberto';
                        $statusClasse = '';
                    }
                ?>
                <article class="eventos-card evento-card-competitivo">
                    <div class="eventos-card-topo">
                        <span class="eventos-esporte"><?= escaparEvento($evento['nome_esporte']) ?></span>
                        <span class="eventos-status <?= $statusClasse ?>"><?= $statusRotulo ?></span>
                    </div>

                    <h2><?= escaparEvento($evento['nome_evento']) ?></h2>

                    <dl class="eventos-dados">
                        <div>
                            <dt>Data e horário</dt>
                            <dd>
                                <time datetime="<?= escaparEvento($evento['data_evento']) ?>">
                                    <?= escaparEvento(date('d/m/Y', strtotime($evento['data_evento']))) ?>
                                </time>
                                às <?= escaparEvento(substr($evento['horario_evento'], 0, 5)) ?>
                            </dd>
                        </div>
                        <div>
                            <dt>Local</dt>
                            <dd><?= escaparEvento($evento['nome_local']) ?></dd>
                            <dd class="eventos-endereco"><?= escaparEvento($evento['endereco_local']) ?></dd>
                        </div>
                        <div>
                            <dt>Organizado por</dt>
                            <dd><?= escaparEvento($evento['criador'] ?? 'Usuário indisponível') ?></dd>
                        </div>
                    </dl>

                    <?php if ($limite !== null): ?>
                        <div class="eventos-times-resumo">
                            <div class="time-1">
                                <span>TIME 1</span>
                                <strong><?= (int) $evento['time_1'] ?> / <?= $limite ?></strong>
                            </div>
                            <div class="time-2">
                                <span>TIME 2</span>
                                <strong><?= (int) $evento['time_2'] ?> / <?= $limite ?></strong>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="eventos-individual-resumo">
                            <span>PARTICIPAÇÃO INDIVIDUAL</span>
                            <strong><?= (int) $evento['confirmados'] ?> confirmado(s)</strong>
                        </div>
                    <?php endif; ?>

                    <div class="eventos-card-rodape">
                        <span><?= (int) $evento['pendentes'] ?> solicitação(ões) pendente(s)</span>
                        <a class="eventos-criar evento-abrir" href="detalhes-evento.php?id_evento=<?= (int) $evento['id_evento'] ?>">
                            Entrar no evento →
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<?php
require __DIR__ . '/../includes/under-bar.php';
require __DIR__ . '/../includes/footer.php';
?>