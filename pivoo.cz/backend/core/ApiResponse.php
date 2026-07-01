<?php

class ApiResponse {
    public function __construct() {
        $this->setCorsHeaders();
    }

    private function setCorsHeaders() {
        // Přísnější CORS politika, povolen pouze přístup z vlastní domény
        header("Access-Control-Allow-Origin: https://www.pivoo.cz");
        header("Content-Type: application/json; charset=UTF-8");
        // Rozšířeno o GET, PUT, DELETE pro pokrytí všech typů vašich endpointů
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

        // Zpracování preflight requestu
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    public function sendSuccess($message, $additionalData = [], $code = 200) {
        http_response_code($code);
        $response = array_merge(["status" => "success", "message" => $message], $additionalData);
        echo json_encode($response);
        exit();
    }

    public function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode(["status" => "error", "message" => $message]);
        exit();
    }
}
?>