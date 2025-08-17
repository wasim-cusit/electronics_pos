<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'orders';

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    die('Invalid order ID');
}

try {
    // Fetch order details
    $stmt = $pdo->prepare("
        SELECT o.*, c.name AS customer_name, c.mobile, c.address, c.email
        FROM cloths_orders o 
        LEFT JOIN customer c ON o.customer_id = c.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        die('Order not found');
    }
    
    // Fetch order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.product_name, p.product_unit
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('Error fetching order: ' . $e->getMessage());
}

// Set page title
$page_title = "Order #" . ($order['order_no'] ?? 'ORD-' . str_pad($order['id'], 3, '0', STR_PAD_LEFT));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Print</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 20px; }
            .container { max-width: 100% !important; }
        }
        
        .print-header {
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-info {
            text-align: center;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .company-tagline {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .customer-info {
            background: #fff;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .items-table {
            margin-bottom: 30px;
        }
        
        .items-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .total-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #666;
        }
        
        .status-badge {
            font-size: 14px;
            padding: 8px 16px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Print Header -->
        <div class="print-header">
            <div class="company-info">
                <div class="company-name">TAILOR SHOP</div>
                <div class="company-tagline">Professional Tailoring Services</div>
                <div class="company-tagline">Quality Stitching & Design</div>
            </div>
        </div>
        
        <!-- Order Details -->
        <div class="order-details">
            <div class="row">
                <div class="col-md-6">
                    <h5><strong>Order Information</strong></h5>
                    <p><strong>Order No:</strong> <?= htmlspecialchars($order['order_no'] ?? 'ORD-' . str_pad($order['id'], 3, '0', STR_PAD_LEFT)) ?></p>
                    <p><strong>Order Date:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($order['order_date']))) ?></p>
                    <p><strong>Delivery Date:</strong> <?= $order['delivery_date'] ? htmlspecialchars(date('d/m/Y', strtotime($order['delivery_date']))) : 'Not set' ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5><strong>Order Status</strong></h5>
                    <?php
                    $status_colors = [
                        'Pending' => 'warning',
                        'Confirmed' => 'info',
                        'In Progress' => 'primary',
                        'Completed' => 'success',
                        'Cancelled' => 'danger'
                    ];
                    $status_icons = [
                        'Pending' => '⏳',
                        'Confirmed' => '✅',
                        'In Progress' => '🔄',
                        'Completed' => '🎉',
                        'Cancelled' => '❌'
                    ];
                    $color = $status_colors[$order['status']] ?? 'secondary';
                    $icon = $status_icons[$order['status']] ?? '❓';
                    ?>
                    <span class="badge bg-<?= $color ?> status-badge">
                        <?= $icon ?> <?= htmlspecialchars($order['status'] ?? 'Pending') ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="customer-info">
            <h5><strong>Customer Information</strong></h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name'] ?? 'Walk-in Customer') ?></p>
                    <?php if ($order['mobile']): ?>
                        <p><strong>Mobile:</strong> <?= htmlspecialchars($order['mobile']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <?php if ($order['address']): ?>
                        <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
                    <?php endif; ?>
                    <?php if ($order['email']): ?>
                        <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="items-table">
            <h5><strong>Order Items</strong></h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Description</th>
                        <th>Unit</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($order_items)): ?>
                        <?php foreach ($order_items as $index => $item): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($item['description'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item['product_unit'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item['quantity']) ?></td>
                                <td>PKR <?= number_format($item['unit_price'], 2) ?></td>
                                <td>PKR <?= number_format($item['total_price'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No items found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pricing Summary -->
        <div class="total-section">
            <div class="row">
                <div class="col-md-6 offset-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Sub Total:</strong></td>
                            <td class="text-end">PKR <?= number_format($order['sub_total'], 2) ?></td>
                        </tr>
                        <?php if ($order['discount'] > 0): ?>
                            <tr>
                                <td><strong>Discount:</strong></td>
                                <td class="text-end">PKR <?= number_format($order['discount'], 2) ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="border-top">
                            <td><strong>Total Amount:</strong></td>
                            <td class="text-end"><strong>PKR <?= number_format($order['total_amount'], 2) ?></strong></td>
                        </tr>
                        <tr>
                            <td><strong>Paid Amount:</strong></td>
                            <td class="text-end">PKR <?= number_format($order['paid_amount'], 2) ?></td>
                        </tr>
                        <tr class="border-top">
                            <td><strong>Remaining Amount:</strong></td>
                            <td class="text-end"><strong>PKR <?= number_format($order['remaining_amount'], 2) ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Order Details/Notes -->
        <?php if ($order['details']): ?>
            <div class="customer-info">
                <h5><strong>Order Details/Notes</strong></h5>
                <p><?= nl2br(htmlspecialchars($order['details'])) ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>Thank you for choosing our services!</strong></p>
            <p>For any queries, please contact us</p>
            <p>Generated on: <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>
    
    <!-- Print Button -->
    <div class="no-print text-center mt-4 mb-4">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Print Order
        </button>
        <a href="order.php" class="btn btn-secondary ms-2">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
