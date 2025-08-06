<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'notifications';

// Mark as read
if (isset($_GET['read'])) {
    $id = intval($_GET['read']);
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: notifications.php");
    exit;
}

// Fetch notifications
$notifications = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>  
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">Notifications</h2>
            <div class="card">
                <div class="card-header">All Notifications</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $n): ?>
                                <tr>
                                    <td><?= htmlspecialchars($n['type']) ?></td>
                                    <td><?= htmlspecialchars($n['message']) ?></td>
                                    <td>
                                        <?php if ($n['is_read']): ?>
                                            <span class="badge bg-success">Read</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Unread</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($n['created_at']) ?></td>
                                    <td>
                                        <?php if (!$n['is_read']): ?>
                                            <a href="notifications.php?read=<?= $n['id'] ?>" class="btn btn-sm btn-primary">Mark as Read</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($notifications)): ?>
                                <tr><td colspan="5" class="text-center">No notifications found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include 'includes/footer.php'; ?>