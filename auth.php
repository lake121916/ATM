<?php
require_once 'db.php';

class User {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            return true;
        }

        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function redirect($url) {
        header("Location: $url");
        exit;
    }

    public function getDashboardUrl() {
        switch ($_SESSION['role']) {
            case 'admin':
                return 'admin_dashboard.php';
            case 'student':
                return 'student_dashboard.php';
            case 'staff':
                return 'staff_dashboard.php';
            default:
                return 'login.php';
        }
    }
}
