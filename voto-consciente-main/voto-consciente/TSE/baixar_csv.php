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