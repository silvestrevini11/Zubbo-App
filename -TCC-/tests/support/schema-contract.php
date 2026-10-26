<?php
declare(strict_types=1);

/** Contract derived from the canonical CREATE script; never creates a database. */
function zubbo_sql_parts(string $sql, string $separator): array
{
    $parts = []; $start = 0; $depth = 0; $quoted = false;
    for ($i = 0, $length = strlen($sql); $i < $length; $i++) {
        $char = $sql[$i];
        if ($char === "'") {
            if ($quoted && ($sql[$i + 1] ?? '') === "'") { $i++; continue; }
            $quoted = !$quoted;
        }
        if ($quoted) continue;
        if ($char === '(') $depth++;
        if ($char === ')') $depth--;
        if ($char === $separator && ($separator === ';' || $depth === 0)) {
            $parts[] = trim(substr($sql, $start, $i - $start)); $start = $i + 1;
        }
    }
    if (trim(substr($sql, $start)) !== '') $parts[] = trim(substr($sql, $start));
    return $parts;
}

function zubbo_schema_contract(): array
{
    $sql = file_get_contents(__DIR__ . '/../../database/scripts/criacaotables.sql');
    $sql = preg_replace('/^\s*--[^\r\n]*/m', '', $sql);
    $tables = [];
    foreach (zubbo_sql_parts($sql, ';') as $statement) {
        if (!preg_match('/^CREATE TABLE (\w+)\s*\((.*)\) ENGINE=/s', $statement, $match)) {
            throw new RuntimeException('CREATE não reconhecido no contrato.');
        }
        $name = $match[1]; $table = ['columns' => [], 'indexes' => [], 'foreign_keys' => []];
        foreach (zubbo_sql_parts($match[2], ',') as $definition) {
            $definition = preg_replace('/\s+/', ' ', trim($definition));
            if (preg_match('/FOREIGN KEY \(([^)]+)\) REFERENCES (\w+)\(([^)]+)\)(?: ON DELETE (CASCADE|SET NULL|RESTRICT|NO ACTION))?/i', $definition, $fk)) {
                $table['foreign_keys'][] = ['column' => trim($fk[1]), 'table' => $fk[2], 'target' => trim($fk[3]), 'delete' => $fk[4] ?? 'RESTRICT', 'definition' => $definition];
            } elseif (preg_match('/^(PRIMARY KEY|UNIQUE(?: KEY)?|INDEX)(?: (\w+))?\s*\(([^)]+)\)$/i', $definition, $index)) {
                $table['indexes'][] = ['columns' => str_replace(' ', '', $index[3]), 'unique' => strtoupper($index[1]) !== 'INDEX', 'primary' => $index[1] === 'PRIMARY KEY', 'definition' => $definition];
            } elseif (preg_match('/^(\w+) ((?:ENUM\([^)]*\)|\w+(?:\(\d+\))?)(?: UNSIGNED)?)(.*)$/i', $definition, $column)) {
                [, $col, $type, $rest] = $column;
                $type = strtolower($type);
                $type = $type === 'boolean' ? 'tinyint' : preg_replace('/\b(tinyint|smallint|int|bigint)\(\d+\)/', '$1', $type);
                $type = preg_replace(['/,[ ]+/', '/\([ ]+/', '/[ ]+\)/'], [',', '(', ')'], $type);
                $default = null;
                if (preg_match("/ DEFAULT ('(?:[^']|'')*'|[A-Z_]+|[0-9]+)/i", $rest, $d)) {
                    $default = trim($d[1], "'");
                    if ($default === 'FALSE') $default = '0';
                    if ($default === 'TRUE') $default = '1';
                    $default = strtolower($default) === 'current_timestamp' ? 'current_timestamp' : $default;
                }
                $generated = null;
                if (preg_match('/GENERATED ALWAYS AS \((.*)\) STORED/', $rest, $g)) $generated = zubbo_normalize_expression($g[1]);
                $table['columns'][$col] = ['type' => $type, 'nullable' => !str_contains(explode('GENERATED ALWAYS', $rest)[0], 'NOT NULL') && !str_contains($rest, 'PRIMARY KEY'), 'default' => $default, 'auto' => str_contains($rest, 'AUTO_INCREMENT'), 'generated' => $generated, 'update' => str_contains($rest, 'ON UPDATE CURRENT_TIMESTAMP'), 'collation' => str_contains($rest, 'ascii_bin') ? 'ascii_bin' : (preg_match('/^(varchar|char|text|enum)/', $type) ? 'utf8mb4_unicode_ci' : null), 'definition' => $definition];
                if (str_contains($rest, 'PRIMARY KEY') || str_contains($rest, 'UNIQUE')) {
                    $primary = str_contains($rest, 'PRIMARY KEY');
                    $table['indexes'][] = ['columns' => $col, 'unique' => true, 'primary' => $primary, 'definition' => ($primary ? 'PRIMARY KEY' : 'UNIQUE KEY') . ' (' . $col . ')'];
                }
            } else {
                throw new RuntimeException('Definição não reconhecida: ' . $name . ' ' . $definition);
            }
        }
        foreach ($table['indexes'] as $index) {
            if ($index['primary']) foreach (explode(',', $index['columns']) as $col) $table['columns'][$col]['nullable'] = false;
        }
        $tables[$name] = $table;
    }
    return $tables;
}

function zubbo_normalize_expression(string $expression): string
{
    return strtolower(preg_replace('/[\s`()]+|_utf8mb4/', '', str_replace(chr(92), '', $expression)));
}

function zubbo_sql_quote(?string $value): string
{
    return $value === null ? 'NULL' : "'" . str_replace("'", "''", $value) . "'";
}

function zubbo_domain_tables(): array
{
    return [
        '001_usuarios_administracao' => ['Usuario', 'Administrador', 'Recuperacao_Senha', 'Bloqueio_Usuario', 'Usuario_Esporte', 'Verificacao_Email', 'Solicitacao_Amizade', 'Amizade', 'Acao_Administrativa'],
        '002_locais_denuncias' => ['LocalEsp', 'Denuncia'],
        '003_eventos_grupos' => ['Evento', 'Solicitacao_Vaga_Evento', 'Fila_Espera_Evento', 'Grupo_Evento', 'Grupo', 'Conversa', 'Participantes_Conversa', 'Comunidade', 'Mensagem', 'Equipe', 'ParticipantesEquipe', 'EquipesEvento', 'Lista_Evento'],
        '004_notificacoes_push' => ['Notificacao', 'PushSubscription'],
        '005_modalidades_confirmacoes' => ['Esporte', 'Local_Esporte_Confirmacao', 'Local_Esporte_Permitido', 'Sugestao_Esporte', 'Voto_Sugestao'],
    ];
}

function zubbo_column_condition(string $table, string $column, array $spec): string
{
    $q = 'zubbo_sql_quote';
    $where = "table_schema=DATABASE() AND table_name={$q($table)} AND column_name={$q($column)}";
    $type = "REGEXP_REPLACE(LOWER(column_type),'(tinyint|smallint|mediumint|int|bigint)\\([0-9]+\\)','$1')";
    // SQL string escaping must preserve the regex backslashes.
    $type = str_replace('\\', '\\\\', $type);
    $where .= " AND $type={$q($spec['type'])} AND is_nullable=" . $q($spec['nullable'] ? 'YES' : 'NO');
    $default = $spec['default'];
    $where .= $default === null ? ' AND column_default IS NULL' : ($default === 'current_timestamp' ? " AND LOWER(REPLACE(column_default,'()',''))=" : " AND REPLACE(column_default,'()','')=") . $q($default);
    $where .= " AND (extra LIKE '%auto_increment%')=" . (int)$spec['auto'];
    $where .= " AND (LOWER(extra) LIKE '%on update current_timestamp%')=" . (int)$spec['update'];
    if ($spec['collation'] !== null) $where .= ' AND collation_name=' . $q($spec['collation']);
    if ($spec['generated'] === null) $where .= " AND COALESCE(generation_expression,'')=''";
    else {
        $where .= " AND LOWER(extra) LIKE '%stored generated%' AND LOWER(REGEXP_REPLACE(REPLACE(REPLACE(generation_expression,'_utf8mb4',''),CHAR(92),''),'[[:space:]`()]+',''))=" . $q($spec['generated']);
    }
    return "EXISTS (SELECT 1 FROM information_schema.columns WHERE $where)";
}

function zubbo_index_condition(string $table, array $index): string
{
    return "EXISTS (SELECT 1 FROM (SELECT index_name,non_unique,GROUP_CONCAT(column_name ORDER BY seq_in_index) cols,SUM(sub_part IS NOT NULL) prefixes FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=" . zubbo_sql_quote($table) . " GROUP BY index_name,non_unique) z WHERE cols=" . zubbo_sql_quote($index['columns']) . ' AND non_unique=' . (int)!$index['unique'] . ' AND prefixes=0' . ($index['primary'] ? " AND index_name='PRIMARY'" : '') . ')';
}

function zubbo_fk_condition(string $table, array $fk): string
{
    $q = 'zubbo_sql_quote';
    return "EXISTS (SELECT 1 FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema=k.constraint_schema AND r.table_name=k.table_name AND r.constraint_name=k.constraint_name WHERE k.table_schema=DATABASE() AND k.table_name={$q($table)} AND k.column_name={$q($fk['column'])} AND k.referenced_table_name={$q($fk['table'])} AND k.referenced_column_name={$q($fk['target'])} AND r.delete_rule IN ({$q($fk['delete'])}" . ($fk['delete'] === 'RESTRICT' ? ", 'NO ACTION'" : '') . ") AND r.update_rule IN ('RESTRICT','NO ACTION'))";
}

function zubbo_contract_checks(array $contract, array $names): array
{
    $checks = [];
    foreach ($names as $table) {
        $checks["$table:tabela"] = "EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=" . zubbo_sql_quote($table) . " AND engine='InnoDB' AND table_collation='utf8mb4_unicode_ci')";
        foreach ($contract[$table]['columns'] as $column => $spec) $checks["$table.$column"] = zubbo_column_condition($table, $column, $spec);
        foreach ($contract[$table]['indexes'] as $index) $checks["$table:indice:" . $index['columns']] = zubbo_index_condition($table, $index);
        foreach ($contract[$table]['foreign_keys'] as $fk) $checks["$table:fk:" . $fk['column']] = zubbo_fk_condition($table, $fk);
    }
    return $checks;
}

function zubbo_contract_failures(PDO $conn, array $checks): array
{
    $failures = [];
    foreach ($checks as $label => $condition) {
        if (!(bool)$conn->query('SELECT ' . $condition)->fetchColumn()) $failures[] = $label;
    }
    return $failures;
}
