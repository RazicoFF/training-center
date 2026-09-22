CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(191) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
    language ENUM('uz','ru') NOT NULL DEFAULT 'uz',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE professions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_uz VARCHAR(191) NOT NULL,
    name_ru VARCHAR(191) NOT NULL,
    description_uz TEXT NOT NULL,
    description_ru TEXT NOT NULL,
    duration_days INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    image_url VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(191) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    profession_id INT NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_user_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profession_id) REFERENCES professions(id),
    FOREIGN KEY (created_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `groups` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profession_id INT NOT NULL,
    teacher_id INT NULL,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    FOREIGN KEY (profession_id) REFERENCES professions(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    lesson_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50) NOT NULL,
    FOREIGN KEY (group_id) REFERENCES `groups`(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    group_id INT NOT NULL,
    status ENUM('active','completed','dropped') NOT NULL DEFAULT 'active',
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (group_id) REFERENCES `groups`(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profession_id INT NOT NULL,
    title_uz VARCHAR(191) NOT NULL,
    title_ru VARCHAR(191) NOT NULL,
    passing_score INT NOT NULL DEFAULT 70,
    FOREIGN KEY (profession_id) REFERENCES professions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    text_uz TEXT NOT NULL,
    text_ru TEXT NOT NULL,
    FOREIGN KEY (test_id) REFERENCES tests(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    text_uz VARCHAR(255) NOT NULL,
    text_ru VARCHAR(255) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE test_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    test_id INT NOT NULL,
    score INT NOT NULL,
    passed TINYINT(1) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (test_id) REFERENCES tests(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    profession_id INT NOT NULL,
    certificate_number VARCHAR(50) NOT NULL UNIQUE,
    issue_date DATE NOT NULL,
    pdf_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (profession_id) REFERENCES professions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
