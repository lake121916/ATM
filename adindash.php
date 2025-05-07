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

if($_SESSION['role'] != 'admin') {
    $user->redirect('index.php');
}

// Get admin's department (simplified for example)
$admin_department = "Library"; // This would normally come from the database

// Get pending requests for admin's department
$pending_requests = $clearance->getPendingRequests($admin_department);

// Handle request approval/rejection
if(isset($_POST['process_request'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $comments = $_POST['comments'] ?? '';
    
    if($clearance->updateRequestStatus($request_id, $action, $admin_department, $comments)) {
        $request = $clearance->getRequest($request_id);
        $notification->create($request['student_id'], "Your clearance request #$request_id has been $action by $admin_department.");
        header("Refresh:0");
    }
}

// Get notifications
$notifications = $notification->getUserNotifications($_SESSION['user_id']);
$unread_count = $notification->getUnreadCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Clearance Management System</title>
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
                            <a class="nav-link active text-white" href="admin_dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manage_requests.php">
                                <i class="bi bi-list-check me-2"></i>Manage Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="reports.php">
                                <i class="bi bi-file-earmark-bar-graph me-2"></i>Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="violations.php">
                                <i class="bi bi-exclamation-octagon me-2"></i>Violations
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
                    <h1 class="h2">Admin Dashboard - <?= $admin_department ?></h1>
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
                                            <a class="dropdown-item <?= $note['is_read'] ? '' : 'fw-bold' ?>" href="admin_dashboard.php?read_notification=<?= $note['id'] ?>">
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

                <!-- Pending Requests -->
                <div class="card">
                    <div class="card-header">
                        <h5>Pending Clearance Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($pending_requests)): ?>
                            <p>No pending clearance requests for your department.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Student</th>
                                            <th>Departments</th>
                                            <th>Submitted On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($pending_requests as $request): ?>
                                            <tr>
                                                <td>#<?= $request['id'] ?></td>
                                                <td><?= $request['student_name'] ?></td>
                                                <td><?= str_replace(",", ", ", $request['departments']) ?></td>
                                                <td><?= date('M j, Y g:i A', strtotime($request['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#processModal<?= $request['id'] ?>">
                                                        Process
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Process Modal -->
                                            <div class="modal fade" id="processModal<?= $request['id'] ?>" tabindex="-1" aria-labelledby="processModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="processModalLabel">Process Request #<?= $request['id'] ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Action</label>
                                                                    <select class="form-select" name="action" required>
                                                                        <option value="">Select action</option>
                                                                        <option value="Approved">Approve</option>
                                                                        <option value="Rejected">Reject</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Comments</label>
                                                                    <textarea class="form-control" name="comments" rows="3"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" name="process_request" class="btn btn-primary">Submit</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
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