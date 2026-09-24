<?php
require_once __DIR__.'/_auth.php'; require_once __DIR__.'/../../../config/database.php'; admin_exigir_post();
$id=(int)($_POST['id_sugestao']??0); $acao=$_POST['acao']??''; $motivo=trim($_POST['motivo']??''); $ok=['pendente','aprovada','rejeitada'];
if($id<=0||!in_array($acao,$ok,true)||$motivo===''){admin_flash('erro','Dados inválidos para alterar a sugestão.'); header('Location: sugestoes.php'); exit;}
try{$conn->beginTransaction(); $q=$conn->prepare('SELECT nome_esporte FROM Sugestao_Esporte WHERE id_sugestao=? FOR UPDATE'); $q->execute([$id]); $s=$q->fetch(PDO::FETCH_ASSOC); if(!$s) throw new RuntimeException('Sugestão não encontrada.');
if($acao==='aprovada'){ $ins=$conn->prepare('INSERT INTO Esporte (nome_esporte) SELECT ? WHERE NOT EXISTS (SELECT 1 FROM Esporte WHERE LOWER(nome_esporte)=LOWER(?))'); $ins->execute([$s['nome_esporte'],$s['nome_esporte']]); }
$data=$acao==='pendente'?null:date('Y-m-d H:i:s'); $adm=$acao==='pendente'?null:(int)$_SESSION['admin']['id']; $up=$conn->prepare('UPDATE Sugestao_Esporte SET status_sugestao=?,id_adm=?,data_analise=? WHERE id_sugestao=?'); $up->execute([$acao,$adm,$data,$id]); admin_registrar_acao($conn,'sugestao_'.$acao,$motivo); $conn->commit(); admin_flash('sucesso','Sugestão atualizada.');}catch(Throwable $e){if($conn->inTransaction())$conn->rollBack(); admin_flash('erro','Não foi possível atualizar a sugestão.');}
header('Location: sugestoes.php'); exit;
