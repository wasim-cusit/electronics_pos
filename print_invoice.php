<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';
require_once 'includes/settings.php';

$sale_id = intval($_GET['id'] ?? 0);
if (!$sale_id) {
    header("Location: sales.php");
    exit;
}

// Fetch sale details
$stmt = $pdo->prepare("
    SELECT s.*, c.name AS customer_name, c.contact AS customer_contact, c.address AS customer_address, c.email AS customer_email,
           u.username AS created_by_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.created_by = u.id
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    header("Location: sales.php");
    exit;
}

// Fetch sale items with product details
$stmt = $pdo->prepare("
    SELECT si.*, p.name AS product_name, p.unit AS product_unit, cat.name AS category_name
    FROM sale_items si
    LEFT JOIN products p ON si.product_id = p.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    WHERE si.sale_id = ?
    ORDER BY si.id
");
$stmt->execute([$sale_id]);
$sale_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper functions with fallbacks
function safe_format_currency($amount) {
    try {
        return format_currency($amount);
    } catch (Exception $e) {
        return 'PKR ' . number_format($amount, 2);
    }
}

function safe_format_date($date) {
    try {
        return format_date($date);
    } catch (Exception $e) {
        return date('d/m/Y', strtotime($date));
    }
}

function safe_get_setting($key, $default = '') {
    try {
        return get_setting($key, $default);
    } catch (Exception $e) {
        return $default;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Invoice - <?= htmlspecialchars($sale['invoice_no']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px 40px;
            background: white;
            line-height: 1.4;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }
        .company-info {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 20px;
        }
        .customer-info, .invoice-info {
            flex: 1;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .invoice-info {
            text-align: right;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 14px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
            font-size: 16px;
        }
        .total-row td {
            border-top: 2px solid #333;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .print-button:hover {
            background: #0056b3;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
        @media print {
            .print-button { display: none; }
            body { padding-left: 20mm; padding-right: 20mm; padding-top: 0; padding-bottom: 0; margin: 0; }
            .invoice-header { margin-bottom: 20px; }
            .invoice-details { margin-bottom: 20px; }
            table { margin-bottom: 20px; }
            .footer { margin-top: 20px; }
        }
        @media screen and (max-width: 768px) {
            .invoice-details { flex-direction: column; }
            .invoice-info { text-align: left; }
            table { font-size: 12px; }
            th, td { padding: 8px 4px; }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Print Invoice</button>
    <div class="invoice-header">
        <div class="company-name"><?= htmlspecialchars(safe_get_setting('company_name', 'TAILOR SHOP')) ?></div>
        <div class="company-info">
            <strong>SALES INVOICE</strong><br>
            <?= htmlspecialchars(safe_get_setting('company_tagline', 'Professional Tailoring Services')) ?><br>
            📞 Contact: <?= htmlspecialchars(safe_get_setting('company_phone', '+92-300-1234567')) ?><br>
            📧 Email: <?= htmlspecialchars(safe_get_setting('company_email', 'info@tailorshop.com')) ?><br>
            📍 Address: <?= htmlspecialchars(safe_get_setting('company_address', 'Shop #123, Main Street, Lahore, Pakistan')) ?>
        </div>
    </div>
    <div class="invoice-details">
        <div class="customer-info">
            <div class="section-title">👤 Customer Information</div>
            <strong><?= htmlspecialchars($sale['customer_name'] ?? 'N/A') ?></strong><br>
            📞 Contact: <?= htmlspecialchars($sale['customer_contact'] ?? 'N/A') ?><br>
            📍 Address: <?= htmlspecialchars($sale['customer_address'] ?? 'N/A') ?><br>
            📧 Email: <?= htmlspecialchars($sale['customer_email'] ?? 'N/A') ?>
        </div>
        <div class="invoice-info">
            <div class="section-title">📄 Invoice Details</div>
            <strong>Invoice No:</strong> <?= htmlspecialchars($sale['invoice_no'] ?? 'N/A') ?><br>
            <strong>Sale Date:</strong> <?= safe_format_date($sale['sale_date']) ?><br>
            <strong>Delivery Date:</strong> <?= htmlspecialchars($sale['delivery_date'] ?? '-') ?><br>
            <strong>Created By:</strong> <?= htmlspecialchars($sale['created_by_name'] ?? 'N/A') ?><br>
            <strong>Total Amount:</strong> <?= safe_format_currency($sale['total_amount'] ?? 0) ?>
        </div>
    </div>
    <?php if (!empty($sale_items)): ?>
    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="25%">Product Name</th>
                <th width="15%">Category</th>
                <th width="10%">Unit</th>
                <th width="10%">Quantity</th>
                <th width="15%">Unit Price</th>
                <th width="20%">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $counter = 1;
            $grand_total = 0;
            foreach ($sale_items as $item): 
                $grand_total += $item['total_price'];
            ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td><?= htmlspecialchars($item['product_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($item['category_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($item['product_unit'] ?? 'N/A') ?></td>
                    <td><?= number_format($item['quantity'] ?? 0, 2) ?></td>
                    <td><?= safe_format_currency($item['unit_price'] ?? 0) ?></td>
                    <td><?= safe_format_currency($item['total_price'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="6" style="text-align: right;"><strong>Grand Total:</strong></td>
                <td><strong><?= safe_format_currency($grand_total) ?></strong></td>
            </tr>
        </tbody>
    </table>
    <?php else: ?>
    <div class="no-data">
        <h3>No items found for this sale.</h3>
        <p>This sales invoice doesn't contain any items.</p>
    </div>
    <?php endif; ?>
    <div class="footer">
        <p><strong><?= htmlspecialchars(safe_get_setting('footer_text', 'Thank you for your business!')) ?></strong></p>
        <p><?= htmlspecialchars(safe_get_setting('print_header', 'This is a computer generated invoice. No signature required.')) ?></p>
        <p>Generated on: <?= date(safe_get_setting('date_format', 'd/m/Y') . ' ' . safe_get_setting('time_format', 'H:i:s')) ?></p>
        <p>Page 1 of 1</p>
    </div>
    <script>
        // Auto-print when page loads (optional - uncomment if needed)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 1000);
        // }
    </script>
</body>
</html>
