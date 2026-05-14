<?php
session_start();

if (isset($_POST['submit'])) {
    include_once ('config.php');

    $nome = $_POST['nome'];
    $senha = $_POST['senha'];

    $stmt = $conexao->prepare("SELECT id, nome_usuario, senha_hash FROM usuarios WHERE nome_usuario = ?");
    $stmt->bind_param("s", $nome);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (password_verify($senha, $user['senha_hash'])) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['nome'] = $user['nome_usuario'];
            header('Location: votoconsciente.php');
            exit();
        } else {
            header('Location: login.php?erro=senha');
            exit();
        }
    } else {
        header('Location: login.php?erro=usuario');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
?>