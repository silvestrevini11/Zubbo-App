<?php
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/_foto-esporte.php';
admin_exigir_post();
$id=(int)($_POST['id_sugestao']??0);
$acao=(string)($_POST['acao']??'');
$motivo=trim((string)($_POST['motivo']??''));
$regras=trim((string)($_POST['regras_esporte']??''));
$jogadoresRaw=(string)($_POST['jogadores_por_time']??'');
$jogadores=$jogadoresRaw===''?null:filter_var($jogadoresRaw,FILTER_VALIDATE_INT);
$times=filter_var($_POST['quantidade_times']??null,FILTER_VALIDATE_INT);
$exigeLocal=isset($_POST['exige_confirmacao_local'])?1:0;
$ok=['pendente','aprovada','rejeitada'];
if ($id<=0 || !in_array($acao,$ok,true) || $motivo==='' || mb_strlen($motivo)>255 ||
    mb_strlen($regras)>2000 ||
    ($acao==='aprovada' && ($times===false || !in_array($times,[1,2],true) ||
        ($jogadores!==null && ($jogadores===false || $jogadores<1 || $jogadores>30))))) {
    admin_flash('erro','Dados inválidos para revisar o esporte.');
    header('Location: sugestoes.php');exit;
}
$foto=null;
try {
    if ($acao==='aprovada') $foto=admin_salvar_foto_esporte($_FILES['foto_esporte']??[]);
    $conn->beginTransaction();
    $q=$conn->prepare('SELECT nome_esporte FROM Sugestao_Esporte WHERE id_sugestao=? FOR UPDATE');
    $q->execute([$id]);$s=$q->fetch(PDO::FETCH_ASSOC);
    if (!$s) throw new RuntimeException('Sugestão não encontrada.');
    if ($acao==='aprovada') {
        $q=$conn->prepare('SELECT id_esporte FROM Esporte WHERE LOWER(nome_esporte)=LOWER(?) LIMIT 1');
        $q->execute([$s['nome_esporte']]);$idEsporte=$q->fetchColumn();
        if ($idEsporte) {
            $q=$conn->prepare('UPDATE Esporte SET regras_esporte=?,jogadores_por_time=?,quantidade_times=?,exige_confirmacao_local=?
              WHERE id_esporte=?');
            $q->execute([$regras?:null,$jogadores,$times,$exigeLocal,(int)$idEsporte]);
            if ($foto) $conn->prepare('UPDATE Esporte SET imagem_esporte=? WHERE id_esporte=?')->execute([$foto,(int)$idEsporte]);
        } else {
            $q=$conn->prepare('INSERT INTO Esporte (nome_esporte,imagem_esporte,regras_esporte,jogadores_por_time,quantidade_times,exige_confirmacao_local)
                VALUES (?,?,?,?,?,?)');
            $q->execute([$s['nome_esporte'],$foto,$regras?:null,$jogadores,$times,$exigeLocal]);
        }
    }
    $data=$acao==='pendente'?null:date('Y-m-d H:i:s');
    $adm=$acao==='pendente'?null:(int)$_SESSION['admin']['id'];
    $q=$conn->prepare('UPDATE Sugestao_Esporte SET status_sugestao=?,id_adm=?,data_analise=? WHERE id_sugestao=?');
    $q->execute([$acao,$adm,$data,$id]);
    admin_registrar_acao($conn,'sugestao_'.$acao,$motivo);
    $conn->commit();
    admin_flash('sucesso','Sugestão analisada e atualizada.');
} catch(Throwable $e) {
    if ($conn->inTransaction())$conn->rollBack();
    if ($foto && is_file(__DIR__.'/../../../'.$foto)) unlink(__DIR__.'/../../../'.$foto);
    error_log('Falha na sugestão: '.$e->getMessage());
    admin_flash('erro','Não foi possível atualizar a sugestão.');
}
header('Location: sugestoes.php',true,303);exit;
