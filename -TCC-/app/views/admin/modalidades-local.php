<?php
require_once __DIR__.'/_auth.php';
admin_exigir_post();
$idLocal=filter_var($_POST['id_local']??null,FILTER_VALIDATE_INT);
$ids=$_POST['esportes']??[];
$fonte=trim((string)($_POST['fonte']??''));
if (!$idLocal || !is_array($ids) || mb_strlen($fonte)<8 || mb_strlen($fonte)>500) {
    admin_flash('erro','Informe local e fonte da verificação.');
    header('Location: locais.php',true,303);exit;
}
$ids=array_values(array_unique(array_filter(array_map('intval',$ids),fn($id)=>$id>0)));
if(count($ids)>50){http_response_code(400);exit('Número de modalidades inválido.');}
try {
    $conn->beginTransaction();
    $st=$conn->prepare("SELECT id_local FROM LocalEsp WHERE id_local=? FOR UPDATE");
    $st->execute([$idLocal]);
    if(!$st->fetchColumn()) throw new RuntimeException('Local não encontrado.');
    if($ids){
      $placeholders=implode(',',array_fill(0,count($ids),'?'));
      $st=$conn->prepare("SELECT COUNT(*) FROM Esporte WHERE id_esporte IN ($placeholders)");
      $st->execute($ids);
      if((int)$st->fetchColumn()!==count($ids))throw new RuntimeException('Esporte inválido.');
    }
    $conn->prepare("DELETE FROM Local_Esporte_Permitido WHERE id_local=?")->execute([$idLocal]);
    $inserir=$conn->prepare("INSERT INTO Local_Esporte_Permitido(id_local,id_esporte,fonte) VALUES(?,?,?)");
    foreach($ids as $esporte) $inserir->execute([$idLocal,$esporte,$fonte]);
    admin_registrar_acao($conn,'local_modalidades','Modalidades atualizadas: '.$fonte,['id_local'=>$idLocal]);
    $conn->commit();
    admin_flash('sucesso','Modalidades do local atualizadas.');
} catch(Throwable $e) {
    if($conn->inTransaction())$conn->rollBack();
    error_log('Atualização das modalidades: '.get_class($e));
    admin_flash('erro','Não foi possível salvar as modalidades.');
}
header('Location: locais.php',true,303);exit;
