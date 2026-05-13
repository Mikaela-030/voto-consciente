-- ============================================================
--  TCC Candidatos — Script completo de criação e teste
--  Cole tudo isso na aba SQL do phpMyAdmin e clique em Executar
-- ============================================================

-- Garante que estamos usando o banco certo
USE tcc_candidatos;

-- SET garante que acentos e caracteres especiais funcionem
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ============================================================
-- BLOCO 1 — TABELAS DO USUÁRIO
-- (criadas primeiro porque não dependem de ninguém)
-- ============================================================

CREATE TABLE IF NOT EXISTS usuarios (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nome_usuario     VARCHAR(100)  NOT NULL,
    senha_hash       VARCHAR(255)  NOT NULL,
    -- NUNCA guardar senha em texto puro — sempre usar password_hash() no PHP
    data_nascimento  DATE,
    email            VARCHAR(150),
    criado_em        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_usuario UNIQUE (nome_usuario),
    CONSTRAINT uq_email   UNIQUE (email)
    -- CONSTRAINT com nome facilita identificar o erro se houver duplicata
);

CREATE TABLE IF NOT EXISTS preferencias_usuario (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario          INT          NOT NULL,
    notificacoes_ativas TINYINT(1)   DEFAULT 1,
    -- 1 = ativado, 0 = desativado
    filtros_salvos      JSON,
    -- Guarda os filtros escolhidos pelo usuário, ex:
    -- {"partido":"PT","uf":"SP","temas":["saude","educacao"]}
    criado_em           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pref_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        -- Se o usuário for deletado, as preferências somem junto
);

-- ============================================================
-- BLOCO 2 — TABELAS DAS APIs PÚBLICAS
-- (candidatos é a tabela-mãe — deve vir antes das filhas)
-- ============================================================

CREATE TABLE IF NOT EXISTS candidatos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(200)  NOT NULL,
    partido       VARCHAR(50),
    uf            CHAR(2),
    cargo         VARCHAR(100),
    cpf           VARCHAR(14),
    -- VARCHAR porque CPF tem pontos e traço: 123.456.789-00
    -- e zeros à esquerda precisam ser preservados
    cnpj_empresa  VARCHAR(18),
    genero        VARCHAR(20),
    -- campo para o filtro de gênero do usuário
    raca_cor      VARCHAR(50),
    -- campo para o filtro de cor/raça do usuário
    -- ambos vêm do CSV do TSE
    fonte         VARCHAR(50)   DEFAULT 'TSE',
    atualizado_em TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    -- ON UPDATE → atualiza automaticamente quando o registro muda

    CONSTRAINT uq_cpf UNIQUE (cpf)
    -- CPF é único: garante que o mesmo candidato não entra duas vezes
);

CREATE TABLE IF NOT EXISTS bens_declarados (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_candidato  INT            NOT NULL,
    descricao     TEXT,
    valor         DECIMAL(15,2),
    -- DECIMAL(15,2): até 15 dígitos, 2 casas decimais — ex: 1.250.000,50
    ano_eleicao   YEAR,

    CONSTRAINT fk_bens_candidato
        FOREIGN KEY (id_candidato)
        REFERENCES candidatos(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS emendas_parlamentares (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_candidato  INT            NOT NULL,
    descricao     TEXT,
    valor         DECIMAL(15,2),
    ano           YEAR,
    area          VARCHAR(100),
    -- ex: 'saude', 'educacao', 'indigena', 'lgbt'
    -- esse campo conecta com os filtros de tema do usuário

    CONSTRAINT fk_emendas_candidato
        FOREIGN KEY (id_candidato)
        REFERENCES candidatos(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contratos_publicos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_candidato  INT            NOT NULL,
    cnpj_empresa  VARCHAR(18),
    nome_empresa  VARCHAR(200),
    objeto        TEXT,
    -- "objeto" é o termo técnico para a descrição do contrato
    valor         DECIMAL(15,2),
    ano           YEAR,

    CONSTRAINT fk_contratos_candidato
        FOREIGN KEY (id_candidato)
        REFERENCES candidatos(id)
        ON DELETE CASCADE
);

-- ============================================================
-- BLOCO 3 — LOG DE SINCRONIZAÇÃO
-- (criado por último porque referencia as duas partes acima)
-- ============================================================

CREATE TABLE IF NOT EXISTS log_atualizacoes (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    tabela_atualizada VARCHAR(100),
    -- qual tabela ou endpoint foi atualizado
    api_origem        VARCHAR(100),
    -- ex: 'TSE_CSV', 'PortalTransparencia'
    atualizado_em     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    status            ENUM('sucesso', 'erro'),
    -- ENUM: só aceita exatamente esses dois valores
    total_registros   INT,
    mensagem_erro     TEXT
    -- fica NULL quando status = 'sucesso'
);

-- ============================================================
-- BLOCO 4 — DADOS DE TESTE
-- Estes dados simulam o que viria das APIs e CSVs
-- Use para testar sem precisar chamar nenhuma API ainda
-- ============================================================

-- Usuário de teste
-- A senha abaixo é o hash de "senha123" gerado pelo PHP password_hash()
-- Para gerar outro: echo password_hash('sua_senha', PASSWORD_DEFAULT);
INSERT INTO usuarios (nome_usuario, senha_hash, data_nascimento, email)
VALUES (
    'usuario_teste',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '1995-08-14',
    'teste@email.com'
);

-- Preferências do usuário de teste
INSERT INTO preferencias_usuario (id_usuario, notificacoes_ativas, filtros_salvos)
VALUES (
    1,
    1,
    '{"partido": "PT", "uf": "SP", "temas": ["saude", "educacao"]}'
    -- JSON válido: aspas duplas dentro, aspas simples fora
);

-- Candidatos de teste (os que estão no escopo do TCC)
INSERT INTO candidatos (nome, partido, uf, cargo, cpf, genero, raca_cor, fonte)
VALUES
    ('LUIZ INÁCIO LULA DA SILVA', 'PT',           'SP', 'Presidente',          '000.000.000-00', 'Masculino', 'Parda',  'TSE'),
    ('FLAVIO BOLSONARO',           'PL',           'RJ', 'Senador',             '111.111.111-11', 'Masculino', 'Branca', 'TSE'),
    ('FERNANDO HADDAD',            'PT',           'SP', 'Governador',          '222.222.222-22', 'Masculino', 'Branca', 'TSE'),
    ('TARCÍSIO DE FREITAS',        'Republicanos', 'SP', 'Governador',          '333.333.333-33', 'Masculino', 'Branca', 'TSE'),
    ('MICHELLE BOLSONARO',         'PL',           'DF', 'Primeira-dama',       '444.444.444-44', 'Feminino',  'Branca', 'TSE'),
    ('ROMEU ZEMA',                 'Novo',         'MG', 'Governador',          '555.555.555-55', 'Masculino', 'Branca', 'TSE');
-- ATENÇÃO: CPFs acima são fictícios para teste
-- Os CPFs reais estão nos CSVs do TSE — substitua antes de ir para produção

-- Bens declarados de teste
INSERT INTO bens_declarados (id_candidato, descricao, valor, ano_eleicao)
VALUES
    (1, 'Imóvel residencial - São Paulo/SP',       850000.00, 2022),
    (1, 'Veículo automotor',                        45000.00, 2022),
    (2, 'Imóvel residencial - Rio de Janeiro/RJ', 1200000.00, 2022),
    (3, 'Imóvel residencial - São Paulo/SP',        700000.00, 2022),
    (4, 'Empresa de engenharia - participação',    2300000.00, 2022),
    (5, 'Joias e objetos de arte',                  90000.00, 2022),
    (6, 'Imóvel comercial - Belo Horizonte/MG',    980000.00, 2022);

-- Emendas parlamentares de teste
INSERT INTO emendas_parlamentares (id_candidato, descricao, valor, ano, area)
VALUES
    (2, 'Aquisição de equipamentos para UBS',        500000.00, 2022, 'saude'),
    (2, 'Reforma de escola municipal',               350000.00, 2021, 'educacao'),
    (2, 'Construção de posto policial',              200000.00, 2021, 'seguranca'),
    (3, 'Programa de merenda escolar',               780000.00, 2022, 'educacao'),
    (4, 'Pavimentação de rodovia estadual',         1500000.00, 2022, 'infraestrutura'),
    (6, 'Centro de atendimento a povos indígenas',   420000.00, 2022, 'indigena');

-- Contratos públicos de teste
INSERT INTO contratos_publicos (id_candidato, cnpj_empresa, nome_empresa, objeto, valor, ano)
VALUES
    (4, '12.345.678/0001-99', 'Freitas Engenharia Ltda', 'Obra de infraestrutura viária', 3200000.00, 2021),
    (6, '98.765.432/0001-11', 'Zema Distribuidora S/A',  'Fornecimento de combustíveis',   870000.00, 2020);

-- Log de sincronização de teste
-- Simula uma sincronização bem-sucedida feita há 2 dias
INSERT INTO log_atualizacoes (tabela_atualizada, api_origem, status, total_registros, atualizado_em)
VALUES
    ('tse_candidatos',       'TSE_CSV',             'sucesso', 6,  NOW() - INTERVAL 2 DAY),
    ('tse_bens',             'TSE_CSV',             'sucesso', 7,  NOW() - INTERVAL 2 DAY),
    ('emendas',              'PortalTransparencia', 'sucesso', 6,  NOW() - INTERVAL 1 DAY),
    ('contratos_publicos',   'PortalTransparencia', 'sucesso', 2,  NOW() - INTERVAL 1 DAY);

-- ============================================================
-- BLOCO 5 — QUERIES DE VERIFICAÇÃO
-- Rode estas consultas separadamente para confirmar que tudo
-- foi criado e populado corretamente
-- ============================================================

-- Ver todos os candidatos
-- SELECT * FROM candidatos;

-- Ver candidatos com seus bens (JOIN entre tabelas)
-- SELECT c.nome, c.partido, b.descricao, b.valor
-- FROM candidatos c
-- JOIN bens_declarados b ON b.id_candidato = c.id
-- ORDER BY b.valor DESC;

-- Simular o filtro por tema (como o usuário vai usar)
-- SELECT DISTINCT c.nome, c.partido, e.area, SUM(e.valor) as total_emendas
-- FROM candidatos c
-- JOIN emendas_parlamentares e ON e.id_candidato = c.id
-- WHERE e.area = 'saude'
-- GROUP BY c.id, e.area;

-- Ver o log de sincronizações
-- SELECT * FROM log_atualizacoes ORDER BY atualizado_em DESC;
