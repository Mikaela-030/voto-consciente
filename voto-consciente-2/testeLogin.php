<?php
session_start();

if (isset($_POST['submit'])) {
    include_once ('config.php');

    $nome = $_POST['nome'];
    $senha = $_POST['senha'];

    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE nome_usuario = ?");
    $stmt->bind_param("s", $nome);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();


    if ($user && password_verify($senha, $user['senha_hash']))
        {
            $_SESSION['id'] = $user['id'];
            $_SESSION['nome'] = $user['nome_usuario'];
            header('Location: votoconsciente.php');
            exit();
    } else {
        header('Location: login.php?erro=1'); // exclui a linha header ('Location: login.php?usuario_invalido=1'); e header('Location: login.php?senha_invalida=1'); por segurança de acesso - evitar que terceiros tentem adivinhar os acessos de outras pessoas
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}

?>