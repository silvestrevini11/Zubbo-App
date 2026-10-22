<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_foto-local.php';
admin_exigir_post();

$nome = trim((string)($_POST['nome_local'] ?? ''));
$endereco = trim((string)($_POST['endereco_local'] ?? ''));
$tipo = (string)($_POST['tipo_local'] ?? 'quadra');
if (mb_strlen($nome) < 3 || mb_strlen($nome) > 60 ||
    mb_strlen($endereco) < 8 || mb_strlen($endereco) > 120 ||
    !in_array($tipo, ['quadra','poliesportivo','clube','ginásio'], true)) {
    admin_flash('erro','Informe nome, endereço e tipo válidos.');
    header('Location: locais.php', true, 303); exit;
}
$foto = null;
try {
    $foto = admin_salvar_foto_local($_FILES['foto_local'] ?? []);
    $conn->beginTransaction();
    $st = $conn->prepare("INSERT INTO LocalEsp
        (nome_local,endereco_local,tipo_local,status_local,id_criador,foto_local)
        VALUES (?, ?, ?, 'aprovado', ?, ?)");
    $st->execute([$nome,$endereco,$tipo,(int)$_SESSION['usuario']['id'],$foto]);
    $id = (int)$conn->lastInsertId();
    admin_registrar_acao($conn,'local_criado','Local cadastrado pelo administrador',['id_local'=>$id]);
    $conn->commit();
    admin_flash('sucesso','Local cadastrado e aprovado.');
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    if ($foto && is_file(__DIR__.'/../../../'.$foto)) unlink(__DIR__.'/../../../'.$foto);
    error_log('Falha no cadastro do local: '.$e->getMessage());
    admin_flash('erro','Não foi possível cadastrar o local; verifique se já existe.');
}
header('Location: locais.php',true,303); exit;
