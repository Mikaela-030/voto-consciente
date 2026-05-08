<?php
session_start();

if (isset($_POST['submit'])) {
    include_once ('config.php');

    $nome = $_POST['nome'];
    $senha = $_POST['senha'];

    $stmt = $conexao->prepare("SELECT * FROM cadastro WHERE nome = ?");
    $stmt->bind_param("s", $nome);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (password_verify($senha, $user['senha'])) {
            $_SESSION['nome'] = $nome;
            header('Location: votoconsciente.php');
            exit();
        }
    } 
} else {
    header('Location: login.php');
    exit();
 }
?>