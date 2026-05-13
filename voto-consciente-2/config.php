<?php
// ============================================================
//  config.php — Conexão unificada com o banco
//  Substitui o config.php anterior do colega
//  Mantém compatibilidade com cadastro.php e testeLogin.php
// ============================================================

// ─── PARTE 1: Ler o arquivo .env ─────────────────────────────────

$linhas = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
// __DIR__ → pasta onde este arquivo está
// Garante que o .env é encontrado não importa de onde config.php for chamado

if ($linhas) {
    foreach ($linhas as $linha) {
        if (str_starts_with($linha, '#')) continue;
        if (!str_contains($linha, '=')) continue;
        // str_contains → verifica se tem '=' na linha (ignora linhas inválidas)

        [$chave, $valor] = explode('=', $linha, 2);
        $_ENV[trim($chave)] = trim($valor);
    }
}

// ─── PARTE 2: Conexão PDO (usada pelos arquivos de API) ──────────

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // FETCH_ASSOC → resultados vêm com nome das colunas: $linha['nome']
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['erro' => 'Erro na conexão com o banco: ' . $e->getMessage()]));
}

// ─── PARTE 3: Conexão mysqli (compatibilidade com cadastro e login) ──
// O código do colega usa mysqli — mantemos as duas conexões
// para não precisar reescrever o cadastro.php e testeLogin.php

$conexao = new mysqli(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    $_ENV['DB_NAME']
);
// Agora $conexao aponta para tcc_candidatos (não mais para 'formulario')

if ($conexao->connect_errno) {
    die('Erro de conexão: (' . $conexao->connect_errno . ') ' . $conexao->connect_error);
}

$conexao->set_charset('utf8mb4');
// Garante que acentos funcionem corretamente

// ─── PARTE 4: Funções auxiliares de log ──────────────────────────

function registrar_log(
    PDO    $pdo,
    string $tabela,
    string $api,
    string $status,
    int    $total = 0,
    string $erro  = null
): void {
    $stmt = $pdo->prepare(
        "INSERT INTO log_atualizacoes
            (tabela_atualizada, api_origem, status, total_registros, mensagem_erro)
         VALUES (:tabela, :api, :status, :total, :erro)"
    );
    $stmt->execute([
        ':tabela' => $tabela,
        ':api'    => $api,
        ':status' => $status,
        ':total'  => $total,
        ':erro'   => $erro,
    ]);
}

function ja_atualizado_hoje(PDO $pdo, string $tabela): bool {
    $stmt = $pdo->prepare(
        "SELECT id FROM log_atualizacoes
          WHERE tabela_atualizada = :tabela
            AND status = 'sucesso'
            AND DATE(atualizado_em) = CURDATE()
          LIMIT 1"
    );
    $stmt->execute([':tabela' => $tabela]);
    return (bool) $stmt->fetch();
}

function ja_atualizado_essa_semana(PDO $pdo, string $tabela): bool {
    $stmt = $pdo->prepare(
        "SELECT id FROM log_atualizacoes
          WHERE tabela_atualizada = :tabela
            AND status = 'sucesso'
            AND atualizado_em > NOW() - INTERVAL 7 DAY
          LIMIT 1"
    );
    $stmt->execute([':tabela' => $tabela]);
    return (bool) $stmt->fetch();
}