CREATE TABLE IF NOT EXISTS forestusers (
  login VARCHAR(150) NOT NULL PRIMARY KEY,
  password VARCHAR(255) NOT NULL,
  fuid VARCHAR(150) NOT NULL,
  status VARCHAR(150) NOT NULL DEFAULT 'user',
  TempKey VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* Ключи WebAuthn (Face ID / отпечаток). Хранится только публичный ключ. */
CREATE TABLE IF NOT EXISTS forestwebauthn (
  id INT AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(150) NOT NULL,
  cred_id VARCHAR(255) NOT NULL,
  pubkey_pem TEXT NOT NULL,
  alg INT NOT NULL DEFAULT -7,
  sign_count BIGINT NOT NULL DEFAULT 0,
  uv TINYINT NOT NULL DEFAULT 1,
  label VARCHAR(120) NOT NULL DEFAULT '',
  transports VARCHAR(120) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_used_at TIMESTAMP NULL,
  UNIQUE KEY uniq_cred (cred_id),
  KEY idx_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
