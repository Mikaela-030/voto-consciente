<?php
// ============================================================
//  TSE/importar_bens.php
//  Importa bens declarados dos candidatos (eleição 2022)
//
//  Rodar pelo terminal (recomendado):
//  cd C:\xampp\htdocs\voto-consciente-main\TSE
//  C:\xampp\php\php.exe importar_bens.php
// ============================================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';

set_time_limit(600);
ini_set('memory_limit', '512M');

echo "🚀 Iniciando importação de bens declarados...\n";
flush();

// ─── Verificar se já foi atualizado essa semana ──────────────────
if (ja_atualizado_essa_semana($pdo, 'tse_bens')) {
    $total = $pdo->query("SELECT COUNT(*) as t FROM bens_declarados")->fetch()['t'];
    echo "✅ Bens já atualizados essa semana ($total registros no banco).\n";
    exit();
}

// ─── Configuração do arquivo ─────────────────────────────────────
$pasta       = __DIR__ . '/arquivos_csv/';
$arquivo_zip = $pasta . 'bens_2022.zip';

if (!is_dir($pasta)) {
    mkdir($pasta, 0755, true);
}

// ─── Download do ZIP ─────────────────────────────────────────────
$url = 'https://cdn.tse.jus.br/estatistica/sead/odsele/bem_candidato/bem_candidato_2022.zip';

echo "⬇️  Baixando arquivo de bens do TSE...\n";
flush();

$fp   = fopen($arquivo_zip, 'wb');
$curl = curl_init($url);
curl_setopt_array($curl, [
    CURLOPT_FILE           => $fp,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 600,
    CURLOPT_USERAGENT      => 'VotoConsciente-TCC/1.0',
]);

curl_exec($curl);
$codigo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
fclose($fp);

if ($codigo !== 200) {
    echo "❌ Erro no download. Código HTTP: $codigo\n";
    registrar_log($pdo, 'tse_bens', 'TSE_CSV', 'erro', 0, "HTTP $codigo");
    exit();
}

echo "✅ Download concluído.\n";
flush();

// ─── Descompactar ────────────────────────────────────────────────
echo "📦 Descompactando...\n";
flush();

$zip = new ZipArchive();
if ($zip->open($arquivo_zip) !== true) {
    echo "❌ Erro ao abrir o ZIP.\n";
    exit();
}
$zip->extractTo($pasta);
$zip->close();

echo "✅ Descompactado.\n";
flush();

// ─── Encontrar o CSV de bens ─────────────────────────────────────
$arquivo_csv = $pasta . 'bem_candidato_2022_BRASIL.csv';

if (!file_exists($arquivo_csv)) {
    $arquivos = glob($pasta . 'bem_candidato_2022_*.csv');
    if (empty($arquivos)) {
        echo "❌ Nenhum CSV de bens encontrado após descompactar.\n";
        exit();
    }
    $arquivo_csv = $arquivos[0];
}

echo "📄 Processando: " . basename($arquivo_csv) . "\n";
flush();

// ─── Buscar SQ_CANDIDATO dos candidatos que estão no banco ───────
// O arquivo de bens do TSE não tem CPF — usa SQ_CANDIDATO
// para identificar o candidato. Por isso precisamos do sq_candidato
// que foi importado junto com os candidatos no importar_csv.php.

$stmt_sq = $pdo->query(
    "SELECT id, sq_candidato
     FROM candidatos
     WHERE fonte = 'TSE'
       AND sq_candidato IS NOT NULL
       AND sq_candidato != ''"
);

$mapa_sq = [];
foreach ($stmt_sq->fetchAll() as $row) {
    $mapa_sq[trim($row['sq_candidato'])] = $row['id'];
}

echo "🔎 " . count($mapa_sq) . " candidatos com SQ_CANDIDATO encontrados no banco.\n";
flush();

if (empty($mapa_sq)) {
    echo "❌ Nenhum candidato com SQ_CANDIDATO no banco.\n";
    echo "Rode o importar_csv.php novamente — ele foi atualizado para salvar o SQ_CANDIDATO.\n";
    exit();
}

// ─── Limpar bens anteriores para evitar duplicatas ───────────────
$pdo->exec(
    "DELETE FROM bens_declarados WHERE id_candidato IN (
        SELECT id FROM candidatos WHERE fonte = 'TSE'
    )"
);

// ─── Processar o CSV linha por linha ─────────────────────────────
$handle = fopen($arquivo_csv, 'r');

// Primeira linha = cabeçalho
$cabecalho = fgetcsv($handle, 0, ';');
$cabecalho = array_map(
    fn($col) => mb_convert_encoding(trim($col), 'UTF-8', 'ISO-8859-1'),
    $cabecalho
);
$indice = array_flip($cabecalho);
// array_flip → permite acessar coluna pelo nome: $indice['SQ_CANDIDATO']

$stmt_insert = $pdo->prepare(
    "INSERT INTO bens_declarados (id_candidato, descricao, valor, ano_eleicao)
     VALUES (:id_candidato, :descricao, :valor, :ano)"
);

$total_importados = 0;
$total_linhas     = 0;

while (($linha = fgetcsv($handle, 0, ';')) !== false) {

    $linha = array_map(
        fn($col) => mb_convert_encoding($col ?? '', 'UTF-8', 'ISO-8859-1'),
        $linha
    );

    $total_linhas++;

    // Cruzar pelo SQ_CANDIDATO (não CPF — bens não tem CPF)
    $sq = trim($linha[$indice['SQ_CANDIDATO']] ?? '');

    if (!isset($mapa_sq[$sq])) continue;
    // Se esse sequencial não está entre nossos candidatos, pula

    $id_candidato = $mapa_sq[$sq];

    // Converter valor do formato brasileiro para número
    // ex: "1.250.000,50" → 1250000.50
    $valor_bruto = $linha[$indice['VR_BEM_CANDIDATO']] ?? '0';
    $valor = (float) str_replace(',', '.', str_replace('.', '', $valor_bruto));

    // Preferir descrição específica; usar tipo como fallback
    $descricao = trim($linha[$indice['DS_BEM_CANDIDATO']] ?? '');
    if (empty($descricao)) {
        $descricao = trim($linha[$indice['DS_TIPO_BEM_CANDIDATO']] ?? 'Bem não especificado');
    }

    $stmt_insert->execute([
        ':id_candidato' => $id_candidato,
        ':descricao'    => $descricao,
        ':valor'        => $valor,
        ':ano'          => 2022,
    ]);

    $total_importados++;

    // Mostrar progresso a cada 1000 registros
    if ($total_importados % 1000 === 0) {
        echo "   ... $total_importados bens importados até agora\n";
        flush();
    }
}

fclose($handle);

// ─── Limpar arquivos temporários ─────────────────────────────────
array_map('unlink', glob($pasta . 'bem_candidato_*.csv'));

// ─── Registrar no log ────────────────────────────────────────────
registrar_log($pdo, 'tse_bens', 'TSE_CSV', 'sucesso', $total_importados);

echo "\n✅ Importação de bens concluída!\n";
echo "📊 $total_importados bens importados de $total_linhas linhas processadas.\n";