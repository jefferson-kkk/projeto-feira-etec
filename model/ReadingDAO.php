<?php

require_once __DIR__ . '/Connection.php';
require_once __DIR__ . '/Reading.php';

class ReadingDAO
{
    private $conn;

    public function __construct()
    {
        $this->conn = Connection::getConnection();

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS sensor_readings (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                device_id INT NOT NULL,
                ppm DECIMAL(10,2) NOT NULL,
                reading_status VARCHAR(32) NOT NULL,
                wifi_rssi INT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                INDEX idx_readings_device_time (
                    device_id,
                    created_at
                ),

                CONSTRAINT fk_readings_device
                    FOREIGN KEY (device_id)
                    REFERENCES devices(id)
                    ON DELETE CASCADE
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
        ");
    }

    public function create(
        $deviceId,
        $ppm,
        $status,
        $wifiRssi = null
    ) {
        $stmt = $this->conn->prepare("
            INSERT INTO sensor_readings (
                device_id,
                ppm,
                reading_status,
                wifi_rssi
            )
            VALUES (
                :device_id,
                :ppm,
                :reading_status,
                :wifi_rssi
            )
        ");

        $stmt->execute([
            ':device_id' => $deviceId,
            ':ppm' => $ppm,
            ':reading_status' => $status,
            ':wifi_rssi' => $wifiRssi
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function getLatest($deviceId)
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM sensor_readings
            WHERE device_id = :device_id
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ");

        $stmt->execute([
            ':device_id' => $deviceId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getHistory($deviceId, $limit = 100)
    {
        $limit = max(
            1,
            min((int) $limit, 1000)
        );

        $stmt = $this->conn->prepare("
            SELECT
                id,
                device_id,
                ppm,
                reading_status,
                wifi_rssi,
                created_at
            FROM sensor_readings
            WHERE device_id = :device_id
            ORDER BY created_at DESC, id DESC
            LIMIT {$limit}
        ");

        $stmt->execute([
            ':device_id' => $deviceId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats($deviceId)
    {
        $stmt = $this->conn->prepare("
            SELECT
                COUNT(*) AS total,
                COALESCE(AVG(ppm), 0) AS average_ppm,
                COALESCE(MIN(ppm), 0) AS min_ppm,
                COALESCE(MAX(ppm), 0) AS max_ppm
            FROM sensor_readings
            WHERE device_id = :device_id
              AND created_at >= CURDATE()
        ");

        $stmt->execute([
            ':device_id' => $deviceId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getToday($deviceId)
    {
        $stmt = $this->conn->prepare("
            SELECT
                id,
                ppm,
                reading_status,
                wifi_rssi,
                created_at
            FROM sensor_readings
            WHERE device_id = :device_id
              AND created_at >= CURDATE()
            ORDER BY created_at ASC
        ");

        $stmt->execute([
            ':device_id' => $deviceId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}   