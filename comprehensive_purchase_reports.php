<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'comprehensive_purchase_reports';

// Handle filters
$from_date = $_GET['from_date'] ?? date('Y-m-01'); // First day of current month
$to_date = $_GET['to_date'] ?? date('Y-m-d'); // Today
$supplier_filter = $_GET['supplier_id'] ?? '';
$product_search = $_GET['product_search'] ?? '';
$category_filter = $_GET['category_id'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build the comprehensive purchase query with filters
$purchase_query = "
    SELECT 
        p.id,
        p.purchase_no,
        p.purchase_date,
        p.total_amount,
        p.paid_amount,
        p.due_amount,
        p.status,
        s.supplier_name,
        s.supplier_contact as contact,
        s.supplier_address as address,
        pi.product_id,
        pi.quantity,
        pi.purchase_price,
        pi.purchase_total as total_price,
        pr.product_name,
        pr.product_code,
        c.category,
        c.id as category_id,
        COALESCE(si.quantity, 0) as current_stock
    FROM purchase p
    LEFT JOIN supplier s ON p.supplier_id = s.id
    LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
    LEFT JOIN products pr ON pi.product_id = pr.id
    LEFT JOIN categories c ON pr.category_id = c.id
    LEFT JOIN (
        SELECT 
            product_id,
            COALESCE(SUM(CASE WHEN status = 'available' THEN quantity ELSE 0 END), 0) as quantity
        FROM stock_items 
        GROUP BY product_id
    ) si ON pi.product_id = si.product_id
    WHERE p.purchase_date BETWEEN ? AND ?
";

$params = [$from_date, $to_date];

// Add supplier filter
if (!empty($supplier_filter)) {
    $purchase_query .= " AND p.supplier_id = ?";
    $params[] = $supplier_filter;
}

// Add product search filter
if (!empty($product_search)) {
    $purchase_query .= " AND (pr.product_name LIKE ? OR pr.product_code LIKE ?)";
    $params[] = "%$product_search%";
    $params[] = "%$product_search%";
}

// Add category filter
if (!empty($category_filter)) {
    $purchase_query .= " AND pr.category_id = ?";
    $params[] = $category_filter;
}

// Add status filter
if (!empty($status_filter)) {
    $purchase_query .= " AND p.status = ?";
    $params[] = $status_filter;
}

$purchase_query .= " ORDER BY p.purchase_date DESC, p.purchase_no";

$stmt = $pdo->prepare($purchase_query);
$stmt->execute($params);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$total_purchases = count(array_unique(array_column($purchases, 'id')));
$total_amount = array_sum(array_column($purchases, 'total_amount'));
$total_paid = array_sum(array_column($purchases, 'paid_amount'));
$total_due = array_sum(array_column($purchases, 'due_amount'));
$total_items = array_sum(array_column($purchases, 'quantity'));

// Get unique suppliers for filter
$suppliers = $pdo->query("SELECT * FROM supplier ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);

// Get unique categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY category")->fetchAll(PDO::FETCH_ASSOC);

// Get unique statuses for filter
$statuses = ['completed', 'pending', 'cancelled'];

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 " style="margin-top: 25px;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-cart-plus me-2"></i>Comprehensive Purchase Reports
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="exportToExcel()">
                            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToCSV()">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="printReport()">
                            <i class="bi bi-printer me-1"></i>Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Advanced Filter Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-funnel me-2"></i>Advanced Filters
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label for="from_date" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="from_date" name="from_date" 
                                   value="<?= htmlspecialchars($from_date) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label for="to_date" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="to_date" name="to_date" 
                                   value="<?= htmlspecialchars($to_date) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label for="supplier_id" class="form-label">Supplier</label>
                            <select class="form-select" id="supplier_id" name="supplier_id">
                                <option value="">All Suppliers</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= $supplier['id'] ?>" <?= $supplier_filter == $supplier['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($supplier['supplier_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['category']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= $status ?>" <?= $status_filter == $status ? 'selected' : '' ?>>
                                        <?= ucfirst($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="product_search" class="form-label">Product Search</label>
                            <input type="text" class="form-control" id="product_search" name="product_search" 
                                   placeholder="Product name/code" value="<?= htmlspecialchars($product_search) ?>">
                        </div>
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-2"></i>Apply Filters
                                </button>
                                <a href="comprehensive_purchase_reports.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Reset All
                                </a>
                                <button type="button" class="btn btn-outline-info" onclick="showFilterSummary()">
                                    <i class="bi bi-info-circle me-2"></i>Filter Summary
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Filter Summary -->
            <?php if (!empty($supplier_filter) || !empty($product_search) || !empty($category_filter) || !empty($status_filter)): ?>
            <div class="alert alert-info mb-4">
                <h6 class="alert-heading">
                    <i class="bi bi-funnel-fill me-2"></i>Active Filters
                </h6>
                <div class="row">
                    <?php if (!empty($supplier_filter)): ?>
                        <div class="col-md-3">
                            <strong>Supplier:</strong> 
                            <?= htmlspecialchars($suppliers[array_search($supplier_filter, array_column($suppliers, 'id'))]['supplier_name'] ?? 'Unknown') ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($category_filter)): ?>
                        <div class="col-md-3">
                            <strong>Category:</strong> 
                            <?= htmlspecialchars($categories[array_search($category_filter, array_column($categories, 'id'))]['category'] ?? 'Unknown') ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($status_filter)): ?>
                        <div class="col-md-3">
                            <strong>Status:</strong> 
                            <span class="badge bg-primary"><?= ucfirst($status_filter) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($product_search)): ?>
                        <div class="col-md-3">
                            <strong>Product Search:</strong> 
                            <span class="badge bg-info"><?= htmlspecialchars($product_search) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Purchases</h6>
                                    <h3 class="mb-0"><?= number_format($total_purchases) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-cart-plus fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Amount</h6>
                                    <h3 class="mb-0"><?= number_format($total_amount, 2) ?> PKR</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-currency-dollar fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Items</h6>
                                    <h3 class="mb-0"><?= number_format($total_items) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-boxes fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Due Amount</h6>
                                    <h3 class="mb-0"><?= number_format($total_due, 2) ?> PKR</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-exclamation-triangle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Details Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-table me-2"></i>Purchase Details 
                        <span class="text-muted">(<?= date('M d, Y', strtotime($from_date)) ?> - <?= date('M d, Y', strtotime($to_date)) ?>)</span>
                    </h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary me-2"><?= count($purchases) ?> Records</span>
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="tableSearch" placeholder="Search in table...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="purchaseTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Purchase No</th>
                                    <th>Date</th>
                                    <th>Supplier</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Purchase Price</th>
                                    <th>Total Price</th>
                                    <th>Current Stock</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($purchases)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            No purchases found for the selected criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($purchases as $purchase): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($purchase['purchase_no']) ?></strong>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($purchase['purchase_date'])) ?></td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($purchase['supplier_name']) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars($purchase['contact']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($purchase['product_name']) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars($purchase['product_code']) ?></small>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($purchase['category']) ?></td>
                                            <td>
                                                <span class="badge bg-primary text-white px-3 py-2">
                                                    <?= number_format($purchase['quantity']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-success">
                                                    <?= number_format($purchase['purchase_price'], 2) ?> PKR
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-primary">
                                                    <?= number_format($purchase['total_price'], 2) ?> PKR
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $purchase['current_stock'] > 0 ? 'bg-success' : 'bg-danger' ?> text-white px-3 py-2">
                                                    <?= number_format($purchase['current_stock']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($purchase['status'] === 'completed'): ?>
                                                    <span class="badge bg-success text-white px-3 py-2">Completed</span>
                                                <?php elseif ($purchase['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark px-3 py-2">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary text-white px-3 py-2"><?= ucfirst($purchase['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Table search functionality
document.getElementById('tableSearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const table = document.getElementById('purchaseTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

function showFilterSummary() {
    const filters = [];
    if (document.getElementById('supplier_id').value) filters.push('Supplier filter applied');
    if (document.getElementById('category_id').value) filters.push('Category filter applied');
    if (document.getElementById('status').value) filters.push('Status filter applied');
    if (document.getElementById('product_search').value) filters.push('Product search applied');
    
    if (filters.length > 0) {
        alert('Active Filters:\n' + filters.join('\n'));
    } else {
        alert('No filters are currently applied.');
    }
}

function exportToExcel() {
    // Create a more Excel-friendly export
    const table = document.getElementById('purchaseTable');
    const rows = table.querySelectorAll('tbody tr');
    
    let excelData = 'Purchase No\tDate\tSupplier\tProduct\tCategory\tQuantity\tPurchase Price\tTotal Price\tCurrent Stock\tStatus\n';
    
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 1) { // Skip empty rows
            const rowData = [];
            cells.forEach((cell, cellIndex) => {
                if (cellIndex < 10) { // Include all columns
                    let text = cell.textContent.trim();
                    // Remove HTML tags and clean up
                    text = text.replace(/<[^>]*>/g, '');
                    text = text.replace(/\t/g, ' ');
                    rowData.push(text);
                }
            });
            excelData += rowData.join('\t') + '\n';
        }
    });
    
    const blob = new Blob([excelData], { type: 'text/tab-separated-values' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'purchase_report_' + new Date().toISOString().split('T')[0] + '.tsv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function exportToCSV() {
    const table = document.getElementById('purchaseTable');
    const rows = table.querySelectorAll('tbody tr');
    
    let csv = 'Purchase No,Date,Supplier,Product,Category,Quantity,Purchase Price,Total Price,Current Stock,Status\n';
    
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 1) { // Skip empty rows
            const rowData = [];
            cells.forEach((cell, cellIndex) => {
                if (cellIndex < 10) { // Include all columns
                    let text = cell.textContent.trim();
                    // Remove HTML tags and clean up
                    text = text.replace(/<[^>]*>/g, '');
                    text = text.replace(/,/g, ';');
                    rowData.push(`"${text}"`);
                }
            });
            csv += rowData.join(',') + '\n';
        }
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'comprehensive_purchase_report_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function printReport() {
    // Create a print-friendly version with only the filtered data
    const printWindow = window.open('', '_blank');
    const table = document.getElementById('purchaseTable');
    const title = document.querySelector('h1').textContent;
    const dateRange = `(${document.getElementById('from_date').value} to ${document.getElementById('to_date').value})`;
    
    // Get active filters for display
    const activeFilters = [];
    const supplierSelect = document.getElementById('supplier_id');
    const categorySelect = document.getElementById('category_id');
    const statusSelect = document.getElementById('status');
    const productSearch = document.getElementById('product_search');
    
    if (supplierSelect.value) {
        const supplierName = supplierSelect.options[supplierSelect.selectedIndex].text;
        activeFilters.push(`Supplier: ${supplierName}`);
    }
    if (categorySelect.value) {
        const categoryName = categorySelect.options[categorySelect.selectedIndex].text;
        activeFilters.push(`Category: ${categoryName}`);
    }
    if (statusSelect.value) {
        activeFilters.push(`Status: ${statusSelect.value.charAt(0).toUpperCase() + statusSelect.value.slice(1)}`);
    }
    if (productSearch.value) {
        activeFilters.push(`Product Search: ${productSearch.value}`);
    }
    
    // Get summary data
    const totalPurchases = document.querySelector('.card.bg-primary h3').textContent;
    const totalAmount = document.querySelector('.card.bg-success h3').textContent;
    const totalItems = document.querySelector('.card.bg-info h3').textContent;
    const totalDue = document.querySelector('.card.bg-warning h3').textContent;
    
    // Get only visible rows (filtered data)
    const visibleRows = Array.from(table.querySelectorAll('tbody tr')).filter(row => 
        row.style.display !== 'none' && row.cells.length > 1
    );
    
    // Create filtered table HTML
    let filteredTableHTML = `
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Purchase No</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Date</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Supplier</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Product</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Category</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Quantity</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Purchase Price</th>
            <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Total Price</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Current Stock</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Status</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    visibleRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 1) {
            filteredTableHTML += '<tr>';
            cells.forEach((cell, index) => {
                if (index < 10) { // Include all columns
                    let text = cell.textContent.trim();
                    // Clean up the text for printing
                    text = text.replace(/\s+/g, ' ').trim();
                    filteredTableHTML += `<td style="border: 1px solid #ddd; padding: 6px 8px; text-align: left;">${text}</td>`;
                }
            });
            filteredTableHTML += '</tr>';
        }
    });
    
    filteredTableHTML += '</tbody></table>';
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title}</title>
            <style>
                @media print {
                    body { margin: 0; padding: 20px; }
                    .no-print { display: none !important; }
                    .page-break { page-break-before: always; }
                }
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 20px; 
                    font-size: 12px;
                    line-height: 1.4;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    border-bottom: 2px solid #333; 
                    padding-bottom: 20px; 
                }
                .header h1 { 
                    margin: 0 0 10px 0; 
                    font-size: 24px; 
                    color: #333; 
                }
                .header h3 { 
                    margin: 0 0 10px 0; 
                    font-size: 18px; 
                    color: #666; 
                }
                .header p { 
                    margin: 0; 
                    font-size: 14px; 
                    color: #888; 
                }
                .filters { 
                    margin-bottom: 20px; 
                    padding: 15px; 
                    background-color: #f8f9fa; 
                    border: 1px solid #dee2e6; 
                    border-radius: 5px; 
                }
                .filters h4 { 
                    margin: 0 0 10px 0; 
                    font-size: 16px; 
                    color: #495057; 
                }
                .filter-tags { 
                    display: flex; 
                    flex-wrap: wrap; 
                    gap: 10px; 
                }
                .filter-tag { 
                    background-color: #007bff; 
                    color: white; 
                    padding: 4px 12px; 
                    border-radius: 15px; 
                    font-size: 12px; 
                    font-weight: bold; 
                }
                .summary { 
                    margin-bottom: 30px; 
                    padding: 20px; 
                    background-color: #f8f9fa; 
                    border: 1px solid #dee2e6; 
                    border-radius: 5px; 
                }
                .summary h4 { 
                    margin: 0 0 15px 0; 
                    font-size: 16px; 
                    color: #495057; 
                    text-align: center; 
                }
                .summary-grid { 
                    display: grid; 
                    grid-template-columns: repeat(4, 1fr); 
                    gap: 20px; 
                }
                .summary-item { 
                    text-align: center; 
                    padding: 15px; 
                    background-color: white; 
                    border-radius: 5px; 
                    border: 1px solid #dee2e6; 
                }
                .summary-item h5 { 
                    margin: 0 0 10px 0; 
                    font-size: 14px; 
                    color: #6c757d; 
                    font-weight: normal; 
                }
                .summary-item .value { 
                    font-size: 20px; 
                    font-weight: bold; 
                    color: #333; 
                }
                .footer { 
                    margin-top: 30px; 
                    text-align: center; 
                    font-size: 12px; 
                    color: #666; 
                    border-top: 1px solid #dee2e6; 
                    padding-top: 20px; 
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 20px; 
                    font-size: 11px; 
                }
                th, td { 
                    border: 1px solid #ddd; 
                    padding: 6px 8px; 
                    text-align: left; 
                    vertical-align: top; 
                }
                th { 
                    background-color: #f2f2f2; 
                    font-weight: bold; 
                    color: #333; 
                }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-muted { color: #6c757d; }
                .badge { 
                    display: inline-block; 
                    padding: 2px 8px; 
                    font-size: 10px; 
                    font-weight: bold; 
                    border-radius: 3px; 
                    color: white; 
                }
                .bg-primary { background-color: #007bff; }
                .bg-success { background-color: #28a745; }
                .bg-warning { background-color: #ffc107; color: #212529; }
                .bg-danger { background-color: #dc3545; }
                .bg-info { background-color: #17a2b8; }
                .bg-secondary { background-color: #6c757d; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>${title}</h1>
                <h3>${dateRange}</h3>
                <p>Generated on: ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}</p>
            </div>
            
            ${activeFilters.length > 0 ? `
            <div class="filters">
                <h4>Applied Filters:</h4>
                <div class="filter-tags">
                    ${activeFilters.map(filter => `<span class="filter-tag">${filter}</span>`).join('')}
                </div>
            </div>
            ` : ''}
            
            <div class="summary">
                <h4>Summary Statistics</h4>
                <div class="summary-grid">
                    <div class="summary-item">
                        <h5>Total Purchases</h5>
                        <div class="value">${totalPurchases}</div>
                    </div>
                    <div class="summary-item">
                        <h5>Total Amount</h5>
                        <div class="value">${totalAmount}</div>
                    </div>
                    <div class="summary-item">
                        <h5>Total Items</h5>
                        <div class="value">${totalItems}</div>
                    </div>
                    <div class="summary-item">
                        <h5>Due Amount</h5>
                        <div class="value">${totalDue}</div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <h4 style="margin: 0 0 15px 0; color: #495057;">Purchase Details (${visibleRows.length} Records)</h4>
                ${filteredTableHTML}
            </div>
            
            <div class="footer">
                <p><strong>Electronics Management System</strong> - Comprehensive Purchase Report</p>
                <p>This report contains ${visibleRows.length} filtered records based on the selected criteria.</p>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    
    // Wait for content to load before printing
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}
</script>

<style>
/* Enhanced styling for the comprehensive purchase reports */
.card-header.bg-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-bottom: 1px solid #dee2e6;
}

.form-control:focus, .form-select:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.card {
    border-radius: 8px;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    border-radius: 8px 8px 0 0 !important;
    border-bottom: 1px solid #e9ecef;
}

.btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.badge {
    font-weight: 500;
    letter-spacing: 0.3px;
}

.card.bg-primary, .card.bg-success, .card.bg-info, .card.bg-warning {
    border: none;
    transition: all 0.3s ease;
}

.card.bg-primary:hover, .card.bg-success:hover, .card.bg-info:hover, .card.bg-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.table {
    font-size: 0.9rem;
}

.table th {
    background-color: #343a40 !important;
    color: white !important;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
}

.table td {
    vertical-align: middle;
    padding: 12px 8px;
}

.alert {
    border-radius: 8px;
    border: none;
}

.alert-info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    color: #0c5460;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .col-md-2, .col-md-3 {
        margin-bottom: 1rem;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
        width: 100%;
    }
    
    .d-flex.gap-2 .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .input-group {
        width: 100% !important;
        margin-top: 1rem;
    }
}

/* Print styles */
@media print {
    .btn-toolbar,
    .btn-group,
    .alert,
    .card-header.bg-light {
        display: none !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }
    
    .table th {
        background-color: #f2f2f2 !important;
        color: #000 !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
