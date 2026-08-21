<?php

class Connection{
    private static $instance = null;

    public static function getConnection(){
        return self::getInstance();
    }

    public static function getInstance(){
        if(!self::$instance){
            try{
                $host = 'localhost';
                $dbname = 'aeris';
                $username = 'root';
                $password = ''; // XAMPP padrão sem senha

                $dsn = "mysql:host=$host;charset=utf8mb4";
                self::$instance = new PDO($dsn, $username, $password);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

                $sqlcreate = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
                self::$instance->exec($sqlcreate);
                self::$instance->exec("USE `$dbname`");
            } catch(PDOException $e){
                throw new Exception('Erro ao conectar no MySQL: ' . $e->getMessage());
            }

        }
        return self::$instance;
    }
}
?>