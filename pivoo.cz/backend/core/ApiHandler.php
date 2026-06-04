<?php

require_once __DIR__ . '/ApiResponse.php';
require_once __DIR__ . '/ApiRequest.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../JwtHandler.php';

class ApiHandler {
    public $response;
    public $request;
    public $db;

    public function __construct() {
        // Inicializace zpracování odpovědí a hlaviček
        $this->response = new ApiResponse();
        
        // Inicializace zpracování vstupních dat
        $this->request = new ApiRequest();
        
        // Inicializace připojení k databázi
        try {
            $database = new Database();
            $this->db = $database->getConnection();
        } catch (PDOException $e) {
            error_log("DB Connection Error (ApiHandler): " . $e->getMessage());
            $this->response->sendError("Vnitřní chyba serveru při komunikaci s databází.", 500);
        }
    }

    // Wrapper pro ověření admina (využívá existující logiku JwtHandleru)
    public function requireAdmin() {
        return JwtHandler::checkAdmin();
    }
}
?>