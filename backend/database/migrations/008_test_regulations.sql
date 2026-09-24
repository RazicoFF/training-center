ALTER TABLE tests
    ADD COLUMN random_question_count INT NULL AFTER passing_score,
    ADD COLUMN time_limit_minutes INT NULL AFTER random_question_count;

CREATE TABLE test_question_selections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    test_id INT NOT NULL,
    question_ids TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_test (user_id, test_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (test_id) REFERENCES tests(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
