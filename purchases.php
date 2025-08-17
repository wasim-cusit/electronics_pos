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
            // Find and remove the exact stock items for this purchase
            $stmt = $pdo->prepare("SELECT id, quantity FROM stock_items WHERE purchase_item_id = ? AND product_id = ? AND status = 'available' ORDER BY id ASC");
            $stmt->execute([$id, $item['product_id']]);
            $stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $remaining_qty = $item['quantity'];
            foreach ($stock_items as $stock_item) {
                if ($remaining_qty <= 0) break;
                
                $qty_to_remove = min($stock_item['quantity'], $remaining_qty);
                
                if ($stock_item['quantity'] <= $qty_to_remove) {
                    // Remove entire stock item
                    $stmt = $pdo->prepare("DELETE FROM stock_items WHERE id = ?");
                    $stmt->execute([$stock_item['id']]);
                } else {
                    // Reduce quantity
                    $stmt = $pdo->prepare("UPDATE stock_items SET quantity = quantity - ? WHERE id = ?");
                    $stmt->execute([$qty_to_remove, $stock_item['id']]);
                }
                
                $remaining_qty -= $qty_to_remove;
            }
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

include 'includes/header.php'; ?>
<style>
    .table th {
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
    }
    .table td {
        vertical-align: middle;
        padding: 12px 8px;
    }
    .btn-group .btn {
        margin: 0 1px;
        border-radius: 4px;
    }
    .badge {
        font-size: 0.85em;
        padding: 6px 10px;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0,123,255,0.05);
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
    }
    .card {
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    .card-header {
        border-radius: 8px 8px 0 0 !important;
        border-bottom: 1px solid #e9ecef;
    }
    .table {
        margin-bottom: 0;
    }
    .text-end {
        text-align: right !important;
    }
    .text-center {
        text-align: center !important;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" 5" style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Purchases</h2>
                <!-- <a href="add_purchase.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Purchase
                </a> -->
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
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-cart-check me-2"></i>Purchase List</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center"><i class="bi bi-hash me-1"></i>Purchase No</th>
                                <th><i class="bi bi-person-badge me-1"></i>Supplier</th>
                                <th><i class="bi bi-calendar-event me-1"></i>Purchase Date & Time</th>
                                <th class="text-end"><i class="bi bi-currency-dollar me-1"></i>Total Amount</th>
                                <th class="text-end"><i class="bi bi-check-circle me-1"></i>Paid Amount</th>
                                <th class="text-end"><i class="bi bi-exclamation-circle me-1"></i>Remaining</th>
                                <th><i class="bi bi-credit-card me-1"></i>Payment Method</th>
                                <th><i class="bi bi-person me-1"></i>Created By</th>
                                <th class="text-center"><i class="bi bi-gear me-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchases as $purchase): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= htmlspecialchars($purchase['purchase_no']) ?></span>
                                    </td>
                                    <td>
                                        <i class="bi bi-person-badge me-1"></i>
                                        <a href="purchase_details.php?id=<?= $purchase['id'] ?>" class="text-decoration-none text-primary fw-medium" title="Click to view purchase details">
                                            <?= htmlspecialchars($purchase['supplier_name']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <strong><?= date('d M Y', strtotime($purchase['purchase_date'])) ?></strong>
                                            <small class="text-muted"><?= date('H:i', strtotime($purchase['purchase_date'])) ?></small>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-primary">PKR <?= number_format($purchase['total_amount'], 2) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-success">PKR <?= number_format($purchase['paid_amount'], 2) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($purchase['due_amount'] > 0): ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                PKR <?= number_format($purchase['due_amount'], 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>
                                                PKR <?= number_format($purchase['due_amount'], 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($purchase['payment_method_id']) {
                                            $stmt = $pdo->prepare("SELECT method FROM payment_method WHERE id = ?");
                                            $stmt->execute([$purchase['payment_method_id']]);
                                            $method = $stmt->fetch(PDO::FETCH_ASSOC);
                                            echo '<span class="badge bg-info">' . htmlspecialchars($method['method'] ?? 'N/A') . '</span>';
                                        } else {
                                            echo '<span class="text-muted">N/A</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-person me-1"></i>
                                        <?= htmlspecialchars($purchase['created_by_name']) ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="purchase_details.php?id=<?= $purchase['id'] ?>" class="btn btn-sm btn-outline-info" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="print_purchase.php?id=<?= $purchase['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Print">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <a href="purchases.php?delete=<?= $purchase['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this purchase?')" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($purchases)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-cart-x fs-1 d-block mb-2"></i>
                                            <h5>No purchases found</h5>
                                            <p>Start by adding your first purchase using the button above.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>



<?php include 'includes/footer.php'; ?>