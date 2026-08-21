<?php
require_once __DIR__ . '/Connection.php';
require_once __DIR__ . '/Login.php';

class LoginDAO {
    private $conn;

    public function __construct() {
        $this->conn = Connection::getConnection();

        $this->conn->exec("CREATE TABLE IF NOT EXISTS login (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            senha VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            username VARCHAR(255) NOT NULL UNIQUE,
            display_name VARCHAR(255) DEFAULT NULL,
            avatar VARCHAR(255) DEFAULT NULL,
            phone VARCHAR(32) DEFAULT NULL,
            biography TEXT DEFAULT NULL,
            language VARCHAR(16) NOT NULL DEFAULT 'en',
            timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
            last_login DATETIME DEFAULT NULL,
            email_verified TINYINT(1) NOT NULL DEFAULT 0,
            notify_email TINYINT(1) NOT NULL DEFAULT 1,
            notify_security TINYINT(1) NOT NULL DEFAULT 1,
            notify_system TINYINT(1) NOT NULL DEFAULT 1,
            notify_project TINYINT(1) NOT NULL DEFAULT 1,
            notify_alerts TINYINT(1) NOT NULL DEFAULT 1,
            datacriacao DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $this->ensureColumn('login', 'avatar', "VARCHAR(255) DEFAULT NULL");
        $this->ensureColumn('login', 'username', "VARCHAR(255) DEFAULT NULL");
        $this->ensureColumn('login', 'display_name', "VARCHAR(255) DEFAULT NULL");
        $this->ensureColumn('login', 'phone', "VARCHAR(32) DEFAULT NULL");
        $this->ensureColumn('login', 'biography', "TEXT DEFAULT NULL");
        $this->ensureColumn('login', 'language', "VARCHAR(16) NOT NULL DEFAULT 'en'");
        $this->ensureColumn('login', 'timezone', "VARCHAR(64) NOT NULL DEFAULT 'UTC'");
        $this->ensureColumn('login', 'last_login', "DATETIME DEFAULT NULL");
        $this->ensureColumn('login', 'email_verified', "TINYINT(1) NOT NULL DEFAULT 0");
        $this->ensureColumn('login', 'notify_email', "TINYINT(1) NOT NULL DEFAULT 1");
        $this->ensureColumn('login', 'notify_security', "TINYINT(1) NOT NULL DEFAULT 1");
        $this->ensureColumn('login', 'notify_system', "TINYINT(1) NOT NULL DEFAULT 1");
        $this->ensureColumn('login', 'notify_project', "TINYINT(1) NOT NULL DEFAULT 1");
        $this->ensureColumn('login', 'notify_alerts', "TINYINT(1) NOT NULL DEFAULT 1");
        $this->ensureColumn('login', 'created_at', "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        $this->ensureColumn('login', 'updated_at', "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->backfillUsernames();
        $this->ensureUniqueIndex('login', 'unique_login_username', 'username');
    }

    private function ensureColumn($table, $column, $definition) {
        $stmt = $this->conn->query("SHOW COLUMNS FROM {$table} LIKE " . $this->conn->quote($column));
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->conn->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function ensureUniqueIndex($table, $indexName, $column) {
        $stmt = $this->conn->query("SHOW INDEX FROM {$table} WHERE Key_name = " . $this->conn->quote($indexName));
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return;
        }

        try {
            $this->conn->exec("ALTER TABLE {$table} ADD UNIQUE KEY {$indexName} ({$column})");
        } catch (PDOException $e) {
            // Keep the app usable even if legacy duplicated usernames need manual review.
        }
    }

    private function backfillUsernames() {
        $stmt = $this->conn->query("SELECT id, email FROM login WHERE username IS NULL OR username = ''");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $update = $this->conn->prepare("UPDATE login SET username = :username WHERE id = :id");

        foreach ($users as $user) {
            $base = $this->usernameFromEmail($user['email']);
            $username = $base;
            $suffix = 1;

            while ($this->usernameExists($username, $user['id'])) {
                $username = substr($base, 0, 34) . '-' . $suffix;
                $suffix++;
            }

            $update->execute([':username' => $username, ':id' => $user['id']]);
        }
    }

    private function usernameFromEmail($email) {
        $parts = explode('@', (string) $email);
        $raw = strtolower($parts[0] ?? 'user');
        $username = preg_replace('/[^a-z0-9._-]+/', '', $raw);
        return $username !== '' ? substr($username, 0, 40) : 'user' . random_int(1000, 9999);
    }

    private function usernameExists($username, $ignoreId = null) {
        $sql = "SELECT COUNT(*) FROM login WHERE username = :username";
        $params = [':username' => $username];

        if ($ignoreId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $ignoreId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);    
        return (int) $stmt->fetchColumn() > 0;
    }

    private function uniqueUsername($username, $ignoreId = null) {
        $base = substr($username ?: 'user', 0, 40);
        $candidate = $base;
        $suffix = 1;

        while ($this->usernameExists($candidate, $ignoreId)) {
            $candidate = substr($base, 0, 34) . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

 public function criarLogin(Login $login) {
    $login->setusername($this->uniqueUsername($login->getusername()));

    $stmt = $this->conn->prepare(
        "INSERT INTO login(
            nome, senha, email, username, display_name, avatar, phone, biography,
            language, timezone, last_login, email_verified,
            notify_email, notify_security, notify_system, notify_project, notify_alerts,
            datacriacao, created_at, updated_at
        ) VALUES (
            :nome, :senha, :email, :username, :display_name, :avatar, :phone, :biography,
            :language, :timezone, :last_login, :email_verified,
            :notify_email, :notify_security, :notify_system, :notify_project, :notify_alerts,
            :datacriacao, :created_at, :updated_at
        )"
    );

    $stmt->execute([
        ':nome' => $login->getnome(),
        ':senha' => $login->getsenha(),
        ':email' => $login->getemail(),
        ':username' => $login->getusername(),
        ':display_name' => $login->getdisplayName(),
        ':avatar' => $login->getavatar(),
        ':phone' => $login->getphone(),
        ':biography' => $login->getbiography(),
        ':language' => $login->getlanguage() ?: 'en',
        ':timezone' => $login->gettimezone() ?: 'UTC',
        ':last_login' => $login->getLastLogin(),

        ':email_verified' => 0,
        ':notify_email' => 1,
        ':notify_security' => 1,
        ':notify_system' => 1,
        ':notify_project' => 1,
        ':notify_alerts' => 1,

        ':datacriacao' => $login->getdatacriacao(),
        ':created_at' => date('Y-m-d H:i:s'),
        ':updated_at' => date('Y-m-d H:i:s')
    ]);
}

    public function lerLogin() {
        $stmt = $this->conn->query("SELECT * FROM login ORDER BY nome");
        $result = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = new Login(
                $row['nome'],
                $row['senha'],
                $row['email'],
                $row['datacriacao'],
                $row['avatar'],
                $row['id'],
                $row['username'],
                $row['display_name'],
                $row['phone'],
                $row['biography'],
                $row['language'],
                $row['timezone'],
                $row['last_login'],
                $row['email_verified'],
                $row['notify_email'],
                $row['notify_security'],
                $row['notify_system'],
                $row['notify_project'],
                $row['notify_alerts'],
                $row['created_at'],
                $row['updated_at']
            );
        }

        return $result;
    }

    public function atualizarLogin(Login $login) {
        $stmt = $this->conn->prepare(
            "UPDATE login SET nome = :nome, senha = :senha, email = :email, username = :username,
                display_name = :display_name, avatar = :avatar, phone = :phone, biography = :biography,
                language = :language, timezone = :timezone, last_login = :last_login,
                email_verified = :email_verified, notify_email = :notify_email,
                notify_security = :notify_security, notify_system = :notify_system,
                notify_project = :notify_project, notify_alerts = :notify_alerts,
                datacriacao = :datacriacao
            WHERE id = :id"
        );

        $stmt->execute([
            ':nome' => $login->getnome(),
            ':senha' => $login->getsenha(),
            ':email' => $login->getemail(),
            ':username' => $login->getusername(),
            ':display_name' => $login->getdisplayName(),
            ':avatar' => $login->getavatar(),
            ':phone' => $login->getphone(),
            ':biography' => $login->getbiography(),
            ':language' => $login->getlanguage(),
            ':timezone' => $login->gettimezone(),
            ':last_login' => $login->getLastLogin(),
            ':email_verified' => $login->getemailVerified(),
            ':notify_email' => $login->getnotifyEmail(),
            ':notify_security' => $login->getnotifySecurity(),
            ':notify_system' => $login->getnotifySystem(),
            ':notify_project' => $login->getnotifyProject(),
            ':notify_alerts' => $login->getnotifyAlerts(),
            ':datacriacao' => $login->getdatacriacao(),
            ':id' => $login->getid()
        ]);
    }

    public function updateProfile(Login $login) {
        $login->setusername($this->uniqueUsername($login->getusername(), $login->getid()));
        $stmt = $this->conn->prepare(
            "UPDATE login SET nome = :nome, email = :email, username = :username,
                display_name = :display_name, avatar = :avatar, phone = :phone,
                biography = :biography, language = :language, timezone = :timezone,
                updated_at = NOW()
            WHERE id = :id"
        );

        $stmt->execute([
            ':nome' => $login->getnome(),
            ':email' => $login->getemail(),
            ':username' => $login->getusername(),
            ':display_name' => $login->getdisplayName(),
            ':avatar' => $login->getavatar(),
            ':phone' => $login->getphone(),
            ':biography' => $login->getbiography(),
            ':language' => $login->getlanguage(),
            ':timezone' => $login->gettimezone(),
            ':id' => $login->getid()
        ]);
    }

    public function updatePassword($id, $senha) {
        $stmt = $this->conn->prepare("UPDATE login SET senha = :senha, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':senha' => $senha, ':id' => $id]);
    }

    public function updateLastLogin($id) {
        $stmt = $this->conn->prepare("UPDATE login SET last_login = NOW(), updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function updateNotifications($id, array $prefs) {
        $stmt = $this->conn->prepare(
            "UPDATE login SET
                notify_email = :notify_email,
                notify_security = :notify_security,
                notify_system = :notify_system,
                notify_project = :notify_project,
                notify_alerts = :notify_alerts,
                updated_at = NOW()
            WHERE id = :id"
        );

        $stmt->execute([
            ':notify_email' => $prefs['notify_email'],
            ':notify_security' => $prefs['notify_security'],
            ':notify_system' => $prefs['notify_system'],
            ':notify_project' => $prefs['notify_project'],
            ':notify_alerts' => $prefs['notify_alerts'],
            ':id' => $id
        ]);
    }

    public function deletarLogin($id) {
        $stmt = $this->conn->prepare("DELETE FROM login WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function getLoginById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM login WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return new Login(
                $row['nome'],
                $row['senha'],
                $row['email'],
                $row['datacriacao'],
                $row['avatar'],
                $row['id'],
                $row['username'],
                $row['display_name'],
                $row['phone'],
                $row['biography'],
                $row['language'],
                $row['timezone'],
                $row['last_login'],
                $row['email_verified'],
                $row['notify_email'],
                $row['notify_security'],
                $row['notify_system'],
                $row['notify_project'],
                $row['notify_alerts'],
                $row['created_at'],
                $row['updated_at']
            );
        }

        return null;
    }

    public function getLoginByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM login WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return new Login(
                $row['nome'],
                $row['senha'],
                $row['email'],
                $row['datacriacao'],
                $row['avatar'],
                $row['id'],
                $row['username'],
                $row['display_name'],
                $row['phone'],
                $row['biography'],
                $row['language'],
                $row['timezone'],
                $row['last_login'],
                $row['email_verified'],
                $row['notify_email'],
                $row['notify_security'],
                $row['notify_system'],
                $row['notify_project'],
                $row['notify_alerts'],
                $row['created_at'],
                $row['updated_at']
            );
        }

        return null;
    }
}
?>
