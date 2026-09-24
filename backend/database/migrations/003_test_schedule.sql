ALTER TABLE tests
    ADD COLUMN opens_at DATETIME NULL AFTER passing_score,
    ADD COLUMN closes_at DATETIME NULL AFTER opens_at;
