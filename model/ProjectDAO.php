<?php

require_once __DIR__ . '/Connection.php';
require_once __DIR__ . '/Project.php';

class ProjectDAO
{
    private $conn;

    public function __construct()
    {
        $this->conn = Connection::getConnection();

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                manufacturer_code VARCHAR(64) NOT NULL,
                registered_at DATETIME NOT NULL,
                status VARCHAR(64) NOT NULL DEFAULT 'offline',
                firmware_version VARCHAR(64) DEFAULT NULL,
                wifi_status VARCHAR(64) DEFAULT NULL,
                esp32_id VARCHAR(64) DEFAULT NULL,
                last_online DATETIME DEFAULT NULL,
                location VARCHAR(255) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                api_key VARCHAR(128) DEFAULT NULL,
                UNIQUE KEY unique_device_code (manufacturer_code),
                UNIQUE KEY unique_api_key (api_key)
            )
        ");

        $this->adicionarColuna('location', 'VARCHAR(255) DEFAULT NULL');
        $this->adicionarColuna('description', 'TEXT DEFAULT NULL');
        $this->adicionarColuna('api_key', 'VARCHAR(128) DEFAULT NULL');

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS sensor_data (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                project_id INT NOT NULL,
                ppm DECIMAL(10,2) NOT NULL,
                raw_value INT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_project_date (project_id, created_at),
                CONSTRAINT fk_sensor_project
                    FOREIGN KEY (project_id)
                    REFERENCES projects(id)
                    ON DELETE CASCADE
            )
        ");
    }

    private function adicionarColuna($nome, $tipo)
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'projects'
            AND COLUMN_NAME = :coluna
        ");

        $stmt->execute([':coluna' => $nome]);

        if ((int)$stmt->fetchColumn() === 0) {
            $this->conn->exec("ALTER TABLE projects ADD COLUMN `$nome` $tipo");
        }
    }

    private function transformar($row)
    {
        return new Project(
            $row['id'],
            $row['user_id'],
            $row['name'],
            $row['manufacturer_code'],
            $row['registered_at'],
            $row['status'],
            $row['firmware_version'],
            $row['wifi_status'],
            $row['esp32_id'],
            $row['last_online'],
            $row['location'] ?? null,
            $row['description'] ?? null,
            $row['api_key'] ?? null
        );
    }

    public function getProjectsByUser($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM projects
            WHERE user_id = :user_id
            ORDER BY registered_at DESC
        ");

        $stmt->execute([':user_id' => $userId]);

        $result = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $this->transformar($row);
        }

        return $result;
    }

    public function countProjectsByUser($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM projects
            WHERE user_id = :user_id
        ");

        $stmt->execute([':user_id' => $userId]);

        return (int)$stmt->fetchColumn();
    }

    public function getProjectByCode($manufacturerCode)
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM projects
            WHERE manufacturer_code = :code
            LIMIT 1
        ");

        $stmt->execute([
            ':code' => $manufacturerCode
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->transformar($row) : null;
    }

    public function getProjectByApiKey($apiKey)
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM projects
            WHERE api_key = :api_key
            LIMIT 1
        ");

        $stmt->execute([
            ':api_key' => $apiKey
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->transformar($row) : null;
    }

    public function createProject(Project $project)
    {
        $apiKey = $project->getApiKey();

        if (!$apiKey) {
            $apiKey = bin2hex(random_bytes(32));
        }

        $stmt = $this->conn->prepare("
            INSERT INTO projects
            (
                user_id,
                name,
                manufacturer_code,
                registered_at,
                status,
                firmware_version,
                wifi_status,
                esp32_id,
                last_online,
                location,
                description,
                api_key
            )
            VALUES
            (
                :user_id,
                :name,
                :manufacturer_code,
                :registered_at,
                :status,
                :firmware_version,
                :wifi_status,
                :esp32_id,
                :last_online,
                :location,
                :description,
                :api_key
            )
        ");

        $stmt->execute([
            ':user_id' => $project->getUserId(),
            ':name' => $project->getName(),
            ':manufacturer_code' => $project->getManufacturerCode(),
            ':registered_at' => $project->getRegisteredAt(),
            ':status' => $project->getStatus(),
            ':firmware_version' => $project->getFirmwareVersion(),
            ':wifi_status' => $project->getWifiStatus(),
            ':esp32_id' => $project->getEsp32Id(),
            ':last_online' => $project->getLastOnline(),
            ':location' => $project->getLocation(),
            ':description' => $project->getDescription(),
            ':api_key' => $apiKey
        ]);

        return $apiKey;
    }

    public function atualizarProjeto(
        $id,
        $name,
        $location,
        $description,
        $firmwareVersion,
        $wifiStatus
    ) {
        $stmt = $this->conn->prepare("
            UPDATE projects
            SET
                name = :name,
                location = :location,
                description = :description,
                firmware_version = :firmware_version,
                wifi_status = :wifi_status
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':location' => $location,
            ':description' => $description,
            ':firmware_version' => $firmwareVersion,
            ':wifi_status' => $wifiStatus
        ]);
    }

    public function atualizarStatus($id, $status, $wifiStatus = null)
    {
        $stmt = $this->conn->prepare("
            UPDATE projects
            SET
                status = :status,
                wifi_status = :wifi_status,
                last_online = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':wifi_status' => $wifiStatus
        ]);
    }

    public function getProjectById($id)
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM projects
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->transformar($row) : null;
    }
}