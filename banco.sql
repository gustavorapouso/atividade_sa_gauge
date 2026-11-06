-- =====================================================================
-- Banco de dados do Ferrorama Gauge
-- Compatível com MySQL 8+ e MariaDB 10.4+
--
-- ATENÇÃO: este script apaga e recria o banco `ferrorama_gauge`.
-- =====================================================================

DROP DATABASE IF EXISTS ferrorama_gauge;
CREATE DATABASE ferrorama_gauge
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_general_ci;

USE ferrorama_gauge;

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Usuários
-- ---------------------------------------------------------------------

-- Perfis de acesso: 1 = cliente, 2 = gestor, 3 = maquinista
CREATE TABLE perfil (
    id_perfil        INT          NOT NULL AUTO_INCREMENT,
    nome_perfil      VARCHAR(45)  NOT NULL,
    descricao_perfil VARCHAR(45)  NOT NULL,
    PRIMARY KEY (id_perfil)
) ENGINE=InnoDB;

-- Data de nascimento e telefone são opcionais no cadastro e na edição
CREATE TABLE pessoa (
    id_pessoa       INT          NOT NULL AUTO_INCREMENT,
    nome            VARCHAR(45)  NOT NULL,
    data_nascimento DATE         NULL,
    email           VARCHAR(45)  NOT NULL,
    telefone        VARCHAR(20)  NULL,
    senha           VARCHAR(255) NOT NULL,
    localizacao     VARCHAR(45)  NULL,
    FK_id_perfil    INT          NOT NULL,
    ativo           TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id_pessoa),
    UNIQUE KEY uq_pessoa_email (email),
    KEY ix_pessoa_perfil (FK_id_perfil),
    CONSTRAINT fk_pessoa_perfil FOREIGN KEY (FK_id_perfil) REFERENCES perfil (id_perfil)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Trens e trilhos
-- ---------------------------------------------------------------------

-- quantidade_assentos é usado só por trens de passageiros
-- e tipo_carga só por trens de carga
CREATE TABLE trem (
    id_trem             INT                         NOT NULL AUTO_INCREMENT,
    prefixo             VARCHAR(20)                 NOT NULL,
    modelo              VARCHAR(45)                 NOT NULL,
    ano                 INT                         NOT NULL,
    status              VARCHAR(20)                 NOT NULL DEFAULT 'ativo', -- ativo, manutencao, inativo
    capacidade          DECIMAL(10,2)               NOT NULL DEFAULT 0,
    ultima_inspecao     DATE                        NULL,
    tipo_trem           ENUM('Passageiro', 'Carga') NOT NULL DEFAULT 'Passageiro',
    quantidade_assentos INT                         NULL,
    tipo_carga          VARCHAR(45)                 NULL,
    PRIMARY KEY (id_trem),
    UNIQUE KEY uq_trem_prefixo (prefixo)
) ENGINE=InnoDB;

CREATE TABLE trilho (
    id_trilho   INT          NOT NULL AUTO_INCREMENT,
    localizacao VARCHAR(100) NOT NULL,
    distancia   FLOAT        NOT NULL,
    PRIMARY KEY (id_trilho)
) ENGINE=InnoDB;

CREATE TABLE carga (
    id_carga   INT         NOT NULL AUTO_INCREMENT,
    mercadoria VARCHAR(45) NOT NULL,
    peso       FLOAT       NOT NULL,
    FK_id_trem INT         NULL,
    PRIMARY KEY (id_carga),
    KEY ix_carga_trem (FK_id_trem),
    CONSTRAINT fk_carga_trem FOREIGN KEY (FK_id_trem) REFERENCES trem (id_trem)
) ENGINE=InnoDB;

CREATE TABLE vagao_passageiro (
    id_passageiro INT NOT NULL AUTO_INCREMENT,
    assentos      INT NOT NULL,
    FK_id_trem    INT NULL,
    PRIMARY KEY (id_passageiro),
    KEY ix_vagao_trem (FK_id_trem),
    CONSTRAINT fk_vagao_trem FOREIGN KEY (FK_id_trem) REFERENCES trem (id_trem)
) ENGINE=InnoDB;

-- status_trem: programada, em andamento, concluida, cancelada
CREATE TABLE viagem (
    id_viagem           INT         NOT NULL AUTO_INCREMENT,
    estacao_trem        VARCHAR(45) NOT NULL,
    data_hora_chegada   DATETIME    NOT NULL,
    data_hora_saida     DATETIME    NOT NULL,
    status_trem         VARCHAR(20) NOT NULL,
    origem              VARCHAR(45) NOT NULL,
    destino             VARCHAR(45) NOT NULL,
    velocidade_km_h     FLOAT       NOT NULL,
    previsao_chegada    DATETIME    NOT NULL,
    historico_descricao VARCHAR(45) NOT NULL,
    FK_id_trem          INT         NOT NULL,
    FK_id_trilho        INT         NOT NULL,
    PRIMARY KEY (id_viagem),
    KEY ix_viagem_trem (FK_id_trem),
    KEY ix_viagem_trilho (FK_id_trilho),
    KEY ix_viagem_saida (data_hora_saida),
    CONSTRAINT fk_viagem_trem   FOREIGN KEY (FK_id_trem)   REFERENCES trem (id_trem),
    CONSTRAINT fk_viagem_trilho FOREIGN KEY (FK_id_trilho) REFERENCES trilho (id_trilho)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Sensores
-- ---------------------------------------------------------------------

-- A última leitura de cada sensor é calculada a partir da tabela leituras
CREATE TABLE sensores (
    id_sensores      INT         NOT NULL AUTO_INCREMENT,
    codigo           VARCHAR(20) NOT NULL,
    nome             VARCHAR(45) NOT NULL,
    tipo             VARCHAR(45) NOT NULL,
    estacao_sensores VARCHAR(45) NOT NULL,
    segmento         VARCHAR(80) NULL,
    `data_adição`    DATETIME    NOT NULL,
    data_remocao     DATETIME    NULL,
    FK_id_trem       INT         NOT NULL,
    FK_id_trilho     INT         NOT NULL,
    PRIMARY KEY (id_sensores),
    UNIQUE KEY uq_sensores_codigo (codigo),
    KEY ix_sensores_trem (FK_id_trem),
    KEY ix_sensores_trilho (FK_id_trilho),
    CONSTRAINT fk_sensores_trem   FOREIGN KEY (FK_id_trem)   REFERENCES trem (id_trem),
    CONSTRAINT fk_sensores_trilho FOREIGN KEY (FK_id_trilho) REFERENCES trilho (id_trilho)
) ENGINE=InnoDB;

-- Leituras do simulador (usadas nos painéis, alertas e relatórios)
CREATE TABLE leituras (
    id_leitura INT           NOT NULL AUTO_INCREMENT,
    id_sensor  INT           NOT NULL,
    data_hora  DATETIME      NOT NULL,
    valor      DECIMAL(10,2) NOT NULL,
    unidade    VARCHAR(10)   NOT NULL,
    PRIMARY KEY (id_leitura),
    KEY ix_leituras_sensor (id_sensor),
    KEY ix_leituras_data (data_hora),
    CONSTRAINT fk_leituras_sensores FOREIGN KEY (id_sensor) REFERENCES sensores (id_sensores) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Alertas gerados quando uma leitura passa do limite
-- severidade: atencao, critico
CREATE TABLE alertas (
    id_alerta  INT          NOT NULL AUTO_INCREMENT,
    id_leitura INT          NOT NULL,
    tipo       VARCHAR(45)  NOT NULL,
    severidade VARCHAR(20)  NOT NULL,
    mensagem   VARCHAR(200) NOT NULL,
    data_hora  DATETIME     NOT NULL,
    PRIMARY KEY (id_alerta),
    KEY ix_alertas_leitura (id_leitura),
    KEY ix_alertas_data (data_hora),
    CONSTRAINT fk_alertas_leituras FOREIGN KEY (id_leitura) REFERENCES leituras (id_leitura) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Leituras geradas pela página "Leituras dos sensores"
CREATE TABLE dados_sensores (
    id_dados_sensores INT         NOT NULL AUTO_INCREMENT,
    data_hora         DATETIME    NOT NULL,
    valor_sensor      FLOAT       NOT NULL,
    status_sensor     VARCHAR(20) NOT NULL,
    FK_id_sensores    INT         NOT NULL,
    PRIMARY KEY (id_dados_sensores),
    KEY ix_dados_sensores_sensor (FK_id_sensores),
    CONSTRAINT fk_dados_sensores_sensores FOREIGN KEY (FK_id_sensores) REFERENCES sensores (id_sensores)
) ENGINE=InnoDB;

CREATE TABLE ocorrencias (
    id_ocorrencias    INT          NOT NULL AUTO_INCREMENT,
    data_hora         DATETIME     NOT NULL,
    localizacao       VARCHAR(45)  NOT NULL,
    descricao         VARCHAR(100) NOT NULL,
    status_ocorrencia VARCHAR(20)  NOT NULL,
    FK_id_sensores    INT          NOT NULL,
    PRIMARY KEY (id_ocorrencias),
    KEY ix_ocorrencias_sensor (FK_id_sensores),
    CONSTRAINT fk_ocorrencias_sensores FOREIGN KEY (FK_id_sensores) REFERENCES sensores (id_sensores)
) ENGINE=InnoDB;

-- =====================================================================
-- Dados iniciais
-- =====================================================================

INSERT INTO perfil (id_perfil, nome_perfil, descricao_perfil) VALUES
(1, 'cliente',    'Usuário cliente'),
(2, 'gestor',     'Usuário gestor'),
(3, 'maquinista', 'Usuário maquinista');

INSERT INTO pessoa (id_pessoa, nome, data_nascimento, email, telefone, senha, localizacao, FK_id_perfil, ativo) VALUES
(14, 'Gustavo Henrique Rapouso', '2008-06-26', 'gustavo_rapouso@estudante.sesisenai.org.br', '(47) 99150-94', '$2y$10$PQgYMMmpkqya9XeaAx7ud.cRernEtb8IMuquG9xUpclLtLD.FrxG2', NULL, 2, 1),
(15, 'Alice',                    '2009-01-16', 'alice@gmail.com',                            '47 99999999',   '$2y$10$5/xScbhcAl8FWr.wyaJ1H.ekA7bC1p1AJQnUAjhCbMSkBSbifg6rC', NULL, 3, 1),
(16, 'Sofia Musgo',              '2001-01-01', 'sofia.musgo@email.com',                      '47988556611',   '$2y$10$p1ofuFT6lHgib3YZnFYbk.E9yPUGZFfqi0YVViZLz907pG9PNxofa', NULL, 2, 1);

INSERT INTO trem (id_trem, prefixo, modelo, ano, status, capacidade, ultima_inspecao, tipo_trem, quantidade_assentos, tipo_carga) VALUES
(5, 'TR-204', 'Vectron', 1900, 'ativo', 5000.00, '2026-10-05', 'Passageiro', NULL, NULL);

INSERT INTO trilho (id_trilho, localizacao, distancia) VALUES
(6,  'Trecho Estação Central - Estação Norte',     12.5),
(7,  'Trecho Estação Norte - Estação Industrial',  18.75),
(8,  'Trecho Estação Central - Estação Sul',       15.2),
(9,  'Trecho Estação Sul - Terminal de Cargas',    22.4),
(10, 'Trecho Estação Industrial - Terminal Norte', 9.8);

INSERT INTO sensores (id_sensores, codigo, nome, tipo, estacao_sensores, segmento, `data_adição`, data_remocao, FK_id_trem, FK_id_trilho) VALUES
(11, 'TEST-TEMP-01', 'Sensor de Temperatura 01', 'Temperatura', 'Estação Central', 'Segmento A', '2026-10-20 09:25:17', NULL, 5, 6),
(12, 'TEST-TEMP-02', 'Sensor de Temperatura 02', 'Temperatura', 'Estação Norte',   'Segmento B', '2026-10-20 09:25:17', NULL, 5, 7),
(13, 'TEST-VIB-01',  'Sensor de Vibração 01',    'Vibração',    'Estação Central', 'Segmento A', '2026-10-20 09:25:17', NULL, 5, 8),
(14, 'TEST-CONS-01', 'Sensor de Consumo 01',     'Consumo',     'Estação Sul',     'Segmento C', '2026-10-20 09:25:17', NULL, 5, 9),
(15, 'TEST-VEL-01',  'Sensor de Velocidade 01',  'Velocidade',  'Estação Norte',   'Segmento B', '2026-10-20 09:25:17', NULL, 5, 10);

INSERT INTO leituras (id_leitura, id_sensor, data_hora, valor, unidade) VALUES
(1, 11, '2026-10-20 09:06:17', 25.50, '°C'),
(2, 12, '2026-10-20 09:06:17', 28.35, '°C'),
(3, 13, '2026-10-20 09:06:17', 1.38, 'mm/s'),
(4, 14, '2026-10-20 09:06:17', 13.75, 'kWh'),
(5, 15, '2026-10-20 09:06:17', 32.50, 'km/h'),
(6, 11, '2026-10-20 09:07:17', 26.00, '°C'),
(7, 12, '2026-10-20 09:07:17', 28.70, '°C'),
(8, 13, '2026-10-20 09:07:17', 1.56, 'mm/s'),
(9, 14, '2026-10-20 09:07:17', 15.00, 'kWh'),
(10, 15, '2026-10-20 09:07:17', 35.00, 'km/h'),
(11, 11, '2026-10-20 09:08:17', 26.50, '°C'),
(12, 12, '2026-10-20 09:08:17', 29.05, '°C'),
(13, 13, '2026-10-20 09:08:17', 1.74, 'mm/s'),
(14, 14, '2026-10-20 09:08:17', 16.25, 'kWh'),
(15, 15, '2026-10-20 09:08:17', 37.50, 'km/h'),
(16, 11, '2026-10-20 09:09:17', 27.00, '°C'),
(17, 12, '2026-10-20 09:09:17', 29.40, '°C'),
(18, 13, '2026-10-20 09:09:17', 1.92, 'mm/s'),
(19, 14, '2026-10-20 09:09:17', 17.50, 'kWh'),
(20, 15, '2026-10-20 09:09:17', 40.00, 'km/h'),
(21, 11, '2026-10-20 09:10:17', 27.50, '°C'),
(22, 12, '2026-10-20 09:10:17', 29.75, '°C'),
(23, 13, '2026-10-20 09:10:17', 2.10, 'mm/s'),
(24, 14, '2026-10-20 09:10:17', 18.75, 'kWh'),
(25, 15, '2026-10-20 09:10:17', 42.50, 'km/h'),
(26, 11, '2026-10-20 09:11:17', 28.00, '°C'),
(27, 12, '2026-10-20 09:11:17', 30.10, '°C'),
(28, 13, '2026-10-20 09:11:17', 2.28, 'mm/s'),
(29, 14, '2026-10-20 09:11:17', 20.00, 'kWh'),
(30, 15, '2026-10-20 09:11:17', 45.00, 'km/h'),
(31, 11, '2026-10-20 09:12:17', 28.50, '°C'),
(32, 12, '2026-10-20 09:12:17', 30.45, '°C'),
(33, 13, '2026-10-20 09:12:17', 2.46, 'mm/s'),
(34, 14, '2026-10-20 09:12:17', 21.25, 'kWh'),
(35, 15, '2026-10-20 09:12:17', 47.50, 'km/h'),
(36, 11, '2026-10-20 09:13:17', 29.00, '°C'),
(37, 12, '2026-10-20 09:13:17', 30.80, '°C'),
(38, 13, '2026-10-20 09:13:17', 2.64, 'mm/s'),
(39, 14, '2026-10-20 09:13:17', 22.50, 'kWh'),
(40, 15, '2026-10-20 09:13:17', 50.00, 'km/h'),
(41, 11, '2026-10-20 09:14:17', 29.50, '°C'),
(42, 12, '2026-10-20 09:14:17', 31.15, '°C'),
(43, 13, '2026-10-20 09:14:17', 2.82, 'mm/s'),
(44, 14, '2026-10-20 09:14:17', 23.75, 'kWh'),
(45, 15, '2026-10-20 09:14:17', 52.50, 'km/h'),
(46, 11, '2026-10-20 09:15:17', 30.00, '°C'),
(47, 12, '2026-10-20 09:15:17', 31.50, '°C'),
(48, 13, '2026-10-20 09:15:17', 3.00, 'mm/s'),
(49, 14, '2026-10-20 09:15:17', 25.00, 'kWh'),
(50, 15, '2026-10-20 09:15:17', 55.00, 'km/h'),
(51, 11, '2026-10-20 09:16:17', 30.50, '°C'),
(52, 12, '2026-10-20 09:16:17', 31.85, '°C'),
(53, 13, '2026-10-20 09:16:17', 3.18, 'mm/s'),
(54, 14, '2026-10-20 09:16:17', 26.25, 'kWh'),
(55, 15, '2026-10-20 09:16:17', 57.50, 'km/h'),
(56, 11, '2026-10-20 09:17:17', 31.00, '°C'),
(57, 12, '2026-10-20 09:17:17', 32.20, '°C'),
(58, 13, '2026-10-20 09:17:17', 3.36, 'mm/s'),
(59, 14, '2026-10-20 09:17:17', 27.50, 'kWh'),
(60, 15, '2026-10-20 09:17:17', 60.00, 'km/h'),
(61, 11, '2026-10-20 09:18:17', 31.50, '°C'),
(62, 12, '2026-10-20 09:18:17', 32.55, '°C'),
(63, 13, '2026-10-20 09:18:17', 3.54, 'mm/s'),
(64, 14, '2026-10-20 09:18:17', 28.75, 'kWh'),
(65, 15, '2026-10-20 09:18:17', 62.50, 'km/h'),
(66, 11, '2026-10-20 09:19:17', 32.00, '°C'),
(67, 12, '2026-10-20 09:19:17', 32.90, '°C'),
(68, 13, '2026-10-20 09:19:17', 3.72, 'mm/s'),
(69, 14, '2026-10-20 09:19:17', 30.00, 'kWh'),
(70, 15, '2026-10-20 09:19:17', 65.00, 'km/h'),
(71, 11, '2026-10-20 09:20:17', 32.50, '°C'),
(72, 12, '2026-10-20 09:20:17', 33.25, '°C'),
(73, 13, '2026-10-20 09:20:17', 3.90, 'mm/s'),
(74, 14, '2026-10-20 09:20:17', 31.25, 'kWh'),
(75, 15, '2026-10-20 09:20:17', 67.50, 'km/h'),
(76, 11, '2026-10-20 09:21:17', 33.00, '°C'),
(77, 12, '2026-10-20 09:21:17', 33.60, '°C'),
(78, 13, '2026-10-20 09:21:17', 4.08, 'mm/s'),
(79, 14, '2026-10-20 09:21:17', 32.50, 'kWh'),
(80, 15, '2026-10-20 09:21:17', 70.00, 'km/h'),
(81, 11, '2026-10-20 09:22:17', 33.50, '°C'),
(82, 12, '2026-10-20 09:22:17', 33.95, '°C'),
(83, 13, '2026-10-20 09:22:17', 4.26, 'mm/s'),
(84, 14, '2026-10-20 09:22:17', 33.75, 'kWh'),
(85, 15, '2026-10-20 09:22:17', 72.50, 'km/h'),
(86, 11, '2026-10-20 09:23:17', 34.00, '°C'),
(87, 12, '2026-10-20 09:23:17', 34.30, '°C'),
(88, 13, '2026-10-20 09:23:17', 4.44, 'mm/s'),
(89, 14, '2026-10-20 09:23:17', 35.00, 'kWh'),
(90, 15, '2026-10-20 09:23:17', 75.00, 'km/h'),
(91, 11, '2026-10-20 09:24:17', 34.50, '°C'),
(92, 12, '2026-10-20 09:24:17', 34.65, '°C'),
(93, 13, '2026-10-20 09:24:17', 4.62, 'mm/s'),
(94, 14, '2026-10-20 09:24:17', 36.25, 'kWh'),
(95, 15, '2026-10-20 09:24:17', 77.50, 'km/h'),
(96, 11, '2026-10-20 09:25:17', 35.00, '°C'),
(97, 12, '2026-10-20 09:25:17', 35.00, '°C'),
(98, 13, '2026-10-20 09:25:17', 4.80, 'mm/s'),
(99, 14, '2026-10-20 09:25:17', 37.50, 'kWh'),
(100, 15, '2026-10-20 09:25:17', 80.00, 'km/h');
