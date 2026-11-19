<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/BanimentoService.php';
require_once __DIR__ . '/support/schema-contract.php';

function ban_check(bool $ok, string $label): void
{
    if (!$ok) throw new RuntimeException($label);
    echo "OK: $label\n";
}
function ban_fixture(): PDO
{
    if (getenv('ZUBBO_BAN_TEST_MYSQL') === '1') {
        $database = (string)getenv('ZUBBO_DB_NAME');
        if (getenv('ZUBBO_TEST_ALLOW_SCHEMA') !== '1' || !preg_match('/^zubbo_test_ban_[a-z0-9_]+$/D', $database)) {
            throw new RuntimeException('Use banco descartável zubbo_test_ban_* e ZUBBO_TEST_ALLOW_SCHEMA=1.');
        }
        $db = new PDO('mysql:host='.(getenv('ZUBBO_DB_HOST') ?: 'localhost').';port='.(getenv('ZUBBO_DB_PORT') ?: '3306').';dbname='.$database.';charset=utf8mb4',
            getenv('ZUBBO_DB_USER') ?: 'root', getenv('ZUBBO_DB_PASSWORD') ?: '',
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
        $tables=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        static $initialized=false;
        if (!$initialized && $tables) throw new RuntimeException('O banco deve estar inicialmente vazio.');
        if (array_diff($tables,array_keys(zubbo_schema_contract()))) throw new RuntimeException('Tabela desconhecida no banco de teste.');
        $initialized=true;
        $db->exec('SET FOREIGN_KEY_CHECKS=0');
        try { foreach ($tables as $table) $db->exec('DROP TABLE `'.$table.'`'); }
        finally { $db->exec('SET FOREIGN_KEY_CHECKS=1'); }
        $sql=preg_replace('/^\s*--[^\r\n]*/m','',file_get_contents(__DIR__.'/../database/scripts/criacaotables.sql'));
        foreach (zubbo_sql_parts($sql,';') as $statement) $db->exec($statement);
    } else {
        $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec('PRAGMA foreign_keys=ON');
    // Testa as relações e ações FK do contrato real; tipos e locks MySQL ficam no CI.
    foreach (zubbo_schema_contract() as $name => $table) {
        $defs = [];
        foreach ($table['columns'] as $column => $spec) {
            if ($spec['generated'] !== null) continue;
            $type = preg_match('/^(?:tinyint|smallint|int|bigint)/', $spec['type']) ? 'INTEGER' : 'TEXT';
            $def = '"'.$column.'" '.$type;
            if ($spec['auto']) $def .= ' PRIMARY KEY AUTOINCREMENT';
            elseif (!$spec['nullable']) $def .= ' NOT NULL';
            if ($spec['default'] !== null) $def .= ' DEFAULT '.($spec['default'] === 'current_timestamp' ? 'CURRENT_TIMESTAMP' : $db->quote($spec['default']));
            $defs[] = $def;
        }
        foreach ($table['indexes'] as $index) {
            $cols = explode(',', $index['columns']);
            if (array_filter($cols, fn($col) => $table['columns'][$col]['generated'] !== null || $table['columns'][$col]['auto'])) continue;
            if ($index['primary']) $defs[] = 'PRIMARY KEY ('.$index['columns'].')';
            elseif ($index['unique']) $defs[] = 'UNIQUE ('.$index['columns'].')';
        }
        foreach ($table['foreign_keys'] as $fk) {
            $defs[] = 'FOREIGN KEY ('.$fk['column'].') REFERENCES '.$fk['table'].'('.$fk['target'].') ON DELETE '.$fk['delete'];
        }
        $db->exec('CREATE TABLE "'.$name.'" ('.implode(',', $defs).')');
    }
    }
    $db->exec("INSERT INTO Usuario(id_user,nome_user,email_user,tel_user,senha_user,date_user) VALUES
        (1,'Alvo','alvo@localhost.test','11999999999','synthetic','2000-01-01'),
        (2,'Outro','outro@localhost.test','11999999999','synthetic','2000-01-01'),
        (3,'Admin','admin@localhost.test','11999999999','synthetic','2000-01-01');
        INSERT INTO Administrador(id_adm,id_user,nome_adm,email_adm,senha_adm,ativo) VALUES
        (8,1,'Alvo','antigo@localhost.test','synthetic',0),(9,3,'Admin','adm@localhost.test','synthetic',1);
        INSERT INTO Esporte(id_esporte,nome_esporte) VALUES(1,'Teste');
        INSERT INTO LocalEsp(id_local,nome_local,endereco_local,id_criador) VALUES(1,'Local','Endereco',1);
        INSERT INTO Local_Esporte_Confirmacao(id_local,id_esporte,id_adm,referencia_contato) VALUES(1,1,8,'Referencia de teste');
        INSERT INTO Conversa(id_conversa,tipo_conversa) VALUES(100,'grupo'),(200,'privado'),(300,'comunidade'),(400,'grupo');
        INSERT INTO Grupo(id_grupo,id_conversa,id_criador,nome_grupo) VALUES(10,100,1,'Grupo alvo'),(20,400,2,'Outro grupo');
        INSERT INTO Grupo_Moderador VALUES(10,1);
        INSERT INTO Comunidade(id_comunidade,id_conversa,id_esporte,nome_comunidade,cidade_comunidade) VALUES(1,300,1,'Comunidade','Cidade');
        INSERT INTO Participantes_Conversa(id_user,id_conversa) VALUES(1,100),(2,100),(1,200),(2,200),(1,300),(2,300),(2,400);
        INSERT INTO Equipe(id_equipe,nome_equipe,id_esporte,id_criador) VALUES(1,'Equipe',1,1);
        INSERT INTO ParticipantesEquipe(id_user,id_equipe) VALUES(1,1),(2,1);
        INSERT INTO Evento(id_evento,nome_evento,data_evento,horario_evento,id_esporte,id_local,id_criador) VALUES
        (1,'Do alvo','2030-01-01','15:00',1,1,1),(2,'Do outro','2030-01-02','15:00',1,1,2);
        INSERT INTO Lista_Evento VALUES(1,1),(1,2),(2,1),(2,2);
        INSERT INTO Solicitacao_Vaga_Evento(id_evento,id_user,status_solicitacao,time_num,numero_vaga) VALUES(2,1,'aprovada',1,1);
        INSERT INTO Fila_Espera_Evento(id_evento,id_user) VALUES(2,1),(1,2);
        INSERT INTO Mensagem(id_mensagem,id_conversa,id_remetente,mensagem) VALUES(1,100,1,'alvo'),(2,100,2,'outro'),(3,200,1,'alvo privado');
        INSERT INTO Notificacao(id_destinatario,id_remetente,id_mensagem) VALUES(1,2,2),(2,1,1),(2,2,2);
        INSERT INTO Sugestao_Esporte(id_sugestao,nome_esporte,id_user) VALUES(1,'Do alvo',1),(2,'Do outro',2);
        INSERT INTO Voto_Sugestao VALUES(1,2),(2,1),(2,2);
        INSERT INTO Denuncia(id_denuncia,id_denunciante,id_denunciado,motivo) VALUES(1,1,2,'teste'),(2,2,1,'teste'),(3,2,3,'teste');
        INSERT INTO Acao_Administrativa(id_acao,id_adm,id_user,id_denuncia,tipo_acao,motivo) VALUES
        (1,9,1,NULL,'teste','alvo'),(2,8,2,NULL,'teste','autor alvo'),(3,9,NULL,2,'teste','denuncia do alvo'),(4,9,2,NULL,'teste','outro');
        INSERT INTO Usuario_Esporte VALUES(1,1),(2,1);
        INSERT INTO Amizade(id_user_1,id_user_2) VALUES(1,2);
        INSERT INTO Solicitacao_Amizade(id_remetente,id_destinatario) VALUES(2,1);
        INSERT INTO Bloqueio_Usuario(id_bloqueador,id_bloqueado) VALUES(1,3);
        INSERT INTO PushSubscription(id_user,endpoint_hash,dados_json) VALUES(1,'fixture','{}');
        INSERT INTO Recuperacao_Senha(id_user,token_hash,expiracao) VALUES(1,'fixture','2030-01-01');
        INSERT INTO Verificacao_Email(id_user,codigo,expiracao) VALUES(1,'123456','2030-01-01');");
    return $db;
}
function ban_snapshot(PDO $db): array
{
    $out=[];
    foreach (array_keys(zubbo_schema_contract()) as $table) $out[$table]=$db->query('SELECT * FROM '.$table)->fetchAll(PDO::FETCH_ASSOC);
    return $out;
}
$db=ban_fixture();
$db->beginTransaction();
zubbo_banir_usuario($db,1);
ban_check($db->inTransaction(),'serviço não confirma a transação do chamador');
$db->commit();
ban_check($db->query('SELECT status_user FROM Usuario WHERE id_user=1')->fetchColumn()==='banido','conta permanece bloqueada');
foreach (zubbo_schema_contract() as $table=>$spec) {
    foreach ($spec['foreign_keys'] as $fk) {
        if ($fk['table']==='Usuario') ban_check((int)$db->query('SELECT COUNT(*) FROM '.$table.' WHERE '.$fk['column'].'=1')->fetchColumn()===0,'vínculo removido: '.$table.'.'.$fk['column']);
    }
}
ban_check((int)$db->query('SELECT id_criador FROM Grupo WHERE id_grupo=10')->fetchColumn()===2,'grupo transfere responsabilidade a integrante ativo');
ban_check((int)$db->query('SELECT COUNT(*) FROM Grupo')->fetchColumn()===2,'grupos e seus demais integrantes são preservados');
ban_check($db->query('SELECT status_evento FROM Evento WHERE id_evento=1')->fetchColumn()==='cancelado','evento do banido cancelado');
ban_check($db->query('SELECT status_evento FROM Evento WHERE id_evento=2')->fetchColumn()==='ativo','evento de outro organizador preservado');
ban_check((int)$db->query('SELECT COUNT(*) FROM Acao_Administrativa')->fetchColumn()===1,'histórico relacionado removido sem apagar ações de terceiros');
ban_check((int)$db->query('SELECT COUNT(*) FROM Mensagem WHERE id_remetente=2')->fetchColumn()===1,'mensagens de terceiros preservadas');
ban_check((int)$db->query('SELECT COUNT(*) FROM Local_Esporte_Confirmacao')->fetchColumn()===1,'confirmações de locais preservadas');
$before=ban_snapshot($db);
$db->beginTransaction(); zubbo_banir_usuario($db,1); $db->commit();
ban_check(ban_snapshot($db)===$before,'repetir o banimento não altera os demais registros');
$db=ban_fixture(); $before=ban_snapshot($db);
$db->beginTransaction();
try { zubbo_banir_usuario($db,3); throw new RuntimeException('Administrador ativo banido indevidamente.'); }
catch (DomainException $e) { $db->rollBack(); }
ban_check(ban_snapshot($db)===$before,'conta administrativa ativa protegida');
$db->exec($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'
    ? "CREATE TRIGGER fail_ban BEFORE UPDATE ON Usuario FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic failure'"
    : "CREATE TRIGGER fail_ban BEFORE UPDATE OF status_user ON Usuario BEGIN SELECT RAISE(ABORT,'synthetic failure'); END;");
$db->beginTransaction();
try { zubbo_banir_usuario($db,1); throw new RuntimeException('Falha esperada ausente.'); }
catch (PDOException $e) { $db->rollBack(); }
ban_check(ban_snapshot($db)===$before,'erro reverte todas as remoções e a sucessão do grupo');
echo 'Banimento: testes concluídos com '.$db->getAttribute(PDO::ATTR_DRIVER_NAME).".\n";
