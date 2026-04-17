<?php
// backend/Database.php
require_once 'config.php';

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            // Nastavení vyhazování výjimek při chybě
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Aby se data vracela jako asociativní pole
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // BEZPEČNOSTNÍ ÚPRAVA: Chyba se loguje interně, nevypisuje se uživateli
            error_log("Chyba připojení k databázi: " . $exception->getMessage());
        }

        return $this->conn;
    }
}