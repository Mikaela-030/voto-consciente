<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config.php';

$stmt = $conexao->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();

session_unset();
session_destroy();

header('Location: home.php');
exit();
?>
