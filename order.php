<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'orders';

// Handle delete order
if (isset($_GET['delete'])) {
  $deleteId = (int)$_GET['delete'];
  if ($deleteId > 0) {
    try {
      // First delete order items
      $stmt = $pdo->prepare('DELETE FROM order_items WHERE order_id = ?');
      $stmt->execute([$deleteId]);
      // Then delete the order
      $stmt = $pdo->prepare('DELETE FROM cloths_orders WHERE id = ?');
      $stmt->execute([$deleteId]);
      header('Location: order.php?success=deleted');
      exit;
    } catch (Throwable $e) {
      header('Location: order.php?error=delete_failed');
      exit;
    }
  }
}

// Handle Status Update
if (isset($_GET['update_status'])) {
  $id = intval($_GET['update_status']);
  $new_status = $_GET['status'];
  $valid_statuses = ['Pending', 'Confirmed', 'In Progress', 'Completed', 'Cancelled'];
  if (in_array($new_status, $valid_statuses)) {
    try {
      $stmt = $pdo->prepare("UPDATE cloths_orders SET status = ? WHERE id = ?");
      $stmt->execute([$new_status, $id]);
      header("Location: order.php?success=status_updated");
      exit;
    } catch (Exception $e) {
      error_log("Error updating order status: " . $e->getMessage());
      header("Location: order.php?error=Failed to update status.");
      exit;
    }
  }
}

// Fetch all orders
$orders = $pdo->query("SELECT o.*, c.name AS customer_name FROM cloths_orders o LEFT JOIN customer c ON o.customer_id = c.id ORDER BY o.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Orders</h2>
    <a href="add_order.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Order</a>
  </div>

  <?php if (isset($_GET['success']) && $_GET['success']==='deleted'): ?>
    <div class="alert alert-success">Order deleted successfully.</div>
  <?php endif; ?>
  <?php if (isset($_GET['success']) && $_GET['success']==='status_updated'): ?>
    <div class="alert alert-success">Order status updated successfully.</div>
  <?php endif; ?>
  <?php if (isset($_GET['error']) && $_GET['error']==='delete_failed'): ?>
    <div class="alert alert-danger">Failed to delete order.</div>
  <?php endif; ?>
  <?php if (isset($_GET['error']) && $_GET['error']==='Failed to update status.'): ?>
    <div class="alert alert-danger">Failed to update order status.</div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">Order List</div>
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Order No</th>
            <th>Customer</th>
            <th>Order Date</th>
            <th>Delivery Date</th>
            <th>Total Amount</th>
            <th>Paid Amount</th>
            <th>Remaining</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td data-label="Order No"><?= htmlspecialchars($order['order_no'] ?? 'ORD-' . str_pad($order['id'], 3, '0', STR_PAD_LEFT)) ?></td>
              <td data-label="Customer"><?= htmlspecialchars($order['customer_name'] ?? 'Walk-in Customer') ?></td>
              <td data-label="Order Date"><?= htmlspecialchars($order['order_date']) ?></td>
              <td data-label="Delivery Date"><?= htmlspecialchars($order['delivery_date'] ?? 'Not set') ?></td>
              <td data-label="Total Amount">PKR <?= number_format($order['total_amount'], 2) ?></td>
              <td data-label="Paid Amount">PKR <?= number_format($order['paid_amount'], 2) ?></td>
              <td data-label="Remaining">PKR <?= number_format($order['remaining_amount'], 2) ?></td>
              <td data-label="Status">
                <?php
                $status_colors = [
                  'Pending' => 'bg-warning',
                  'Confirmed' => 'bg-info',
                  'In Progress' => 'bg-primary',
                  'Completed' => 'bg-success',
                  'Cancelled' => 'bg-danger'
                ];
                $status_icons = [
                  'Pending' => '⏳',
                  'Confirmed' => '✅',
                  'In Progress' => '🔄',
                  'Completed' => '🎉',
                  'Cancelled' => '❌'
                ];
                $color = $status_colors[$order['status']] ?? 'bg-secondary';
                $icon = $status_icons[$order['status']] ?? '❓';
                ?>
                <span class="badge <?= $color ?>">
                  <?= $icon ?> <?= htmlspecialchars($order['status'] ?? 'Pending') ?>
                </span>
                <div class="btn-group-vertical btn-group-sm mt-1">
                  <?php if (($order['status'] ?? 'Pending') !== 'Pending'): ?>
                    <a href="?update_status=<?= $order['id'] ?>&status=Pending" class="btn btn-sm btn-warning btn-sm">⏳ Pending</a>
                  <?php endif; ?>
                  <?php if (($order['status'] ?? 'Pending') !== 'Confirmed'): ?>
                    <a href="?update_status=<?= $order['id'] ?>&status=Confirmed" class="btn btn-sm btn-info btn-sm">✅ Confirmed</a>
                  <?php endif; ?>
                  <?php if (($order['status'] ?? 'Pending') !== 'In Progress'): ?>
                    <a href="?update_status=<?= $order['id'] ?>&status=In Progress" class="btn btn-sm btn-primary btn-sm">🔄 In Progress</a>
                  <?php endif; ?>
                  <?php if (($order['status'] ?? 'Pending') !== 'Completed'): ?>
                    <a href="?update_status=<?= $order['id'] ?>&status=Completed" class="btn btn-sm btn-success btn-sm">🎉 Completed</a>
                  <?php endif; ?>
                  <?php if (($order['status'] ?? 'Pending') !== 'Cancelled'): ?>
                    <a href="?update_status=<?= $order['id'] ?>&status=Cancelled" class="btn btn-sm btn-danger btn-sm">❌ Cancelled</a>
                  <?php endif; ?>
                </div>
              </td>
              <td data-label="Actions">
                <div class="d-flex flex-wrap gap-1">
                  <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-info">View</a>
                  <a href="print_order.php?id=<?= $order['id'] ?>" target="_blank" class="btn btn-sm btn-success">Print</a>
                  <a href="?delete=<?= $order['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this order?')">Delete</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($orders)): ?>
            <tr><td colspan="9" class="text-center">No orders found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>


