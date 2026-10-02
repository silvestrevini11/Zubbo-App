USE app_zubbo;

-- Consolida locais repetidos antes de criar a restrição UNIQUE.
CREATE TEMPORARY TABLE tmp_local_duplicado AS
SELECT
    atual.id_local AS id_duplicado,
    MIN(canonico.id_local) AS id_canonico
FROM LocalEsp atual
INNER JOIN LocalEsp canonico
    ON LOWER(TRIM(canonico.nome_local)) = LOWER(TRIM(atual.nome_local))
   AND LOWER(TRIM(canonico.endereco_local)) = LOWER(TRIM(atual.endereco_local))
GROUP BY atual.id_local
HAVING atual.id_local <> MIN(canonico.id_local);

UPDATE Evento ev
INNER JOIN tmp_local_duplicado mapa
    ON mapa.id_duplicado = ev.id_local
SET ev.id_local = mapa.id_canonico;

UPDATE Acao_Administrativa aa
INNER JOIN tmp_local_duplicado mapa
    ON mapa.id_duplicado = aa.id_local
SET aa.id_local = mapa.id_canonico;

DELETE l
FROM LocalEsp l
INNER JOIN tmp_local_duplicado mapa
    ON mapa.id_duplicado = l.id_local;

DROP TEMPORARY TABLE tmp_local_duplicado;

SET @tem_indice_local = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'LocalEsp'
      AND INDEX_NAME = 'uq_local_nome_endereco'
);

SET @sql_local_indice = IF(
    @tem_indice_local = 0,
    'ALTER TABLE LocalEsp ADD UNIQUE INDEX uq_local_nome_endereco (nome_local, endereco_local)',
    'SELECT 1'
);

PREPARE stmt_local_indice FROM @sql_local_indice;
EXECUTE stmt_local_indice;
DEALLOCATE PREPARE stmt_local_indice;
