<?php
echo "PHP funcionando! Versão: " . phpversion();
echo "<br>";

// Testa a conexão com o banco
require_once 'config.php';

echo "Banco de dados conectado com sucesso!";