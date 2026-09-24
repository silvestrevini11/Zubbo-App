<?php
require_once __DIR__.'/_auth.php'; require_once __DIR__.'/../../../config/database.php'; admin_exigir_post();
$id=(int)($_POST['id_denuncia']??0); $acao=$_POST['acao']??''; $motivo=trim($_POST['motivo']??''); $ok=['pendente','em_analise','resolvida','rejeitada'];
if($id<=0||!in_array($acao,$ok,true)||$motivo===''){admin_flash('erro','Dados inválidos para alterar a denúncia.'); header('Location: denuncias.php'); exit;}
$dataAnalise=in_array($acao,['resolvida','rejeitada'],true)?date('Y-m-d H:i:s'):null;
$st=$conn->prepare('UPDATE Denuncia SET status_denuncia=?, id_adm=?, data_analise=? WHERE id_denuncia=?'); $st->execute([$acao,$_SESSION['admin']['id'],$dataAnalise,$id]); admin_registrar_acao($conn,'denuncia_'.$acao,$motivo,['id_denuncia'=>$id]); admin_flash('sucesso','Denúncia atualizada.'); header('Location: denuncias.php'); exit;
