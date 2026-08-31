<?php
include __DIR__.'/includes/head.php';

if(!isset($_SERVER['REQUEST_METHOD'])==="POST"){
    header('Location:form-usuario.php');
    die();
}
// if(empty($_POST['name']),empty($_POST['name']))
include __DIR__.'/config/database.php';
$nome=$_POST['name-txt'];
$email=$_POST['email-txt'];
$telefone=$_POST['telefone-tel'];
$senha=$_POST['Senha-pass'];
$data=$_POST['data-nasc'];
//variaveis statment para preparar a
$stmt=$conn->prepare('insert into Usuario (nome_user, email_user, tel_user, senha_user, date_user)values(?,?,?,?,?)');
$stmt->bindParam(1,$nome);
$stmt->bindParam(2,$email);
$stmt->bindParam(3,$telefone);
$stmt->bindParam(4,$senha);
$stmt->bindParam(5,$data);
$stmt->execute();

header('Location: "../views/auth/login.php"');

include __DIR__.'/includes/footer.php';
?>