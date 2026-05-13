<?php
require_once '../config.php';

// função reutilizavel para qualquer endpoint do portal

function chamar_portal(string $endpoint, array $parametros, PDO $pdo): array {
    // verificar log - não chamar API se já rodou hoje

    $stmt = $pdo->prepare(
        "SELECT id FROM log_atualizacoes
        WHERE tabela_atualizada = :endpoint
        AND status = 'sucesso'
        AND atualizado_em > CURDATE()
        LIMIT 1"

        // CURDATE() - traz a data de hoje sem hora
        // atualizado_em > CURDATE() - "foi atualizado hoje"
    );

    $stmt->execute([':endpoint' => $endpoint]);

    if ($stmt->fetch()) {
        return ['cache' => true, 'mensagem' => 'usando dados do banco'];
    }

    // montar URL com paginação automatica
    $todos_os_dados = [];
    $pagina_atual = 1;

    do {
        // do... while - executa uma vez e depois verificar a condição
        $parametros['pagina'] = $pagina_atual;

        $url = 'https://api.portaldatransparencia.gov.br/api-de-dados/'
        . $endpoint . '?' . http_build_query($parametros);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETUNRTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'chave-api-dados: '.$_ENV['TOKEN_TRANSPARENCIA'],
            'Accept: aplication/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $resposta = json_decode(curl_exec($curl), true);
    $codigo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($codigo === 429) {
        // 429 = too many requests
        sleep(60);
        continue;
    }

    if($codigo !== 200 || empty($resposta)) break;
    
    $todos_os_dados = array_merge($todos_os_dados, $resposta);
    // array_merge = junta os resultados de todas as páginas num array só

    $pagina_atual++;

    sleep(1);
    // pausa 1seg entre páginas
    } while (count($resposta) === 100);
    // o portal retorna 100/pág se vier menos que isso acaba

    // registrar sucesso no log
    $pdo->prepare(
        "INSERT INTO log_atualizacoes
            (tabela_atualizada, api_origem, status, total_registros)
        VALUES (:endpoint, 'PortalTransparencia', 'sucesso', :total)"
    )->execute([
        ':endpoint' => $endpoint
        ':total' => count($todos_os_dados),
    ]);

    return $todos_os_dados;
}

//----- usar a função para buscar emendas de cada candidato

// primeiro: pegar os candidatos que já estão no banco (vieram do TSE)
$candidatos => $pdo->query("SELECT id, nome, cpf FROM candidatos")->fetchAll();

foreach($candidatos as $candidato) {
    $emendas = chamar_portal('emendas', [
        'nomeAutor' => $candidato['nome'],
        'ano' => 2022,
    ] $pdo);

    // Salvar emenda no banco
}