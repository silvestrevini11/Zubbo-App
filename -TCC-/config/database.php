<?php
//Variáveis de conexão
$host="localhost";
$user="root";
$password="";
$port=3306;
$database="app_zubbo";

//Módulo PDO do PHP
try{
//instânciar a classe PDO
$conn=new PDO('mysql:host='.$host.';port='.$port.';dbname='.$database,$user,$password);
$conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados. Verifique se o MySQL está iniciado.');
}
