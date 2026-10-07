-- Kmetija Skledar – podatkovna baza spletne trgovine
-- Uvoz: phpMyAdmin > Uvozi (Import) > izberi to datoteko > Izvedi

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS kmetija_skledar;
CREATE DATABASE kmetija_skledar CHARACTER SET utf8mb4 COLLATE utf8mb4_slovenian_ci;
USE kmetija_skledar;

-- Vloge uporabnikov (kupec, admin)
CREATE TABLE roles (
    id_role   INT AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Uporabniki
CREATE TABLE users (
    id_user        INT AUTO_INCREMENT PRIMARY KEY,
    id_role        INT NOT NULL DEFAULT 1,
    first_name     VARCHAR(50)  NOT NULL,
    last_name      VARCHAR(50)  NOT NULL,
    email          VARCHAR(100) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    phone          VARCHAR(20),
    address        VARCHAR(150),
    postal_code    VARCHAR(10),
    city           VARCHAR(50),
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_role) REFERENCES roles(id_role)
) ENGINE=InnoDB;

-- Kategorije izdelkov
CREATE TABLE categories (
    id_category  INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(50) NOT NULL,
    description  TEXT
) ENGINE=InnoDB;

-- Izdelki
CREATE TABLE products (
    id_product   INT AUTO_INCREMENT PRIMARY KEY,
    id_category  INT NOT NULL,
    name         VARCHAR(100) NOT NULL,
    description  TEXT,
    price        DECIMAL(8,2) NOT NULL CHECK (price >= 0),
    packaging    VARCHAR(30)  NOT NULL,          -- npr. 0,5 l / 250 g
    stock        INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
    image        VARCHAR(255),                   -- pot do naložene slike, npr. uploads/izdelki/3f9a1c2e.jpg (NULL = ilustracija)
    active       TINYINT(1) NOT NULL DEFAULT 1,  -- 0 = skrit v trgovini
    featured     TINYINT(1) NOT NULL DEFAULT 0,  -- 1 = izpostavljen na domači strani
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_category) REFERENCES categories(id_category)
) ENGINE=InnoDB;

-- Naročila
CREATE TABLE orders (
    id_order          INT AUTO_INCREMENT PRIMARY KEY,
    id_user           INT NOT NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status            ENUM('oddano','v obdelavi','poslano','zaključeno') NOT NULL DEFAULT 'oddano',
    total             DECIMAL(10,2) NOT NULL,
    shipping_address  VARCHAR(255) NOT NULL,
    payment_method    ENUM('po povzetju','predračun') NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user)
) ENGINE=InnoDB;

-- Postavke naročila (razreši M:N med orders in products)
CREATE TABLE order_items (
    id_item         INT AUTO_INCREMENT PRIMARY KEY,
    id_order        INT NOT NULL,
    id_product      INT NOT NULL,
    quantity        INT NOT NULL CHECK (quantity > 0),
    price_at_order  DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (id_order)   REFERENCES orders(id_order) ON DELETE CASCADE,
    FOREIGN KEY (id_product) REFERENCES products(id_product)
) ENGINE=InnoDB;

-- ---------- Začetni podatki ----------

INSERT INTO roles (id_role, name) VALUES (1, 'kupec'), (2, 'admin');

-- Testna uporabnika: admin@skledar.si / admin123, kupec@test.si / kupec123
INSERT INTO users (id_role, first_name, last_name, email, password_hash, city) VALUES
(2, 'Admin', 'Skledar', 'admin@skledar.si', '$2y$12$06Gmp2vFFBEbxVBRUKocsOSqAfrMK1lDZxa/ce3vNI9ehFvrQs80.', 'Ptuj'),
(1, 'Testni', 'Kupec',  'kupec@test.si',    '$2y$12$HENSTf5bdkLpcSUht6Z04.oYhhYtH7vOdSAK8idPFafGjEC4B7AZ.', 'Maribor');

INSERT INTO categories (id_category, name, description) VALUES
(1, 'Bučno olje',     'Hladno stiskano bučno olje iz štajerske oljne buče.'),
(2, 'Bučnice',        'Surove, pražene in oplemenitene bučnice.'),
(3, 'Darilni paketi', 'Kombinacije izdelkov za darilo.');

INSERT INTO products (id_category, name, description, price, packaging, stock, image, featured) VALUES
(1, 'Bučno olje',               'Hladno stiskano bučno olje s kmetije Skledar.', 7.00,  '0,25 l', 40, NULL, 0),
(1, 'Bučno olje',               'Hladno stiskano bučno olje s kmetije Skledar.', 12.00, '0,5 l',  60, NULL, 1),
(1, 'Bučno olje',               'Hladno stiskano bučno olje s kmetije Skledar.', 22.00, '1 l',    30, NULL, 1),
(2, 'Lupljene bučnice',         'Surove lupljene bučnice.',                      4.00,  '250 g',  50, NULL, 0),
(2, 'Pražene soljene bučnice',  'Pražene in rahlo soljene bučnice.',             4.50,  '250 g',  50, NULL, 1),
(2, 'Bučnice v čokoladi',       'Bučnice, oblite s temno čokolado.',             5.00,  '150 g',  25, NULL, 0),
(3, 'Darilni paket',            'Bučno olje 0,5 l in pražene bučnice 250 g.',    16.00, 'paket',  15, NULL, 1);
