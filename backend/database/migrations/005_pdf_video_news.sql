ALTER TABLE professions
    ADD COLUMN pdf_url VARCHAR(255) NULL AFTER image_url;

CREATE TABLE profession_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profession_id INT NOT NULL,
    youtube_url VARCHAR(255) NOT NULL,
    title_uz VARCHAR(191) NULL,
    title_ru VARCHAR(191) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (profession_id) REFERENCES professions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title_uz VARCHAR(255) NOT NULL,
    title_ru VARCHAR(255) NOT NULL,
    body_uz TEXT NULL,
    body_ru TEXT NULL,
    image_url VARCHAR(255) NULL,
    published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
