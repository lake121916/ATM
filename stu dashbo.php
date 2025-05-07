<?php
session_start();
 require_once 'db.php';
require_once 'auth.php';
require_once 'clearance.php';
require_once 'notif.php';

$user = new User();
$clearance = new Clearance();
$notification = new Notification();

if(!$user->isLoggedIn()) {
    $user->redirect('login.php');
}

if($_SESSION['role'] != 'student') {
    $user->redirect('index.php');
}

// Get student requests
$requests = $clearance->getStudentRequests($_SESSION['user_id']);

// Get notifications
$notifications = $notification->getUserNotifications($_SESSION['user_id']);
$unread_count = $notification->getUnreadCount($_SESSION['user_id']);

// Handle new request submission
if(isset($_POST['submit_request'])) {
    $departments = isset($_POST['departments']) ? $_POST['departments'] : array();
    if(!empty($departments)) {
        $request_id = $clearance->submitRequest($_SESSION['user_id'], $departments);
        if($request_id) {
            $notification->create($_SESSION['user_id'], "Your clearance request #$request_id has been submitted successfully.");
            header("Refresh:0");
        }
    }
}

// Mark notification as read
if(isset($_GET['read_notification'])) {
    $notification->markAsRead($_GET['read_notification']);
    header("Location: student_dashboard.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Clearance Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
        }
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(25%, -25%);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="student_dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="new_request.php">
                                <i class="bi bi-plus-circle me-2"></i>New Request
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="profile.php">
                                <i class="bi bi-person me-2"></i>Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Student Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="position: relative;">
                                <i class="bi bi-bell fs-4"></i>
                                <?php if($unread_count > 0): ?>
                                    <span class="notification-badge badge rounded-pill bg-danger"><?= $unread_count ?></span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="width: 300px;">
                                <?php if(empty($notifications)): ?>
                                    <li><a class="dropdown-item">No notifications</a></li>
                                <?php else: ?>
                                    <?php foreach($notifications as $note): ?>
                                        <li>
                                            <a class="dropdown-item <?= $note['is_read'] ? '' : 'fw-bold' ?>" href="student_dashboard.php?read_notification=<?= $note['id'] ?>">
                                                <?= $note['message'] ?>
                                                <small class="text-muted d-block"><?= date('M j, Y g:i A', strtotime($note['created_at'])) ?></small>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php endforeach; ?>
                                    <li><a class="dropdown-item text-center" href="notifications.php">View All</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- New Request Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Submit New Clearance Request</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Select Departments</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="departments[]" value="Library" id="libraryCheck">
                                    <label class="form-check-label" for="libraryCheck">Library</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="departments[]" value="Cafeteria" id="cafeteriaCheck">
                                    <label class="form-check-label" for="cafeteriaCheck">Cafeteria</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="departments[]" value="Dormitory" id="dormitoryCheck">
                                    <label class="form-check-label" for="dormitoryCheck">Dormitory</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="departments[]" value="Department" id="departmentCheck">
                                    <label class="form-check-label" for="departmentCheck">Department Office</label>
                                </div>
                            </div>
                            <button type="submit" name="submit_request" class="btn btn-primary">Submit Request</button>
                        </form>
                    </div>
                </div>

                <!-- Recent Requests -->
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Clearance Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($requests)): ?>
                            <p>No clearance requests found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Departments</th>
                                            <th>Status</th>
                                            <th>Submitted On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($requests as $request): ?>
                                            <tr>
                                                <td>#<?= $request['id'] ?></td>
                                                <td><?= str_replace(",", ", ", $request['departments']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $request['status'] == 'Approved' ? 'success' : ($request['status'] == 'Rejected' ? 'danger' : 'warning') ?>">
                                                        <?= $request['status'] ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M j, Y g:i A', strtotime($request['created_at'])) ?></td>
                                                <td>
                                                    <a href="request_details.php?id=<?= $request['id'] ?>" class="btn btn-sm btn-info">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>