CREATE TABLE profession_brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profession_id INT NOT NULL,
    name VARCHAR(191) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (profession_id) REFERENCES professions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE applications
    ADD COLUMN brand_id INT NULL AFTER profession_id,
    ADD FOREIGN KEY (brand_id) REFERENCES profession_brands(id);
