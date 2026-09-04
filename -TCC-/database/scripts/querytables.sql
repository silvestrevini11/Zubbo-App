-- =====================================================
-- Consultas
-- =====================================================

use app_zubbo;
ALTER TABLE Usuario
ADD foto_user VARCHAR(255) NULL;

ALTER TABLE Usuario
    MODIFY senha_user VARCHAR(255) NULL,
    MODIFY tel_user VARCHAR(20) NULL,
    MODIFY date_user DATE NULL;



select * from Usuario;
select * from Esporte;
select * from Usuario_Esporte;
select * from Mensagem;
select * from Conversa;