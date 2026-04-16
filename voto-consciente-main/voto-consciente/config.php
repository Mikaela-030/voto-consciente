<?php

$linhas = file('.env', FILE_IGNORE_NEW_LINES, FILE_SKIP_EMPTY_LINES)

foreach ($linhas as $linha) {
    if (str_starts_with($linha, '#')) continue;
    [$chave, $valor] = explode('=', $linha, 2);

    $_ENV[trim($chave)] = trim($valor);
}

try{
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']}; dbname={$_ENV['DB_NAME']};charset_utf8",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOexception $e) {
    die('Erro na conexão com o banco: '. $e->getMessage());
}