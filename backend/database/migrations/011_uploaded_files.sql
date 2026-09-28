-- Durable copy of every admin/site upload. public/uploads/ lives on the container's
-- disk, which Railway wipes on each deploy; public/upload.php serves (and re-caches)
-- a file from here whenever its disk copy is missing.
CREATE TABLE uploaded_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    path VARCHAR(255) NOT NULL UNIQUE,
    mime VARCHAR(100) NOT NULL,
    data LONGBLOB NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
