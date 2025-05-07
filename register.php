<?php
session_start();
require_once 'db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $username = trim($_POST['username'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $dob = $_POST['dob'] ?? '';

    // Input validation
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($password) || strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if (!in_array($role, ['student', 'staff', 'admin'])) $errors[] = "Invalid role selected";
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($phone_number)) $errors[] = "Phone number is required";
    if (empty($dob)) $errors[] = "Date of birth is required";

    // Profile picture handling
    $profile_pic_name = '';
    if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                $errors[] = "Failed to create upload directory";
            }
        }

        if (empty($errors)) {
            $file_ext = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
            $profile_pic_name = uniqid("img_") . "." . $file_ext;
            $target_file = $target_dir . $profile_pic_name;

            $valid_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_ext, $valid_extensions)) {
                $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed.";
            } elseif ($_FILES["profile_picture"]["size"] > 5000000) {
                $errors[] = "File is too large. Maximum size is 5MB.";
            } elseif (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                $errors[] = "Error uploading profile picture.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $db = (new Database())->getConnection();

            // Check if username or email exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $errors[] = "Username or email already exists.";
            } else {
                $stmt->close();

                // Insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, role, first_name, last_name, phone_number, date_of_birth, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $db->error);
                }
                
                $stmt->bind_param("sssssssss", $username, $email, $hashed_password, $role, $first_name, $last_name, $phone_number, $dob, $profile_pic_name);

                if ($stmt->execute()) {
                    $success = "Account created successfully. <a href='login.php'>Login here</a>.";
                } else {
                    $errors[] = "Error: " . $stmt->error;
                }
            }
            
            $stmt->close();
            $db->close();
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Clearance Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .register-container {
            max-width: 600px;
            margin: 0 auto;
            margin-top: 50px;
        }
        .error-message {
            color: red;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="register-container">
        <div class="card">
            <div class="card-header text-center">
                <h4>Sign Up - Clearance Management System</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?= implode('<br>', $errors) ?>
                    </div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success">
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control" required
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required
                               minlength="8">
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="">Select role</option>
                            <option value="student" <?= ($_POST['role'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                            <option value="staff" <?= ($_POST['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control" required
                                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control" required
                                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="tel" name="phone_number" id="phone_number" class="form-control" required
                               value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" name="dob" id="dob" class="form-control" required
                               value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>"
                               max="<?= date('Y-m-d', strtotime('-13 years')) ?>">
                        <small class="text-muted">You must be at least 13 years old</small>
                    </div>

                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Profile Picture</label>
                        <input type="file" name="profile_picture" id="profile_picture" class="form-control"
                               accept="image/jpeg, image/png, image/gif">
                        <small class="text-muted">Optional. Max 5MB. JPG, PNG or GIF only.</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">Create Account</button>
                    </div>

                    <div class="text-center mt-3">
                        <a href="login.php">Already have an account? Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>