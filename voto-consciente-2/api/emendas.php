<?php
// Busca emendas para cada candidato que o importar_csv já trouxe
// as requisições são feitas de acordo com as regras - 400 req/min das 06:00 - 00:00 - isso evita bloqueio do token no portal

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';

set_time_limit(0);
// como é um processo que leva muito tempo, o ideal é rodar via cmd e não pelo navegador na V0 - ajustar para próximas versões 

// ─── Ano de referência das emendas 
// candidatos eleitos em 2022 e que assumiram em 2023, logo as emendas que buscamos são de 2023

const ANO_EMENDAS = 2023;

// ─── Configuração de segurança contra bloqueio 
// Pausa de 2s entre requisições para evitar bloqueio

const PAUSA_ENTRE_REQUISICOES = 2;
// 2 segundos entre cada chamada = no máximo 30 req/min

const PAUSA_APOS_BLOQUEIO = 1800;
// Se receber 429 - espera 20 min antes de tentar novamente - evitar bloqueio

// ─── Mapa de classificação por área

function classificar_area(string $funcao): string {
    $funcao = mb_strtolower(trim($funcao));

    $mapa_funcoes = [
        'saude'          => ['saúde'],
        'educacao'       => ['educação'],
        'meio-ambiente'  => ['gestão ambiental'],
        'indigena'       => ['direitos da cidadania'],
        'seguranca'      => ['segurança pública', 'defesa nacional'],
        'infraestrutura' => ['urbanismo', 'transporte', 'saneamento'],
    ];

    foreach ($mapa_funcoes as $area => $termos) {
        foreach ($termos as $termo) {
            if (str_contains($funcao, $termo)) {
                return $area;
            }
        }
    }

    return 'outros';
}

function converter_valor_brasileiro(string $valor_texto): float {
    $limpo = str_replace('.', '', $valor_texto);
    $limpo = str_replace(',', '.', $limpo);
    return (float) $limpo;
}

// ─── Chamada única à API, com tratamento de bloqueio
// Retorna: ['status' => 'ok'|'bloqueado'|'erro', 'dados' => [...]]

function chamar_api_emendas(string $nome_autor, int $pagina): array {

    $parametros = http_build_query([
        'nomeAutor' => $nome_autor,
        'ano'       => ANO_EMENDAS,
        'pagina'    => $pagina,
    ]);

    $url = 'https://api.portaldatransparencia.gov.br/api-de-dados/emendas?' . $parametros;

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'chave-api-dados: ' . $_ENV['TOKEN_TRANSPARENCIA'],
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $resposta_bruta = curl_exec($curl);
    $codigo         = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($codigo === 429) {
        return ['status' => 'bloqueado', 'dados' => []];
    }

    if ($codigo !== 200) {
        return ['status' => 'erro', 'dados' => [], 'codigo' => $codigo];
    }

    $resposta = json_decode($resposta_bruta, true);

    if (empty($resposta) || !is_array($resposta)) {
        return ['status' => 'ok', 'dados' => []];
        // Array vazio é resposta válida — só significa "sem emendas nessa página"
    }

    return ['status' => 'ok', 'dados' => $resposta];
}

// ─── Buscar todas as páginas de emendas de um candidato
// Retorna null se bater num bloqueio (sinal para o loop principal parar tudo)

function buscar_emendas_candidato(string $nome_autor): ?array {

    $todas_emendas = [];
    $pagina_atual  = 1;

    do {
        $resultado = chamar_api_emendas($nome_autor, $pagina_atual);

        if ($resultado['status'] === 'bloqueado') {
            return null;
            // null é o sinal de "pare tudo, fomos bloqueados"
        }

        if ($resultado['status'] === 'erro') {
            break;
            // Erro pontual (não bloqueio) — desiste só desse candidato
        }

        $pagina_dados  = $resultado['dados'];
        $qtd_pagina    = count($pagina_dados);
        $todas_emendas = array_merge($todas_emendas, $pagina_dados);
        $pagina_atual++;

        if ($qtd_pagina < 15) break;
        // Página não veio cheia = essa foi a última

        sleep(PAUSA_ENTRE_REQUISICOES);

    } while (true);

    return $todas_emendas;
}

// ─── Gerar variações do nome para tentar encontrar o autor 
// O Portal da Transparência usa o "nome parlamentar" (forma curta,
// sem acento, como o político é conhecido publicamente), além de ser case-sensitive, enquanto o TSE fornece o nome civil completo. Ex: TSE = "NATÁLIA BASTOS BONAVIDES" → Portal = "NATALIA BONAVIDES"

function gerar_variacoes_nome(string $nome_completo): array {

    $partes = explode(' ', trim($nome_completo));

    if (count($partes) < 2) {
        return [$nome_completo];
        // Nome de uma palavra só — não há o que variar
    }

    $primeiro = $partes[0];
    $ultimo   = end($partes);

    $variacoes = [
        $primeiro . ' ' . $ultimo,
        // 1ª tentativa (mais provável de funcionar, testado e confirmado):
        // primeiro + último nome — ex: "Andre Janones", "Natalia Bonavides"

        $nome_completo,
        // 2ª tentativa: nome completo, caso o parlamentar use nome completo

        $ultimo,
        // 3ª tentativa: só o sobrenome — última opção, mais arriscada
        // (pode encontrar outra pessoa com sobrenome parecido)
    ];

    // Remove acentos — o Portal costuma usar nomes sem acentuação
    $variacoes_sem_acento = array_map(
        fn($v) => iconv('UTF-8', 'ASCII//TRANSLIT', $v),
        $variacoes
    );

    $todas_variacoes = array_merge($variacoes, $variacoes_sem_acento);

    // O parâmetro nomeAutor é sensível a maiúsculas/minúsculas.
    $maiusculas = array_map(
        fn($v) => mb_strtoupper($v, 'UTF-8'),
        $todas_variacoes
    );

    // array_unique remove duplicatas, preservando a ordem de prioridade:
    // CAIXA ALTA primeiro (mais provável), depois as demais como fallback
    return array_values(array_unique(array_merge($maiusculas, $todas_variacoes)));
}

// ─── Buscar emendas tentando variações do nome em cascata
// Para assim que uma variação encontrar resultado — não tenta as próximas, economizando requisições

function buscar_emendas_com_variacoes(string $nome_completo): ?array {

    $variacoes = gerar_variacoes_nome($nome_completo);

    foreach ($variacoes as $variacao) {

        $emendas = buscar_emendas_candidato($variacao);

        if ($emendas === null) {
            return null;
            // Bloqueio — propaga para parar o script inteiro
        }

        if (!empty($emendas)) {
            return $emendas;
            // Achou resultado nessa variação — para de tentar as outras
        }

        sleep(PAUSA_ENTRE_REQUISICOES);
        // Pausa também entre tentativas de variação do mesmo candidato
    }

    return [];
    // Nenhuma variação encontrou nada — candidato realmente sem emendas
    
}

// ─── Verificar se já foi atualizado hoje

if (ja_atualizado_hoje($pdo, 'emendas')) {
    $total = $pdo->query("SELECT COUNT(*) as t FROM emendas_parlamentares")->fetch()['t'];
    echo "✅ Emendas já atualizadas hoje ($total registros no banco).\n";
    exit();
}

// ─── Checkpoint: retomar de onde parou
// Criamos uma tabela simples de controle para não reprocessar candidatos já buscados, caso o script seja interrompido

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS emendas_checkpoint (
        id_candidato INT PRIMARY KEY,
        processado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
);

$candidatos = $pdo->query(
    "SELECT c.id, c.nome
     FROM candidatos c
     LEFT JOIN emendas_checkpoint chk ON chk.id_candidato = c.id
     WHERE c.fonte = 'TSE' AND chk.id_candidato IS NULL
     ORDER BY c.id"
    // LEFT JOIN + IS NULL → pega só candidatos que AINDA NÃO têm checkpoint - permite rodar o script várias vezes sem reprocessar quem já foi feito
)->fetchAll();

if (empty($candidatos)) {
    echo "✅ Todos os candidatos já foram processados anteriormente.\n";
    echo "Se quiser reprocessar do zero, rode:\n";
    echo "  DELETE FROM emendas_checkpoint;\n";
    exit();
}

echo "🔎 " . count($candidatos) . " candidato(s) pendente(s) de busca.\n";
echo "⏱️  Pausa de " . PAUSA_ENTRE_REQUISICOES . "s entre requisições — processo pode demorar.\n\n";
flush();

$stmt_insert = $pdo->prepare(
    "INSERT INTO emendas_parlamentares (id_candidato, descricao, valor, ano, area)
     VALUES (:id_candidato, :descricao, :valor, :ano, :area)"
);

$stmt_checkpoint = $pdo->prepare(
    "INSERT INTO emendas_checkpoint (id_candidato) VALUES (:id_candidato)"
);

$total_emendas_importadas = 0;
$total_processados        = 0;

foreach ($candidatos as $candidato) {

    echo "👤 {$candidato['nome']}... ";
    flush();

    $emendas = buscar_emendas_com_variacoes($candidato['nome']);

    // ─── Bloqueio detectado: parar tudo com segurança 
    if ($emendas === null) {
        echo "\n\n🛑 BLOQUEIO DETECTADO (HTTP 429).\n";
        echo "Processo interrompido em segurança após $total_processados candidato(s).\n";
        echo "Os candidatos já processados foram salvos e não serão refeitos.\n";
        echo "Aguarde antes de rodar novamente, ou tente fora do horário de pico.\n";
        registrar_log(
            $pdo, 'emendas', 'PortalTransparencia', 'erro',
            $total_emendas_importadas,
            "Bloqueado por limite de requisições após $total_processados candidatos"
        );
        exit();
        // Para o script imediatamente — NÃO insiste, NÃO espera e tenta de novo sozinho. É mais seguro rodar de novo manualmente mais tarde após o período de bloqueio.
    }

    if (!empty($emendas)) {
        foreach ($emendas as $emenda) {
            $funcao    = $emenda['funcao'] ?? '';
            $descricao = trim(($emenda['tipoEmenda'] ?? '') . ' - ' . ($emenda['localidadeDoGasto'] ?? ''));
            $valor     = converter_valor_brasileiro($emenda['valorEmpenhado'] ?? '0');

            $stmt_insert->execute([
                ':id_candidato' => $candidato['id'],
                ':descricao'    => $descricao,
                ':valor'        => $valor,
                ':ano'          => $emenda['ano'] ?? ANO_EMENDAS,
                ':area'         => classificar_area($funcao),
            ]);

            $total_emendas_importadas++;
        }
        echo count($emendas) . " emenda(s).\n";
    } else {
        echo "nenhuma emenda.\n";
    }

    // Marca esse candidato como processado, independente de ter encontrado algo
    $stmt_checkpoint->execute([':id_candidato' => $candidato['id']]);
    $total_processados++;

    flush();

    sleep(PAUSA_ENTRE_REQUISICOES);
    // Pausa também entre candidatos, não só entre páginas
}

// ─── Registrar sucesso no log

registrar_log($pdo, 'emendas', 'PortalTransparencia', 'sucesso', $total_emendas_importadas);

echo "\n✅ Importação concluída!\n";
echo "📊 $total_processados candidato(s) processado(s).\n";
echo "📊 $total_emendas_importadas emenda(s) importada(s) no total.\n";