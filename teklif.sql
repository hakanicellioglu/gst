/* ------------------------------------------------------------------ */
/* 0 | VERİ TABANI                                                    */
/* ------------------------------------------------------------------ */
CREATE DATABASE IF NOT EXISTS teklif
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_turkish_ci;
USE teklif;

/* ------------------------------------------------------------------ */
/* 1 | KULLANICILAR & RBAC                                            */
/* ------------------------------------------------------------------ */
CREATE TABLE users (
  id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  first_name      VARCHAR(50)   NOT NULL,
  last_name       VARCHAR(50)   NOT NULL,
  username        VARCHAR(30)   NOT NULL UNIQUE,
  password_hash   CHAR(60)      NOT NULL,
  email           VARCHAR(100)  NOT NULL UNIQUE,
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_turkish_ci;

CREATE TABLE roles (
  id          SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name        VARCHAR(30) NOT NULL UNIQUE,
  description VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE permissions (
  id          SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code        VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE role_user (
  user_id INT UNSIGNED      NOT NULL,
  role_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id)
              ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id)
              ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE permission_role (
  role_id       SMALLINT UNSIGNED NOT NULL,
  permission_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id)       REFERENCES roles(id)
              ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id)
              ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

/* ------------------------------------------------------------------ */
/* 2 | FİRMALAR & MÜŞTERİLER                                          */
/* ------------------------------------------------------------------ */
CREATE TABLE companies (
  id        INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name      VARCHAR(100) NOT NULL,
  phone     VARCHAR(20),
  address   VARCHAR(255),
  email     VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE customers (
  id            INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  company_id    INT UNSIGNED,
  first_name    VARCHAR(50)  NOT NULL,
  last_name     VARCHAR(50)  NOT NULL,
  title         VARCHAR(30),             -- Hitap
  email         VARCHAR(100),
  phone         VARCHAR(20),
  address       VARCHAR(255),
  FOREIGN KEY (company_id) REFERENCES companies(id)
          ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

/* ------------------------------------------------------------------ */
/* 3 | SİSTEM LOG TABLOSU                                             */
/* ------------------------------------------------------------------ */
CREATE TABLE audit_logs (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id         INT UNSIGNED,
  action_type     ENUM('INSERT','UPDATE','DELETE') NOT NULL,
  action_time     DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  table_name      VARCHAR(50) NOT NULL,
  column_name     VARCHAR(50) DEFAULT NULL,
  record_id       BIGINT UNSIGNED,
  old_value       TEXT,
  new_value       TEXT,
  description     VARCHAR(255),
  FOREIGN KEY (user_id) REFERENCES users(id)
          ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_table_record (table_name, record_id),
  INDEX idx_user_time   (user_id, action_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

/* ------------------------------------------------------------------ */
/* 4 | ÜRÜNLER                                                        */
/* ------------------------------------------------------------------ */
CREATE TABLE products (
  id        INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name      VARCHAR(100) NOT NULL,
  category  VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;


/* ------------------------------------------------------------------ */
/* 5 | GİYOTİN TEKLİF                                                 */
/* ------------------------------------------------------------------ */
CREATE TABLE guillotine_quotes (
  id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  system_type       VARCHAR(50) NOT NULL,  -- Giyotin Sistemi
  width_mm          DECIMAL(10,2) NOT NULL,
  height_mm         DECIMAL(10,2) NOT NULL,
  system_qty        INT UNSIGNED NOT NULL,
  motor_system      VARCHAR(50),
  remote_system     VARCHAR(50),
  remote_qty        INT UNSIGNED,
  ral_code          VARCHAR(50),
  glass_inner_mm    DECIMAL(6,2),
  glass_gap_mm      DECIMAL(6,2),
  glass_outer_mm    DECIMAL(6,2),
  tempered          TINYINT(1) DEFAULT 0,
  gap_type          VARCHAR(20),           -- argon / düz
  glass_width_mm    DECIMAL(10,2),
  glass_height_mm   DECIMAL(10,2),
  glass_qty         INT UNSIGNED,
  glass_color       VARCHAR(30),
  sqm_system        DECIMAL(12,2),
  kg_aluminium      DECIMAL(12,2),
  sqm_glass         DECIMAL(12,2),
  delta             DECIMAL(12,2),
  customer_id       INT UNSIGNED,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id)
          ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE guillotine_quote_items (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  quote_id      BIGINT UNSIGNED NOT NULL,
  product_id    INT UNSIGNED    NOT NULL,
  dimension     VARCHAR(50),
  quantity      INT UNSIGNED    NOT NULL,
  FOREIGN KEY (quote_id)   REFERENCES guillotine_quotes(id)
          ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
          ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

/* ------------------------------------------------------------------ */
/* 6 | SÜRME TEKLİF                                                   */
/* ------------------------------------------------------------------ */
CREATE TABLE sliding_quotes (
  id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  system_type       VARCHAR(50) NOT NULL,  -- Sürme sistemi
  width_mm          DECIMAL(10,2) NOT NULL,
  height_mm         DECIMAL(10,2) NOT NULL,
  system_name       VARCHAR(50),
  system_qty        INT UNSIGNED NOT NULL,
  ral_code          VARCHAR(50),
  fastening_type    VARCHAR(30),           -- kenet
  glass_inner_mm    DECIMAL(6,2),
  glass_gap_mm      DECIMAL(6,2),
  glass_outer_mm    DECIMAL(6,2),
  tempered          TINYINT(1) DEFAULT 0,
  glass_width_mm    DECIMAL(10,2),
  glass_height_mm   DECIMAL(10,2),
  glass_qty         INT UNSIGNED,
  sqm_system        DECIMAL(12,2),
  kg_aluminium      DECIMAL(12,2),
  sqm_glass         DECIMAL(12,2),
  delta             DECIMAL(12,2),
  customer_id       INT UNSIGNED,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id)
          ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE sliding_quote_items (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  quote_id      BIGINT UNSIGNED NOT NULL,
  product_id    INT UNSIGNED    NOT NULL,
  dimension     VARCHAR(50),
  quantity      INT UNSIGNED    NOT NULL,
  FOREIGN KEY (quote_id)   REFERENCES sliding_quotes(id)
          ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
          ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

/* ------------------------------------------------------------------ */
/* 7 | GENEL TEKLİF (MASTER)                                          */
/* ------------------------------------------------------------------ */
CREATE TABLE master_quotes (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  company_id      INT UNSIGNED             NOT NULL,
  contact_person  VARCHAR(100),
  phone           VARCHAR(20),
  address         VARCHAR(255),
  quote_date      DATE                     NOT NULL,
  prepared_by     VARCHAR(100),
  email           VARCHAR(100) DEFAULT 'satis@alumann.com.tr',
  delivery_term   VARCHAR(50),
  payment_method  VARCHAR(50),
  payment_due     VARCHAR(50),
  quote_validity  VARCHAR(50),
  maturity        VARCHAR(50),
  total_amount    DECIMAL(14,2),
  discount_rate   DECIMAL(5,2),
  discount_amount DECIMAL(14,2),
  vat_rate        DECIMAL(5,2),
  vat_amount      DECIMAL(14,2),
  taxes_included  TINYINT(1) DEFAULT 0,
  notes           TEXT,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(id)
          ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE master_quote_items (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  master_id     BIGINT UNSIGNED NOT NULL,
  product_id    INT UNSIGNED,
  description   VARCHAR(255),        -- TODO: miktar, birim fiyat, tutar alanlarını ekleyin
  quantity      DECIMAL(12,2),
  unit_price    DECIMAL(14,2),
  total_price   DECIMAL(14,2),
  FOREIGN KEY (master_id)  REFERENCES master_quotes(id)
          ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
          ON DELETE SET NULL  ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

/* ------------------------------------------------------------------ */
/* 8 | SİSTEM AYARLARI                                                */
/* ------------------------------------------------------------------ */
CREATE TABLE settings (
  id         TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  group_name VARCHAR(30)  NOT NULL,
  key_name   VARCHAR(50)  NOT NULL,
  value      JSON         NOT NULL,
  UNIQUE KEY uq_group_key (group_name, key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
