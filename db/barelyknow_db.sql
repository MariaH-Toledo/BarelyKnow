CREATE DATABASE barelyknow;
USE barelyknow;

CREATE TABLE categorias(
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nome_categoria VARCHAR(50) UNIQUE
);

CREATE TABLE salas(
    id_sala INT AUTO_INCREMENT PRIMARY KEY,
    codigo_sala VARCHAR(6) UNIQUE,
    id_categoria INT,
    rodadas INT,
    tempo_resposta INT,
    status ENUM('aberta', 'iniciada', 'encerrada') DEFAULT 'aberta',
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
);

CREATE TABLE jogadores(
    id_jogador INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    pontos INT DEFAULT 0,
    id_sala INT,
    is_host BOOLEAN DEFAULT 0,
    FOREIGN KEY (id_sala) REFERENCES salas(id_sala) ON DELETE CASCADE
);

CREATE TABLE perguntas(
    id_pergunta INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT,
    pergunta TEXT,
    alternativa1 VARCHAR(255),
    alternativa2 VARCHAR(255),
    alternativa3 VARCHAR(255),
    alternativa4 VARCHAR(255),
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
);

CREATE TABLE respostas(
    id_resposta INT AUTO_INCREMENT PRIMARY KEY,
    id_jogador INT,
    id_pergunta INT,
    resposta_escolhida VARCHAR(255),
    correta BOOLEAN,
    tempo_resposta INT,
    FOREIGN KEY (id_jogador) REFERENCES jogadores(id_jogador),
    FOREIGN KEY (id_pergunta) REFERENCES perguntas(id_pergunta)
);

INSERT INTO categorias (nome_categoria) VALUES ('Esportes'), ('Conhecimentos Gerais'), ('Tecnologia');
SELECT * FROM categorias;
SELECT * FROM jogadores;
SELECT * FROM perguntas;
SELECT * FROM respostas;
SELECT * FROM salas;
