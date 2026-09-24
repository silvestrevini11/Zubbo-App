<?php
require_once __DIR__.'/_auth.php'; require_once __DIR__.'/../../../config/database.php'; admin_exigir_post();
$id=(int)($_POST['id_local']??0); $acao=$_POST['acao']??''; $motivo=trim($_POST['motivo']??''); $ok=['pendente','aprovado','rejeitado'];
if($id<=0||!in_array($acao,$ok,true)||$motivo===''){admin_flash('erro','Dados inválidos para alterar o local.'); header('Location: locais.php'); exit;}
$st=$conn->prepare('UPDATE LocalEsp SET status_local=? WHERE id_local=?'); $st->execute([$acao,$id]); admin_registrar_acao($conn,'local_'.$acao,$motivo,['id_local'=>$id]); admin_flash('sucesso','Local atualizado.'); header('Location: locais.php'); exit;
