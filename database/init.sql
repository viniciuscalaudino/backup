SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS tech_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tech_store;

CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(180) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 role ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE categories (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE products (
 id INT AUTO_INCREMENT PRIMARY KEY,
 category_id INT NOT NULL,
 name VARCHAR(180) NOT NULL,
 description TEXT,
 price DECIMAL(10,2) NOT NULL,
 stock INT NOT NULL DEFAULT 0,
 image VARCHAR(255),
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (category_id) REFERENCES categories(id)
);
CREATE TABLE carts (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE cart_items (
 id INT AUTO_INCREMENT PRIMARY KEY,
 cart_id INT NOT NULL,
 product_id INT NOT NULL,
 quantity INT NOT NULL,
 UNIQUE KEY uq_cart_product(cart_id, product_id),
 FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
 FOREIGN KEY (product_id) REFERENCES products(id)
);
CREATE TABLE orders (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 status ENUM('aguardando_pagamento','pago','enviado','entregue','cancelado') NOT NULL DEFAULT 'aguardando_pagamento',
 total DECIMAL(10,2) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 paid_at DATETIME NULL,
 FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE TABLE order_items (
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_id INT NOT NULL,
 product_id INT NOT NULL,
 product_name VARCHAR(180) NOT NULL,
 unit_price DECIMAL(10,2) NOT NULL,
 quantity INT NOT NULL,
 FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
 FOREIGN KEY (product_id) REFERENCES products(id)
);

INSERT INTO categories (name) VALUES ('Notebooks'),('Celulares'),('Acessórios');
INSERT INTO products (category_id,name,description,price,stock,image) VALUES
(1,'Notebook Tech Pro','Notebook para estudos e trabalho.',3499.90,10,'notebook.png'),
(1,'Notebook Tech Air','Modelo leve para produtividade.',2899.90,8,'notebook2.png'),
(2,'Smartphone Tech X','Smartphone com ótimo desempenho.',1999.90,15,'smartphone.png'),
(2,'Smartphone Tech Mini','Compacto e eficiente.',1299.90,12,'smartphone2.png'),
(3,'Mouse Wireless','Mouse sem fio ergonômico.',89.90,30,'mouse.png'),
(3,'Teclado Mecânico','Teclado mecânico para produtividade.',249.90,20,'teclado.png'),
(3,'Headset Gamer','Headset com microfone.',179.90,0,'fone.png');
INSERT INTO users(name, email, password, role) VALUES ("TimeFront", "emaildaempresa@gmail.com", "$2y$10$lckezau8B/CM4mjIEf0u0OTwOkzrk4.nbwDhkkNdPdNFmoUHwxTBO", "admin");