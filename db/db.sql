CREATE DATABASE IF NOT EXISTS ecommerce_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ecommerce_db;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO usuarios (nome, email, senha) VALUES 
('Administrador', 'admin@exemplo.com', '$2y$10$ky2VmLAoDhxJPKGt.jHFN.U0V.Y1v1BEDiYu/rcDdZrj8q1YCb7RO');

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    descricao TEXT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    estoque INT NOT NULL DEFAULT 0,
    categoria_id INT,
    imagem VARCHAR(255),
    destaque BOOLEAN DEFAULT FALSE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;

CREATE INDEX idx_produtos_nome ON produtos(nome);
CREATE INDEX idx_produtos_preco ON produtos(preco);
CREATE INDEX idx_produtos_categoria ON produtos(categoria_id);
CREATE INDEX idx_produtos_destaque ON produtos(destaque);

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    cpf VARCHAR(14) UNIQUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pendente', 'aprovado', 'enviado', 'entregue', 'cancelado') DEFAULT 'pendente',
    valor_total DECIMAL(10,2) NOT NULL,
    forma_pagamento ENUM('cartao', 'boleto', 'pix', 'transferencia') NOT NULL,
    observacoes TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB;

CREATE INDEX idx_pedidos_cliente ON pedidos(cliente_id);
CREATE INDEX idx_pedidos_data ON pedidos(data_pedido);
CREATE INDEX idx_pedidos_status ON pedidos(status);

CREATE TABLE IF NOT EXISTS itens_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;

CREATE INDEX idx_itens_pedido_pedido ON itens_pedido(pedido_id);
CREATE INDEX idx_itens_pedido_produto ON itens_pedido(produto_id);

CREATE TABLE IF NOT EXISTS log_alteracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabela VARCHAR(50) NOT NULL,
    operacao ENUM('insert', 'update', 'delete') NOT NULL,
    registro_id INT NOT NULL,
    campos_alterados TEXT,
    valor_antigo TEXT,
    valor_novo TEXT,
    usuario_id INT,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45)
) ENGINE=InnoDB;

DELIMITER //
CREATE TRIGGER trigger_log_produtos_update
AFTER UPDATE ON produtos
FOR EACH ROW
BEGIN
    DECLARE campos_alterados TEXT;
    DECLARE valor_antigo TEXT;
    DECLARE valor_novo TEXT;
    
    SET campos_alterados = '';
    SET valor_antigo = '';
    SET valor_novo = '';
    
    IF NEW.nome <> OLD.nome THEN
        SET campos_alterados = CONCAT(campos_alterados, 'nome,');
        SET valor_antigo = CONCAT(valor_antigo, OLD.nome, ';');
        SET valor_novo = CONCAT(valor_novo, NEW.nome, ';');
    END IF;
    
    IF NEW.preco <> OLD.preco THEN
        SET campos_alterados = CONCAT(campos_alterados, 'preco,');
        SET valor_antigo = CONCAT(valor_antigo, OLD.preco, ';');
        SET valor_novo = CONCAT(valor_novo, NEW.preco, ';');
    END IF;
    
    IF NEW.estoque <> OLD.estoque THEN
        SET campos_alterados = CONCAT(campos_alterados, 'estoque,');
        SET valor_antigo = CONCAT(valor_antigo, OLD.estoque, ';');
        SET valor_novo = CONCAT(valor_novo, NEW.estoque, ';');
    END IF;
    
    IF NEW.ativo <> OLD.ativo THEN
        SET campos_alterados = CONCAT(campos_alterados, 'ativo,');
        SET valor_antigo = CONCAT(valor_antigo, IF(OLD.ativo, 'sim', 'não'), ';');
        SET valor_novo = CONCAT(valor_novo, IF(NEW.ativo, 'sim', 'não'), ';');
    END IF;
    
    IF LENGTH(campos_alterados) > 0 THEN
        INSERT INTO log_alteracoes (tabela, operacao, registro_id, campos_alterados, valor_antigo, valor_novo)
        VALUES ('produtos', 'update', OLD.id, campos_alterados, valor_antigo, valor_novo);
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER trigger_log_produtos_delete
BEFORE DELETE ON produtos
FOR EACH ROW
BEGIN
    INSERT INTO log_alteracoes (tabela, operacao, registro_id, campos_alterados, valor_antigo)
    VALUES ('produtos', 'delete', OLD.id, 'exclusão completa', 
            CONCAT('id:', OLD.id, ';nome:', OLD.nome, ';preco:', OLD.preco, ';estoque:', OLD.estoque));
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE exportar_pedidos_diarios(IN data_ref DATE)
BEGIN
    DECLARE arquivo_nome VARCHAR(255);
    DECLARE conteudo_arquivo TEXT DEFAULT '';
    DECLARE cabecalho VARCHAR(500);
    DECLARE fim_registros BOOLEAN DEFAULT FALSE;

    DECLARE pid INT;
    DECLARE data DATETIME;
    DECLARE st VARCHAR(20);
    DECLARE val DECIMAL(10,2);
    DECLARE pgto VARCHAR(20);
    DECLARE cid INT;
    DECLARE cnome VARCHAR(100);
    DECLARE cemail VARCHAR(100);
    DECLARE ccpf VARCHAR(14);
    DECLARE num_itens INT;
    DECLARE total_prod INT;
    DECLARE linha TEXT;

    DECLARE cur CURSOR FOR
        SELECT 
            p.id, p.data_pedido, p.status, p.valor_total, p.forma_pagamento,
            c.id, c.nome, c.email, c.cpf,
            COUNT(ip.id), SUM(ip.quantidade)
        FROM pedidos p
        JOIN clientes c ON p.cliente_id = c.id
        JOIN itens_pedido ip ON ip.pedido_id = p.id
        WHERE DATE(p.data_pedido) = data_ref
        GROUP BY p.id;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET fim_registros = TRUE;

    SET arquivo_nome = CONCAT('pedidos_', DATE_FORMAT(data_ref, '%Y%m%d'), '.txt');
    SET cabecalho = 'ID_PEDIDO;DATA_PEDIDO;STATUS;VALOR_TOTAL;FORMA_PAGAMENTO;ID_CLIENTE;NOME_CLIENTE;EMAIL_CLIENTE;CPF_CLIENTE;NUM_ITENS;TOTAL_PRODUTOS';
    SET conteudo_arquivo = CONCAT(cabecalho, '\n');

    OPEN cur;

    pedidos_loop: LOOP
        FETCH cur INTO pid, data, st, val, pgto, cid, cnome, cemail, ccpf, num_itens, total_prod;
        IF fim_registros THEN
            LEAVE pedidos_loop;
        END IF;
        SET linha = CONCAT(pid, ';', DATE_FORMAT(data, '%Y-%m-%d %H:%i:%s'), ';', st, ';',
                           REPLACE(val, '.', ','), ';', pgto, ';', cid, ';', cnome, ';',
                           cemail, ';', ccpf, ';', num_itens, ';', total_prod, '\n');
        SET conteudo_arquivo = CONCAT(conteudo_arquivo, linha);
    END LOOP;

    CLOSE cur;

    SELECT arquivo_nome AS nome_arquivo, conteudo_arquivo AS conteudo;
END //
DELIMITER ;
