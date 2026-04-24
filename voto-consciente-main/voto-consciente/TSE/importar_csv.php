<?php
require_once '../config.php';

// ─── A lógica de leitura de CSV ──────────────────────────────────
// Os CSVs do TSE usam ponto-e-vírgula como separador (não vírgula)
// e encoding LATIN-1 (não UTF-8) — precisamos converter

function importar_candidatos(PDO $pdo, string $caminho_csv): void {

    $handle = fopen($caminho_csv, 'r');
    // fopen com 'r' → abre só para leitura

    // Converter encoding da linha antes de processar
    // O TSE usa LATIN-1 mas nosso banco está em UTF-8

    $cabecalho = fgetcsv($handle, 0, ';');
    // fgetcsv()  → lê uma linha do CSV e já divide pelo separador
    // 0          → sem limite de tamanho por linha
    // ';'        → separador usado pelo TSE

    // A primeira linha é sempre o cabeçalho — guardamos ela separada
    // para saber o índice de cada coluna pelo nome

    $cabecalho = array_map(fn($col) => mb_convert_encoding(trim($col), 'UTF-8', 'ISO-8859-1'), $cabecalho);
    // array_map              → aplica uma função em cada item do array
    // mb_convert_encoding    → converte de LATIN-1 (ISO-8859-1) para UTF-8
    // trim()                 → remove espaços e caracteres invisíveis

    $indice = array_flip($cabecalho);
    // array_flip → inverte chave↔valor
    // resultado: em vez de [0=>'NM_CANDIDATO'], vira ['NM_CANDIDATO'=>0]
    // assim você acessa pelo nome da coluna, não pelo número

    // Preparar o INSERT uma vez só — fora do loop é muito mais eficiente
    $stmt = $pdo->prepare(
        "INSERT INTO candidatos (nome, partido, uf, cargo, cpf, fonte)
         VALUES (:nome, :partido, :uf, :cargo, :cpf, 'TSE')
         ON DUPLICATE KEY UPDATE
             partido = VALUES(partido),
             cargo   = VALUES(cargo)"
        // ON DUPLICATE KEY → se o CPF já existe, atualiza em vez de duplicar
    );

    $contador = 0;

    while (($linha = fgetcsv($handle, 0, ';')) !== false) {
        // while + fgetcsv → lê linha por linha até o fim do arquivo
        // !== false        → fgetcsv retorna false quando acaba o arquivo

        $linha = array_map(fn($col) => mb_convert_encoding($col, 'UTF-8', 'ISO-8859-1'), $linha);
        // converte o encoding de cada célula da linha

        // Filtrar só os candidatos da lista do TCC
        // (evita importar milhões de registros desnecessários)
        $cpfs_monitorados = [
            '123.456.789-00', // CPF real do Lula — preencher com os corretos
            '987.654.321-00', // CPF real do Haddad
            // ... demais CPFs
        ];

        $cpf = $linha[$indice['NR_CPF_CANDIDATO']] ?? '';
        // ?? '' → se a coluna não existir, usa string vazia (evita erro)

        if (!in_array($cpf, $cpfs_monitorados)) continue;
        // Se o candidato não está na lista monitorada, pula a linha

        $stmt->execute([
            ':nome'    => $linha[$indice['NM_CANDIDATO']]    ?? '',
            ':partido' => $linha[$indice['SG_PARTIDO']]      ?? '',
            ':uf'      => $linha[$indice['SG_UF']]           ?? '',
            ':cargo'   => $linha[$indice['DS_CARGO']]        ?? '',
            ':cpf'     => $cpf,
        ]);

        $contador++;
    }

    fclose($handle);
    echo "Importados: $contador candidatos\n";
}

// ─── Descompactar o ZIP antes de ler ─────────────────────────────

$zip = new ZipArchive();
// ZipArchive → classe nativa do PHP para manipular arquivos ZIP

if ($zip->open(__DIR__ . '/arquivos_csv/candidatos_2022.zip') === true) {
    $zip->extractTo(__DIR__ . '/arquivos_csv/');
    // extractTo → descompacta todos os arquivos para a pasta indicada
    $zip->close();

    // O TSE coloca arquivos por estado dentro do ZIP
    // Vamos processar só os estados que nos interessam
    $estados = ['SP', 'RJ', 'MG', 'DF']; // ajuste conforme os candidatos

    foreach ($estados as $uf) {
        $arquivo = __DIR__ . "/arquivos_csv/consulta_cand_2022_{$uf}.csv";
        if (file_exists($arquivo)) {
            importar_candidatos($pdo, $arquivo);
        }
    }
}