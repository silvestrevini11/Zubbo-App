<?php
declare(strict_types=1);

// Auditoria somente leitura. Evidência de estado não prova histórico de execução.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../config/database.php';
$conn->exec('START TRANSACTION READ ONLY');
try {
    $schema = (string)$conn->query('SELECT DATABASE()')->fetchColumn();
    $columns = [];
    foreach ($conn->query('SELECT table_name,column_name,column_type,column_default FROM information_schema.columns WHERE table_schema=DATABASE()') as $row) {
        $columns[strtolower($row['table_name'])][strtolower($row['column_name'])] = $row;
    }
    $indexes = [];
    $indexColumns = [];
    foreach ($conn->query('SELECT table_name,index_name,non_unique,column_name FROM information_schema.statistics WHERE table_schema=DATABASE() ORDER BY table_name,index_name,seq_in_index') as $row) {
        $table = strtolower($row['table_name']);
        $indexes[$table][$row['index_name']] = (int)$row['non_unique'];
        $indexColumns[$table][$row['index_name']][] = strtolower($row['column_name']);
    }
    // Bancos legados podem ter a mesma unicidade com outro nome de índice.
    $hasUniqueIndex = static function (string $table, array $names) use ($indexes, $indexColumns): bool {
        $table = strtolower($table);
        foreach ($indexColumns[$table] ?? [] as $index => $namesInIndex) {
            if (($indexes[$table][$index] ?? 1) === 0 && $namesInIndex === $names) return true;
        }
        return false;
    };
    $foreignKeys = [];
    foreach ($conn->query("SELECT table_name,column_name,referenced_table_name FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND referenced_table_name IS NOT NULL") as $row) {
        $foreignKeys[strtolower($row['table_name'])][strtolower($row['column_name'])] = strtolower($row['referenced_table_name']);
    }
    $has = static function (string $table, array $names) use ($columns): bool {
        foreach ($names as $name) if (!isset($columns[strtolower($table)][$name])) return false;
        return true;
    };
    $results = [];
    $record = static function (string $id, bool $ok, string $evidence) use (&$results): void {
        $results[$id] = ['estado' => $ok ? 'compativel' : 'pendente_ou_divergente', 'evidencia' => $evidence];
    };
    $record('002', $has('Administrador', ['id_user'])
        && $hasUniqueIndex('Administrador', ['id_user'])
        && ($foreignKeys['administrador']['id_user'] ?? '') === 'usuario', 'Coluna, unicidade e FK do administrador.');
    $record('003', ($indexes['localesp']['uq_local_nome_endereco'] ?? 1) === 0, 'Índice único de nome/endereço de local.');
    $record('004', $has('Denuncia', ['id_local']) && ($foreignKeys['denuncia']['id_local'] ?? '') === 'localesp', 'Denúncia vinculada a local.');
    $record('005', $has('Notificacao', ['data_leitura']) && isset($indexes['notificacao']['idx_notificacao_expirar']), 'Leitura e índice de expiração.');
    $record('006', $has('LocalEsp', ['foto_local']), 'Foto de local.');
    $record('007', $has('Bloqueio_Usuario', ['id_bloqueador','id_bloqueado']), 'Tabela de bloqueios.');
    $record('008', $has('Fila_Espera_Evento', ['id_evento','id_user']), 'Tabela da fila de espera.');
    $record('009', $has('Evento', ['nome_time_1','nome_time_2','descricao_times']), 'Nomes e descrição dos times.');
    $record('010', $has('Esporte', ['imagem_esporte','regras_esporte','jogadores_por_time','quantidade_times','exige_confirmacao_local']), 'Campos de moderação de modalidade.');
    $record('011', $has('Evento', ['duracao_minutos']), 'Duração de evento.');
    $record('012', $has('Local_Esporte_Confirmacao', ['id_local','id_esporte'])
        && (string)($columns['esporte']['exige_confirmacao_local']['column_default'] ?? '') === '0', 'Tabela de confirmação e default sem confirmação obrigatória.');
    $record('013', $has('PushSubscription', ['id_user','endpoint_hash','dados_json','atualizado_em']), 'Inscrições Web Push.');
    $allowed = $has('Local_Esporte_Permitido', ['id_local','id_esporte','fonte']);
    $record('014', $allowed && str_contains($columns['localesp']['tipo_local']['column_type'] ?? '', 'campo'), 'Tabela de modalidades por local e tipo campo; revisar fontes/dados separadamente.');
    $record('015', $has('Grupo_Evento', ['id_grupo','id_evento']) && $has('Notificacao', ['id_evento'])
        && ($foreignKeys['notificacao']['id_evento'] ?? '') === 'evento', 'Vínculos grupo/evento e notificação/evento.');
    if ($allowed && $has('Esporte', ['nome_esporte']) && $has('LocalEsp', ['tipo_local'])) {
        $futsal = (int)$conn->query("SELECT COUNT(*) FROM Esporte WHERE nome_esporte='Futsal'")->fetchColumn();
        $gyms = (int)$conn->query("SELECT COUNT(*) FROM LocalEsp WHERE tipo_local='ginásio'")->fetchColumn();
        $missing = (int)$conn->query("SELECT COUNT(*) FROM LocalEsp l WHERE l.tipo_local='ginásio' AND NOT EXISTS (
            SELECT 1 FROM Local_Esporte_Permitido p JOIN Esporte e ON e.id_esporte=p.id_esporte
            WHERE p.id_local=l.id_local AND e.nome_esporte='Futsal')")->fetchColumn();
        $record('016', $futsal > 0 && $gyms > 0 && $missing === 0, "$missing de $gyms ginásios sem Futsal; $futsal modalidade(s) Futsal.");
    } else {
        $record('016', false, 'Estrutura anterior ausente.');
    }
    echo json_encode(['banco' => $schema, 'servidor' => $conn->getAttribute(PDO::ATTR_SERVER_VERSION),
        'aviso' => 'Sem ledger de migrações: compatibilidade do estado atual, não comprovação histórica. Nenhum SQL de migração foi executado.',
        'migracoes' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
} finally {
    $conn->rollBack();
}
