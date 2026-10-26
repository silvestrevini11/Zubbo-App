<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/GrupoService.php';

// In-memory integration tests: no application database or real users are accessed.
function group_db(): PDO
{
    $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec('PRAGMA foreign_keys=ON;
        CREATE TABLE Usuario(id_user INTEGER PRIMARY KEY,status_user TEXT);
        CREATE TABLE Grupo(id_grupo INTEGER PRIMARY KEY,id_conversa INTEGER UNIQUE,id_criador INTEGER);
        CREATE TABLE Participantes_Conversa(id_conversa INTEGER,id_user INTEGER,PRIMARY KEY(id_conversa,id_user));
        CREATE TABLE Grupo_Moderador(id_grupo INTEGER REFERENCES Grupo(id_grupo),id_user INTEGER REFERENCES Usuario(id_user),PRIMARY KEY(id_grupo,id_user));
        CREATE TABLE Notificacao(id_conversa INTEGER,id_destinatario INTEGER);
        INSERT INTO Usuario VALUES (1,"ativo"),(2,"ativo"),(3,"ativo"),(4,"banido"),(5,"ativo"),(6,"suspenso");
        INSERT INTO Grupo VALUES(10,100,1),(20,200,5);
        INSERT INTO Participantes_Conversa VALUES(100,1),(100,2),(100,3),(100,4),(100,6),(200,5);
        INSERT INTO Notificacao VALUES(100,1),(100,2),(100,3);');
    return $db;
}
function group_assert(bool $ok, string $label): void
{
    if (!$ok) throw new RuntimeException($label);
    echo "OK: $label\n";
}
function group_denied(callable $action, string $label): void
{
    try { $action(); } catch (DomainException $e) { group_assert(true, $label); return; }
    throw new RuntimeException('Permissão indevida: ' . $label);
}
function group_row(PDO $db): array { return $db->query('SELECT * FROM Grupo WHERE id_grupo=10')->fetch(PDO::FETCH_ASSOC); }

$db=group_db();
group_denied(fn()=>zubbo_grupo_promover($db,10,2,3),'integrante comum não promove');
group_denied(fn()=>zubbo_grupo_promover($db,10,5,3),'moderador de outro grupo não promove');
group_denied(fn()=>zubbo_grupo_promover($db,10,1,5),'não promove usuário fora do grupo');
group_denied(fn()=>zubbo_grupo_promover($db,10,1,4),'não promove conta banida');
group_denied(fn()=>zubbo_grupo_promover($db,10,1,6),'não promove conta suspensa');
zubbo_grupo_promover($db,10,1,2);
zubbo_grupo_promover($db,10,1,2);
group_assert((int)$db->query('SELECT COUNT(*) FROM Grupo_Moderador')->fetchColumn()===1,'promoção repetida é idempotente');
group_assert(zubbo_grupo_moderador($db,group_row($db),2),'promovido recebe permissão');
zubbo_grupo_promover($db,10,2,3);
group_assert(zubbo_grupo_moderador($db,group_row($db),3),'moderador pode promover outro integrante');
zubbo_grupo_sair($db,100,2);
group_assert(!zubbo_grupo_moderador($db,group_row($db),2),'saída revoga moderação e participação');
group_assert(zubbo_grupo_moderador($db,group_row($db),3),'moderadores existentes mantêm permissão');
group_denied(fn()=>zubbo_grupo_promover($db,10,2,1),'ex-moderador não conserva acesso');
group_denied(fn()=>zubbo_grupo_sair($db,100,2),'saída de não participante é recusada');

$db=group_db();
zubbo_grupo_sair($db,100,1);
$grupo=group_row($db);
group_assert(in_array((int)$grupo['id_criador'],[2,3],true),'criador sai e sucessor é um integrante ativo');
group_assert(zubbo_grupo_moderador($db,$grupo,(int)$grupo['id_criador']),'sucessor possui permissão real');
group_assert((int)$db->query('SELECT COUNT(*) FROM Notificacao')->fetchColumn()===2,'saída limpa alertas do ex-integrante sem anunciar sucessão');
zubbo_grupo_sair($db,100,(int)$grupo['id_criador']);
$grupo=group_row($db);
group_assert(in_array((int)$grupo['id_criador'],[2,3],true),'sucessões consecutivas mantêm moderação');
zubbo_grupo_sair($db,100,(int)$grupo['id_criador']);
group_assert(group_row($db)['id_criador']===null,'sem integrante ativo, saída continua permitida');

$db=group_db();
zubbo_grupo_sair($db,100,2);
group_assert((int)$db->query('SELECT COUNT(*) FROM Grupo_Moderador')->fetchColumn()===0,'saída de integrante comum não promove ninguém');
group_assert((int)group_row($db)['id_criador']===1,'saída comum preserva responsável');
zubbo_grupo_sair($db,200,5);
group_assert((int)$db->query('SELECT COUNT(*) FROM Participantes_Conversa WHERE id_conversa=200')->fetchColumn()===0,'último integrante pode sair');

$db=group_db();
zubbo_grupo_promover($db,10,1,2);
zubbo_grupo_sair($db,100,2);
group_assert(zubbo_grupo_moderador($db,group_row($db),3),'saída de moderador adicional escolhe novo integrante');

$db=group_db();
zubbo_grupo_promover($db,10,1,2);
zubbo_grupo_promover($db,10,1,3);
zubbo_grupo_sair($db,100,1);
group_assert(in_array((int)group_row($db)['id_criador'],[2,3],true),'sem candidato novo, responsável passa a moderador existente');

$db=group_db();
$db->exec("CREATE TRIGGER fail_successor BEFORE INSERT ON Grupo_Moderador BEGIN SELECT RAISE(ABORT,'test failure'); END;");
try { zubbo_grupo_sair($db,100,1); throw new RuntimeException('Falha não propagada.'); }
catch (PDOException $e) { /* Expected storage failure. */ }
group_assert((int)group_row($db)['id_criador']===1 && zubbo_grupo_moderador($db,group_row($db),1),'falha na sucessão reverte saída integralmente');
group_assert((int)$db->query('SELECT COUNT(*) FROM Notificacao')->fetchColumn()===3,'rollback preserva notificações');
echo "Moderação: testes SQLite em memória concluídos; locks MySQL exigem validação própria.\n";
