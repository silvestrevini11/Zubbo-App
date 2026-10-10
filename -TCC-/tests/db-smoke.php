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

$conn->prepare('INSERT INTO Amizade (id_user_1, id_user_2) VALUES (?, ?)')->execute([$id1, $id2]);
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
