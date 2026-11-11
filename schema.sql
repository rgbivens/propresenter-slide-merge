-- Optional. count.php and stats.php create this table on first use if the
-- database user is allowed to. Otherwise, run this once in phpMyAdmin.
CREATE TABLE IF NOT EXISTS sm_merges (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  created_at DATETIME NOT NULL,
  presentations INT UNSIGNED NOT NULL,
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
