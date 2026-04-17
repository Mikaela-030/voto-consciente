<?php
require_once '.../config.php';

// arquivos que serão baixados do TSE
// nesse caso serão baixados os arquivos de 2022 já que não temos os dados de 2026

$arquivos_tse = [
    'candidatos' => [
        'url' => 'https://cdn.tse.jus.br/estatistica/sead/odsele/consulta_cand/consulta_cand_2022.zip'
        // URL pública do TSE, não precisa de token
        'destino' => __DIR__. '/arquivos_csv/candidatos_2022.zip',
        // __DIR__ pasta atual do arquivo PHP
    ],
    'bens' => [
        'url' =>'https://cdn.tse.jus.br/estatistica/sead/odsele/bem_candidato/bem_candidato_2022.zip',
        'destino' => __DIR__.'/arquivos_csv/bens_2022.zip',
    ],
];

#------ Criar pasta de destino se não existir -----
$pasta = __DIR__.'/arquivos_csv/';

if (!is_dir($pasta)) {
    mkdir($pasta, 0755, true);
    // mkdir() -> cria pasta 
    // 0075 -> permissões de acesso (o dono pode tudo, outros podem ler)
    // true -> cria pastas intermediárias se necessário
}

#----- Cria e baixa arquivo  -----

foreach($arquivos_tse as $nome => $info) {
    // verificar no log se já foi baixado essa semana - não há necessidade de baixar diariamente 
    $stmt => $pdo->prepare(
        "SELECT atualizado_em FROM log_atualizacoes
        WHERE tabela_atualizada = :tabela
        AND status = 'sucesso'
        AND atualizado_em >NOW() - INTERVAL 7 day
        LIMIT 1"
    );
    // NOW() - INTERVAL 7 DAY "data atual menos de 7 dias" - só baixa se o último download foi há mais de 7 dias 

    $stmt->execute([':tabela' => 'tse_'.$nome]);

    if ($stmt->fetch()) {
        echo "[$nome] Já atualizado essa semana. Pulando \n.";
        continue;
        // continue -> pula pro próximo item do foreach
    }

    // fazer download com cURL
    echo "[$nome] Baixando...\n.";

    $fp = fopen($info['destino'], 'wb');
    // fopen() > abre ou cria um arquivo para escrita
    // 'wb' -> w = escrita, b = modo binário (obrigatório para ZIPs)

    $curl = curl_init($info['url']);
    curl_setopt_array($curl, [
        CURLOPT_FILE => $fp,
        // CURLOPT_FILE -> salva o conteúdo direto no arquivo aberto
        // sem isso teria que guardar tudo na memória RAM

        CURLOPT_FOLLOWLOCATION => true,
        // FOLLOWLOCATION > segue redirecionamento HTTP automaticamente

        CURLOPT_TIMEOUT => 300,
        // 5 minutos de timeout - arquivos do TSE podem ser grandes
    ]);

    $sucesso = curl_exec($curl);
    $codigo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    fclose($fp);
    // fclose() > fecha o arquivo - importante para garantir que foi salvo

    // registrar no log
    $log = $pdo->prepare(
        "INSERT INTO log_atualizacoes
            (tabela_atualizada, api_origem, status, mensagem_erro)
        VALUES (:tabela, 'TSE_CSV', :status, :erro)"
    );
    $log->execute([
        ':tabela' =>'tse_'.$nome,
        ':status' => ($codigo === 200 ? 'sucesso':'erro'),
        ':erro' => ($codigo !== 200 ? "HTTP $codigo" : null),
    ]);

}