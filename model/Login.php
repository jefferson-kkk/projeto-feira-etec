<?php

class Login {
    private $id;
    private $nome;
    private $senha;
    private $email;
    private $username;
    private $displayName;
    private $avatar;
    private $phone;
    private $biography;
    private $language;
    private $timezone;
    private $lastLogin;
    private $emailVerified;
    private $notifyEmail;
    private $notifySecurity;
    private $notifySystem;
    private $notifyProject;
    private $notifyAlerts;
    private $datacriacao;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $nome,
        $senha,
        $email,
        $datacriacao,
        $avatar = null,
        $id = null,
        $username = null,
        $displayName = null,
        $phone = null,
        $biography = null,
        $language = 'en',
        $timezone = 'UTC',
        $lastLogin = null,
        $emailVerified = false,
        $notifyEmail = true,
        $notifySecurity = true,
        $notifySystem = true,
        $notifyProject = true,
        $notifyAlerts = true,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->nome = $nome;
        $this->senha = $senha;
        $this->email = $email;
        $this->avatar = $avatar;
        $this->username = $username ?? $this->makeUsername($email);
        $this->displayName = $displayName ?? $nome;
        $this->phone = $phone;
        $this->biography = $biography;
        $this->language = $language;
        $this->timezone = $timezone;
        $this->lastLogin = $lastLogin;
        $this->emailVerified = (bool) $emailVerified;
        $this->notifyEmail = (bool) $notifyEmail;
        $this->notifySecurity = (bool) $notifySecurity;
        $this->notifySystem = (bool) $notifySystem;
        $this->notifyProject = (bool) $notifyProject;
        $this->notifyAlerts = (bool) $notifyAlerts;
        $this->datacriacao = $datacriacao;
        $this->createdAt = $createdAt ?? $datacriacao;
        $this->updatedAt = $updatedAt ?? $datacriacao;
    }

    private function makeUsername($email) {
        $parts = explode('@', $email);
        $raw = strtolower($parts[0] ?? $email);
        $username = preg_replace('/[^a-z0-9._-]+/', '', $raw);
        if ($username === '') {
            $username = 'user' . rand(1000, 9999);
        }
        return substr($username, 0, 40);
    }

    public function getid() { return $this->id; }
    public function getnome() { return $this->nome; }
    public function getsenha() { return $this->senha; }
    public function getemail() { return $this->email; }
    public function getusername() { return $this->username; }
    public function getdisplayName() { return $this->displayName; }
    public function getavatar() { return $this->avatar; }
    public function getphone() { return $this->phone; }
    public function getbiography() { return $this->biography; }
    public function getlanguage() { return $this->language; }
    public function gettimezone() { return $this->timezone; }
    public function getLastLogin() { return $this->lastLogin; }
    public function getemailVerified() { return $this->emailVerified; }
    public function getnotifyEmail() { return $this->notifyEmail; }
    public function getnotifySecurity() { return $this->notifySecurity; }
    public function getnotifySystem() { return $this->notifySystem; }
    public function getnotifyProject() { return $this->notifyProject; }
    public function getnotifyAlerts() { return $this->notifyAlerts; }
    public function getdatacriacao() { return $this->datacriacao; }
    public function getcreatedAt() { return $this->createdAt; }
    public function getupdatedAt() { return $this->updatedAt; }

    public function setid($id) { $this->id = $id; return $this; }
    public function setnome($nome) { $this->nome = $nome; return $this; }
    public function setsenha($senha) { $this->senha = $senha; return $this; }
    public function setemail($email) { $this->email = $email; return $this; }
    public function setusername($username) { $this->username = $username; return $this; }
    public function setdisplayName($displayName) { $this->displayName = $displayName; return $this; }
    public function setavatar($avatar) { $this->avatar = $avatar; return $this; }
    public function setphone($phone) { $this->phone = $phone; return $this; }
    public function setbiography($biography) { $this->biography = $biography; return $this; }
    public function setlanguage($language) { $this->language = $language; return $this; }
    public function settimezone($timezone) { $this->timezone = $timezone; return $this; }
    public function setLastLogin($lastLogin) { $this->lastLogin = $lastLogin; return $this; }
    public function setemailVerified($emailVerified) { $this->emailVerified = (bool) $emailVerified; return $this; }
    public function setnotifyEmail($notifyEmail) { $this->notifyEmail = (bool) $notifyEmail; return $this; }
    public function setnotifySecurity($notifySecurity) { $this->notifySecurity = (bool) $notifySecurity; return $this; }
    public function setnotifySystem($notifySystem) { $this->notifySystem = (bool) $notifySystem; return $this; }
    public function setnotifyProject($notifyProject) { $this->notifyProject = (bool) $notifyProject; return $this; }
    public function setnotifyAlerts($notifyAlerts) { $this->notifyAlerts = (bool) $notifyAlerts; return $this; }
    public function setdatacriacao($datacriacao) { $this->datacriacao = $datacriacao; return $this; }
    public function setcreatedAt($createdAt) { $this->createdAt = $createdAt; return $this; }
    public function setupdatedAt($updatedAt) { $this->updatedAt = $updatedAt; return $this; }
}
?>
