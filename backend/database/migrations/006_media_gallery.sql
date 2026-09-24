CREATE TABLE media_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('image','video') NOT NULL,
    file_url VARCHAR(255) NULL,
    youtube_url VARCHAR(255) NULL,
    title_uz VARCHAR(191) NULL,
    title_ru VARCHAR(191) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
