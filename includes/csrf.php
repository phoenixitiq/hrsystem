<?php
class CSRF {
    private static $instance = null;
    private $token;

    private function __construct() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->token = $_SESSION['csrf_token'];
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getToken() {
        return $this->token;
    }

    public function verifyToken($token) {
        if (empty($token)) {
            return false;
        }
        return hash_equals($this->token, $token);
    }

    public function generateInput() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($this->token) . '">';
    }

    public function validateRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !$this->verifyToken($_POST['csrf_token'])) {
                throw new Exception('رمز CSRF غير صالح');
            }
        }
    }
} 