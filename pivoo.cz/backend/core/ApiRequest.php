<?php

class ApiRequest {
    private $data;

    public function __construct() {
        $this->parseRequest();
    }

    private function parseRequest() {
        // Podpora pro FormData (např. při nahrávání obrázků přes $_POST) i pro standardní JSON payload
        if (!empty($_POST)) {
            $this->data = (object) $_POST;
        } else {
            $payload = json_decode(file_get_contents("php://input"));
            $this->data = $payload !== null ? $payload : new stdClass();
        }
    }

    // Bezpečné získání textové hodnoty
    public function getParam($key, $default = null) {
        if (isset($this->data->$key) && $this->data->$key !== '') {
            return $this->data->$key;
        }
        return $default;
    }

    // Bezpečné získání celého čísla
    public function getIntParam($key, $default = null) {
        $val = $this->getParam($key);
        return $val !== null ? (int)$val : $default;
    }

    // Bezpečné získání desetinného čísla (např. pro lat a lng)
    public function getFloatParam($key, $default = null) {
        $val = $this->getParam($key);
        return $val !== null ? (float)$val : $default;
    }

    // Bezpečné získání boolean hodnoty (např. is_unfiltered)
    public function getBoolParam($key, $default = false) {
        if (isset($this->data->$key)) {
            if ($this->data->$key === 'true' || $this->data->$key === 1 || $this->data->$key === '1' || $this->data->$key === true) {
                return 1;
            }
            if ($this->data->$key === 'false' || $this->data->$key === 0 || $this->data->$key === '0' || $this->data->$key === false) {
                return 0;
            }
        }
        return $default ? 1 : 0;
    }

    // Bezpečné získání souboru
    public function getFile($key) {
        return isset($_FILES[$key]) ? $_FILES[$key] : null;
    }
}
?>