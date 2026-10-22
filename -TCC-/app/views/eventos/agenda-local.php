<?php
require_once __DIR__ . '/../../middleware/auth.php';
$idLocal=filter_var($_GET['id_local'] ?? null,FILTER_VALIDATE_INT);
if (!$idLocal) { http_response_code(404);exit('Local não encontrado.'); }
$st=$conn->prepare("SELECT nome_local,endereco_local,tipo_local FROM LocalEsp
  WHERE id_local=? AND status_local='aprovado' LIMIT 1");
$st->execute([$idLocal]); $local=$st->fetch(PDO::FETCH_ASSOC);
if (!$local) { http_response_code(404);exit('Local indisponível.'); }
$st=$conn->prepare("SELECT ev.id_evento,ev.nome_evento,ev.data_evento,ev.horario_evento,
  e.nome_esporte FROM Evento ev JOIN Esporte e ON ev.id_esporte=e.id_esporte
  WHERE ev.id_local=? AND ev.status_evento='ativo'
    AND TIMESTAMP(ev.data_evento,ev.horario_evento)>=NOW()
  ORDER BY ev.data_evento,ev.horario_evento,ev.id_evento LIMIT 100");
$st->execute([$idLocal]);$eventos=$st->fetchAll(PDO::FETCH_ASSOC);
function agenda_escape($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
include __DIR__.'/../includes/head.php';
?><link rel="stylesheet" href="<?= htmlspecialchars(zubbo_url('/public/css/eventos.css'), ENT_QUOTES, 'UTF-8') ?>"><?php
?>
<main class="eventos-container agenda-local">
    <p><a href="../painel/Painel-inicial.php?id_local=<?= (int)$idLocal ?>">← Voltar ao mapa</a></p>
    <h1>Agenda esportiva: <?= agenda_escape($local['nome_local']) ?></h1>
    <p><?= agenda_escape($local['endereco_local']) ?></p>
    <p class="agenda-aviso" role="note">Estes horários representam eventos cadastrados no Zubbo.
    Não são reservas oficiais da prefeitura ou do estabelecimento.</p>
    <?php if (!$eventos): ?><p>Não há eventos futuros cadastrados neste local.</p><?php endif; ?>
    <ul class="agenda-lista">
        <?php foreach ($eventos as $ev): ?>
            <li>
                <strong><?= agenda_escape($ev['nome_evento']) ?></strong>
                <span><?= agenda_escape($ev['nome_esporte']) ?> ·
                <?= agenda_escape(date('d/m/Y',strtotime($ev['data_evento']))) ?> às <?= agenda_escape(substr($ev['horario_evento'],0,5)) ?></span>
                <a href="detalhes-evento.php?id_evento=<?= (int)$ev['id_evento'] ?>">Ver evento</a>
            </li>
        <?php endforeach; ?>
    </ul>
</main>
<?php include __DIR__.'/../includes/under-bar.php'; include __DIR__.'/../includes/footer.php'; ?>
