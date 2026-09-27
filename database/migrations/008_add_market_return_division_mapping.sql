USE store_aging;

CREATE TABLE IF NOT EXISTS market_return_division_mappings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_type ENUM('exact','contains') NOT NULL DEFAULT 'exact',
    match_text VARCHAR(255) NOT NULL,
    match_key VARCHAR(255) NOT NULL,
    division_id INT UNSIGNED NOT NULL,
    source_type ENUM('manual','file','login') NOT NULL DEFAULT 'manual',
    created_by INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mr_division_mapping_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
    CONSTRAINT fk_mr_division_mapping_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_mr_mapping (match_type, match_key, division_id),
    INDEX idx_mr_mapping_key (match_type, match_key),
    INDEX idx_mr_mapping_division (division_id, is_active)
) ENGINE=InnoDB;
