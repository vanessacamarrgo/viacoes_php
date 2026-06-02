SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- TABELA DE USUÁRIOS

CREATE TABLE IF NOT EXISTS usuarios
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(100) NOT NULL,
    email      VARCHAR(120) NOT NULL UNIQUE,
    senha      VARCHAR(255) NOT NULL,
    status     TINYINT(1)   NOT NULL DEFAULT 1,
    data_criacao TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP    NULL     DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,
    data_exclusao TIMESTAMP    NULL     DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- TABELA DE VIAÇÕES
CREATE TABLE IF NOT EXISTS viacoes
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(100) NOT NULL,
    url        VARCHAR(255) NOT NULL DEFAULT '',
    cidade     VARCHAR(100) NOT NULL DEFAULT '',
    logo       VARCHAR(255)          DEFAULT NULL,
    status     TINYINT(1)   NOT NULL DEFAULT 1,
    data_criacao TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP    NULL     DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,
    data_exclusao TIMESTAMP    NULL     DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- TABELA DE HISTÓRICO DE VIAÇÕES
CREATE TABLE IF NOT EXISTS historico_viacoes
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    viacao_id    INT          NOT NULL,
    usuario_id   INT          NOT NULL,
    acao         VARCHAR(50)  NOT NULL,
    dados        JSON         NOT NULL,
    data_criacao TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_viacao_id  (viacao_id),
    INDEX idx_usuario_id (usuario_id)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- Email : admin@admin.com
-- Senha : password
INSERT IGNORE INTO usuarios (nome, email, senha)
VALUES (
           'Adm',
           'admin@admin.com',
           '$2y$12$9JM0Smbuv4SeH01PQFIU/OFZfacYmQYQFdvLqvmcQR/hLOW4cgLWu'
       );
-- Email : pablo@gmail.com
-- Senha : password
INSERT IGNORE INTO usuarios (nome, email, senha)
VALUES (
           'Pablo',
           'pablo@admin.com',
           '$2y$12$9JM0Smbuv4SeH01PQFIU/OFZfacYmQYQFdvLqvmcQR/hLOW4cgLWu'
       );
-- Email : austin@gmail.com
-- Senha : password
INSERT IGNORE INTO usuarios (nome, email, senha)
VALUES (
           'Austin',
           'austin@admin.com',
           '$2y$12$9JM0Smbuv4SeH01PQFIU/OFZfacYmQYQFdvLqvmcQR/hLOW4cgLWu'
       );

-- se necessário criar outro hash use: echo password_hash('sua_senha',PASSWORD_BCRYPT);
-- VIAÇÕES
INSERT IGNORE INTO viacoes (nome, url, cidade, logo, status)
VALUES
    (
        'Reunidas Paulista',
        'https://queropassagem.com.br/reunidaspaulista.com.br',
        'São Paulo - SP',
        '8f67fcf90ead4c5d18c5075bb326f266.svg',
        1
    ),
    (
        'Catarinense',
        'https://queropassagem.com.br/catarinense.net',
        'Florianópolis - SC',
        '7637d11fc910395427cb557880247246.svg',
        1
    ),
    (
        '1001',
        'https://queropassagem.com.br/auto-viacao-1001',
        'Curitiba - PR',
        '53b0ded0529b845827081171a6b3e0a8.svg',
        1
    ),
    (
        'Cometa',
        'https://queropassagem.com.br/viacaocometa.com.br',
        'São Paulo - SP',
        'cb6a32d0830b4494359d747fe2469331.svg',
        1
    );

CREATE TABLE IF NOT EXISTS historico_alteracoes (
                                      id SERIAL PRIMARY KEY,
                                      entidade_id INT NOT NULL,            -- ID do Usuário ou da Viação
                                      entidade_tipo VARCHAR(50) NOT NULL,  -- 'Usuario' ou 'Viacao'
                                      campo_alterado VARCHAR(100) NOT NULL,
                                      valor_antigo TEXT,
                                      valor_novo TEXT,
                                      alterado_por INT,                    -- ID do usuário que fez a alteração
                                      data_alteracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
