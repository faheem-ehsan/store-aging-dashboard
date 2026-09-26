USE store_aging;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id INT UNSIGNED NULL,
    actor_name VARCHAR(120) NULL,
    actor_email VARCHAR(190) NULL,
    actor_role VARCHAR(40) NULL,
    division_id INT UNSIGNED NULL,
    division_code VARCHAR(20) NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id VARCHAR(80) NULL,
    description VARCHAR(500) NOT NULL,
    details_json JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_action (action, created_at),
    INDEX idx_audit_actor (actor_user_id, created_at),
    INDEX idx_audit_division (division_id, created_at),
    INDEX idx_audit_entity (entity_type, entity_id)
) ENGINE=InnoDB;
