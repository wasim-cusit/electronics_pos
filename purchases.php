<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'purchases';



// Handle Delete Purchase
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get purchase items to reverse stock
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM purchase_items WHERE purchase_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        try {
            // Remove from stock_items table
            $stmt = $pdo->prepare("DELETE FROM stock_items WHERE purchase_item_id = ? AND product_id = ? LIMIT ?");
            $stmt->execute([$id, $item['product_id'], $item['quantity']]);
        } catch (Exception $e) {
            error_log("Stock items removal failed: " . $e->getMessage());
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM purchase_items WHERE purchase_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM purchase WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: purchases.php?success=deleted");
    exit;
}



// Fetch all purchases
$purchases = $pdo->query("SELECT p.*, s.supplier_name, u.username AS created_by_name FROM purchase p LEFT JOIN supplier s ON p.supplier_id = s.id LEFT JOIN system_users u ON p.created_by = u.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" 5" style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Purchases</h2>
                <a href="add_purchase.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Purchase
                </a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo "Purchase added successfully!";
                    if ($_GET['success'] === 'deleted') echo "Purchase deleted successfully!";
                    ?>
                </div>
            <?php endif; ?>



            <!-- Purchase List Table -->
            <div class="card">
                <div class="card-header">Purchase List</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Purchase No</th>
                                <th>Supplier</th>
                                <th>Purchase Date & Time</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Remaining</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchases as $purchase): ?>
                                <tr>
                                    <td><?= htmlspecialchars($purchase['purchase_no']) ?></td>
                                    <td><?= htmlspecialchars($purchase['supplier_name']) ?></td>
                                    <td>
                                        <small>
                                            <?php if ($purchase['purchase_date']): ?>
                                                <?= date('d M Y', strtotime($purchase['purchase_date'])) ?><br>
                                                <span class="text-secondary"><?= date('H:i') ?></span>
                                            <?php else: ?>
                                                <span class="text-secondary">N/A</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>PKR <?= number_format($purchase['total_amount'], 2) ?></td>
                                    <td>PKR <?= number_format($purchase['paid_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= $purchase['due_amount'] > 0 ? 'bg-warning' : 'bg-success' ?>">
                                            PKR <?= number_format($purchase['due_amount'], 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($purchase['payment_method_id']) {
                                            $stmt = $pdo->prepare("SELECT method FROM payment_method WHERE id = ?");
                                            $stmt->execute([$purchase['payment_method_id']]);
                                            $method = $stmt->fetch(PDO::FETCH_ASSOC);
                                            echo htmlspecialchars($method['method'] ?? 'N/A');
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $purchase['due_amount'] > 0 ? 'bg-warning' : 'bg-success' ?>">
                                            <?= $purchase['due_amount'] > 0 ? 'Pending' : 'Paid' ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($purchase['created_by_name']) ?></td>
                                    <td>
                                        <a href="purchase_details.php?id=<?= $purchase['id'] ?>" class="btn btn-sm btn-info">View</a>
                                        <a href="print_purchase.php?id=<?= $purchase['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">Print</a>
                                        <a href="purchases.php?delete=<?= $purchase['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this purchase?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($purchases)): ?>
                                <tr><td colspan="10" class="text-center">No purchases found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>



<?php include 'includes/footer.php'; ?>