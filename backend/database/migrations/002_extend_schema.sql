ALTER TABLE professions
    ADD COLUMN career_info_uz TEXT NULL AFTER image_url,
    ADD COLUMN career_info_ru TEXT NULL AFTER career_info_uz;

CREATE TABLE teacher_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    age INT NULL,
    experience_years INT NULL,
    skills_uz TEXT NULL,
    skills_ru TEXT NULL,
    education_uz TEXT NULL,
    education_ru TEXT NULL,
    telegram VARCHAR(100) NULL,
    email VARCHAR(150) NULL,
    photo_url VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
