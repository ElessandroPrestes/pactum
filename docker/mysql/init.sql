-- Cria o banco de testes ao inicializar o container do MySQL.
-- Executado apenas na primeira inicializacao (volume de dados vazio).

CREATE DATABASE IF NOT EXISTS pactum_testing
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON pactum_testing.* TO 'pactum'@'%';
FLUSH PRIVILEGES;
