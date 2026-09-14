use app_zubbo;

ALTER TABLE Usuario
ADD foto_user VARCHAR(255) NULL;

ALTER TABLE Notificacao
MODIFY id_conversa INT NULL,
MODIFY id_mensagem INT NULL;


ALTER TABLE Usuario
ADD COLUMN email_verificado BOOLEAN NOT NULL DEFAULT FALSE;

select * from Amizade;
select * from Usuario;
select * from Esporte;
select * from Usuario_Esporte;
select * from Mensagem;
select * from Conversa;