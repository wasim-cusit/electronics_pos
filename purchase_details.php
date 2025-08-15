<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'purchases';

// Get purchase ID
$purchase_id = intval($_GET['id'] ?? 0);
if (!$purchase_id) {
    header("Location: purchases.php");
    exit;
}

// Fetch purchase details
$stmt = $pdo->prepare("
    SELECT p.*, s.supplier_name, s.supplier_contact, s.supplier_address, s.supplier_email,
           u.username AS created_by_name
    FROM purchase p 
    LEFT JOIN supplier s ON p.supplier_id = s.id 
    LEFT JOIN system_users u ON p.created_by = u.id 
    WHERE p.id = ?
");
$stmt->execute([$purchase_id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    header("Location: purchases.php");
    exit;
}

// Fetch purchase items with product details
$stmt = $pdo->prepare("
    SELECT pi.*, p.product_name, p.product_unit, c.category AS category_name
    FROM purchase_items pi
    LEFT JOIN products p ON pi.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE pi.purchase_id = ?
    ORDER BY pi.id
");
$stmt->execute([$purchase_id]);
$purchase_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>📋 Purchase Details</h2>
                <div>
                    <a href="purchases.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Purchases
                    </a>
                    <a href="print_purchase.php?id=<?= $purchase_id ?>" target="_blank" class="btn btn-success">
                        <i class="bi bi-printer"></i> Print
                    </a>
                </div>
            </div>

            <!-- Purchase Header Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">📄 Purchase Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Purchase No:</strong></td>
                                    <td><?= htmlspecialchars($purchase['purchase_no']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Purchase Date:</strong></td>
                                    <td><?= date('d M Y', strtotime($purchase['purchase_date'])) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Amount:</strong></td>
                                    <td><span class="badge bg-primary fs-6">PKR <?= number_format($purchase['total_amount'], 2) ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong>Created By:</strong></td>
                                    <td><?= htmlspecialchars($purchase['created_by_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Created At:</strong></td>
                                    <td><?= date('d M Y H:i', strtotime($purchase['created_at'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">🏢 Supplier Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td><?= htmlspecialchars($purchase['supplier_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Contact:</strong></td>
                                    <td><?= htmlspecialchars($purchase['supplier_contact'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?= htmlspecialchars($purchase['supplier_email'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Address:</strong></td>
                                    <td><?= htmlspecialchars($purchase['supplier_address'] ?? 'N/A') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Items Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📦 Purchase Items (<?= count($purchase_items) ?> items)</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Color</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total Price</th>
                                <th>Paid</th>
                                <th>Remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_items = 0;
                            foreach ($purchase_items as $index => $item): 
                                $total_items += $item['quantity'];
                            ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= htmlspecialchars($item['category_name'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['color'])): ?>
                                            <span class="badge bg-light text-dark border">
                                                <?= htmlspecialchars($item['color']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No color specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($item['product_unit']) ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= number_format($item['quantity'], 2) ?> <?= htmlspecialchars($item['product_unit']) ?>
                                        </span>
                                    </td>
                                    <td>PKR <?= number_format($item['purchase_price'], 2) ?></td>
                                    <td>
                                        <strong>PKR <?= number_format($item['purchase_total'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            PKR <?= number_format($purchase['paid_amount'] ?? 0, 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">
                                            PKR <?= number_format(($purchase['total_amount'] ?? 0) - ($purchase['paid_amount'] ?? 0), 2) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($purchase_items)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">
                                        <i class="bi bi-inbox"></i> No items found for this purchase.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="5"><strong>Summary:</strong></td>
                                <td><strong><?= number_format($total_items, 2) ?> total units</strong></td>
                                <td><strong>Total:</strong></td>
                                <td><strong>PKR <?= number_format($purchase['total_amount'], 2) ?></strong></td>
                                <td><strong>PKR <?= number_format($purchase['paid_amount'] ?? 0, 2) ?></strong></td>
                                <td><strong>PKR <?= number_format(($purchase['total_amount'] ?? 0) - ($purchase['paid_amount'] ?? 0), 2) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">💰 Payment Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-danger">PKR <?= number_format($purchase['total_amount'], 2) ?></h4>
                                        <p class="text-muted">Total Purchase Amount</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-success">PKR <?= number_format($purchase['paid_amount'] ?? 0, 2) ?></h4>
                                        <p class="text-muted">Paid Amount</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-warning">PKR <?= number_format(($purchase['total_amount'] ?? 0) - ($purchase['paid_amount'] ?? 0), 2) ?></h4>
                                        <p class="text-muted">Remaining Balance</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Impact Information -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">📊 Stock Impact</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-primary"><?= count($purchase_items) ?></h4>
                                        <p class="text-muted">Products Purchased</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-success"><?= number_format($total_items, 2) ?></h4>
                                        <p class="text-muted">Total Units Added to Stock</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-info">PKR <?= number_format($purchase['total_amount'], 2) ?></h4>
                                        <p class="text-muted">Total Investment</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
