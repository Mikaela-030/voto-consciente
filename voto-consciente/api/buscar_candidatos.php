<?php
require_once '../config.php';
// require_once -> inclua o arquivo config.php aqui

// ----- montar requisição -----
$url_base = 'https://api.portaldatransparencia.gov.br/api-de-dados/';
// URL raiz da API - onde os endpoints começam

$endpoint = 'candidatos_eleitorais';
// endpoint que será consultado nesse php

$parametros = http_build_query([
    'ano' => '2026',
    'uf' => 'SP',
    'pagina' => '1',
]);

// http_build_query transfora o array em texto em url "ano=2-26&uf=sp&pagina=1"

$url_completa = $url_base . $endpoint . '?' . $parametros;
// monta a url final

// ------ configurar e executar requisição
curl = curl_init(); // curl_init() -> inicializa uma sessão cURL - ligação com a API

curl_setopt_array($curl, [
    //define várias configurações de uma vez

    CURLOPT_URL => $url_completa
    // qual endereço chamar

    CURLOPT_RETURNTRANSFER => true,
    // RETURNTRANSFER guarda a resposta numa variável mas não imprime - sem isso a resposta da API seria exibito imediatamente

    CURLOPT_HTTPHEADER => [
        'chave-api-dados: ' . $_ENV[TOKEN_TRANSPARENCIA],
        'Accept: application/json',
    ],
    // HTTPHEADER = cabeçalho da requisição
    // 'chave-api-dados' = nome exato do cabeçalho que o portal da transparência exige
    // Accept = diz a API que queremos a resposta em JSON

    CURLOPT_TIMEOUT => 30,
    // se a API não responder em 30seg - desiste e gera erro

]);

$resposta_bruta = curl_exec($curl);
// resposta_bruta = ainda é texto puro (string JSON)
// curl_exec() = executa requisição e guarda o resultado

$codigo_http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
// curl_getinfo = pega informações sobre a requisição executada
// CURLINFO_HTTP_CODE = código de resposta da API
// 200 = SUCESSO / 401 = TOKEN INVÁLIDO OU AUSENTE / 404 ENDPOINT NÃO ENCONTRADO / 429 = EXCESSO DE REQUISIÇÕES (LIMITE DA API)

curl_close($curl);
// encerra a sessão cURL e libera memória 

// ------ verificar e processar resposta ------
