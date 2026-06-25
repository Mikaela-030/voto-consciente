<?php
// ============================================================
//  TSE/importar_csv.php
//  Importa candidatos à Presidência (2022) e Deputados Federais eleitos (2022)
//
//  Rodar pelo navegador:
//  http://localhost/voto-consciente-2/TSE/importar_csv.php
//
//  Ou pelo terminal (recomendado — sem limite de tempo do navegador):
//  cd C:\xampp\htdocs\voto-consciente-2\TSE
//  C:\xampp\php\php.exe importar_csv.php
// ============================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';

set_time_limit(600);
ini_set('memory_limit', '512M');

echo "🚀 Iniciando importação...\n";
flush();

// ─── Candidatos à presidência monitorados ────────────────────────
// Usamos os dados de 2022 dessas pessoas, mesmo que disputem 2026
$presidentes_monitorados = [
    'LUIZ INÁCIO LULA DA SILVA',
    'JAIR MESSIAS BOLSONARO',
    'FERNANDO HADDAD',
    'TARCÍSIO GOMES DE FREITAS',
    'ROMEU ZEMA NETO',
    'FLAVIO BOLSONARO',
    'MICHELLE BOLSONARO',
];
// Michelle e Flavio não foram candidatos à presidência em 2022
// mas os dados deles (patrimônio, emendas) existem no TSE
// e serão buscados pelo nome nos arquivos de bens declarados

// ─── Situações que indicam candidato ELEITO ──────────────────────
$situacoes_eleito = [
    'ELEITO',
    'ELEITO POR QP',
    'ELEITO POR MÉDIA',
    // O TSE usa essas três variações para candidatos eleitos
];

// ─── URLs dos arquivos do TSE ────────────────────────────────────
$arquivos_tse = [
    [
        'url'    => 'https://cdn.tse.jus.br/estatistica/sead/odsele/consulta_cand/consulta_cand_2022.zip',
        // URL correta confirmada no Portal de Dados Abertos do TSE
        // (sem sufixo _BRASIL — esse sufixo só existe no CSV de dentro do ZIP)
        'destino' => __DIR__ . '/arquivos_csv/candidatos_2022.zip',
        'label'  => 'Candidatos 2022',
    ],
];

$pasta = __DIR__ . '/arquivos_csv/';
if (!is_dir($pasta)) {
    mkdir($pasta, 0755, true);
}

// ─── Verificar se já foi atualizado essa semana ──────────────────
if (ja_atualizado_essa_semana($pdo, 'tse_candidatos')) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM candidatos WHERE fonte = 'TSE'");
    $total = $stmt->fetch()['total'];
    echo "✅ Candidatos já atualizados essa semana ($total no banco).<br>";
    echo "<a href='../votoconsciente.php'>Ir para a aplicação</a>";
    exit();
}

// ─── Download ────────────────────────────────────────────────────
foreach ($arquivos_tse as $arquivo) {

    echo "⬇️ Baixando: {$arquivo['label']}...<br>";
    flush();

    $fp   = fopen($arquivo['destino'], 'wb');
    $curl = curl_init($arquivo['url']);
    curl_setopt_array($curl, [
        CURLOPT_FILE           => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 300,
        CURLOPT_USERAGENT      => 'VotoConsciente-TCC/1.0',
    ]);

    curl_exec($curl);
    $codigo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    fclose($fp);

    if ($codigo !== 200) {
        echo "❌ Erro no download. Código HTTP: $codigo<br>";
        registrar_log($pdo, 'tse_candidatos', 'TSE_CSV', 'erro', 0, "HTTP $codigo");
        exit();
    }

    echo "✅ Download concluído.<br>";
    flush();

    // ─── Descompactar ────────────────────────────────────────────
    echo "📦 Descompactando...<br>";
    flush();

    $zip = new ZipArchive();
    if ($zip->open($arquivo['destino']) !== true) {
        echo "❌ Erro ao abrir o ZIP.<br>";
        exit();
    }
    $zip->extractTo($pasta);
    $zip->close();
    echo "✅ Descompactado.<br>";
    flush();
}

// ─── Processar os CSVs ───────────────────────────────────────────
// Preferimos o arquivo consolidado BRASIL.csv (mais rápido, um arquivo só)
// Se não existir, processamos todos os arquivos por UF como alternativa
$arquivo_brasil = $pasta . 'consulta_cand_2022_BRASIL.csv';

if (file_exists($arquivo_brasil)) {
    $arquivos_csv = [$arquivo_brasil];
    echo "📌 Usando arquivo consolidado nacional (mais rápido).<br>";
} else {
    $arquivos_csv = glob($pasta . 'consulta_cand_2022_*.csv');
    echo "📌 Arquivo nacional não encontrado — processando por estado.<br>";
}

$total_importados = 0;

if (empty($arquivos_csv)) {
    echo "❌ Nenhum CSV encontrado após descompactar.<br>";
    exit();
}

// Preparar INSERT uma vez só — fora do loop por eficiência
$stmt_insert = $pdo->prepare(
    "INSERT INTO candidatos
        (nome, partido, uf, cargo, cpf, genero, raca_cor, fonte, sq_candidato, atualizado_em)
     VALUES
        (:nome, :partido, :uf, :cargo, :cpf, :genero, :raca_cor, 'TSE', :sq_candidato, NOW())
     ON DUPLICATE KEY UPDATE
         partido      = VALUES(partido),
         cargo        = VALUES(cargo),
         genero       = VALUES(genero),
         raca_cor     = VALUES(raca_cor),
         sq_candidato = VALUES(sq_candidato),
         atualizado_em = NOW()"
);

foreach ($arquivos_csv as $arquivo_csv) {

    echo "📄 Processando: " . basename($arquivo_csv) . "<br>";
    flush();

    $handle = fopen($arquivo_csv, 'r');

    // Primeira linha = cabeçalho
    $cabecalho = fgetcsv($handle, 0, ';');
    $cabecalho = array_map(
        fn($col) => mb_convert_encoding(trim($col), 'UTF-8', 'ISO-8859-1'),
        $cabecalho
        // TSE usa ISO-8859-1 — precisamos converter para UTF-8
    );
    $indice = array_flip($cabecalho);
    // array_flip → permite acessar coluna pelo nome: $indice['NM_CANDIDATO']

    while (($linha = fgetcsv($handle, 0, ';')) !== false) {

        $linha = array_map(
            fn($col) => mb_convert_encoding($col ?? '', 'UTF-8', 'ISO-8859-1'),
            $linha
        );

        $cargo     = strtoupper(trim($linha[$indice['DS_CARGO']]            ?? ''));
        $nome      = strtoupper(trim($linha[$indice['NM_CANDIDATO']]        ?? ''));
        $situacao  = strtoupper(trim($linha[$indice['DS_SIT_TOT_TURNO']]    ?? ''));
        // DS_SIT_TOT_TURNO → situação final do candidato: ELEITO, NÃO ELEITO, etc.

        // ── Regra 1: Presidentes monitorados (qualquer situação) ──
        // Importamos independente de eleito/não eleito
        // porque usamos os dados históricos dessas pessoas
        if ($cargo === 'PRESIDENTE') {
            $encontrado = false;
            foreach ($presidentes_monitorados as $monitorado) {
                if (str_contains($nome, explode(' ', strtoupper($monitorado))[0])) {
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) continue;
        }

        // ── Regra 2: Deputados Federais — só os eleitos ───────────
        elseif ($cargo === 'DEPUTADO FEDERAL') {
            if (!in_array($situacao, $situacoes_eleito)) continue;
            // Se não está na lista de situações de eleito, pula
        }

        // ── Qualquer outro cargo — ignora ─────────────────────────
        else {
            continue;
        }

        $stmt_insert->execute([
            ':nome'         => mb_convert_case(mb_strtolower($nome, 'UTF-8'), MB_CASE_TITLE, 'UTF-8'),
            ':partido'      => trim($linha[$indice['SG_PARTIDO']]       ?? ''),
            ':uf'           => trim($linha[$indice['SG_UF']]            ?? ''),
            ':cargo'        => mb_convert_case(mb_strtolower($cargo, 'UTF-8'), MB_CASE_TITLE, 'UTF-8'),
            ':cpf'          => trim($linha[$indice['NR_CPF_CANDIDATO']] ?? ''),
            ':genero'       => trim($linha[$indice['DS_GENERO']]        ?? ''),
            ':raca_cor'     => trim($linha[$indice['DS_COR_RACA']]      ?? ''),
            ':sq_candidato' => trim($linha[$indice['SQ_CANDIDATO']]     ?? ''),
        ]);

        $total_importados++;
    }

    fclose($handle);
    echo "✅ Arquivo processado.<br>";
    flush();
}

// ─── Adicionar aviso de dados no banco 
// Garante que a tabela tenha a coluna de aviso - (só executa se a coluna ainda não existir)
try {
    $pdo->exec(
        "ALTER TABLE candidatos
         ADD COLUMN IF NOT EXISTS aviso_dados TEXT DEFAULT NULL"
    );

    // Atualiza o aviso para todos os candidatos importados do TSE
    $pdo->exec(
        "UPDATE candidatos
         SET aviso_dados = 'Dados referentes à eleição de 2022. Algumas informações podem não refletir a situação atual do candidato.'
         WHERE fonte = 'TSE'"
    );
} catch (PDOException $e) {
    // Coluna já existe — sem problema, continua
}

// ─── Registrar no log 
registrar_log($pdo, 'tse_candidatos', 'TSE_CSV', 'sucesso', $total_importados);

// ─── Limpar arquivos temporários 
array_map('unlink', glob($pasta . '*.csv'));
// Mantém o ZIP como backup mas remove os CSVs que podem ser grandes

echo "<br>";
echo "✅ <strong>Importação concluída!</strong><br>";
echo "📊 <strong>$total_importados candidatos</strong> importados.<br><br>";
echo "⚠️ <em>Aviso de dados inserido: os usuários serão informados que os dados são de 2022.</em><br><br>";
echo "<a href='../votoconsciente.php'>Ir para a aplicação</a>";