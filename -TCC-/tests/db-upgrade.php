<?php
declare(strict_types=1);

// Opt-in and explicit disposable name must be supplied by the caller before .env loading.
$testDatabase = (string)getenv('ZUBBO_DB_NAME');
if (PHP_SAPI !== 'cli' || getenv('ZUBBO_TEST_ALLOW_SCHEMA') !== '1'
    || !preg_match('/^zubbo_(?:ci|test_[a-z0-9_]+)$/D', $testDatabase)) {
    fwrite(STDERR, "Use ZUBBO_TEST_ALLOW_SCHEMA=1 e banco descartável vazio zubbo_ci ou zubbo_test_*.\n");
    exit(2);
}
require_once __DIR__ . '/support/schema-contract.php';
require_once __DIR__ . '/../config/security.php';

function upgrade_assert(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
    echo 'OK: ' . $message . PHP_EOL;
}
function upgrade_sql(PDO $db, string $sql): void
{
    // SQL files are controlled fixtures/migrations, with no DELIMITER or quoted semicolon splitting.
    $sql = preg_replace('/^\s*--[^\r\n]*/m', '', $sql);
    foreach (zubbo_sql_parts($sql, ';') as $statement) {
        if (preg_match('/^\s*(?:USE|CREATE\s+DATABASE|DROP\s+DATABASE)\b/i', $statement)) {
            throw new RuntimeException('SQL não pode mudar ou remover o banco selecionado.');
        }
        $query = $db->query($statement);
        while ($query->nextRowset()) { /* Drain SELECT from migration PREPARE/EXECUTE. */ }
        $query->closeCursor();
    }
}
function upgrade_file(PDO $db, string $relative): void
{
    $sql = file_get_contents(__DIR__ . '/../' . $relative);
    if ($sql === false) throw new RuntimeException('Arquivo SQL ausente: ' . $relative);
    upgrade_sql($db, $sql);
}
function upgrade_migrations(PDO $db, int $limit = 6): void
{
    $files = glob(__DIR__ . '/../database/migrations/*.sql'); sort($files);
    upgrade_assert(count($files) === 6, 'exatamente seis migrations ativas');
    foreach (array_slice($files, 0, $limit) as $path) upgrade_sql($db, file_get_contents($path));
}
function upgrade_tables(PDO $db): array
{
    return $db->query('SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE() ORDER BY table_name')->fetchAll(PDO::FETCH_COLUMN);
}
function upgrade_reset(PDO $db, string $database, array $knownTables): void
{
    // Never create/drop databases, and never delete an object not owned by this suite.
    upgrade_assert($db->query('SELECT DATABASE()')->fetchColumn() === $database, 'destino descartável continua selecionado');
    $tables = upgrade_tables($db);
    upgrade_assert(!array_diff(array_map('strtolower', $tables), array_map('strtolower', $knownTables)), 'somente tabelas conhecidas do teste antes da limpeza');
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    try { foreach ($tables as $table) $db->exec('DROP TABLE `' . $table . '`'); }
    finally { $db->exec('SET FOREIGN_KEY_CHECKS=1'); }
}
function upgrade_rows(PDO $db, ?array $projection = null): array
{
    $snapshot = [];
    foreach ($projection ?? array_fill_keys(upgrade_tables($db), null) as $table => $oldRows) {
        $fields = $oldRows ? implode(',', array_map(static fn($name) => '`' . $name . '`', array_keys($oldRows[0]))) : '*';
        $rows = $db->query("SELECT $fields FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        usort($rows, static fn($a, $b) => strcmp(json_encode($a), json_encode($b)));
        $snapshot[$table] = $rows;
    }
    return $snapshot;
}
function upgrade_structure(PDO $db): array
{
    $result = [];
    foreach ($db->query('SELECT table_name AS table_name,column_name AS column_name,column_type AS column_type,is_nullable AS is_nullable,column_default AS column_default,extra AS extra,collation_name AS collation_name,generation_expression AS generation_expression FROM information_schema.columns WHERE table_schema=DATABASE() ORDER BY table_name,column_name') as $row) {
        $row['extra'] = str_replace('DEFAULT_GENERATED', '', $row['extra']);
        $row['extra'] = trim(strtolower($row['extra']));
        $row['generation_expression'] = zubbo_normalize_expression((string)$row['generation_expression']);
        $result['columns'][$row['table_name']][$row['column_name']] = array_slice($row, 2);
    }
    // Ignore names and redundant nonunique FK indexes, but compare all unique constraints.
    foreach ($db->query("SELECT table_name AS table_name,index_name AS index_name,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols FROM information_schema.statistics WHERE table_schema=DATABASE() AND non_unique=0 GROUP BY table_name,index_name ORDER BY table_name,cols") as $row) {
        $result['unique'][$row['table_name']][] = [$row['index_name'] === 'PRIMARY', $row['cols']];
    }
    foreach ($db->query("SELECT k.table_name AS table_name,k.column_name AS column_name,k.referenced_table_name AS referenced_table_name,k.referenced_column_name AS referenced_column_name,r.delete_rule AS delete_rule,r.update_rule AS update_rule FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() ORDER BY k.table_name,k.column_name") as $row) $result['foreign_keys'][] = $row;
    foreach ($db->query('SELECT table_name AS table_name,engine AS engine,table_collation AS table_collation FROM information_schema.tables WHERE table_schema=DATABASE() ORDER BY table_name') as $row) $result['tables'][] = $row;
    return $result;
}
function upgrade_catalog(PDO $db): array
{
    return [
        'sports' => $db->query('SELECT nome_esporte,imagem_esporte,regras_esporte,jogadores_por_time,quantidade_times,exige_confirmacao_local FROM Esporte ORDER BY nome_esporte')->fetchAll(PDO::FETCH_ASSOC),
        'locations' => $db->query('SELECT nome_local,endereco_local,tipo_local,status_local,foto_local FROM LocalEsp ORDER BY nome_local,endereco_local')->fetchAll(PDO::FETCH_ASSOC),
        'links' => $db->query('SELECT l.nome_local,l.endereco_local,e.nome_esporte,p.fonte FROM Local_Esporte_Permitido p JOIN LocalEsp l ON l.id_local=p.id_local JOIN Esporte e ON e.id_esporte=p.id_esporte ORDER BY l.nome_local,l.endereco_local,e.nome_esporte')->fetchAll(PDO::FETCH_ASSOC),
    ];
}
function upgrade_legacy(PDO $db): void
{
    upgrade_file($db, 'database/archive/pre-consolidacao/criacaotables.sql');
    upgrade_file($db, 'tests/fixtures/upgrade-legacy.sql');
    upgrade_file($db, 'tests/fixtures/upgrade-data.sql');
}
function upgrade_preserved(PDO $db, array $before): void
{
    // Catalog additions are expected; compare only originally existing rows and columns.
    foreach ($before as $table => $rows) {
        if (!$rows) continue;
        foreach ($rows as $row) {
            if (strcasecmp($table, 'Solicitacao_Vaga_Evento') === 0) {
                $row['status_solicitacao'] = 'aprovada';
                unset($row['data_resposta']); // Defined transformation stamps the acceptance time.
            }
            $conditions = []; $values = [];
            foreach ($row as $column => $value) { $conditions[] = '`' . $column . '` <=> ?'; $values[] = $value; }
            $query = $db->prepare('SELECT COUNT(*) FROM `' . $table . '` WHERE ' . implode(' AND ', $conditions));
            $query->execute($values);
            upgrade_assert((int)$query->fetchColumn() === 1, 'preservar registro/vínculos em ' . $table);
        }
    }
    upgrade_assert((int)$db->query('SELECT COUNT(*) FROM Administrador WHERE id_adm=9001 AND id_user IS NULL')->fetchColumn() === 1, 'admin legado sem promoção automática por e-mail');
    upgrade_assert((int)$db->query('SELECT COUNT(*) FROM Notificacao WHERE id_notificacao=9001 AND data_leitura IS NOT NULL')->fetchColumn() === 1, 'notificação lida recebe data de leitura');
    upgrade_assert((int)$db->query("SELECT COUNT(*) FROM Solicitacao_Vaga_Evento WHERE id_solicitacao=9001 AND status_solicitacao='aprovada' AND data_resposta IS NOT NULL AND vaga_confirmada=1")->fetchColumn() === 1, 'vaga legada confirmada pela lista de participantes');
}
function upgrade_expected_failure(PDO $db, string $label, string $marker): void
{
    $before = upgrade_rows($db);
    try { upgrade_migrations($db); }
    catch (PDOException $error) {
        upgrade_assert(str_contains(strtolower($error->getMessage()), strtolower($marker)), "$label interrompido com marcador $marker");
        // Compare original columns only: additive DDL before a conflict is not transactional.
        upgrade_assert(upgrade_rows($db, $before) === $before, "$label preserva todos os registros existentes");
        return;
    }
    throw new RuntimeException($label . ': migration aceitou estrutura/dados conflitantes');
}

function upgrade_audit(int $expected, string $label, bool $strict = true, ?string $database = null): void
{
    $arguments = [PHP_BINARY, __DIR__ . '/audit-migrations.php'];
    if ($strict) $arguments[] = '--strict';
    $environment = getenv();
    if ($database !== null) $environment['ZUBBO_DB_NAME'] = $database;
    $process = proc_open($arguments, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment);
    if (!is_resource($process)) throw new RuntimeException('Não foi possível iniciar auditoria CLI.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $status = proc_close($process);
    upgrade_assert($status === $expected, "$label: auditoria CLI retorna $expected (recebido $status)");
    if ($expected === 1 || !$strict) {
        upgrade_assert(str_contains($output, 'pendente_ou_divergente'), "$label: divergência identificável no diagnóstico");
    }
    if ($expected === 2) upgrade_assert(str_contains($errors, 'Falha na auditoria'), "$label: erro de execução identificado");
}

$conn = null;
try {
    $conn = new PDO('mysql:host=' . (getenv('ZUBBO_DB_HOST') ?: 'localhost') . ';port=' . (getenv('ZUBBO_DB_PORT') ?: '3306')
        . ';dbname=' . $testDatabase . ';charset=utf8mb4', getenv('ZUBBO_DB_USER') ?: 'root', getenv('ZUBBO_DB_PASSWORD') ?: '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    upgrade_assert(upgrade_tables($conn) === [], 'banco inicialmente vazio; nenhuma base existente é reutilizada');
    $lock = $conn->prepare('SELECT GET_LOCK(?,0)'); $lock->execute(['zubbo_upgrade_' . $testDatabase]);
    upgrade_assert((int)$lock->fetchColumn() === 1, 'execução exclusiva da suíte');
    $conn->exec("SET SESSION sql_mode='STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    $conn->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
    $contract = zubbo_schema_contract(); $knownTables = array_keys($contract);
    $checks = zubbo_contract_checks($contract, $knownTables);
    upgrade_file($conn, 'database/scripts/criacaotables.sql');
    upgrade_file($conn, 'database/scripts/insertstables.sql');
    upgrade_assert(zubbo_contract_failures($conn, $checks) === [], 'instalação nova satisfaz contrato semântico');
    $referenceStructure = upgrade_structure($conn); $referenceCatalog = upgrade_catalog($conn);
    upgrade_assert(count($referenceCatalog['sports']) === 8 && count($referenceCatalog['locations']) === 40, 'catálogo novo contém oito esportes e 40 locais');
    upgrade_audit(0, 'instalação compatível');
    upgrade_audit(2, 'banco inexistente', true, 'zubbo_test_audit_missing_' . bin2hex(random_bytes(6)));
    upgrade_reset($conn, $testDatabase, $knownTables);

    foreach (['legado', 'parcial'] as $scenario) {
        echo "CENÁRIO: $scenario\n";
        upgrade_legacy($conn); $before = upgrade_rows($conn);
        if ($scenario === 'parcial') {
            upgrade_migrations($conn, 2);
            $conn->exec("ALTER TABLE Evento ADD nome_time_1 VARCHAR(30) NOT NULL DEFAULT 'Time 1'");
            $conn->exec('CREATE TABLE Fila_Espera_Evento(id_evento INT NOT NULL,id_user INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            $conn->exec('INSERT INTO Fila_Espera_Evento(id_evento,id_user) VALUES(9001,9002)');
        }
        upgrade_migrations($conn);
        upgrade_file($conn, 'database/scripts/insertstables.sql');
        $failures = zubbo_contract_failures($conn, $checks);
        upgrade_assert($failures === [], "$scenario: contrato semântico completo " . implode(',', $failures));
        upgrade_assert(upgrade_structure($conn) === $referenceStructure, "$scenario: estrutura equivale à instalação nova");
        upgrade_assert(upgrade_catalog($conn) === $referenceCatalog, "$scenario: catálogo e fontes equivalem à instalação nova");
        upgrade_preserved($conn, $before);
        if ($scenario === 'parcial') upgrade_assert((int)$conn->query('SELECT COUNT(*) FROM Fila_Espera_Evento WHERE id_evento=9001 AND id_user=9002')->fetchColumn() === 1, 'fila parcial preservada ao completar timestamp, chave e vínculos');
        $conn->exec("UPDATE Esporte SET regras_esporte='Regra editada',quantidade_times=3,exige_confirmacao_local=1 WHERE nome_esporte='Futebol'");
        $conn->exec("UPDATE LocalEsp SET foto_local='foto-editada.png' WHERE nome_local='Ginásio Poliesportivo Ayrton Senna'");
        $conn->exec("UPDATE Local_Esporte_Permitido SET fonte='Fonte editada manualmente' WHERE id_local=(SELECT id_local FROM LocalEsp WHERE nome_local='Ginásio Poliesportivo Ayrton Senna')");
        $snapshot = upgrade_rows($conn);
        upgrade_migrations($conn); upgrade_file($conn, 'database/scripts/insertstables.sql');
        upgrade_assert(upgrade_rows($conn) === $snapshot, "$scenario: reexecução preserva todos os campos, fontes e vínculos editados");
        upgrade_reset($conn, $testDatabase, $knownTables);
    }

    echo "CENÁRIO: duplicidades\n";
    upgrade_legacy($conn);
    $conn->exec('ALTER TABLE Administrador ADD id_user INT NULL');
    $conn->exec('UPDATE Administrador SET id_user=9001');
    $conn->exec("INSERT INTO Administrador(id_user,nome_adm,email_adm,senha_adm) VALUES(9001,'Duplicado','admin-dup@localhost.test','synthetic')");
    upgrade_expected_failure($conn, 'administradores duplicados', 'ERRO_administradores_duplicados');
    upgrade_reset($conn, $testDatabase, $knownTables);
    upgrade_legacy($conn);
    $conn->exec("INSERT INTO LocalEsp(nome_local,endereco_local,tipo_local) SELECT CONCAT(' ',nome_local),endereco_local,tipo_local FROM LocalEsp LIMIT 1");
    upgrade_expected_failure($conn, 'locais normalizados duplicados', 'ERRO_locais_duplicados');
    upgrade_reset($conn, $testDatabase, $knownTables);
    upgrade_legacy($conn);
    $conn->exec('INSERT INTO Lista_Evento(id_user,id_evento) VALUES(9002,9001)');
    $conn->exec("INSERT INTO Solicitacao_Vaga_Evento(id_evento,id_user,time_num,numero_vaga,status_solicitacao) VALUES(9001,9002,1,1,'pendente')");
    upgrade_expected_failure($conn, 'vagas legadas duplicadas', 'ERRO_vagas_confirmadas_duplicadas');
    upgrade_reset($conn, $testDatabase, $knownTables);

    echo "CENÁRIO: definição incorreta\n";
    upgrade_file($conn, 'database/scripts/criacaotables.sql');
    upgrade_file($conn, 'database/scripts/insertstables.sql');
    $conn->exec("ALTER TABLE Evento MODIFY duracao_minutos VARCHAR(20) NOT NULL DEFAULT '60'");
    upgrade_expected_failure($conn, 'tipo de duração divergente', 'ERRO_def_Evento_duracao_minutos');
    upgrade_assert(in_array('Evento.duracao_minutos', zubbo_contract_failures($conn, $checks), true), 'auditoria detecta coluna existente com definição errada');
    upgrade_audit(0, 'modo diagnóstico com divergência', false);
    upgrade_audit(1, 'modo estrito com divergência');
    upgrade_reset($conn, $testDatabase, $knownTables);
    upgrade_file($conn, 'database/scripts/criacaotables.sql');
    upgrade_file($conn, 'database/scripts/insertstables.sql');
    $conn->exec('ALTER TABLE Administrador DROP FOREIGN KEY fk_admin_usuario');
    $conn->exec('ALTER TABLE Administrador ADD CONSTRAINT fk_admin_usuario FOREIGN KEY(id_user) REFERENCES Usuario(id_user) ON DELETE SET NULL');
    upgrade_expected_failure($conn, 'ação de chave estrangeira divergente', 'ERRO_fk_Administrador_id_user');
    upgrade_reset($conn, $testDatabase, $knownTables);

    echo "CENÁRIO: base atual e reexecução\n";
    upgrade_file($conn, 'database/scripts/criacaotables.sql');
    upgrade_file($conn, 'database/scripts/insertstables.sql');
    upgrade_file($conn, 'tests/fixtures/upgrade-data.sql');
    $conn->exec("UPDATE Solicitacao_Vaga_Evento SET status_solicitacao='aprovada',data_resposta='2026-01-01 00:00:00'");
    $conn->exec("UPDATE Notificacao SET data_leitura='2026-01-01 00:00:00'");
    $before = upgrade_rows($conn);
    upgrade_migrations($conn); upgrade_migrations($conn);
    upgrade_file($conn, 'database/scripts/insertstables.sql');
    upgrade_assert(upgrade_rows($conn) === $before, 'base atual: seis migrations repetidas preservam todos os registros');
    upgrade_assert(upgrade_structure($conn) === $referenceStructure, 'base atual: estrutura preservada');
    upgrade_assert(zubbo_contract_failures($conn, $checks) === [], 'base final apta à auditoria estrita');
    upgrade_audit(0, 'base atual compatível');
    echo 'Upgrade concluído em ' . $conn->getAttribute(PDO::ATTR_SERVER_VERSION) . '; banco descartável mantido para auditoria: ' . $testDatabase . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'FALHOU: ' . $error->getMessage() . PHP_EOL);
    // Leave evidence in the disposable database. Never attempt rollback of implicit DDL commits.
    exit(1);
}
