<?php
include 'db.php';

class Clearance {
    private $db;
    private $table = 'clearance_requests';

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // Submit a new clearance request
    public function submitRequest($student_id, $departments = array()) {
        $departments_str = implode(",", $departments);
        $status = "Pending";

        $query = "INSERT INTO " . $this->table . " (student_id, departments, status) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("sss", $student_id, $departments_str, $status);

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    // Get clearance requests for a student
    public function getStudentRequests($student_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE student_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get request by ID
    public function getRequest($request_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Update request status
    public function updateRequestStatus($request_id, $status, $department, $comments = "") {
        $request = $this->getRequest($request_id);
        $approval_history = json_decode($request['approval_history'], true) ?? [];

        // Add new approval entry
        $approval_history[] = [
            'department' => $department,
            'status' => $status,
            'comments' => $comments,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Check if all departments have approved
        $all_approved = true;
        $request_departments = explode(",", $request['departments']);
        foreach ($request_departments as $dept) {
            $dept_approved = false;
            foreach ($approval_history as $approval) {
                if ($approval['department'] == $dept && $approval['status'] == 'Approved') {
                    $dept_approved = true;
                    break;
                }
            }
            if (!$dept_approved) {
                $all_approved = false;
                break;
            }
        }

        $new_status = $all_approved ? 'Approved' : 'Pending';
        $approval_json = json_encode($approval_history);

        $query = "UPDATE " . $this->table . " SET status = ?, approval_history = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssi", $new_status, $approval_json, $request_id);
        return $stmt->execute();
    }

    // Get pending requests for a department
    public function getPendingRequests($department) {
        $query = "SELECT r.*, u.username as student_name 
                  FROM " . $this->table . " r
                  JOIN users u ON r.student_id = u.id
                  WHERE r.status = 'Pending' AND r.departments LIKE ?";
        $stmt = $this->db->prepare($query);
        $department_param = "%" . $department . "%";
        $stmt->bind_param("s", $department_param);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
