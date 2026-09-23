<?php

class Connection{
    private static $instance = null;

    public static function getConnection(){
        return self::getInstance();
    }

    public static function getInstance(){
        if(!self::$instance){
            try{
                // 127.0.0.1 em vez de 'localhost': no Windows, resolver
                // o nome 'localhost' via getaddrinfo() pode levar ~2s
                // (tenta IPv6 antes de cair para IPv4), e isso acontecia
                // em TODA conexao com o banco -- ou seja, em quase toda
                // requisicao do site. Usar o IP literal pula essa
                // resolucao e liga em menos de 2ms.
                $host = '127.0.0.1';
                $dbname = 'aeris';
                $username = 'root';
                $password = getenv('AERIS_DB_PASSWORD') ?: 'senaisp';

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