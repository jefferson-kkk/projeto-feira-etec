<?php

require_once __DIR__ . '/Connection.php';
require_once __DIR__ . '/Device.php';

class DeviceDAO
{
    private $conn;

    /*
     * $ensureSchema=false pula o CREATE/ALTER TABLE de verificação do
     * schema (só precisa rodar uma vez; repetir em toda requisição
     * custa uns 15-20ms à toa). Use false só em caminhos quentes,
     * chamados com muita frequência, como api/receive.php.
     */
    public function __construct($ensureSchema = true)
    {
        $this->conn = Connection::getConnection();

        if ($ensureSchema) {
            $this->createTable();
        }
    }

    /*
     * A api_key é uma string aleatória de 192 bits (bin2hex(random_bytes(24))),
     * não uma senha escolhida por humano — então não precisa de bcrypt
     * (proposital e corretamente lento, ~150-200ms por verificação, ótimo
     * para senha de login, péssimo para autenticar toda leitura do ESP32).
     * SHA-256 + comparação em tempo constante é igualmente seguro aqui e
     * quase instantâneo. Dispositivos antigos com hash bcrypt continuam
     * funcionando (verifyApiKey detecta o formato automaticamente).
     */
    public static function hashApiKey($apiKey)
    {
        return hash('sha256', $apiKey);
    }

    public static function verifyApiKey($apiKey, $hash)
    {
        if ($hash === '' || $hash === null) {
            return false;
        }

        if (strncmp($hash, '$2', 2) === 0) {
            // Hash antigo, gerado com password_hash() (bcrypt).
            return password_verify($apiKey, $hash);
        }

        return hash_equals($hash, self::hashApiKey($apiKey));
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
                sensor_type VARCHAR(100) NOT NULL DEFAULT 'MQ-6',
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

        /*
         * O hardware do projeto passou a usar o sensor MQ-6.
         * Isso só atualiza o valor padrão para NOVOS cadastros;
         * dispositivos já cadastrados mantêm o sensor_type real
         * que foi informado no momento do cadastro (histórico).
         */
        $this->conn->exec(
            "ALTER TABLE devices
             MODIFY sensor_type VARCHAR(100) NOT NULL DEFAULT 'MQ-6'"
        );

        /*
         * Um dispositivo físico continua tendo um único dono (quem
         * cadastrou e recebeu a api_key). device_shares permite que
         * OUTRAS contas também acompanhem o mesmo dispositivo, desde
         * que informem o esp32_id e a api_key corretos (ver
         * DeviceDAO::shareWithUser). Isso evita duplicar o cadastro
         * do mesmo ESP32 (o que a trave UNIQUE de esp32_id já impede)
         * e ao mesmo tempo permite várias pessoas verem o mesmo
         * sensor na feira, sem enfraquecer a segurança: quem não tem
         * a api_key não consegue vincular.
         */
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS device_shares (
                id INT AUTO_INCREMENT PRIMARY KEY,
                device_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_device_shares (device_id, user_id),
                CONSTRAINT fk_device_shares_device
                    FOREIGN KEY (device_id)
                    REFERENCES devices(id)
                    ON DELETE CASCADE
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
        ");
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

    /*
     * Um dispositivo só é considerado ONLINE se ele se comunicou
     * (leitura ou anúncio de presença) há menos de ONLINE_TIMEOUT_SECONDS.
     * Sem isso, o status ficava "preso" no último valor gravado para
     * sempre — um ESP32 desligado há semanas continuava aparecendo
     * como online no dashboard.
     */
    const ONLINE_TIMEOUT_SECONDS = 60;

    /*
     * A comparação de "há quanto tempo o dispositivo foi visto" é feita
     * pelo próprio MySQL (TIMESTAMPDIFF ... NOW()) nas consultas abaixo,
     * nunca misturando o relógio do PHP com o do banco — servidores
     * costumam ter fusos horários diferentes configurados (o PHP deste
     * projeto roda em UTC, o MySQL em horário local), e comparar os dois
     * diretamente por strtotime()/time() gerava uma diferença de horas
     * inteira, fazendo dispositivos recém-ativos aparecerem como offline.
     */
    public static function effectiveStatus($storedStatus, $secondsSinceSeen)
    {
        if ($storedStatus === 'offline') {
            return 'offline';
        }

        if ($secondsSinceSeen === null) {
            return 'offline';
        }

        return ((int) $secondsSinceSeen <= self::ONLINE_TIMEOUT_SECONDS) ? 'online' : 'offline';
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
        $row['sensor_type'] ?? 'MQ-6',
        self::effectiveStatus($row['status'] ?? 'offline', $row['seconds_since_seen'] ?? null),
        $row['firmware_version'] ?? null,
        $row['wifi_status'] ?? null,
        $row['esp32_id'] ?? null,
        $row['last_online'] ?? null,
        $row['created_at'] ?? null,
        $row['updated_at'] ?? null,
        $row['last_seen_at'] ?? null
    );
}

    public function getConnection()
    {
        return $this->conn;
    }

    public function getById($id, $userId = null)
    {
        $sql = "
            SELECT d.*, TIMESTAMPDIFF(SECOND, d.last_seen_at, NOW()) AS seconds_since_seen
            FROM devices d
            WHERE d.id = :id
        ";

        $params = [
            ':id' => $id
        ];

        if ($userId !== null) {
            $sql .= "
              AND (
                d.user_id = :user_id
                OR EXISTS (
                    SELECT 1 FROM device_shares s
                    WHERE s.device_id = d.id AND s.user_id = :user_id_share
                )
              )
            ";
            $params[':user_id'] = $userId;
            $params[':user_id_share'] = $userId;
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
            SELECT *, TIMESTAMPDIFF(SECOND, last_seen_at, NOW()) AS seconds_since_seen
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
                l.floor,
                (d.user_id = :user_id_owner) AS is_owner,
                TIMESTAMPDIFF(SECOND, d.last_seen_at, NOW()) AS seconds_since_seen
            FROM devices d
            LEFT JOIN locations l
                ON l.id = d.location_id
            WHERE d.user_id = :user_id
               OR EXISTS (
                    SELECT 1 FROM device_shares s
                    WHERE s.device_id = d.id AND s.user_id = :user_id_share
               )
            ORDER BY d.name ASC
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':user_id_owner' => $userId,
            ':user_id_share' => $userId
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['status'] = self::effectiveStatus($row['status'], $row['seconds_since_seen'] ?? null);
        }
        unset($row);

        return $rows;
    }

    /**
     * Vincula um dispositivo já existente a OUTRA conta, sem duplicar
     * o cadastro. Só deve ser chamado depois de validar a api_key do
     * dispositivo (ver api/manage.php, ação "join_device").
     */
    public function shareWithUser($deviceId, $userId)
    {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO device_shares (device_id, user_id)
            VALUES (:device_id, :user_id)
        ");

        return $stmt->execute([
            ':device_id' => $deviceId,
            ':user_id' => $userId
        ]);
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
            ':sensor_type' => $data[':sensor_type'] ?? 'MQ-6',
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
            ':sensor_type' => $data[':sensor_type'] ?? 'MQ-6',
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
        SELECT *, TIMESTAMPDIFF(SECOND, last_seen_at, NOW()) AS seconds_since_seen
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
