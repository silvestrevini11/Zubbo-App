<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_foto-local.php';
admin_exigir_post();
$id = filter_var($_POST['id_local'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Local inválido.'); }
$foto = null;
try {
    $foto = admin_salvar_foto_local($_FILES['foto_local'] ?? []);
    if (!$foto) throw new RuntimeException('Selecione uma foto.');
    $conn->beginTransaction();
    $st = $conn->prepare('SELECT foto_local FROM LocalEsp WHERE id_local = ? FOR UPDATE');
    $st->execute([$id]);
    $antiga = $st->fetchColumn();
    if ($antiga === false) throw new RuntimeException('Local não encontrado.');
    $conn->prepare('UPDATE LocalEsp SET foto_local = ? WHERE id_local = ?')->execute([$foto,$id]);
    admin_registrar_acao($conn,'local_foto','Foto do local alterada',['id_local'=>$id]);
    $conn->commit();
    if ($antiga && str_starts_with((string)$antiga,'public/uploads/locais/')) {
        $arquivoAntigo = __DIR__.'/../../../'.$antiga;
        if (is_file($arquivoAntigo)) unlink($arquivoAntigo);
    }
    admin_flash('sucesso','Foto do local atualizada.');
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    if ($foto && is_file(__DIR__.'/../../../'.$foto)) unlink(__DIR__.'/../../../'.$foto);
    error_log('Falha na foto de local: '.$e->getMessage());
    admin_flash('erro','Não foi possível atualizar a foto.');
}
header('Location: locais.php',true,303); exit;
