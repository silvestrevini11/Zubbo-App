-- 016 - Regra interna do Zubbo: habilitar Futsal em todos os locais do tipo ginásio.
-- A inclusão é idempotente e preserva a fonte de associações já existentes.
-- É preciso confirmar a disponibilidade e autorização do responsável pelo local.
INSERT IGNORE INTO Local_Esporte_Permitido (id_local, id_esporte, fonte)
SELECT l.id_local, e.id_esporte,
       'Habilitado por regra de ginásio do Zubbo; confirmar uso com o responsável'
FROM LocalEsp l
INNER JOIN Esporte e ON e.nome_esporte = 'Futsal'
WHERE l.tipo_local = 'ginásio';
