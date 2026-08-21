<?php

require_once __DIR__ . '/Connection.php';

class SensorDataDAO
{
    private $conn;

    public function __construct()
    {
        $this->conn = Connection::getConnection();

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS sensor_data (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                project_id INT NOT NULL,
                ppm DECIMAL(10,2) NOT NULL,
                raw_value INT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                INDEX idx_project_date (
                    project_id,
                    created_at
                ),

                CONSTRAINT fk_sensor_project
                    FOREIGN KEY (project_id)
                    REFERENCES projects(id)
                    ON DELETE CASCADE
            )
        ");
    }

    public function salvar($projectId, $ppm, $rawValue = null)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO sensor_data
            (
                project_id,
                ppm,
                raw_value,
                created_at
            )
            VALUES
            (
                :project_id,
                :ppm,
                :raw_value,
                NOW()
            )
        ");

        return $stmt->execute([
            ':project_id' => $projectId,
            ':ppm' => $ppm,
            ':raw_value' => $rawValue
        ]);
    }

    public function getUltimo($projectId)
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM sensor_data
            WHERE project_id = :project_id
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->execute([
            ':project_id' => $projectId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getHistorico($projectId, $limite = 100)
    {
        $limite = (int)$limite;

        $stmt = $this->conn->prepare("
            SELECT
                id,
                project_id,
                ppm,
                raw_value,
                created_at
            FROM sensor_data
            WHERE project_id = :project_id
            ORDER BY created_at DESC
            LIMIT $limite
        ");

        $stmt->execute([
            ':project_id' => $projectId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}