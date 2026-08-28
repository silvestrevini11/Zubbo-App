ALTER TABLE Usuario
ADD foto_user VARCHAR(255) NULL;

alter table Mensagem
	add constraint id_conversaFK foreign key (id_conversa) references Conversa(id_conversa); 
alter table Usuario
	add password_user varchar(35);
ALTER table sugestao_esporte 
change cod_sugestao id_sugestao INT NOT NULL auto_increment;
 
 ALTER TABLE LocalEsp 
MODIFY COLUMN tipo_local ENUM('quadra', 'poliesportivo', 'clube', 'ginásio') 
NOT NULL 
DEFAULT 'poliesportivo'; /*modifica a coluna tipo local adicionando uma coluna para enum*/




select * from Usuario;
select * from Esporte;
select * from Usuario_Esporte;
select * from Mensagem;
select * from Conversa;