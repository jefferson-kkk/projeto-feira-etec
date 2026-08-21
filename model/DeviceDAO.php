<?php

require_once __DIR__ . '/Connection.php';
require_once __DIR__ . '/Device.php';

class DeviceDAO
{
    private $conn;

    public function __construct()
    {
        $this->conn = Connection::getConnection();

        $this->createTable();
    }

    private function createTable()
    {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS devices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                manufacturer_code VARCHAR(64) NOT NULL,
                description VARCHAR(500) DEFAULT NULL,
                location_id INT DEFAULT NULL,
                sensor_type VARCHAR(100) NOT NULL DEFAULT 'MQ135',
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
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                UNIQUE KEY uq_devices_esp32_id (esp32_id),
                INDEX idx_devices_user (user_id),
                INDEX idx_devices_location (location_id)
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
        ");

        $this->ensureColumn(
            'devices',
            'last_seen_ip',
            "VARCHAR(45) DEFAULT NULL"
        );

        $this->ensureColumn(
            'devices',
            'last_seen_at',
            "DATETIME DEFAULT NULL"
        );

        $this->ensureColumn(
            'devices',
            'last_alert_status',
            "VARCHAR(32) DEFAULT 'normal'"
        );
    }

    private function ensureColumn($table, $column, $definition)
    {
        $stmt = $this->conn->query(
            "SHOW COLUMNS FROM {$table} LIKE " .
            $this->conn->quote($column)
        );

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->conn->exec(
                "ALTER TABLE {$table}
                 ADD COLUMN {$column} {$definition}"
            );
        }
    }

 private function map($row)
{
    return new Device(
        $row['id'],
        $row['user_id'],
        $row['name'],
        $row['manufacturer_code'],
        $row['description'] ?? null,
        $row['location_id'] ?? null,
        $row['sensor_type'] ?? 'MQ135',
        $row['status'] ?? 'offline',
        $row['firmware_version'] ?? null,
        $row['wifi_status'] ?? null,
        $row['esp32_id'] ?? null,
        $row['last_online'] ?? null,
        $row['created_at'] ?? null,
        $row['updated_at'] ?? null
    );
}

    public function getConnection()
    {
        return $this->conn;
    }

    public function getById($id, $userId = null)
    {
        $sql = "
            SELECT d.*
            FROM devices d
            WHERE d.id = :id
        ";

        $params = [
            ':id' => $id
        ];

        if ($userId !== null) {
            $sql .= " AND d.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->map($row) : null;
    }

    public function getByEsp32Id($esp32Id)
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM devices
            WHERE esp32_id = :esp32_id
            LIMIT 1
        ");

        $stmt->execute([
            ':esp32_id' => $esp32Id
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->map($row) : null;
    }

    public function getAllByUser($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT
                d.*,
                l.name AS location_name,
                l.sector,
                l.floor
            FROM devices d
            LEFT JOIN locations l
                ON l.id = d.location_id
            WHERE d.user_id = :user_id
            ORDER BY d.name ASC
        ");

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO devices (
                user_id,
                name,
                manufacturer_code,
                description,
                location_id,
                sensor_type,
                status,
                firmware_version,
                wifi_status,
                esp32_id,
                api_key_hash
            )
            VALUES (
                :user_id,
                :name,
                :manufacturer_code,
                :description,
                :location_id,
                :sensor_type,
                :status,
                :firmware_version,
                :wifi_status,
                :esp32_id,
                :api_key_hash
            )
        ");

        $stmt->execute([
            ':user_id' => $data[':user_id'],
            ':name' => $data[':name'],
            ':manufacturer_code' => $data[':manufacturer_code'],
            ':description' => $data[':description'] ?? null,
            ':location_id' => $data[':location_id'] ?? null,
            ':sensor_type' => $data[':sensor_type'] ?? 'TGS2610',
            ':status' => $data[':status'] ?? 'offline',
            ':firmware_version' => $data[':firmware_version'] ?? null,
            ':wifi_status' => $data[':wifi_status'] ?? null,
            ':esp32_id' => $data[':esp32_id'],
            ':api_key_hash' => $data[':api_key_hash']
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function update(array $data)
    {
        $stmt = $this->conn->prepare("
            UPDATE devices
            SET
                name = :name,
                manufacturer_code = :manufacturer_code,
                description = :description,
                location_id = :location_id,
                sensor_type = :sensor_type,
                status = :status,
                firmware_version = :firmware_version,
                esp32_id = :esp32_id,
                updated_at = NOW()
            WHERE id = :id
              AND user_id = :user_id
        ");

        return $stmt->execute([
            ':id' => $data[':id'],
            ':user_id' => $data[':user_id'],
            ':name' => $data[':name'],
            ':manufacturer_code' => $data[':manufacturer_code'],
            ':description' => $data[':description'] ?? null,
            ':location_id' => $data[':location_id'] ?? null,
            ':sensor_type' => $data[':sensor_type'] ?? 'TGS2610',
            ':status' => $data[':status'] ?? 'offline',
            ':firmware_version' => $data[':firmware_version'] ?? null,
            ':esp32_id' => $data[':esp32_id']
        ]);
    }

    public function updateOnline(
        $id,
        $wifiStatus = 'online',
        $ip = null,
        $alertStatus = 'normal'
    ) {
        $stmt = $this->conn->prepare("
            UPDATE devices
            SET
                status = 'online',
                wifi_status = :wifi_status,
                last_online = NOW(),
                last_seen_ip = :ip,
                last_seen_at = NOW(),
                last_alert_status = :alert_status,
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':wifi_status' => $wifiStatus,
            ':ip' => $ip,
            ':alert_status' => $alertStatus
        ]);
    }

    public function markOffline($id)
    {
        $stmt = $this->conn->prepare("
            UPDATE devices
            SET
                status = 'offline',
                wifi_status = 'offline',
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id
        ]);
    }
public function getByManufacturerCode($manufacturerCode)
{
    $stmt = $this->conn->prepare("
        SELECT *
        FROM devices
        WHERE manufacturer_code = :manufacturer_code
        LIMIT 1
    ");

    $stmt->execute([
        ':manufacturer_code' => $manufacturerCode
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row
        ? $this->map($row)
        : null;
}


    public function delete($id, $userId)
    {
        $stmt = $this->conn->prepare("
            DELETE FROM devices
            WHERE id = :id
              AND user_id = :user_id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId
        ]);
    }
}
