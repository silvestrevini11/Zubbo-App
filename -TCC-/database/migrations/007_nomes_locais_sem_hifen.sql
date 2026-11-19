-- Normaliza nomes de locais já cadastrados, preservando os demais campos e vínculos.
-- Pode ser executada novamente: apenas nomes que ainda contêm hífen são alterados.
UPDATE LocalEsp
SET nome_local = TRIM(REGEXP_REPLACE(REPLACE(nome_local, '-', ' '), '[[:space:]]+', ' '))
WHERE nome_local LIKE '%-%';
