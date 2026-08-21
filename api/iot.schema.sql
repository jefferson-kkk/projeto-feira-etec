CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(500) DEFAULT NULL,
    sector VARCHAR(150) DEFAULT NULL,
    floor VARCHAR(50) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_locations_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    manufacturer_code VARCHAR(64) NOT NULL,
    description VARCHAR(500) DEFAULT NULL,
    location_id INT DEFAULT NULL,
    sensor_type VARCHAR(100) NOT NULL DEFAULT 'TGS2610',
    status VARCHAR(32) NOT NULL DEFAULT 'offline',
    firmware_version VARCHAR(64) DEFAULT NULL,
    wifi_status VARCHAR(64) DEFAULT NULL,
    esp32_id VARCHAR(64) NOT NULL,
    api_key_hash VARCHAR(255) NOT NULL,
    last_online DATETIME DEFAULT NULL,
    last_seen_ip VARCHAR(45) DEFAULT NULL,
    last_seen_at DATETIME DEFAULT NULL,
    last_alert_status VARCHAR(32) DEFAULT 'normal',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_devices_manufacturer_code (manufacturer_code),
    UNIQUE KEY uq_devices_esp32_id (esp32_id),
    INDEX idx_devices_user (user_id),
    INDEX idx_devices_location (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sensor_readings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    ppm DECIMAL(10,2) NOT NULL,
    reading_status VARCHAR(32) NOT NULL,
    wifi_rssi INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_readings_device_time (device_id, created_at),
    CONSTRAINT fk_readings_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_settings (
    user_id INT PRIMARY KEY,
    language VARCHAR(10) NOT NULL DEFAULT 'pt-BR',
    safe_ppm DECIMAL(10,2) NOT NULL DEFAULT 400,
    alert_ppm DECIMAL(10,2) NOT NULL DEFAULT 550,
    critical_ppm DECIMAL(10,2) NOT NULL DEFAULT 700,
    reading_interval INT NOT NULL DEFAULT 5,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_id INT DEFAULT NULL,
    type VARCHAR(40) NOT NULL,
    title VARCHAR(180) NOT NULL,
    message VARCHAR(500) NOT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'info',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user (user_id,is_read,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    device_id INT DEFAULT NULL,
    action VARCHAR(80) NOT NULL,
    entity VARCHAR(80) DEFAULT NULL,
    entity_id VARCHAR(80) DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user_time (user_id,created_at),
    INDEX idx_audit_device_time (device_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_tickets (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_id INT DEFAULT NULL,
    subject VARCHAR(200) NOT NULL,
    category VARCHAR(80) NOT NULL DEFAULT 'Problema técnico',
    status VARCHAR(30) NOT NULL DEFAULT 'aberto',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ticket_user (user_id,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT NOT NULL,
    user_id INT DEFAULT NULL,
    sender_type VARCHAR(20) NOT NULL DEFAULT 'user',
    message TEXT NOT NULL,
    email_sent TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_support_ticket (ticket_id,created_at),
    CONSTRAINT fk_support_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS device_presence (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    esp32_id VARCHAR(64) NOT NULL,
    manufacturer_code VARCHAR(64) DEFAULT NULL,
    hostname VARCHAR(120) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    wifi_rssi INT DEFAULT NULL,
    firmware_version VARCHAR(64) DEFAULT NULL,
    seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_presence_esp_time (esp32_id,seen_at),
    INDEX idx_presence_time (seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;