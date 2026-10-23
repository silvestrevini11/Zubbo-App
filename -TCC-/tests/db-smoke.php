<?php
declare(strict_types=1);

// Teste de integração exclusivamente em MySQL efêmero do CI.
require __DIR__ . '/../config/database.php';

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

// Executa também as migrações específicas da branch de testes no MySQL efêmero.
foreach ([
    '005_notificacao_leitura.sql',
    '006_foto_local.sql',
    '007_bloqueio_usuario.sql',
    '008_fila_espera.sql',
    '009_editar_times.sql',
    '010_esporte_moderacao.sql',
    '011_duracao_eventos.sql',
    '012_confirmacao_esportes_locais.sql',
] as $migracao) {
    $sql = file_get_contents(__DIR__ . '/../database/migrations/' . $migracao);
    confirmar($sql !== false, 'ler migration ' . $migracao);
    foreach (explode(';', $sql) as $comando) {
        if (trim($comando) !== '') $conn->exec($comando);
    }
}
confirmar((int)$conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='nome_time_1'")->fetchColumn() === 1, 'migração nomes dos times');
confirmar((int)$conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Notificacao' AND column_name='data_leitura'")->fetchColumn() === 1, 'migração data de leitura');
confirmar((int)$conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='LocalEsp' AND column_name='foto_local'")->fetchColumn() === 1, 'migração foto dos locais');

confirmar((int)$conn->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Evento' AND column_name='duracao_minutos'")->fetchColumn() === 1, 'migration duracao do evento');
confirmar((int)$conn->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='Local_Esporte_Confirmacao'")->fetchColumn() === 1, 'migration confirmacao esporte-local');
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
$amizade = $conn->query('SELECT id_amizade, data_aceita FROM Amizade LIMIT 1')->fetch(PDO::FETCH_ASSOC);
confirmar((int)$amizade['id_amizade'] > 0 && $amizade['data_aceita'] !== null, 'amizade e DEFAULT CURRENT_TIMESTAMP');

$conn->exec("INSERT INTO Conversa (tipo_conversa) VALUES ('grupo')");
$idConversa = (int)$conn->lastInsertId();
$conn->prepare('INSERT INTO Grupo (id_conversa, id_criador, nome_grupo) VALUES (?, ?, ?)')->execute([$idConversa, $id1, 'Teste']);
$insParticipante = $conn->prepare('INSERT INTO Participantes_Conversa (id_conversa, id_user) VALUES (?, ?)');
$insParticipante->execute([$idConversa, $id1]);
$insParticipante->execute([$idConversa, $id2]);
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

// Só lidas e com data_leitura antiga devem ser descartadas.
$conn->prepare('UPDATE Notificacao SET lida=TRUE, data_leitura=DATE_SUB(NOW(), INTERVAL 21 MINUTE)
 WHERE id_destinatario=?')->execute([$id2]);
$removerLidas = $conn->prepare("DELETE FROM Notificacao WHERE lida=TRUE
 AND data_leitura IS NOT NULL AND data_leitura <= DATE_SUB(NOW(), INTERVAL 20 MINUTE)");
$removerLidas->execute();
confirmar($removerLidas->rowCount()>=1,'limpeza remove notificacoes lidas ha mais de 20 minutos');

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
