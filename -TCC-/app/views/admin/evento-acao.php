<?php
require_once __DIR__.'/_auth.php'; require_once __DIR__.'/../../../config/database.php'; admin_exigir_post();
$id=(int)($_POST['id_evento']??0); $acao=$_POST['acao']??''; $motivo=trim($_POST['motivo']??''); $ok=['ativo','cancelado','removido'];
if($id<=0||!in_array($acao,$ok,true)||$motivo===''){admin_flash('erro','Dados inválidos para alterar o evento.'); header('Location: eventos.php'); exit;}
$stmt=$conn->prepare('UPDATE Evento SET status_evento=? WHERE id_evento=?'); $stmt->execute([$acao,$id]);
admin_registrar_acao($conn,'evento_'.$acao,$motivo,['id_evento'=>$id]); admin_flash('sucesso','Evento atualizado.'); header('Location: eventos.php'); exit;
