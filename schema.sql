-- Import via phpMyAdmin (select your database first)
CREATE TABLE IF NOT EXISTS puzzles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hotspots (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  puzzle_id INT UNSIGNED NOT NULL,
  name VARCHAR(60) NOT NULL,
  x DECIMAL(7,4) NOT NULL,
  y DECIMAL(7,4) NOT NULL,
  r DECIMAL(7,4) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (puzzle_id) REFERENCES puzzles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(50) PRIMARY KEY,
  v VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Admin password (key 'admin_hash') is stored as a bcrypt hash.
-- If absent, the first password entered in the Upload prompt becomes the admin password (min 8 chars).
-- To reset: delete the 'admin_hash' row in phpMyAdmin.

INSERT INTO puzzles (title,image_url) VALUES ('Default','default.jpg');
