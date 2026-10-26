<?php
declare(strict_types=1);

// Fluxos reais por HTTP, exclusivamente em servidor local e banco descartável.
$base = rtrim((string)getenv('ZUBBO_TEST_BASE_URL'), '/');
$url = parse_url($base);
if (PHP_SAPI !== 'cli' || getenv('ZUBBO_TEST_ALLOW_SCHEMA') !== '1'
    || !preg_match('/^zubbo_(?:ci|test_[a-z0-9_]+)$/D', (string)getenv('ZUBBO_DB_NAME'))
    || !in_array($url['host'] ?? '', ['127.0.0.1','localhost'], true)
    || ($url['scheme'] ?? '') !== 'http') {
    fwrite(STDERR, "Use banco zubbo_ci ou zubbo_test_*, ZUBBO_TEST_ALLOW_SCHEMA=1 e ZUBBO_TEST_BASE_URL=http://127.0.0.1:PORTA.\n");
    exit(1);
}
require __DIR__ . '/../config/database.php';
$run = bin2hex(random_bytes(5));
$password = 'TesteLocal123!';
$failures = 0;
$checks = 0;
$jars = [];
function verify(bool $ok, string $label): void {
    global $failures, $checks;
    $checks++;
    if (!$ok) $failures++;
    echo ($ok ? 'OK: ' : 'FALHOU: ') . $label . PHP_EOL;
}
function request(string $who, string $path, ?array $data = null, array $headers = []): array {
    global $base, $jars;
    if (!isset($jars[$who])) $jars[$who] = tempnam(sys_get_temp_dir(), 'zubbo-http-');
    $curl = curl_init($base . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_COOKIEFILE => $jars[$who], CURLOPT_COOKIEJAR => $jars[$who],
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => $headers]);
    if ($data !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data)]);
    $raw = curl_exec($curl);
    if ($raw === false) throw new RuntimeException(curl_error($curl));
    $size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    $head = substr($raw, 0, $size);
    preg_match('/^Location:\s*(.+)$/mi', $head, $match);
    return ['status' => $status, 'body' => substr($raw, $size), 'headers' => $head, 'location' => trim($match[1] ?? '')];
}
function token(array $response, string $name = 'csrf_token'): string {
    if (!preg_match('/name="' . preg_quote($name, '/') . '"\s+value="([a-f0-9]+)"/', $response['body'], $match)) {
        throw new RuntimeException('Token ausente: ' . $name . ' (HTTP ' . $response['status'] . ')');
    }
    return $match[1];
}
function scalar(string $sql, array $args = []): mixed {
    global $conn;
    $stmt = $conn->prepare($sql); $stmt->execute($args); return $stmt->fetchColumn();
}
function login(string $who, string $email, string $password): array {
    $page = request($who, '/app/views/auth/login.php');
    return request($who, '/app/views/auth/processa-login.php', ['email'=>$email, 'password'=>$password, 'csrf_token'=>token($page)]);
}
try {
    // Primeiro autentica fixture existente: confirma que o servidor usa o banco de teste.
    $insert = $conn->prepare('INSERT INTO Usuario(nome_user,email_user,tel_user,senha_user,date_user,email_verificado) VALUES(?,?,?,?,?,TRUE)');
    $ids = [];
    foreach (['a','b'] as $who) {
        $insert->execute(['HTTP ' . $who, "$who-$run@localhost.test", '11912345678', password_hash($password, PASSWORD_DEFAULT), '2000-01-01']);
        $ids[$who] = (int)$conn->lastInsertId();
    }
    $auth = login('a', "a-$run@localhost.test", $password);
    if (!str_contains($auth['location'], 'Painel-inicial.php')) throw new RuntimeException('Servidor não autenticou fixture do banco descartável.');
    verify(true, 'login real com fixture do banco isolado');
    verify(str_contains($auth['headers'], 'HttpOnly') && str_contains($auth['headers'], 'SameSite=Lax'), 'cookie de sessão protegido');
    $sport = (int)scalar('SELECT id_esporte FROM Esporte ORDER BY id_esporte LIMIT 1');
    $page = request('c', '/app/views/auth/cadastro.php');
    $form = ['name-txt'=>'Cadastro HTTP', 'email-txt'=>"c-$run@localhost.test", 'telefone-tel'=>'11912345678',
        'Senha-pass'=>$password, 'confirmar-senha'=>$password, 'data-nasc'=>'2030-01-01', 'csrf_token'=>token($page)];
    $invalid = request('c', '/app/views/auth/insert-user.php', $form);
    verify(str_contains($invalid['location'], 'erro=dados'), 'cadastro recusa nascimento futuro');
    $form['data-nasc'] = '2000-02-31';
    verify(str_contains(request('c', '/app/views/auth/insert-user.php', $form)['location'], 'erro=dados'), 'cadastro recusa data inexistente');
    $form['data-nasc'] = '2000-01-01';
    verify(str_contains(request('c', '/app/views/auth/insert-user.php', $form)['location'], 'verificar-email.php'), 'cadastro inicia confirmação');
    $page = request('c', '/app/views/auth/verificar-email.php');
    preg_match('/Código:\s*<strong>([0-9]{6})<\/strong>/u', $page['body'], $code);
    if (!isset($code[1])) throw new RuntimeException('Servidor local deve usar ZUBBO_ENV=test e ZUBBO_DEMO_MODE=true.');
    $verified = request('c', '/app/views/auth/processa-verificacao.php', ['codigo'=>$code[1], 'csrf_token'=>token($page)]);
    verify(str_contains($verified['location'], 'escolher-esportes.php'), 'código confirma cadastro');
    $page = request('c', '/app/views/auth/escolher-esportes.php');
    $bad = request('c', '/app/views/auth/salvar-esportes.php', ['esportes'=>(string)$sport, 'csrf_token'=>token($page)]);
    verify($bad['status'] < 500 && str_contains($bad['location'], 'erro=nenhum'), 'esportes escalar recusado sem erro fatal');
    $saved = request('c', '/app/views/auth/salvar-esportes.php', ['esportes'=>[$sport], 'csrf_token'=>token($page)]);
    verify(str_contains($saved['location'], 'login.php') && (int)scalar('SELECT COUNT(*) FROM Usuario WHERE email_user=? AND email_verificado=TRUE', ["c-$run@localhost.test"]) === 1, 'cadastro e esportes persistidos');
    $ids['c'] = (int)scalar('SELECT id_user FROM Usuario WHERE email_user=?', ["c-$run@localhost.test"]);
    $hash = (string)scalar('SELECT senha_user FROM Usuario WHERE id_user=?', [$ids['c']]);
    verify($hash !== $password && password_verify($password, $hash), 'senha armazenada com hash válido');
    verify(str_contains(login('c', "c-$run@localhost.test", $password)['location'], 'Painel-inicial.php'), 'login após cadastro completo');
    login('b', "b-$run@localhost.test", $password);
    verify(request('anon', '/app/views/painel/Painel-inicial.php')['status'] === 302, 'painel exige sessão');
    verify(request('a', '/app/views/chats/salvar-grupo.php', ['nome_grupo'=>'Sem CSRF'])['status'] === 403, 'grupo recusa POST sem CSRF');
    $conn->prepare('INSERT INTO Amizade(id_user_1,id_user_2) VALUES(?,?)')->execute([$ids['a'],$ids['b']]);
    $page = request('a', '/app/views/chats/criar-grupos.php');
    $csrf = token($page);
    $bad = request('a', '/app/views/chats/salvar-grupo.php', ['nome_grupo'=>'Inválido', 'participantes'=>(string)$ids['b'], 'csrf_token'=>$csrf]);
    verify($bad['status'] < 500, 'participantes escalar recusado sem erro fatal');
    $created = request('a', '/app/views/chats/salvar-grupo.php', ['nome_grupo'=>"Grupo $run", 'participantes'=>[$ids['b']], 'csrf_token'=>$csrf]);
    $group = (int)scalar('SELECT id_grupo FROM Grupo WHERE nome_grupo=?', ["Grupo $run"]);
    $conversation = (int)scalar('SELECT id_conversa FROM Grupo WHERE id_grupo=?', [$group]);
    verify($group > 0 && str_contains($created['location'], 'criado=1'), 'grupo criado pelo endpoint real');
    verify((int)scalar('SELECT COUNT(*) FROM Participantes_Conversa WHERE id_conversa=?', [$conversation]) === 2, 'criador e amigo no grupo');
    $badEdit = request('a', '/app/views/chats/salvar-edicao-grupo.php', ['id_grupo'=>$group,'nome_grupo'=>"Grupo $run",'adicionar'=>'inválido','csrf_token'=>$csrf]);
    verify($badEdit['status'] < 500 && str_contains($badEdit['location'], 'editar-grupo.php'), 'edição recusa lista escalar sem erro fatal');
    verify((int)scalar("SELECT COUNT(*) FROM Notificacao WHERE id_destinatario=? AND id_conversa=? AND tipo='grupo_adicionado'", [$ids['b'],$conversation]) === 1, 'novo membro recebe aviso de grupo');
    $page = request('c', '/app/views/chats/chat-grupo.php?id_conversa=' . $conversation);
    verify($page['status'] !== 200, 'terceiro não acessa chat do grupo');
    $eventPage = '/app/views/eventos/criar-evento.php?id_grupo=' . $group;
    verify(request('c', $eventPage)['status'] === 403, 'terceiro não cria evento do grupo');
    verify(request('b', $eventPage)['status'] === 403, 'integrante comum não abre criação de evento do grupo');
    $settings = request('b', '/app/views/chats/configuracoes-grupo.php?id_grupo='.$group);
    verify($settings['status'] === 200 && str_contains($settings['body'], 'Integrantes do grupo')
        && str_contains($settings['body'], '../perfil/perfil-ver.php?id='.$ids['a'])
        && str_contains($settings['body'], '../perfil/perfil.php'), 'integrante comum vê participantes e links de perfil');
    verify(!str_contains($settings['body'], 'Criar evento e convidar o grupo')
        && !str_contains($settings['body'], 'action="promover-moderador.php"'), 'integrante comum não recebe ações de moderação');
    $pair = $conn->query("SELECT p.id_local,p.id_esporte FROM Local_Esporte_Permitido p JOIN LocalEsp l ON l.id_local=p.id_local JOIN Esporte e ON e.id_esporte=p.id_esporte WHERE l.status_local='aprovado' AND e.nome_esporte='Futsal' LIMIT 1")->fetch();
    $page = request('a', $eventPage);
    $day = (new DateTimeImmutable('2031-01-02'))->modify('+' . (int)scalar('SELECT COUNT(*) FROM Evento') . ' days')->format('Y-m-d');
    $event = ['nome_evento'=>"Evento $run", 'data_evento'=>$day, 'horario_evento'=>'15:00', 'duracao_minutos'=>60,
        'id_grupo'=>$group, 'id_local'=>$pair['id_local'], 'id_esporte'=>$pair['id_esporte'], 'csrf'=>token($page, 'csrf')];
    $forgedEvent = $event;
    $forgedEvent['csrf'] = token(request('b', '/app/views/eventos/criar-evento.php'), 'csrf');
    verify(request('b', '/app/views/eventos/criar-evento.php', $forgedEvent)['status'] === 403
        && (int)scalar('SELECT COUNT(*) FROM Evento WHERE nome_evento=?',[$event['nome_evento']]) === 0, 'POST direto de integrante comum não cria evento do grupo');
    $created = request('a', '/app/views/eventos/criar-evento.php', $event);
    $eventId = (int)scalar('SELECT id_evento FROM Evento WHERE nome_evento=?', [$event['nome_evento']]);
    verify($created['status'] === 303 && $eventId > 0, 'evento de grupo criado por HTTP');
    verify((int)scalar('SELECT COUNT(*) FROM Grupo_Evento WHERE id_grupo=? AND id_evento=?', [$group,$eventId]) === 1, 'evento vinculado ao grupo');
    verify((int)scalar("SELECT COUNT(*) FROM Notificacao WHERE id_evento=? AND id_destinatario=? AND tipo='convite_evento'", [$eventId,$ids['b']]) === 1, 'membro recebe convite de evento');
    $event['csrf'] = token(request('a', $eventPage), 'csrf');
    $event['nome_evento'] = "Conflito $run";
    $conflict = request('a', '/app/views/eventos/criar-evento.php', $event);
    verify($conflict['status'] === 200 && str_contains($conflict['body'], 'já possui') && (int)scalar('SELECT COUNT(*) FROM Evento WHERE nome_evento=?', [$event['nome_evento']]) === 0, 'sobreposição recusada pelo endpoint');
    $page = request('b', '/app/views/eventos/lista-presenca.php?id_evento=' . $eventId);
    $requestSeat = request('b', '/app/views/eventos/vaga-evento-acao.php', ['id_evento'=>$eventId,'time_num'=>1,'numero_vaga'=>1,'acao'=>'solicitar','csrf'=>token($page,'csrf')]);
    $seat = (int)scalar("SELECT id_solicitacao FROM Solicitacao_Vaga_Evento WHERE id_evento=? AND id_user=? AND status_solicitacao='pendente'", [$eventId,$ids['b']]);
    verify($requestSeat['status'] === 303 && $seat > 0, 'solicitação de vaga real');
    $page = request('a', '/app/views/eventos/lista-presenca.php?id_evento=' . $eventId);
    request('a', '/app/views/eventos/vaga-evento-acao.php', ['id_evento'=>$eventId,'id_solicitacao'=>$seat,'acao'=>'aprovar','csrf'=>token($page,'csrf')]);
    verify(scalar('SELECT status_solicitacao FROM Solicitacao_Vaga_Evento WHERE id_solicitacao=?', [$seat]) === 'aprovada', 'organizador aprova vaga');
    verify((int)scalar("SELECT COUNT(*) FROM Notificacao WHERE id_evento=? AND tipo='vaga_aprovada' AND id_destinatario=?", [$eventId,$ids['b']]) === 1, 'aprovação notifica participante');
    $count = json_decode(request('b', '/app/views/notificacoes/buscar-notificacoes.php')['body'], true);
    verify(($count['quantidade'] ?? 0) >= 3, 'contador inclui grupo, convite e aprovação');
    $page = request('b', '/app/views/notificacoes/notificacoes.php');
    verify($page['status'] === 200 && (int)scalar('SELECT COUNT(*) FROM Notificacao WHERE id_destinatario=? AND (lida=FALSE OR data_leitura IS NULL)', [$ids['b']]) === 0, 'caixa marca todas as categorias como lidas');
    $notification = (int)scalar("SELECT id_notificacao FROM Notificacao WHERE id_evento=? AND id_destinatario=? LIMIT 1", [$eventId,$ids['b']]);
    verify(str_contains(request('b', '/app/views/notificacoes/abrir-notificacao.php?id=' . $notification)['location'], 'detalhes-evento.php'), 'notificação abre evento correto');
    verify(!str_contains(request('c', '/app/views/notificacoes/abrir-notificacao.php?id=' . $notification)['location'], 'detalhes-evento.php'), 'notificação de outro usuário protegida');
    $conn->prepare('UPDATE Notificacao SET data_leitura=DATE_SUB(NOW(),INTERVAL 6 MINUTE) WHERE id_destinatario=?')->execute([$ids['b']]);
    request('b', '/app/views/notificacoes/buscar-notificacoes.php');
    verify((int)scalar('SELECT COUNT(*) FROM Notificacao WHERE id_destinatario=? AND lida=TRUE', [$ids['b']]) === 0, 'polling remove lidas após cinco minutos');
    $map = request('a', '/app/views/painel/Painel-inicial.php');
    verify($map['status'] === 200 && str_contains($map['body'], 'new mapboxgl.Map') && str_contains($map['body'], 'new mapboxgl.Marker'), 'painel renderiza mapa e marcadores');
    $search = request('a', '/app/views/painel/sugestoes-busca.php?q=Diadema');
    $json = json_decode($search['body'], true);
    verify($search['status'] === 200 && count($json['locais'] ?? []) > 0, 'busca de locais do mapa retorna dados');
    $csrf = token(request('a', '/app/views/chats/criar-grupos.php'));
    $push = '/app/views/notificacoes/push-inscricao.php';
    verify(request('a', $push, ['acao'=>'ativar'])['status'] === 403, 'push recusa POST sem CSRF');
    verify(request('a', $push, ['acao'=>'ativar','subscription'=>'{}','csrf_token'=>$csrf])['status'] === 400, 'push recusa inscrição inválida');
    $subscription = json_encode(['endpoint'=>'https://fcm.googleapis.com/fcm/send/fixture-' . $run,
        'keys'=>['p256dh'=>str_repeat('A',87),'auth'=>str_repeat('B',22)]]);
    verify(request('a', $push, ['acao'=>'ativar','subscription'=>$subscription,'csrf_token'=>$csrf])['status'] === 503, 'push informa ausência de VAPID');
    verify(request('a', '/app/views/notificacoes/push-teste.php', ['csrf_token'=>$csrf])['status'] === 503, 'envio de teste informa ausência de VAPID');
    $worker = request('anon', '/sw.js');
    verify($worker['status'] === 200 && str_contains($worker['headers'], 'application/javascript'), 'Service Worker servido como JavaScript');
    foreach (['/config/database.php','/database/scripts/criacaotables.sql','/vendor/autoload.php','/.local/app_zubbo-before-tests.sql','/tests/http-smoke.php','/app/views/admin/_auth.php'] as $path) {
        verify(request('anon', $path)['status'] === 404, 'arquivo interno protegido: ' . $path);
    }
    $promotion = '/app/views/chats/promover-moderador.php';
    $csrfB = token(request('b', '/app/views/chats/criar-grupos.php'));
    $csrfC = token(request('c', '/app/views/chats/criar-grupos.php'));
    verify(request('a', $promotion)['status'] === 405, 'promoção exige POST');
    verify(request('a', $promotion, ['id_grupo'=>$group,'id_user'=>$ids['b']])['status'] === 403, 'promoção exige CSRF');
    request('b', $promotion, ['id_grupo'=>$group,'id_user'=>$ids['b'],'csrf_token'=>$csrfB]);
    request('c', $promotion, ['id_grupo'=>$group,'id_user'=>$ids['b'],'csrf_token'=>$csrfC]);
    verify((int)scalar('SELECT COUNT(*) FROM Grupo_Moderador WHERE id_grupo=?',[$group]) === 0, 'integrante comum e terceiro não promovem');
    $promoted = request('a', $promotion, ['id_grupo'=>$group,'id_user'=>$ids['b'],'csrf_token'=>$csrf]);
    verify($promoted['status'] === 303 && (int)scalar('SELECT COUNT(*) FROM Grupo_Moderador WHERE id_grupo=? AND id_user=?',[$group,$ids['b']]) === 1, 'criador promove integrante pelo endpoint real');
    verify(request('b', $eventPage)['status'] === 200, 'integrante promovido pode abrir criação de evento do grupo');
    verify(request('b', '/app/views/chats/editar-grupo.php?id_grupo='.$group)['status'] === 200, 'moderador acessa edição');
    request('b', '/app/views/chats/salvar-edicao-grupo.php', ['id_grupo'=>$group,'nome_grupo'=>"Moderado $run",'remover'=>[$ids['a']],'csrf_token'=>$csrfB]);
    verify(scalar('SELECT nome_grupo FROM Grupo WHERE id_grupo=?',[$group]) === "Moderado $run", 'moderador salva edição no MySQL');
    verify((int)scalar('SELECT COUNT(*) FROM Participantes_Conversa WHERE id_conversa=?',[$conversation]) === 2, 'POST adulterado não remove responsável');
    request('b', $promotion, ['id_grupo'=>$group,'id_user'=>$ids['c'],'csrf_token'=>$csrfB]);
    verify((int)scalar('SELECT COUNT(*) FROM Grupo_Moderador WHERE id_grupo=?',[$group]) === 1, 'moderador não promove pessoa fora do grupo');
    $messageCount = (int)scalar('SELECT COUNT(*) FROM Mensagem WHERE id_conversa=?',[$conversation]);
    $left = request('a', '/app/views/chats/sair-grupo.php', ['id_conversa'=>$conversation,'csrf_token'=>$csrf]);
    verify($left['status'] === 303 && (int)scalar('SELECT id_criador FROM Grupo WHERE id_grupo=?',[$group]) === $ids['b'], 'responsável sai e outro moderador assume');
    verify(request('a', '/app/views/chats/configuracoes-grupo.php?id_grupo='.$group)['status'] === 403, 'ex-integrante perde acesso às configurações');
    verify((int)scalar('SELECT COUNT(*) FROM Mensagem WHERE id_conversa=?',[$conversation]) === $messageCount, 'sucessão não publica mensagem no chat');
    $last = request('b', '/app/views/chats/sair-grupo.php', ['id_conversa'=>$conversation,'csrf_token'=>$csrfB]);
    verify($last['status'] === 303 && (int)scalar('SELECT COUNT(*) FROM Participantes_Conversa WHERE id_conversa=?',[$conversation]) === 0, 'último moderador pode sair pelo endpoint');
    request('a', '/app/views/auth/logout.php', ['csrf_token'=>$csrf]);
    verify(request('a', '/app/views/painel/Painel-inicial.php')['status'] === 302, 'logout encerra sessão');
    $conn->prepare("UPDATE Usuario SET status_user='suspenso' WHERE id_user=?")->execute([$ids['b']]);
    verify(request('b', '/app/views/painel/Painel-inicial.php')['status'] === 302, 'conta suspensa perde sessão');
} catch (Throwable $error) {
    verify(false, 'execução interrompida: ' . $error->getMessage());
} finally {
    foreach ($jars as $jar) if (is_file($jar)) unlink($jar);
}
echo "$checks verificações; $failures falhas. Fixtures mantidas somente no banco descartável.\n";
exit($failures > 0 ? 1 : 0);
