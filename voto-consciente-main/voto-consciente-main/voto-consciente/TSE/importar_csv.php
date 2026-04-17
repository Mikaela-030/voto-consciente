<?php
require_once '.../config.php';

// Leitura CSV TSE
// Os arquivos do TSE são separados por ; e não por ,
// e encoding LATIN-1 (não UTF-8) - precisamos converter

function importar_candidatos(PDO $pdo, string $caminho_csv): void {
    $handle = fopen($caminho_csv, 'r');
    // fopen com 'r' abre só como leitura

    // converter encoding da linha antes de processar
    $cabecalho = fgetcsv($handle, 0, ';');
    //fgetcsv() > lê uma linha do CSV e divide pelo separador
    // 0 > sem limite de tamanho por linha
    //';' > separador usado pelo TSE
    
    // a primeira linha é sempre o cabeçalho - guardamos ela separada para saber o índice de cada coluna pelo nome

    $cabecalho = array_map(fn($col) => mb_convert)
}