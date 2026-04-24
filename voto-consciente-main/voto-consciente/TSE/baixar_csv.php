<?php
require_once '../config.php';

// ─── Quais arquivos baixar do TSE ────────────────────────────────
// Estes são os arquivos públicos da eleição 2022
// Para 2026, os URLs seguirão o mesmo padrão — só muda o ano

$arquivos_tse = [
    'candidatos' => [
        'url'    => 'https://cdn.tse.jus.br/estatistica/sead/odsele/consulta_cand/consulta_cand_2022.zip',
        // URL pública do TSE — não precisa de token
        'destino' => __DIR__ . '/arquivos_csv/candidatos_2022.zip',
        // __DIR__ → pasta atual do arquivo PHP
    ],
    'bens' => [
        'url'    => 'https://cdn.tse.jus.br/estatistica/sead/odsele/bem_candidato/bem_candidato_2022.zip',
        'destino' => __DIR__ . '/arquivos_csv/bens_2022.zip',
    ],
    'receitas' => [
        'url'    => 'https://cdn.tse.jus.br/estatistica/sead/odsele/prestacao_contas/receitas_candidatos_2022_BRASIL.zip',
        'destino' => __DIR__ . '/arquivos_csv/receitas_2022.zip',
    ],
];

// ─── Criar a pasta de destino se não existir ─────────────────────

$pasta = __DIR__ . '/arquivos_csv/';

if (!is_dir($pasta)) {
    mkdir($pasta, 0755, true);
    // mkdir()     → cria a pasta
    // 0755        → permissões de acesso (o dono pode tudo, outros podem ler)
    // true        → cria pastas intermediárias se necessário
}

// ─── Baixar cada arquivo ─────────────────────────────────────────

foreach ($arquivos_tse as $nome => $info) {

    // Verificar no log se já foi baixado essa semana
    // (CSVs do TSE não mudam diariamente — checar semanalmente é suficiente)

    $stmt = $pdo->prepare(
        "SELECT atualizado_em FROM log_atualizacoes
         WHERE tabela_atualizada = :tabela
           AND status = 'sucesso'
           AND atualizado_em > NOW() - INTERVAL 7 DAY
         LIMIT 1"
    );
    // NOW() - INTERVAL 7 DAY → "data atual menos 7 dias"
    // ou seja: só baixa de novo se o último sucesso foi há mais de 7 dias

    $stmt->execute([':tabela' => 'tse_' . $nome]);
    
    if ($stmt->fetch()) {
        echo "[$nome] Já atualizado essa semana. Pulando.\n";
        continue;
        // continue → pula para o próximo item do foreach
    }

    // Fazer o download com cURL
    echo "[$nome] Baixando...\n";

    $fp = fopen($info['destino'], 'wb');
    // fopen()  → abre (ou cria) um arquivo para escrita
    // 'wb'     → w = escrita, b = modo binário (obrigatório para ZIPs)

    $curl = curl_init($info['url']);
    curl_setopt_array($curl, [
        CURLOPT_FILE           => $fp,
        // CURLOPT_FILE → salva o conteúdo direto no arquivo aberto
        // sem isso teria que guardar tudo na memória RAM

        CURLOPT_FOLLOWLOCATION => true,
        // FOLLOWLOCATION → segue redirecionamentos HTTP automaticamente

        CURLOPT_TIMEOUT        => 300,
        // 5 minutos de timeout — arquivos do TSE podem ser grandes
    ]);

    $sucesso = curl_exec($curl);
    $codigo  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    fclose($fp);
    // fclose() → fecha o arquivo — importante para garantir que foi salvo

    // Registrar no log
    $log = $pdo->prepare(
        "INSERT INTO log_atualizacoes
            (tabela_atualizada, api_origem, status, mensagem_erro)
         VALUES (:tabela, 'TSE_CSV', :status, :erro)"
    );
    $log->execute([
        ':tabela' => 'tse_' . $nome,
        ':status' => ($codigo === 200 ? 'sucesso' : 'erro'),
        ':erro'   => ($codigo !== 200 ? "HTTP $codigo" : null),
    ]);
}