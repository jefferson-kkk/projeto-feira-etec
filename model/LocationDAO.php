<?php

require_once __DIR__ . '/Connection.php';
require_once __DIR__ . '/Location.php';

class LocationDAO
{
    private $conn;

    public function __construct()
    {
        $this->conn = Connection::getConnection();

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS locations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(150) NOT NULL,
                description VARCHAR(500) DEFAULT NULL,
                sector VARCHAR(150) DEFAULT NULL,
                floor VARCHAR(50) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_locations_user (user_id)
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
        ");
    }

    private function map($row)
    {
        return new Location(
            $row['id'],
            $row['user_id'],
            $row['name'],
            $row['description'] ?? null,
            $row['sector'] ?? null,
            $row['floor'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }

    public function getByUser($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM locations
            WHERE user_id = :user_id
            ORDER BY name ASC
        ");

        $stmt->execute([
            ':user_id' => $userId
        ]);

        $locations = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $locations[] = $this->map($row);
        }

        return $locations;
    }

    public function getById($id, $userId = null)
    {
        $sql = "
            SELECT *
            FROM locations
            WHERE id = :id
        ";

        $params = [
            ':id' => $id
        ];

        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->map($row) : null;
    }

    public function create(Location $location)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO locations (
                user_id,
                name,
                description,
                sector,
                floor
            )
            VALUES (
                :user_id,
                :name,
                :description,
                :sector,
                :floor
            )
        ");

        $stmt->execute([
            ':user_id' => $location->getUserId(),
            ':name' => $location->getName(),
            ':description' => $location->getDescription(),
            ':sector' => $location->getSector(),
            ':floor' => $location->getFloor()
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function update(Location $location)
    {
        $stmt = $this->conn->prepare("
            UPDATE locations
            SET
                name = :name,
                description = :description,
                sector = :sector,
                floor = :floor,
                updated_at = NOW()
            WHERE id = :id
              AND user_id = :user_id
        ");

        return $stmt->execute([
            ':id' => $location->getId(),
            ':user_id' => $location->getUserId(),
            ':name' => $location->getName(),
            ':description' => $location->getDescription(),
            ':sector' => $location->getSector(),
            ':floor' => $location->getFloor()
        ]);
    }

    public function delete($id, $userId)
    {
        $stmt = $this->conn->prepare("
            DELETE FROM locations
            WHERE id = :id
              AND user_id = :user_id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId
        ]);
    }
}