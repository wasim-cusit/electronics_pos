<?php
/**
 * Test Notifications System
 * This file tests the notification functionality
 */

require_once 'includes/config.php';
require_once 'includes/settings.php';
require_once 'includes/notifications.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Please log in first to test notifications.");
}

$user_id = $_SESSION['user_id'];

// Handle test notification creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'info';
    $message = $_POST['message'] ?? 'Test notification message';
    $title = $_POST['title'] ?? 'Test Notification';
    
    if (create_notification($user_id, $type, $message, $title)) {
        $success = "Test notification created successfully!";
    } else {
        $error = "Failed to create test notification.";
    }
}

// Get user's notifications
$notifications = get_user_notifications($user_id, 10, 0, false);
$unread_count = get_unread_notification_count($user_id);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-bell me-2"></i>Test Notifications System</h2>
                <a href="notifications.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Notifications
                </a>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Test Notification Form -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create Test Notification</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Notification Type</label>
                                <select name="type" class="form-control" required>
                                    <option value="info">Info</option>
                                    <option value="success">Success</option>
                                    <option value="warning">Warning</option>
                                    <option value="error">Error</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Title</label>
                                <input type="text" name="title" class="form-control" value="Test Notification" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Message</label>
                                <input type="text" name="message" class="form-control" value="This is a test notification message" required>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-bell me-1"></i>Create Test Notification
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Test Buttons -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Test Notifications</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="type" value="info">
                                <input type="hidden" name="title" value="Information Alert">
                                <input type="hidden" name="message" value="This is an informational notification for testing purposes.">
                                <button type="submit" class="btn btn-info w-100">Test Info</button>
                            </form>
                        </div>
                        <div class="col-md-3 mb-2">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="type" value="success">
                                <input type="hidden" name="title" value="Success Message">
                                <input type="hidden" name="message" value="Operation completed successfully! This is a test notification.">
                                <button type="submit" class="btn btn-success w-100">Test Success</button>
                            </form>
                        </div>
                        <div class="col-md-3 mb-2">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="type" value="warning">
                                <input type="hidden" name="title" value="Warning Alert">
                                <input type="hidden" name="message" value="Please be careful! This is a warning notification test.">
                                <button type="submit" class="btn btn-warning w-100">Test Warning</button>
                            </form>
                        </div>
                        <div class="col-md-3 mb-2">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="type" value="error">
                                <input type="hidden" name="title" value="Error Alert">
                                <input type="hidden" name="message" value="Something went wrong! This is an error notification test.">
                                <button type="submit" class="btn btn-danger w-100">Test Error</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Business Notifications -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-briefcase me-2"></i>Test Business Notifications</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="type" value="warning">
                                <input type="hidden" name="title" value="Low Stock Alert">
                                <input type="hidden" name="message" value="Low stock alert: Test Product stock is 5 (threshold: 10)">
                                <button type="submit" class="btn btn-warning w-100">Test Low Stock</button>
                            </form>
                        </div>
                        <div class="col-md-4 mb-2">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="type" value="info">
                                <input type="hidden" name="title" value="Payment Reminder">
                                <input type="hidden" name="message" value="Payment reminder: Test Customer owes 500 due on 2025-12-31">
                                <button type="submit" class="btn btn-info w-100">Test Payment Reminder</button>
                            </form>
                        </div>
                        <div class="col-md-4 mb-2">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="type" value="info">
                                <input type="hidden" name="title" value="Order Status Update">
                                <input type="hidden" name="message" value="Order TEST-001 status changed from Pending to Completed">
                                <button type="submit" class="btn btn-success w-100">Test Order Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Notifications Status -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-list me-2"></i>Current Notifications Status</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Statistics</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Total Notifications:</span>
                                    <span class="badge bg-primary"><?= count($notifications) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Unread Notifications:</span>
                                    <span class="badge bg-warning text-dark"><?= $unread_count ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Read Notifications:</span>
                                    <span class="badge bg-success"><?= count($notifications) - $unread_count ?></span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Recent Notifications</h6>
                            <?php if (!empty($notifications)): ?>
                                <div class="list-group">
                                    <?php foreach (array_slice($notifications, 0, 5) as $n): ?>
                                        <div class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?= htmlspecialchars($n['title'] ?: 'No Title') ?></h6>
                                                <small class="text-muted"><?= date('H:i', strtotime($n['created_at'])) ?></small>
                                            </div>
                                            <p class="mb-1"><?= htmlspecialchars($n['message']) ?></p>
                                            <small class="text-muted">
                                                Type: <span class="badge bg-<?= get_notification_type_color($n['type']) ?>"><?= ucfirst($n['type']) ?></span>
                                                Status: <?= $n['is_read'] ? '<span class="badge bg-success">Read</span>' : '<span class="badge bg-warning text-dark">Unread</span>' ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No notifications found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Auto-refresh page every 10 seconds to show new notifications
setInterval(() => {
    location.reload();
}, 10000);
</script>

<?php include 'includes/footer.php'; ?>
