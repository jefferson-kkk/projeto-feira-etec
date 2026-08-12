<?php
require_once __DIR__ . '/../model/LoginDAO.php';
require_once __DIR__ . '/../model/Login.php';

class LoginController{
    private $LoginDAO;

    public function __construct(){
        $this->LoginDAO = new LoginDAO();
    }

    public function criarLogin($nome, $senha, $email, $datacriacao, $avatar = null){
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $login = new Login($nome, $senhaHash, $email, $datacriacao, $avatar, null);
        $this->LoginDAO->criarLogin($login);
        return $this->LoginDAO->getLoginByEmail($email);
    }

    public function lerLogin(){
        return $this->LoginDAO->lerLogin();
    }

    public function getLoginById($id){
        return $this->LoginDAO->getLoginById($id);
    }

    public function getLoginByEmail($email){
        return $this->LoginDAO->getLoginByEmail($email);
    }

    public function atualizarLogin($id, $nome, $senha, $email, $datacriacao, $avatar = null){
        $login = new Login($nome, $senha, $email, $datacriacao, $avatar, $id);
        $this->LoginDAO->atualizarLogin($login);
    }

    public function atualizarPerfil(Login $login){
        $this->LoginDAO->updateProfile($login);
        return $this->LoginDAO->getLoginById($login->getid());
    }

    public function atualizarSenha($id, $senha){
        $this->LoginDAO->updatePassword($id, password_hash($senha, PASSWORD_DEFAULT));
    }

    public function atualizarUltimoLogin($id){
        $this->LoginDAO->updateLastLogin($id);
    }

    public function atualizarNotificacoes($id, array $prefs){
        $this->LoginDAO->updateNotifications($id, $prefs);
        return $this->LoginDAO->getLoginById($id);
    }

    public function deletarLogin($id){
        $this->LoginDAO->deletarLogin($id);
    }
}
