CREATE DATABASE barelyknow;
USE barelyknow;
DROP DATABASE barelyknow;

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
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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

SELECT * FROM categorias;
SELECT * FROM jogadores;
SELECT * FROM perguntas;
SELECT * FROM respostas;
SELECT * FROM salas;

INSERT INTO categorias (nome_categoria) VALUES 
('Esportes'),
('Conhecimentos Gerais'), 
('Tecnologia'),
('Cultura Pop'),
('Cinema'),
('Animação');

ALTER TABLE salas ADD COLUMN rodada_atual INT DEFAULT 0;
ALTER TABLE salas ADD COLUMN id_pergunta_atual INT DEFAULT NULL;
ALTER TABLE salas ADD COLUMN tempo_inicio_pergunta TIMESTAMP NULL;
ALTER TABLE salas ADD COLUMN alternativas_ordem TEXT NULL;


INSERT INTO perguntas (id_categoria, pergunta, alternativa1, alternativa2, alternativa3, alternativa4) VALUES
(1, 'Quantos jogadores formam um time de futebol em campo?', '11 jogadores', '10 jogadores', '9 jogadores', '12 jogadores'),
(1, 'Qual país ganhou mais Copas do Mundo?', 'Brasil', 'Alemanha', 'Itália', 'Argentina'),
(1, 'Quantos pontos vale um touchdown no futebol americano?', '6 pontos', '7 pontos', '3 pontos', '2 pontos'),
(1, 'Qual esporte é jogado em uma quadra de 28x15m?', 'Basquete', 'Vôlei', 'Tênis', 'Handebol'),
(1, 'Quantos sets são necessários para vencer no vôlei?', '3 sets', '2 sets', '4 sets', '5 sets'),
(1, 'Qual nadador tem mais medalhas olímpicas?', 'Michael Phelps', 'Mark Spitz', 'Ian Thorpe', 'César Cielo'),
(1, 'Quantos buracos tem um campo de golfe padrão?', '18 buracos', '9 buracos', '12 buracos', '15 buracos'),
(1, 'Qual esporte usa uma "bola oval"?', 'Rugby', 'Futebol', 'Basquete', 'Vôlei'),
(1, 'Quantos jogadores tem um time de beisebol?', '9 jogadores', '7 jogadores', '10 jogadores', '8 jogadores'),
(1, 'Qual país inventou o judô?', 'Japão', 'China', 'Coreia', 'Brasil'),
(1, 'Quantos metros tem uma maratona?', '42.195 metros', '40.000 metros', '45.000 metros', '38.000 metros'),
(1, 'Qual esporte é conhecido como "xadrez humano"?', 'Futebol Americano', 'Basquete', 'Tênis', 'Natação'),
(1, 'Quantos rounds tem uma luta de boxe profissional?', '12 rounds', '10 rounds', '15 rounds', '8 rounds'),
(1, 'Qual atleta é chamado de "Raio"?', 'Usain Bolt', 'Michael Johnson', 'Carl Lewis', 'Asafa Powell'),
(1, 'Quantos jogadores tem um time de hóquei no gelo?', '6 jogadores', '5 jogadores', '7 jogadores', '8 jogadores'),
(1, 'Qual esporte usa um "birdie"?', 'Badminton', 'Tênis', 'Vôlei', 'Futebol'),
(1, 'Quantos Grand Slams existem no tênis?', '4 torneios', '3 torneios', '5 torneios', '6 torneios'),
(1, 'Qual país sediou as Olimpíadas de 2016?', 'Brasil', 'China', 'Inglaterra', 'Estados Unidos'),
(1, 'Quantos jogadores tem um time de futsal?', '5 jogadores', '6 jogadores', '4 jogadores', '7 jogadores'),
(1, 'Qual esporte é jogado em um "ringue"?', 'Boxe', 'Judô', 'Luta Livre', 'Ginástica'),

(2, 'Qual é a capital do Brasil?', 'Brasília', 'São Paulo', 'Rio de Janeiro', 'Belo Horizonte'),
(2, 'Quantos continentes existem?', '7 continentes', '5 continentes', '6 continentes', '8 continentes'),
(2, 'Qual é o maior oceano do mundo?', 'Oceano Pacífico', 'Oceano Atlântico', 'Oceano Índico', 'Oceano Ártico'),
(2, 'Quem pintou a Mona Lisa?', 'Leonardo da Vinci', 'Vincent van Gogh', 'Pablo Picasso', 'Michelangelo'),
(2, 'Qual é o elemento químico representado por "O"?', 'Oxigênio', 'Ouro', 'Ósmio', 'Oganésson'),
(2, 'Quantos lados tem um hexágono?', '6 lados', '5 lados', '7 lados', '8 lados'),
(2, 'Qual é o planeta conhecido como "Planeta Vermelho"?', 'Marte', 'Vênus', 'Júpiter', 'Saturno'),
(2, 'Quem escreveu "Dom Casmurro"?', 'Machado de Assis', 'José de Alencar', 'Lima Barreto', 'Carlos Drummond'),
(2, 'Qual é a moeda do Japão?', 'Iene', 'Won', 'Yuan', 'Dólar'),
(2, 'Quantos ossos tem o corpo humano?', '206 ossos', '200 ossos', '210 ossos', '195 ossos'),
(2, 'Qual é o maior animal terrestre?', 'Elefante africano', 'Girafa', 'Hipopótamo', 'Rinoceronte'),
(2, 'Quem descobriu a penicilina?', 'Alexander Fleming', 'Louis Pasteur', 'Marie Curie', 'Albert Einstein'),
(2, 'Qual é a capital da França?', 'Paris', 'Londres', 'Roma', 'Berlim'),
(2, 'Quantos anos tem um século?', '100 anos', '50 anos', '200 anos', '1000 anos'),
(2, 'Qual é o rio mais longo do mundo?', 'Rio Amazonas', 'Rio Nilo', 'Rio Mississippi', 'Rio Yangtzé'),
(2, 'Quem foi o primeiro homem na Lua?', 'Neil Armstrong', 'Buzz Aldrin', 'Yuri Gagarin', 'John Glenn'),
(2, 'Qual é o país mais populoso do mundo?', 'China', 'Índia', 'Estados Unidos', 'Indonésia'),
(2, 'Quantos planetas existem no sistema solar?', '8 planetas', '9 planetas', '7 planetas', '10 planetas'),
(2, 'Qual é a língua mais falada no mundo?', 'Mandarim', 'Inglês', 'Espanhol', 'Hindi'),
(2, 'Quem escreveu "Romeu e Julieta"?', 'William Shakespeare', 'Charles Dickens', 'Jane Austen', 'Victor Hugo'),

(3, 'O que significa HTML?', 'HyperText Markup Language', 'HighTech Modern Language', 'Hyper Transfer Markup Language', 'HighText Machine Language'),
(3, 'Qual empresa criou o Windows?', 'Microsoft', 'Apple', 'Google', 'IBM'),
(3, 'O que significa "CPU"?', 'Central Processing Unit', 'Computer Processing Unit', 'Central Program Unit', 'Computer Program Utility'),
(3, 'Qual destes NÃO é um sistema operacional?', 'Google Chrome', 'Windows', 'Linux', 'macOS'),
(3, 'Quantos bits tem um byte?', '8 bits', '4 bits', '16 bits', '32 bits'),
(3, 'Qual empresa criou o iPhone?', 'Apple', 'Samsung', 'Google', 'Microsoft'),
(3, 'O que significa "URL"?', 'Uniform Resource Locator', 'Universal Resource Link', 'Uniform Reference Location', 'Universal Reference Locator'),
(3, 'Qual destes é um navegador web?', 'Google Chrome', 'Microsoft Word', 'Adobe Photoshop', 'Windows Media Player'),
(3, 'O que significa "PDF"?', 'Portable Document Format', 'Personal Data File', 'Public Document Form', 'Printed Document File'),
(3, 'Qual empresa criou o Android?', 'Google', 'Apple', 'Microsoft', 'Samsung'),
(3, 'O que significa "Wi-Fi"?', 'Wireless Fidelity', 'Wireless Frequency', 'Wide Frequency', 'Wireless Internet'),
(3, 'Qual destes NÃO é uma rede social?', 'Microsoft Excel', 'Facebook', 'Instagram', 'Twitter'),
(3, 'O que significa "RAM"?', 'Random Access Memory', 'Read Access Memory', 'Random Available Memory', 'Read Available Memory'),
(3, 'Qual empresa criou o Photoshop?', 'Adobe', 'Microsoft', 'Apple', 'Corel'),
(3, 'O que significa "HTTP"?', 'HyperText Transfer Protocol', 'HighTech Transfer Protocol', 'Hyper Transfer Text Protocol', 'High Transfer Text Protocol'),
(3, 'Qual destes é um antivírus?', 'Avast', 'Google Drive', 'Microsoft Word', 'Adobe Reader'),
(3, 'O que significa "SSD"?', 'Solid State Drive', 'Super Speed Disk', 'System Storage Device', 'Solid Storage Drive'),
(3, 'Qual empresa criou a linguagem Java?', 'Sun Microsystems', 'Microsoft', 'Google', 'Apple'),
(3, 'O que significa "VPN"?', 'Virtual Private Network', 'Virtual Public Network', 'Verified Private Network', 'Virtual Protocol Network'),
(3, 'Qual destes é um banco de dados?', 'MySQL', 'Java', 'Python', 'HTML'),

(4, 'Quem canta a música "Shake It Off"?', 'Taylor Swift', 'Ariana Grande', 'Billie Eilish', 'Lady Gaga'),
(4, 'Qual série tem a casa "Stark" e "Lannister"?', 'Game of Thrones', 'Stranger Things', 'The Walking Dead', 'Breaking Bad'),
(4, 'Quem é o criador da Marvel?', 'Stan Lee', 'Jack Kirby', 'Steve Ditko', 'Walt Disney'),
(4, 'Qual cantor é conhecido como "Rei do Pop"?', 'Michael Jackson', 'Elvis Presley', 'Prince', 'Justin Bieber'),
(4, 'Qual banda britânica é conhecida como "Fab Four"?', 'The Beatles', 'Rolling Stones', 'Queen', 'Led Zeppelin'),
(4, 'Quem interpreta o Homem de Ferro no MCU?', 'Robert Downey Jr.', 'Chris Evans', 'Chris Hemsworth', 'Mark Ruffalo'),
(4, 'Qual é o nome real da Lady Gaga?', 'Stefani Germanotta', 'Madonna Ciccone', 'Beyoncé Knowles', 'Katy Perry'),
(4, 'Qual série tem os personagens "Eleven" e "Mike"?', 'Stranger Things', 'Riverdale', 'Outer Banks', 'The 100'),
(4, 'Quem canta "Bohemian Rhapsody"?', 'Queen', 'The Beatles', 'Rolling Stones', 'Led Zeppelin'),
(4, 'Qual atriz interpretou Hermione em Harry Potter?', 'Emma Watson', 'Emma Stone', 'Jennifer Lawrence', 'Kristen Stewart'),
(4, 'Quem é o criador do Facebook?', 'Mark Zuckerberg', 'Bill Gates', 'Steve Jobs', 'Elon Musk'),
(4, 'Qual cantora é conhecida como "Rainha do Pop"?', 'Madonna', 'Britney Spears', 'Rihanna', 'Beyoncé'),
(4, 'Qual série tem "Walter White" e "Jesse Pinkman"?', 'Breaking Bad', 'Better Call Saul', 'The Sopranos', 'Narcos'),
(4, 'Quem canta "Bad Guy"?', 'Billie Eilish', 'Ariana Grande', 'Dua Lipa', 'Olivia Rodrigo'),
(4, 'Qual é o nome do mascote da Nintendo?', 'Mario', 'Sonic', 'Pikachu', 'Crash Bandicoot'),
(4, 'Quem interpreta o Coringa no filme de 2019?', 'Joaquin Phoenix', 'Heath Ledger', 'Jared Leto', 'Jack Nicholson'),
(4, 'Qual banda tem o álbum "The Dark Side of the Moon"?', 'Pink Floyd', 'The Beatles', 'Led Zeppelin', 'Queen'),
(4, 'Quem é conhecido como "Beyhive"?', 'Fãs da Beyoncé', 'Fãs da Taylor Swift', 'Fãs da Rihanna', 'Fãs da Lady Gaga'),
(4, 'Qual série tem "Sherlock Holmes" moderno?', 'Sherlock', 'Elementary', 'Mentalist', 'Castle'),
(4, 'Quem canta "Blinding Lights"?', 'The Weeknd', 'Drake', 'Post Malone', 'Ed Sheeran'),

(5, 'Qual dos seguintes filmes foi protagonizado pelo Charlie Chaplin?', 'Tempos Modernos', 'Star Wars', 'Toy Story', 'Frozen'),
(5, 'Quem dirigiu "Titanic"?', 'James Cameron', 'Steven Spielberg', 'Christopher Nolan', 'Quentin Tarantino'),
(5, 'Qual filme ganhou o Oscar de Melhor Filme em 2020?', 'Parasita', '1917', 'Joker', 'Once Upon a Time in Hollywood'),
(5, 'Quem interpreta Jack Sparrow?', 'Johnny Depp', 'Orlando Bloom', 'Leonardo DiCaprio', 'Brad Pitt'),
(5, 'Qual filme tem a frase "May the Force be with you"?', 'Star Wars', 'Star Trek', 'Guardians of the Galaxy', 'The Matrix'),
(5, 'Quem dirigiu "O Poderoso Chefão"?', 'Francis Ford Coppola', 'Martin Scorsese', 'Alfred Hitchcock', 'Stanley Kubrick'),
(5, 'Qual ator interpreta James Bond em "007 - Cassino Royale"?', 'Daniel Craig', 'Pierce Brosnan', 'Sean Connery', 'Roger Moore'),
(5, 'Qual filme tem um personagem chamado "Forrest Gump"?', 'Forrest Gump', 'Rain Man', 'The Curious Case of Benjamin Button', 'Big'),
(5, 'Quem interpreta a Rainha Elizabeth em "The Crown"?', 'Claire Foy', 'Emma Corrin', 'Olivia Colman', 'Helena Bonham Carter'),
(5, 'Qual filme ganhou 11 Oscars?', 'Titanic', 'Ben-Hur', 'The Lord of the Rings', 'La La Land'),
(5, 'Quem é o diretor de "Interestelar"?', 'Christopher Nolan', 'Steven Spielberg', 'James Cameron', 'Ridley Scott'),
(5, 'Qual filme tem os personagens "Mufasa" e "Simba"?', 'O Rei Leão', 'Aladdin', 'A Bela e a Fera', 'Mulan'),
(5, 'Quem interpreta Tony Stark no MCU?', 'Robert Downey Jr.', 'Chris Evans', 'Chris Hemsworth', 'Mark Ruffalo'),
(5, 'Qual filme tem a frase "I will be back"?', 'O Exterminador do Futuro', 'Duro de Matar', 'Rambo', 'Mad Max'),
(5, 'Quem dirigiu "Cidade de Deus"?', 'Fernando Meirelles', 'José Padilha', 'Walter Salles', 'Breno Silveira'),
(5, 'Qual filme tem o personagem "Vito Corleone"?', 'O Poderoso Chefão', 'Scarface', 'Os Intocáveis', 'Casino'),
(5, 'Quem interpreta Neo em "The Matrix"?', 'Keanu Reeves', 'Laurence Fishburne', 'Hugo Weaving', 'Carrie-Anne Moss'),
(5, 'Qual filme ganhou o primeiro Oscar de Melhor Filme?', 'Wings', 'Sunrise', 'The Jazz Singer', 'Metropolis'),
(5, 'Quem é conhecido como "Rei do Terror" no cinema?', 'Stephen King', 'Alfred Hitchcock', 'Wes Craven', 'John Carpenter'),
(5, 'Qual filme tem a música "My Heart Will Go On"?', 'Titanic', 'Ghost', 'The Bodyguard', 'Dirty Dancing'),

(6, 'Qual é o irmão do Gumball?', 'Darwin', 'Jake', 'Elsa', 'Bob Esponja'),
(6, 'Qual estúdio criou "Toy Story"?', 'Pixar', 'Disney', 'DreamWorks', 'Studio Ghibli'),
(6, 'Qual é o nome do peixe em "Procurando Nemo"?', 'Nemo', 'Dory', 'Marlin', 'Gill'),
(6, 'Quem é o vilão em "A Pequena Sereia"?', 'Úrsula', 'Malévola', 'Jafar', 'Scar'),
(6, 'Qual é o nome do dragão em "Como Treinar Seu Dragão"?', 'Banguela', 'Solsitício', 'Fúria da Noite', 'Vermelho'),
(6, 'Qual personagem diz "Até mais, e obrigado pelos peixes!"?', 'Dory', 'Nemo', 'Marlin', 'Pearl'),
(6, 'Qual estúdio criou "A Viagem de Chihiro"?', 'Studio Ghibli', 'Pixar', 'Disney', 'DreamWorks'),
(6, 'Qual é o nome real do Homem-Aranha nos filmes da Marvel?', 'Peter Parker', 'Miles Morales', 'Tony Stark', 'Steve Rogers'),
(6, 'Qual personagem vive em um abacaxi no fundo do mar?', 'Bob Esponja', 'Patrick', 'Lula Molusco', 'Senhor Siriguejo'),
(6, 'Qual é o nome do rato na Disney?', 'Mickey Mouse', 'Jerry', 'Stuart Little', 'Remy'),
(6, 'Qual filme tem os personagens "Remy" e "Linguini"?', 'Ratatouille', 'Os Incríveis', 'Carros', 'Procurando Nemo'),
(6, 'Qual é o nome do urso na Disney?', 'Zé Colmeia', 'Poo', 'Baloo', 'Winnie the Pooh'),
(6, 'Qual personagem diz "Hakuna Matata"?', 'Timão e Pumba', 'Simba e Nala', 'Zazu', 'Rafiki'),
(6, 'Qual estúdio criou "Shrek"?', 'DreamWorks', 'Disney', 'Pixar', 'Blue Sky'),
(6, 'Qual é o nome da princesa em "Frozen"?', 'Elsa', 'Anna', 'Rapunzel', 'Merida'),
(6, 'Qual personagem tem um gancho no lugar da mão?', 'Capitão Gancho', 'Jack Sparrow', 'Barba Negra', 'Long John Silver'),
(6, 'Qual é o nome do cavalo em "Toy Story"?', 'Bulleseye', 'Pégasus', 'Maximus', 'Álamo'),
(6, 'Qual filme tem os personagens "Judy Hopps" e "Nick Wilde"?', 'Zootopia', 'Madagascar', 'A Era do Gelo', 'Kung Fu Panda'),
(6, 'Qual é o nome do panda em "Kung Fu Panda"?', 'Po', 'Shifu', 'Tigresa', 'Mestre Oogway'),
(6, 'Qual personagem é conhecido como "O Máscara"?', 'Shrek', 'Gato de Botas', 'Burro', 'Lord Farquaad');
