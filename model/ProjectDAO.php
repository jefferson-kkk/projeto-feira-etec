<?php
require_once __DIR__ . '/Connection.php';
require_once __DIR__ . '/Project.php';

class ProjectDAO {
    private $conn;

    public function __construct(){
        $this->conn = Connection::getConnection();

        $this->conn->exec("CREATE TABLE IF NOT EXISTS projects (
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
            UNIQUE KEY unique_device_code (manufacturer_code)
        )");
    }

    public function getProjectsByUser($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM projects WHERE user_id = :user_id ORDER BY registered_at DESC");
        $stmt->execute([':user_id' => $userId]);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = new Project(
                $row['id'],
                $row['user_id'],
                $row['name'],
                $row['manufacturer_code'],
                $row['registered_at'],
                $row['status'],
                $row['firmware_version'],
                $row['wifi_status'],
                $row['esp32_id'],
                $row['last_online']
            );
        }
        return $result;
    }

    public function countProjectsByUser($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM projects WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function getProjectByCode($manufacturerCode) {
        $stmt = $this->conn->prepare("SELECT * FROM projects WHERE manufacturer_code = :code");
        $stmt->execute([':code' => $manufacturerCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
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
            $row['last_online']
        );
    }

    public function createProject(Project $project) {
        $stmt = $this->conn->prepare("INSERT INTO projects (user_id, name, manufacturer_code, registered_at, status, firmware_version, wifi_status, esp32_id, last_online)
            VALUES (:user_id, :name, :manufacturer_code, :registered_at, :status, :firmware_version, :wifi_status, :esp32_id, :last_online)");
        $stmt->execute([
            ':user_id' => $project->getUserId(),
            ':name' => $project->getName(),
            ':manufacturer_code' => $project->getManufacturerCode(),
            ':registered_at' => $project->getRegisteredAt(),
            ':status' => $project->getStatus(),
            ':firmware_version' => $project->getFirmwareVersion(),
            ':wifi_status' => $project->getWifiStatus(),
            ':esp32_id' => $project->getEsp32Id(),
            ':last_online' => $project->getLastOnline()
        ]);
    }
}
