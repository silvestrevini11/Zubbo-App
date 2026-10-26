<?php
declare(strict_types=1);

// Read only: observed compatibility is not proof of migration execution.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$arguments = array_slice($argv, 1);
if (array_diff($arguments, ['--strict', '--help'])) {
    fwrite(STDERR, "Uso: php tests/audit-migrations.php [--strict]\n"); exit(2);
}
if (in_array('--help', $arguments, true)) {
    echo "Auditoria somente leitura. --strict: 0 compatível, 1 divergência, 2 erro de execução.\n"; exit(0);
}
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/support/schema-contract.php';
$conn = null;
try {
    // Do not use database.php: its web-oriented connection failure exits with code 0.
    $conn = new PDO('mysql:host=' . (getenv('ZUBBO_DB_HOST') ?: 'localhost')
        . ';port=' . (getenv('ZUBBO_DB_PORT') ?: '3306')
        . ';dbname=' . (getenv('ZUBBO_DB_NAME') ?: 'app_zubbo') . ';charset=utf8mb4',
        getenv('ZUBBO_DB_USER') ?: 'root', getenv('ZUBBO_DB_PASSWORD') ?: '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5]);
    $conn->exec('START TRANSACTION READ ONLY');
    $contract = zubbo_schema_contract(); $results = []; $compatible = true;
    foreach (zubbo_domain_tables() as $domain => $tables) {
        $checks = zubbo_contract_checks($contract, $tables);
        $failures = zubbo_contract_failures($conn, $checks);
        $results[$domain] = ['estado' => $failures ? 'pendente_ou_divergente' : 'compativel',
            'verificacoes' => count($checks), 'divergencias' => $failures];
        if ($failures) $compatible = false;
    }
    $catalog = ['estado' => 'pendente_ou_divergente', 'evidencia' => 'Estrutura de modalidades divergente.'];
    if ($results['005_modalidades_confirmacoes']['estado'] === 'compativel'
        && $results['002_locais_denuncias']['estado'] === 'compativel') {
        $sports = (int)$conn->query("SELECT COUNT(*) FROM Esporte WHERE nome_esporte IN ('Futebol','Basquete','Vôlei','Futsal','Corrida','Handebol','Futebol Society','Tênis')")->fetchColumn();
        $missing = (int)$conn->query("SELECT COUNT(*) FROM LocalEsp l WHERE l.tipo_local='ginásio' AND NOT EXISTS (SELECT 1 FROM Local_Esporte_Permitido p JOIN Esporte e ON e.id_esporte=p.id_esporte WHERE p.id_local=l.id_local AND e.nome_esporte='Futsal')")->fetchColumn();
        $catalog = ['estado' => $sports === 8 && $missing === 0 ? 'compativel' : 'pendente_ou_divergente',
            'esportes_esperados_presentes' => $sports, 'ginasios_sem_futsal' => $missing,
            'evidencia' => 'Catálogo é aplicado pelos inserts, separadamente das migrations. Fontes editadas não são sobrescritas.'];
    }
    $compatible = $compatible && $catalog['estado'] === 'compativel';
    echo json_encode(['banco' => $conn->query('SELECT DATABASE()')->fetchColumn(),
        'servidor' => $conn->getAttribute(PDO::ATTR_SERVER_VERSION),
        'aviso' => 'Sem ledger: compatibilidade observada, não comprovação histórica. Nenhuma migration executada.',
        'migracoes' => $results, 'catalogo' => $catalog], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
    $conn->rollBack();
    exit(in_array('--strict', $arguments, true) && !$compatible ? 1 : 0);
} catch (Throwable $error) {
    if ($conn instanceof PDO && $conn->inTransaction()) $conn->rollBack();
    // SQLSTATE is sufficient to diagnose execution without exposing connection secrets or rows.
    fwrite(STDERR, 'Falha na auditoria (' . get_class($error) . ', código ' . $error->getCode() . "). Nenhuma compatibilidade comprovada.\n");
    exit(2);
}
