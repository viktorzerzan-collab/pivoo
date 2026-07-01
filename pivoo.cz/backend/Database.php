<?php
// backend/Database.php
require_once 'config.php';

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    
    // Statická proměnná pro sdílení jediné instance připojení (Singleton pattern)
    private static $sharedConnection = null;
    public $conn;

    public function getConnection() {
        // Pokud spojení pro tento požadavek už existuje, rovnou ho vrátíme (šetříme výkon serveru)
        if (self::$sharedConnection !== null) {
            $this->conn = self::$sharedConnection;
            return $this->conn;
        }

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
            
            // Uložíme připojení pro případná další volání v rámci téhož skriptu
            self::$sharedConnection = $this->conn;
            
        } catch(PDOException $exception) {
            // Chyba se loguje interně, nevypisuje se uživateli
            error_log("Chyba připojení k databázi: " . $exception->getMessage());
            // ZMĚNA: Vyhodíme výjimku výš, aby ji dokázal odchytit náš nový ApiHandler
            throw $exception; 
        }

        return $this->conn;
    }
}
?>