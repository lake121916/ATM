
// Add this method to the User class in auth.php
public function getDashboardUrl() {
    if(!$this->isLoggedIn()) return 'login.php';
    
    switch($_SESSION['role']) {
        case 'student':
            return 'student_dashboard.php';
        case 'admin':
            return 'admin_dashboard.php';
        case 'academic_staff':
            return 'academic_dashboard.php';
        default:
            return 'index.php';
    }
}