<?php

//  public/candidatos.php — Endpoint para o front-end
//  Recebe os filtros do usuário e retorna candidatos em JSON

header('Content-Type: application/json; charset=utf-8');
// Diz ao navegador que a resposta é JSON

header('Access-Control-Allow-Origin: *');
// Permite que o front-end (mesmo domínio local) acesse este endpoint

require_once '../config.php';

// ─── PARTE 1: Receber e validar os filtros 

// Os filtros chegam via POST em formato JSON no corpo da requisição
$body = file_get_contents('php://input');
// php://input → lê o corpo bruto da requisição (o JSON enviado pelo JS)

$filtros = json_decode($body, true) ?? [];
// ?? [] → se não vier nada, usa array vazio (sem filtros = mostra todos)

// Valores permitidos para cada filtro — evita SQL injection por valores inválidos
$cargos_validos   = ['Vereador', 'Deputado Estadual', 'Deputado Federal', 'Senador', 'Governador', 'Presidente'];
$perfis_validos   = ['negro', 'mulher', 'lgbtqia', 'indigena'];
$propostas_validas = ['educacao', 'saude', 'meio-ambiente', 'indigena', 'seguranca', 'infraestrutura'];

// Filtrar só os valores que existem nas listas permitidas
$cargos   = array_intersect($filtros['cargo']   ?? [], $cargos_validos);
$perfis   = array_intersect($filtros['perfil']  ?? [], $perfis_validos);
$propostas = array_intersect($filtros['proposta'] ?? [], $propostas_validas);
// array_intersect → mantém só os valores que existem nos dois arrays
// Se o front mandar um valor estranho, ele é descartado aqui

// ─── PARTE 2: Montar a query com os filtros ativos

// Começamos com a query base que sempre roda
$sql = "SELECT 
            c.id,
            c.nome,
            c.partido,
            c.uf,
            c.cargo,
            c.genero,
            c.raca_cor,
            -- Soma total de bens declarados (subconsulta)
            (SELECT SUM(b.valor) 
             FROM bens_declarados b 
             WHERE b.id_candidato = c.id) AS total_bens,

            -- Áreas das emendas como lista separada por vírgula
            (SELECT GROUP_CONCAT(DISTINCT e.area ORDER BY e.area SEPARATOR ', ')
             FROM emendas_parlamentares e
             WHERE e.id_candidato = c.id) AS areas_emendas,

            -- Soma total de emendas
            (SELECT SUM(e.valor)
             FROM emendas_parlamentares e
             WHERE e.id_candidato = c.id) AS total_emendas

        FROM candidatos c
        WHERE 1=1";
// WHERE 1=1 → sempre verdadeiro, facilita adicionar filtros com AND depois

$params = [];
// Array que vai guardar os valores dos filtros para o prepared statement

// ─── Filtro de cargo
if (!empty($cargos)) {
    $placeholders = implode(',', array_fill(0, count($cargos), '?'));
    // array_fill   → cria array com N vezes '?': ['?', '?', '?']
    // implode      → junta com vírgula: '?,?,?'

    $sql .= " AND c.cargo IN ($placeholders)";
    $params = array_merge($params, array_values($cargos));
    // array_merge → adiciona os valores dos cargos ao array de parâmetros
}

// ─── Filtro de perfil (gênero e raça/cor)
if (!empty($perfis)) {
    $condicoes_perfil = [];

    foreach ($perfis as $perfil) {
        if ($perfil === 'mulher') {
            $condicoes_perfil[] = "c.genero = ?";
            $params[] = 'Feminino';
        }
        if ($perfil === 'negro') {
            $condicoes_perfil[] = "c.raca_cor IN (?, ?)";
            $params[] = 'Preta';
            $params[] = 'Parda';
            // TSE usa 'Preta' e 'Parda' como categorias separadas
        }
        if ($perfil === 'indigena') {
            $condicoes_perfil[] = "c.raca_cor = ?";
            $params[] = 'Indígena';
        }
        // lgbtqia+ não está nos dados do TSE — filtrar por emendas na área
        if ($perfil === 'lgbtqia') {
            $condicoes_perfil[] = "EXISTS (
                SELECT 1 FROM emendas_parlamentares e
                WHERE e.id_candidato = c.id AND e.area = ?
            )";
            $params[] = 'lgbtqia';
        }
    }

    if (!empty($condicoes_perfil)) {
        $sql .= " AND (" . implode(" OR ", $condicoes_perfil) . ")";
        // OR → candidato aparece se atender QUALQUER um dos perfis marcados
    }
}

// ─── Filtro de proposta (por área das emendas) 
if (!empty($propostas)) {
    $placeholders = implode(',', array_fill(0, count($propostas), '?'));
    $sql .= " AND EXISTS (
        SELECT 1 FROM emendas_parlamentares e
        WHERE e.id_candidato = c.id
          AND e.area IN ($placeholders)
    )";
    // EXISTS → mais eficiente que JOIN quando só precisamos saber "tem ou não tem"
    $params = array_merge($params, array_values($propostas));
}

$sql .= " ORDER BY c.nome ASC";

// ─── PARTE 3: Executar e retornar 

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $candidatos = $stmt->fetchAll();

    // Para cada candidato, buscar também os bens detalhados
    foreach ($candidatos as &$candidato) {
        // & → referência: modifica o array original, não uma cópia

        $stmt_bens = $pdo->prepare(
            "SELECT descricao, valor, ano_eleicao
             FROM bens_declarados
             WHERE id_candidato = ?
             ORDER BY valor DESC
             LIMIT 5"
             // LIMIT 5 → só os 5 maiores bens para não sobrecarregar a resposta
        );
        $stmt_bens->execute([$candidato['id']]);
        $candidato['bens'] = $stmt_bens->fetchAll();

        // Buscar emendas agrupadas por área
        $stmt_emendas = $pdo->prepare(
            "SELECT area, COUNT(*) as quantidade, SUM(valor) as total
             FROM emendas_parlamentares
             WHERE id_candidato = ?
             GROUP BY area
             ORDER BY total DESC"
        );
        $stmt_emendas->execute([$candidato['id']]);
        $candidato['emendas_por_area'] = $stmt_emendas->fetchAll();

        // Formatar valores monetários para exibição
        $candidato['total_bens_formatado']   = 'R$ ' . number_format($candidato['total_bens']   ?? 0, 2, ',', '.');
        $candidato['total_emendas_formatado'] = 'R$ ' . number_format($candidato['total_emendas'] ?? 0, 2, ',', '.');
    }

    echo json_encode([
        'sucesso'    => true,
        'total'      => count($candidatos),
        'filtros_aplicados' => [
            'cargo'   => array_values($cargos),
            'perfil'  => array_values($perfis),
            'proposta' => array_values($propostas),
        ],
        'candidatos' => $candidatos,
    ], JSON_UNESCAPED_UNICODE);
    // JSON_UNESCAPED_UNICODE → mantém acentos legíveis no JSON (não converte para \u00e3)

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar candidatos: ' . $e->getMessage()]);
}