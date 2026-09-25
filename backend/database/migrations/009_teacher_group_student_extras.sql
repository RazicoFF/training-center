ALTER TABLE teacher_profiles
    ADD COLUMN birth_date DATE NULL AFTER age;

ALTER TABLE users
    ADD COLUMN photo_url VARCHAR(255) NULL AFTER password_hash;

ALTER TABLE applications
    ADD COLUMN photo_url VARCHAR(255) NULL AFTER phone;

ALTER TABLE `groups`
    ADD COLUMN brand_id INT NULL AFTER profession_id,
    ADD FOREIGN KEY (brand_id) REFERENCES profession_brands(id);
