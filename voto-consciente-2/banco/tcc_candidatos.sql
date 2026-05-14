CREATE DATABASE IF NOT EXISTS tcc_candidatos
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_general_ci;


USE tcc_candidatos;

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nome_usuario     VARCHAR(100)  NOT NULL,
    senha_hash       VARCHAR(255)  NOT NULL,
    data_nascimento  DATE,
    email            VARCHAR(150),
    criado_em        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_usuario UNIQUE (nome_usuario),
    CONSTRAINT uq_email   UNIQUE (email)
);

CREATE TABLE IF NOT EXISTS preferencias_usuario (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario          INT          NOT NULL,
    notificacoes_ativas TINYINT(1)   DEFAULT 1,
    filtros_salvos      JSON,
    criado_em           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pref_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS candidatos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(200)  NOT NULL,
    partido       VARCHAR(50),
    uf            CHAR(2),
    cargo         VARCHAR(100),
    cpf           VARCHAR(14),
    cnpj_empresa  VARCHAR(18),
    genero        VARCHAR(20),
    raca_cor      VARCHAR(50),
    fonte         VARCHAR(50)   DEFAULT 'TSE',
    atualizado_em TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_cpf UNIQUE (cpf)
);

CREATE TABLE IF NOT EXISTS bens_declarados (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_candidato  INT            NOT NULL,
    descricao     TEXT,
    valor         DECIMAL(15,2),
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
    valor         DECIMAL(15,2),
    ano           YEAR,

    CONSTRAINT fk_contratos_candidato
        FOREIGN KEY (id_candidato)
        REFERENCES candidatos(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS log_atualizacoes (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    tabela_atualizada VARCHAR(100),
    api_origem        VARCHAR(100),
    atualizado_em     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    status            ENUM('sucesso', 'erro'),
    total_registros   INT,
    mensagem_erro     TEXT
);

INSERT INTO usuarios (nome_usuario, senha_hash, data_nascimento, email)
VALUES (
    'usuario_teste',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '1995-08-14',
    'teste@email.com'
);

INSERT INTO preferencias_usuario (id_usuario, notificacoes_ativas, filtros_salvos)
VALUES (
    1,
    1,
    '{"partido": "PT", "uf": "SP", "temas": ["saude", "educacao"]}'
);

INSERT INTO candidatos (nome, partido, uf, cargo, cpf, genero, raca_cor, fonte)
VALUES
    ('LUIZ INÁCIO LULA DA SILVA', 'PT',           'SP', 'Presidente',          '000.000.000-00', 'Masculino', 'Parda',  'TSE'),
    ('FLAVIO BOLSONARO',           'PL',           'RJ', 'Senador',             '111.111.111-11', 'Masculino', 'Branca', 'TSE'),
    ('FERNANDO HADDAD',            'PT',           'SP', 'Governador',          '222.222.222-22', 'Masculino', 'Branca', 'TSE'),
    ('TARCÍSIO DE FREITAS',        'Republicanos', 'SP', 'Governador',          '333.333.333-33', 'Masculino', 'Branca', 'TSE'),
    ('MICHELLE BOLSONARO',         'PL',           'DF', 'Primeira-dama',       '444.444.444-44', 'Feminino',  'Branca', 'TSE'),
    ('ROMEU ZEMA',                 'Novo',         'MG', 'Governador',          '555.555.555-55', 'Masculino', 'Branca', 'TSE');

INSERT INTO bens_declarados (id_candidato, descricao, valor, ano_eleicao)
VALUES
    (1, 'Imóvel residencial - São Paulo/SP',       850000.00, 2022),
    (1, 'Veículo automotor',                        45000.00, 2022),
    (2, 'Imóvel residencial - Rio de Janeiro/RJ', 1200000.00, 2022),
    (3, 'Imóvel residencial - São Paulo/SP',        700000.00, 2022),
    (4, 'Empresa de engenharia - participação',    2300000.00, 2022),
    (5, 'Joias e objetos de arte',                  90000.00, 2022),
    (6, 'Imóvel comercial - Belo Horizonte/MG',    980000.00, 2022);

INSERT INTO emendas_parlamentares (id_candidato, descricao, valor, ano, area)
VALUES
    (2, 'Aquisição de equipamentos para UBS',        500000.00, 2022, 'saude'),
    (2, 'Reforma de escola municipal',               350000.00, 2021, 'educacao'),
    (2, 'Construção de posto policial',              200000.00, 2021, 'seguranca'),
    (3, 'Programa de merenda escolar',               780000.00, 2022, 'educacao'),
    (4, 'Pavimentação de rodovia estadual',         1500000.00, 2022, 'infraestrutura'),
    (6, 'Centro de atendimento a povos indígenas',   420000.00, 2022, 'indigena');

INSERT INTO contratos_publicos (id_candidato, cnpj_empresa, nome_empresa, objeto, valor, ano)
VALUES
    (4, '12.345.678/0001-99', 'Freitas Engenharia Ltda', 'Obra de infraestrutura viária', 3200000.00, 2021),
    (6, '98.765.432/0001-11', 'Zema Distribuidora S/A',  'Fornecimento de combustíveis',   870000.00, 2020);

INSERT INTO log_atualizacoes (tabela_atualizada, api_origem, status, total_registros, atualizado_em)
VALUES
    ('tse_candidatos',       'TSE_CSV',             'sucesso', 6,  NOW() - INTERVAL 2 DAY),
    ('tse_bens',             'TSE_CSV',             'sucesso', 7,  NOW() - INTERVAL 2 DAY),
    ('emendas',              'PortalTransparencia', 'sucesso', 6,  NOW() - INTERVAL 1 DAY),
    ('contratos_publicos',   'PortalTransparencia', 'sucesso', 2,  NOW() - INTERVAL 1 DAY);
