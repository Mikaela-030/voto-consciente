<?php

 $dbHost = 'localhost';
 $dbUsername = 'root';
 $dbPassword = '';
 $dbName = 'formulario';

 $conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

 if ($conexao->connect_errno) {
    die('Erro de conexão: (' . $conexao->connect_errno . ') ' . $conexao->connect_error);
 }

?>