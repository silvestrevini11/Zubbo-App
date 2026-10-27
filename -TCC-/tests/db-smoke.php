<?php
declare(strict_types=1);

// O teste cria tabelas e fixtures: recuse bancos reais antes de conectar.
if (PHP_SAPI !== 'cli'
    || getenv('ZUBBO_TEST_ALLOW_SCHEMA') !== '1'
    || !preg_match('/^zubbo_(?:ci|test_[a-z0-9_]+)$/D', (string)getenv('ZUBBO_DB_NAME'))) {
    fwrite(STDERR, "Use ZUBBO_TEST_ALLOW_SCHEMA=1 e banco descartável zubbo_ci ou zubbo_test_*.\n");
    exit(1);
}
require __DIR__ . '/../config/database.php';
if ((int)$conn->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchColumn() !== 0) {
    fwrite(STDERR, "O banco de teste deve estar vazio. Nenhuma alteração executada.\n");
    exit(1);
}
session_start();

function confirmar(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException('FALHOU: ' . $mensagem);
    }
    echo 'OK: ' . $mensagem . PHP_EOL;
}

foreach (['criacaotables.sql', 'insertstables.sql'] as $nome) {
    $sql = file_get_contents(__DIR__ . '/../database/scripts/' . $nome);
    confirmar($sql !== false, 'ler ' . $nome);
    foreach (explode(';', $sql) as $comando) {
        if (trim($comando) !== '') {
            $conn->exec($comando);
        }
    }
}

// A instalação nova já contém o esquema final; não reaplicar migrations históricas.
confirmar((int)$conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND LOWER(table_name)='evento' AND column_name='nome_time_1'")->fetchColumn() === 1, 'migração nomes dos times');
confirmar((int)$conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND LOWER(table_name)='notificacao' AND column_name='data_leitura'")->fetchColumn() === 1, 'migração data de leitura');
confirmar((int)$conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND LOWER(table_name)='localesp' AND column_name='foto_local'")->fetchColumn() === 1, 'migração foto dos locais');

confirmar((int)$conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND LOWER(table_name)='evento' AND column_name='duracao_minutos'")->fetchColumn() === 1, 'migration duracao do evento');
confirmar((int)$conn->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND LOWER(table_name)='local_esporte_confirmacao'")->fetchColumn() === 1, 'migration confirmacao esporte-local');
require_once __DIR__ . '/../app/services/PushService.php';
confirmar(PushService::allowedEndpoint('https://fcm.googleapis.com/fcm/send/abcdefghijk1234567890'),
    'endpoint push conhecido permitido');
confirmar(!PushService::allowedEndpoint('https://example.org/private/abc'),
    'endpoint push desconhecido recusado');
confirmar((int)$conn->query("SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema=DATABASE() AND LOWER(table_name)='pushsubscription'")->fetchColumn() === 1,
    'migration de web push aplicada');
confirmar((int)$conn->query("SELECT COUNT(*) FROM LocalEsp WHERE tipo_local='campo'")->fetchColumn() >= 5,
    'campos de Diadema importados');
confirmar((int)$conn->query("SELECT COUNT(*) FROM Local_Esporte_Permitido")->fetchColumn() >= 12,
    'modalidades por local importadas');
$modalidadesNaoAssociadas = (int)$conn->query("SELECT COUNT(*) FROM LocalEsp l
 CROSS JOIN Esporte e
 LEFT JOIN Local_Esporte_Permitido p ON p.id_local=l.id_local AND p.id_esporte=e.id_esporte
 WHERE ((l.tipo_local='ginásio' AND e.nome_esporte IN ('Futsal','Vôlei','Basquete','Handebol'))
     OR (l.tipo_local='quadra' AND e.nome_esporte IN ('Handebol','Futsal','Vôlei')))
   AND p.id_local IS NULL")->fetchColumn();
confirmar($modalidadesNaoAssociadas===0,
    'ginasios e quadras possuem todas as modalidades definidas para seu tipo');

$verifSociety=$conn->query("SELECT COUNT(*) FROM Local_Esporte_Permitido p
   JOIN LocalEsp l ON l.id_local=p.id_local JOIN Esporte e ON e.id_esporte=p.id_esporte
   WHERE l.nome_local='Signello Esportes Society' AND e.nome_esporte='Futebol Society'")->fetchColumn();
confirmar((int)$verifSociety===1,'society somente nas instalacoes informadas');
$verifBasquete=$conn->query("SELECT COUNT(*) FROM Local_Esporte_Permitido p
   JOIN LocalEsp l ON l.id_local=p.id_local JOIN Esporte e ON e.id_esporte=p.id_esporte
   WHERE l.nome_local='Signello Esportes Society' AND e.nome_esporte='Basquete'")->fetchColumn();
confirmar((int)$verifBasquete===0,'nao liberar esporte nao confirmado no local');
confirmar((int)$conn->query("SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema=DATABASE() AND LOWER(table_name)='notificacao' AND column_name='id_evento'")->fetchColumn()===1,
 'notificacoes de evento suportadas');
confirmar((int)$conn->query('SELECT COUNT(*) FROM Esporte')->fetchColumn() >= 3, 'esportes importados');
$esporteId = (int)$conn->query('SELECT id_esporte FROM Esporte ORDER BY id_esporte LIMIT 1')->fetchColumn();
$localId = (int)$conn->query('SELECT id_local FROM LocalEsp ORDER BY id_local LIMIT 1')->fetchColumn();
confirmar($localId > 0, 'local de teste cadastrado');

$insUser = $conn->prepare('INSERT INTO Usuario (nome_user, email_user, tel_user, senha_user, date_user, email_verificado) VALUES (?, ?, ?, ?, ?, TRUE)');
$senha = password_hash('SenhaTesteCI123', PASSWORD_DEFAULT);
$insUser->execute(['Primeiro', 'primeiro-ci@localhost.test', '11911111111', $senha, '2000-01-01']);
$id1 = (int)$conn->lastInsertId();
$insUser->execute(['Segundo', 'segundo-ci@localhost.test', '11922222222', $senha, '2000-01-01']);
$id2 = (int)$conn->lastInsertId();
confirmar($id1 > 0 && $id2 > $id1, 'usuarios AUTO_INCREMENT');

require_once __DIR__ . '/../app/services/AdminService.php';
$conn->prepare("INSERT INTO Administrador (id_user, nome_adm, email_adm, senha_adm, ativo) VALUES (NULL, ?, ?, ?, 1)")
    ->execute(['Legado CI', 'segundo-ci@localhost.test', password_hash('NaoUtilizarCI123', PASSWORD_DEFAULT)]);
confirmar(
    AdminService::buscarIdAtivo($conn, $id2, 'segundo-ci@localhost.test') === null,
    'sem privilegio admin apenas por e-mail legado'
);
$idAdm = AdminService::conceder($conn, [
    'id_user' => $id1,
    'nome_user' => 'Primeiro',
    'email_user' => 'primeiro-ci@localhost.test',
]);
confirmar($idAdm > 0 && AdminService::buscarIdAtivo($conn, $id1, 'primeiro-ci@localhost.test') === $idAdm,
    'admin autorizado por id_user');


require_once __DIR__ . '/../app/services/AmizadeService.php';
confirmar(!zubbo_amizade_aceita($conn, $id1, $id2),
    'chat privado bloqueado antes de aceitar amizade');
$conn->prepare('INSERT INTO Amizade (id_user_1, id_user_2) VALUES (?, ?)')->execute([$id1, $id2]);
confirmar(zubbo_amizade_aceita($conn, $id1, $id2),
    'chat privado autorizado apos amizade aceita');
$conn->prepare("INSERT INTO PushSubscription(id_user,endpoint_hash,dados_json) VALUES (?,?,?)")
    ->execute([$id1,hash('sha256','https://fcm.googleapis.com/fcm/send/teste1234567890'),
        json_encode(['endpoint'=>'https://fcm.googleapis.com/fcm/send/teste1234567890',
        'keys'=>['p256dh'=>str_repeat('A',87),'auth'=>str_repeat('B',22)]])]);
confirmar((int)$conn->query('SELECT COUNT(*) FROM PushSubscription')->fetchColumn()===1,
    'dispositivo inscrito no banco');

$amizade = $conn->query('SELECT id_amizade, data_aceita FROM Amizade LIMIT 1')->fetch(PDO::FETCH_ASSOC);
confirmar((int)$amizade['id_amizade'] > 0 && $amizade['data_aceita'] !== null, 'amizade e DEFAULT CURRENT_TIMESTAMP');

$conn->exec("INSERT INTO Conversa (tipo_conversa) VALUES ('grupo')");
$idConversa = (int)$conn->lastInsertId();
$conn->prepare('INSERT INTO Grupo (id_conversa, id_criador, nome_grupo) VALUES (?, ?, ?)')->execute([$idConversa, $id1, 'Teste']);
$insParticipante = $conn->prepare('INSERT INTO Participantes_Conversa (id_conversa, id_user) VALUES (?, ?)');
$insParticipante->execute([$idConversa, $id1]);
$insParticipante->execute([$idConversa, $id2]);
require_once __DIR__ . '/../app/services/NotificacaoService.php';
confirmar(zubbo_notificar($conn,$id2,$id1,'grupo_adicionado',null,$idConversa),
    'notificacao de novo integrante do grupo salva');
$notificarGrupo=$conn->prepare("SELECT COUNT(*) FROM Notificacao
 WHERE id_destinatario=? AND id_conversa=? AND tipo='grupo_adicionado'");
$notificarGrupo->execute([$id2,$idConversa]);
confirmar((int)$notificarGrupo->fetchColumn()===1,
    'notificacao do novo grupo vinculada a conversa');

$conn->exec("SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
$grupo = $conn->prepare("
    SELECT g.*, (SELECT COUNT(*) FROM Participantes_Conversa p WHERE p.id_conversa = g.id_conversa) AS total_participantes
    FROM Grupo g
    INNER JOIN Conversa c ON c.id_conversa = g.id_conversa AND c.tipo_conversa = 'grupo'
    INNER JOIN Participantes_Conversa pc ON pc.id_conversa = g.id_conversa AND pc.id_user = ?
    WHERE g.id_conversa = ? LIMIT 1
");
$grupo->execute([$id1, $idConversa]);
confirmar((int)$grupo->fetch(PDO::FETCH_ASSOC)['total_participantes'] === 2, 'consulta do chat com ONLY_FULL_GROUP_BY');

$conn->prepare('INSERT INTO Mensagem (id_conversa, id_remetente, mensagem) VALUES (?, ?, ?)')->execute([$idConversa, $id1, 'Mensagem CI']);
$idMensagem = (int)$conn->lastInsertId();
$conn->prepare("INSERT INTO Notificacao (id_destinatario, id_remetente, id_conversa, id_mensagem, tipo) VALUES (?, ?, ?, ?, 'mensagem')")->execute([$id2, $id1, $idConversa, $idMensagem]);
$contar = $conn->prepare("SELECT COUNT(DISTINCT CASE WHEN tipo='mensagem' AND id_conversa IS NOT NULL THEN id_conversa END) FROM Notificacao WHERE id_destinatario=? AND lida=FALSE");
$contar->execute([$id2]);
confirmar((int)$contar->fetchColumn() === 1, 'contador de conversa nao lida');
$conn->prepare("UPDATE Notificacao SET lida=TRUE WHERE id_destinatario=? AND id_conversa=? AND tipo='mensagem'")->execute([$id2,$idConversa]);
$contar->execute([$id2]);
confirmar((int)$contar->fetchColumn() === 0, 'contador zerado apos leitura');

$conn->prepare('INSERT INTO Evento (nome_evento, data_evento, horario_evento, id_esporte, id_local, id_criador) VALUES (?, ?, ?, ?, ?, ?)')->execute(['Evento CI', '2030-01-01', '19:00:00', $esporteId, $localId, $id1]);
$idEvento = (int)$conn->lastInsertId();
confirmar(zubbo_notificar($conn,$id1,$id2,'vaga_solicitada',$idEvento),
    'solicitacao de vaga notifica organizador');
confirmar(zubbo_notificar($conn,$id2,$id1,'vaga_aprovada',$idEvento),
    'aprovacao de vaga notifica participante');
$tiposEvento=$conn->prepare("SELECT COUNT(DISTINCT tipo) FROM Notificacao
 WHERE id_evento=? AND tipo IN ('vaga_solicitada','vaga_aprovada')");
$tiposEvento->execute([$idEvento]);
confirmar((int)$tiposEvento->fetchColumn()===2,
    'ambas as notificacoes de evento foram salvas');

$conn->prepare('INSERT INTO Grupo_Evento(id_grupo,id_evento) VALUES(?,?)')
    ->execute([$conn->query('SELECT id_grupo FROM Grupo LIMIT 1')->fetchColumn(),$idEvento]);
$conn->prepare("INSERT INTO Notificacao(id_destinatario,id_remetente,id_evento,tipo)
 VALUES (?,?,?,'convite_evento')")->execute([$id2,$id1,$idEvento]);
confirmar((int)$conn->query("SELECT COUNT(*) FROM Notificacao
 WHERE tipo='convite_evento' AND id_evento=$idEvento")->fetchColumn()===1,
 'convite para evento registrado');


// Mesmo local e dia: sobreposição proibida, faixa imediatamente posterior liberada.
$checarHorario = $conn->prepare("SELECT COUNT(*) FROM Evento
    WHERE id_local = ? AND data_evento = ? AND status_evento = 'ativo'
      AND TIME_TO_SEC(horario_evento) < TIME_TO_SEC(?) + ? * 60
      AND TIME_TO_SEC(horario_evento) + duracao_minutos * 60 > TIME_TO_SEC(?)");
$checarHorario->execute([$localId, '2030-01-01', '19:30:00', 60, '19:30:00']);
confirmar((int)$checarHorario->fetchColumn() === 1,
    'rejeitar evento que coincide com agendamento existente');
$checarHorario->execute([$localId, '2030-01-01', '20:00:00', 60, '20:00:00']);
confirmar((int)$checarHorario->fetchColumn() === 0,
    'permitir horario posterior sem sobreposicao');


// A fila usa a mesma trava do Evento; o primeiro elegível vira pedido pendente.
$conn->prepare("INSERT INTO Solicitacao_Vaga_Evento (id_evento,id_user,time_num,numero_vaga,status_solicitacao)
 VALUES (?,?,1,1,'aprovada')")->execute([$idEvento,$id1]);
$conn->prepare("INSERT INTO Lista_Evento (id_user,id_evento) VALUES (?,?)")->execute([$id1,$idEvento]);
$conn->prepare("INSERT INTO Fila_Espera_Evento (id_evento,id_user) VALUES (?,?)")->execute([$idEvento,$id2]);
require_once __DIR__ . '/../app/views/eventos/_fila-espera.php';
$conn->beginTransaction();
$lock = $conn->prepare('SELECT id_evento FROM Evento WHERE id_evento=? FOR UPDATE');
$lock->execute([$idEvento]);
$conn->prepare("UPDATE Solicitacao_Vaga_Evento SET status_solicitacao='cancelada' WHERE id_evento=? AND id_user=?")
  ->execute([$idEvento,$id1]);
evento_promover_fila($conn,$idEvento,1,1);
$conn->commit();
$testarFila = $conn->prepare("SELECT COUNT(*) FROM Solicitacao_Vaga_Evento WHERE id_evento=? AND id_user=?
  AND status_solicitacao='pendente' AND time_num=1 AND numero_vaga=1");
$testarFila->execute([$idEvento,$id2]);
confirmar((int)$testarFila->fetchColumn()===1, 'primeiro da fila promovido para pendente');
$testarFila = $conn->prepare("SELECT COUNT(*) FROM Fila_Espera_Evento WHERE id_evento=?");
$testarFila->execute([$idEvento]);
confirmar((int)$testarFila->fetchColumn()===0, 'fila remove participante promovido');

// Uma transação em outro processo/conexão não deve tomar o local reservado.
// MySQL devolve timeout de lock, provando exclusão mútua para criações concorrentes.
$segunda = new PDO(
 'mysql:host='.(getenv('ZUBBO_DB_HOST') ?: 'localhost').
 ';port='.(getenv('ZUBBO_DB_PORT') ?: 3306).
 ';dbname='.(getenv('ZUBBO_DB_NAME') ?: 'app_zubbo').';charset=utf8mb4',
 getenv('ZUBBO_DB_USER') ?: 'root', getenv('ZUBBO_DB_PASSWORD') ?: '',
 [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
);
$segunda->exec('SET SESSION innodb_lock_wait_timeout=1');
$conn->beginTransaction();
$bloquearLocal = $conn->prepare('SELECT id_local FROM LocalEsp WHERE id_local=? FOR UPDATE');
$bloquearLocal->execute([$localId]);
$segunda->beginTransaction();
$foiBloqueada = false;
try {
    $segundoLock = $segunda->prepare('SELECT id_local FROM LocalEsp WHERE id_local=? FOR UPDATE');
    $segundoLock->execute([$localId]);
} catch (PDOException $e) {
    $foiBloqueada = (($e->errorInfo[1] ?? 0) == 1205);
}
$segunda->rollBack();
$conn->rollBack();
confirmar($foiBloqueada, 'trava por local impede duas criacoes simultaneas');

// Abrir a caixa deve marcar todas as categorias como lidas, não só mensagens.
$conn->prepare("INSERT INTO Notificacao(id_destinatario,id_remetente,tipo)
 VALUES (?,?,'amizade_aceita')")->execute([$id2,$id1]);
$conn->prepare("UPDATE Notificacao SET lida=TRUE,
 data_leitura=COALESCE(data_leitura,NOW())
 WHERE id_destinatario=? AND (lida=FALSE OR data_leitura IS NULL)")
 ->execute([$id2]);
$contarNaoLidas=$conn->prepare("SELECT COUNT(*) FROM Notificacao
 WHERE id_destinatario=? AND (lida=FALSE OR data_leitura IS NULL)");
$contarNaoLidas->execute([$id2]);
confirmar((int)$contarNaoLidas->fetchColumn()===0,
    'entrada em notificacoes leu todos os tipos');
$conn->prepare("UPDATE Notificacao SET data_leitura=DATE_SUB(NOW(),INTERVAL 6 MINUTE)
 WHERE id_destinatario=?")->execute([$id2]);
$removerLidas=$conn->prepare("DELETE FROM Notificacao WHERE id_destinatario=?
 AND lida=TRUE AND data_leitura IS NOT NULL
 AND data_leitura<=DATE_SUB(NOW(),INTERVAL 5 MINUTE)");
$removerLidas->execute([$id2]);
confirmar($removerLidas->rowCount()>=3,
    'limpeza em 5 minutos remove grupo amizade e evento');


$conn->prepare('INSERT INTO Denuncia (id_denunciante, id_local, motivo, descricao) VALUES (?, ?, ?, ?)')->execute([$id2, $localId, 'Outro', 'Denúncia de teste sem dados reais.']);
$conn->prepare('INSERT INTO Denuncia (id_denunciante, id_evento, motivo, descricao) VALUES (?, ?, ?, ?)')->execute([$id2, $idEvento, 'Outro', 'Evento simulado para testar a denúncia.']);
confirmar((int)$conn->query('SELECT COUNT(*) FROM Denuncia WHERE id_local IS NOT NULL OR id_evento IS NOT NULL')->fetchColumn() === 2, 'denuncias de evento e local');


if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['usuario'] = [
    'id' => $id1,
    'nome' => 'Primeiro',
    'email' => 'primeiro-ci@localhost.test'
];

// Exercita os arquivos reais de listagem, para detectar parâmetros PDO
// inexistentes, consultas incompatíveis e erros fatais de renderização.
foreach ([
    'chats.php' => 'conversas privadas',
    'chats-grupos.php' => 'conversas em grupo',
] as $arquivo => $rotulo) {
    ob_start();
    require __DIR__ . '/../app/views/chats/' . $arquivo;
    $html = ob_get_clean();
    confirmar(str_contains($html, 'Conversas'), 'renderizar lista de ' . $rotulo);
}
unset($_SESSION['usuario']);

echo "Testes de integracao MySQL concluidos.\n";
