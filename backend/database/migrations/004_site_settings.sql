CREATE TABLE site_settings (
    id INT PRIMARY KEY,
    address_uz TEXT NULL,
    address_ru TEXT NULL,
    map_embed_url VARCHAR(500) NULL,
    telegram VARCHAR(100) NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(50) NULL,
    about_uz TEXT NULL,
    about_ru TEXT NULL,
    stat_graduates INT NULL,
    stat_years INT NULL,
    stat_employment_percent INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO site_settings (id) VALUES (1);
