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

        /*
         * Retângulo do cômodo na planta (metros), usado pelo editor
         * de planta e pela cena 3D. Padrão 3x3m na origem — o usuário
         * reposiciona no editor.
         */
        $this->ensureColumn('locations', 'pos_x', 'DECIMAL(6,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('locations', 'pos_y', 'DECIMAL(6,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('locations', 'width', 'DECIMAL(6,2) NOT NULL DEFAULT 3');
        $this->ensureColumn('locations', 'depth', 'DECIMAL(6,2) NOT NULL DEFAULT 3');
    }

    private function ensureColumn($table, $column, $definition)
    {
        $stmt = $this->conn->query(
            "SHOW COLUMNS FROM {$table} LIKE " . $this->conn->quote($column)
        );

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->conn->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
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
            $row['updated_at'] ?? null,
            $row['pos_x'] ?? 0,
            $row['pos_y'] ?? 0,
            $row['width'] ?? 3,
            $row['depth'] ?? 3
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
                floor,
                pos_x,
                pos_y,
                width,
                depth
            )
            VALUES (
                :user_id,
                :name,
                :description,
                :sector,
                :floor,
                :pos_x,
                :pos_y,
                :width,
                :depth
            )
        ");

        $stmt->execute([
            ':user_id' => $location->getUserId(),
            ':name' => $location->getName(),
            ':description' => $location->getDescription(),
            ':sector' => $location->getSector(),
            ':floor' => $location->getFloor(),
            ':pos_x' => $location->getPosX(),
            ':pos_y' => $location->getPosY(),
            ':width' => $location->getWidth(),
            ':depth' => $location->getDepth()
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /*
     * Salva só a posição/tamanho do cômodo na planta — usado pelo
     * editor visual a cada vez que o usuário solta um retângulo
     * arrastado ou redimensionado, sem precisar reenviar nome/etc.
     */
    public function updateLayout($id, $userId, $posX, $posY, $width, $depth)
    {
        $stmt = $this->conn->prepare("
            UPDATE locations
            SET pos_x = :pos_x, pos_y = :pos_y, width = :width, depth = :depth, updated_at = NOW()
            WHERE id = :id AND user_id = :user_id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':pos_x' => $posX,
            ':pos_y' => $posY,
            ':width' => $width,
            ':depth' => $depth
        ]);
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