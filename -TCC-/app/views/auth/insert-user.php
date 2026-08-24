<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form-usuario.php');
    exit;
}
// if(empty($_POST['name']),empty($_POST['name']))
include __DIR__.'/../../../config/database.php'; 
$nome=$_POST['name-txt'];
$email=$_POST['email-txt'];
$telefone=$_POST['telefone-tel'];
$senha = password_hash($_POST['Senha-pass'], PASSWORD_DEFAULT);
$data=$_POST['data-nasc'];
//variaveis statment para preparar a
$stmt=$conn->prepare('insert into Usuario (nome_user, email_user, tel_user, senha_user, date_user)values(?,?,?,?,?)');
$stmt->bindParam(1,$nome);
$stmt->bindParam(2,$email);
$stmt->bindParam(3,$telefone);
$stmt->bindParam(4,$senha);
$stmt->bindParam(5,$data);
$stmt->execute();

$id_user = $conn->lastInsertId();

$_SESSION['usuario'] = [
    'id' => $id_user,
    'nome' => $nome
];

header('Location: escolher-esportes.php');
?>