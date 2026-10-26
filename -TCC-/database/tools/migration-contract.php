<?php
declare(strict_types=1);

// Maintainer tool: emits deterministic postconditions from the final CREATE script.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../../tests/support/schema-contract.php';
$write = in_array('--write', $argv, true);
$contract = zubbo_schema_contract();
$scopes = [
    '001_usuarios_administracao' => ['Usuario' => ['sobre_mim'], 'Administrador' => ['id_user'], 'Recuperacao_Senha' => null, 'Bloqueio_Usuario' => null],
    '002_locais_denuncias' => ['LocalEsp' => ['foto_local', 'tipo_local'], 'Denuncia' => ['id_local']],
    '003_eventos_grupos' => ['Evento' => ['nome_time_1', 'nome_time_2', 'descricao_times', 'duracao_minutos'], 'Solicitacao_Vaga_Evento' => null, 'Fila_Espera_Evento' => null, 'Grupo_Evento' => null],
    '004_notificacoes_push' => ['Notificacao' => ['data_leitura', 'id_evento'], 'PushSubscription' => null],
    '005_modalidades_confirmacoes' => ['Esporte' => ['imagem_esporte', 'regras_esporte', 'jogadores_por_time', 'quantidade_times', 'exige_confirmacao_local'], 'Local_Esporte_Confirmacao' => null, 'Local_Esporte_Permitido' => null],
];
$conditional = static function (string $condition, string $statement): string {
    return 'SET @zubbo_sql = IF(' . $condition . ', ' . zubbo_sql_quote($statement) . ", 'SELECT 1');\nPREPARE zubbo_stmt FROM @zubbo_sql;\nEXECUTE zubbo_stmt;\nDEALLOCATE PREPARE zubbo_stmt;\n";
};
$failed = false;
foreach ($scopes as $domain => $tables) {
    $path = __DIR__ . '/../migrations/' . $domain . '.sql';
    $original = str_replace("\r\n", "\n", file_get_contents($path));
    $base = explode('-- BEGIN GENERATED CONTRACT', $original)[0];
    $sql = rtrim($base) . "\n\n-- BEGIN GENERATED CONTRACT\n-- Gerado por php database/tools/migration-contract.php --write.\n-- Completa apenas colunas com NULL/default e vínculos/índices ausentes.\n-- Definições incompatíveis interrompem sem apagar ou adivinhar dados.\n";
    foreach ($tables as $table => $columns) {
        $spec = $contract[$table];
        if ($columns === null) {
            // CREATE IF NOT EXISTS does not repair a partially created table.
            foreach ($spec['columns'] as $column => $definition) {
                if ($definition['auto'] || $definition['generated'] !== null) continue;
                if (!$definition['nullable'] && $definition['default'] === null) continue;
                $absent = "NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=" . zubbo_sql_quote($table) . ' AND column_name=' . zubbo_sql_quote($column) . ')';
                $sql .= $conditional($absent, "ALTER TABLE $table ADD COLUMN " . $definition['definition']);
            }
        }
        // Every relevant column must match its definition before keys can be added.
        foreach ($columns ?? array_keys($spec['columns']) as $column) {
            $label = substr('ERRO_def_' . $table . '_' . $column, 0, 64);
            $sql .= $conditional('NOT ' . zubbo_column_condition($table, $column, $spec['columns'][$column]), 'SELECT * FROM `' . $label . '`');
        }
        foreach ($spec['indexes'] as $index) {
            // Existing base-table keys are also checked; equivalent names are accepted.
            $sql .= $conditional('NOT ' . zubbo_index_condition($table, $index), "ALTER TABLE $table ADD " . $index['definition']);
        }
        foreach ($spec['foreign_keys'] as $fk) {
            $exists = "EXISTS (SELECT 1 FROM information_schema.key_column_usage WHERE table_schema=DATABASE() AND table_name=" . zubbo_sql_quote($table) . ' AND column_name=' . zubbo_sql_quote($fk['column']) . ' AND referenced_table_name IS NOT NULL)';
            $correct = zubbo_fk_condition($table, $fk);
            $sql .= $conditional("$exists AND NOT $correct", 'SELECT * FROM `' . substr('ERRO_fk_' . $table . '_' . $fk['column'], 0, 64) . '`');
            $sql .= $conditional('NOT ' . $correct, "ALTER TABLE $table ADD " . $fk['definition']);
        }
    }
    $sql .= "-- END GENERATED CONTRACT\n";
    if ($original !== $sql) {
        if ($write) file_put_contents($path, $sql);
        else { fwrite(STDERR, "Contrato desatualizado: $domain\n"); $failed = true; }
    }
}
exit($failed ? 1 : 0);
