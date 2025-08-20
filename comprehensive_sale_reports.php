<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'comprehensive_sale_reports';

// Handle filters
$from_date = $_GET['from_date'] ?? date('Y-m-01'); // First day of current month
$to_date = $_GET['to_date'] ?? date('Y-m-d'); // Today
$customer_filter = $_GET['customer_id'] ?? '';
$product_search = $_GET['product_search'] ?? '';
$category_filter = $_GET['category_id'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build the comprehensive sale query with filters
$sale_query = "
    SELECT 
        s.id,
        s.sale_no as invoice_no,
        s.sale_date,
        s.total_amount,
        s.paid_amount,
        s.due_amount,
        s.status,
        s.walk_in_cust_name as customer_name,
        c.mobile as customer_mobile,
        s.customer_cnic,
        si.product_id,
        si.quantity,
        si.price as sale_price,
        si.total_price,
        pr.product_name,
        pr.product_code,
        si.category_name as category,
        pr.category_id,
        COALESCE(stock.quantity, 0) as current_stock,
        COALESCE(stock.quantity + si.quantity, si.quantity) as stock_before_sale
    FROM sale s
    LEFT JOIN customer c ON s.customer_id = c.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    LEFT JOIN products pr ON si.product_id = pr.id
    LEFT JOIN (
        SELECT 
            product_id,
            COALESCE(SUM(CASE WHEN status = 'available' THEN quantity ELSE 0 END), 0) as quantity
        FROM stock_items 
        GROUP BY product_id
    ) stock ON si.product_id = stock.product_id
    WHERE s.sale_date BETWEEN ? AND ?
";

$params = [$from_date, $to_date];

// Add customer filter
if (!empty($customer_filter)) {
    $sale_query .= " AND s.customer_id = ?";
    $params[] = $customer_filter;
}

// Add product search filter
if (!empty($product_search)) {
    $sale_query .= " AND (pr.product_name LIKE ? OR pr.product_code LIKE ?)";
    $params[] = "%$product_search%";
    $params[] = "%$product_search%";
}

// Add category filter
if (!empty($category_filter)) {
    $sale_query .= " AND pr.category_id = ?";
    $params[] = $category_filter;
}

// Add status filter
if (!empty($status_filter)) {
    $sale_query .= " AND s.status = ?";
    $params[] = $status_filter;
}

$sale_query .= " ORDER BY s.sale_date DESC, s.sale_no";

$stmt = $pdo->prepare($sale_query);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$total_sales = count(array_unique(array_column($sales, 'id')));
$total_amount = array_sum(array_column($sales, 'total_amount'));
$total_paid = array_sum(array_column($sales, 'paid_amount'));
$total_due = array_sum(array_column($sales, 'due_amount'));
$total_items = array_sum(array_column($sales, 'quantity'));

// Get unique customers for filter
$customers = $pdo->query("SELECT * FROM customer ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

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
                    <i class="bi bi-cash-coin me-2"></i>Comprehensive Sale Reports
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
                            <label for="customer_id" class="form-label">Customer</label>
                            <select class="form-select" id="customer_id" name="customer_id">
                                <option value="">All Customers</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>" <?= $customer_filter == $customer['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($customer['name']) ?>
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
                                <a href="comprehensive_sale_reports.php" class="btn btn-secondary">
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
            <?php if (!empty($customer_filter) || !empty($product_search) || !empty($category_filter) || !empty($status_filter)): ?>
            <div class="alert alert-info mb-4">
                <h6 class="alert-heading">
                    <i class="bi bi-funnel-fill me-2"></i>Active Filters
                </h6>
                <div class="row">
                    <?php if (!empty($customer_filter)): ?>
                        <div class="col-md-3">
                            <strong>Customer:</strong> 
                            <?= htmlspecialchars($customers[array_search($customer_filter, array_column($customers, 'id'))]['name'] ?? 'Unknown') ?>
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
                                    <h6 class="card-title">Total Sales</h6>
                                    <h3 class="mb-0"><?= number_format($total_sales) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-cash-coin fs-1"></i>
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
                                    <h6 class="card-title">Total Revenue</h6>
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
                                    <h6 class="card-title">Total Items Sold</h6>
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

            <!-- Sale Details Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-table me-2"></i>Sale Details 
                        <span class="text-muted">(<?= date('M d, Y', strtotime($from_date)) ?> - <?= date('M d, Y', strtotime($to_date)) ?>)</span>
                    </h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary me-2"><?= count($sales) ?> Records</span>
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="tableSearch" placeholder="Search in table...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="saleTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Invoice No</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Quantity Sold</th>
                                    <th>Sale Price</th>
                                    <th>Total Price</th>
                                    <th>Stock Before Sale</th>
                                    <th>Current Stock</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sales)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            No sales found for the selected criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sales as $sale): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($sale['invoice_no']) ?></strong>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($sale['sale_date'])) ?></td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($sale['customer_name']) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars($sale['customer_mobile']) ?></small>
                                                    <?php if ($sale['customer_cnic']): ?>
                                                        <br><small class="text-muted">CNIC: <?= htmlspecialchars($sale['customer_cnic']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($sale['product_name']) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars($sale['product_code']) ?></small>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($sale['category']) ?></td>
                                            <td>
                                                <span class="badge bg-primary text-white px-3 py-2">
                                                    <?= number_format($sale['quantity']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-success">
                                                    <?= number_format($sale['sale_price'], 2) ?> PKR
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-primary">
                                                    <?= number_format($sale['total_price'], 2) ?> PKR
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-white px-3 py-2">
                                                    <?= number_format($sale['stock_before_sale']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $sale['current_stock'] > 0 ? 'bg-success' : 'bg-danger' ?> text-white px-3 py-2">
                                                    <?= number_format($sale['current_stock']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($sale['status'] === 'completed'): ?>
                                                    <span class="badge bg-success text-white px-3 py-2">Completed</span>
                                                <?php elseif ($sale['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark px-3 py-2">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary text-white px-3 py-2"><?= ucfirst($sale['status']) ?></span>
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

            <!-- Stock Analysis Section -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Stock Analysis for Selected Period
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-primary">
                                        <i class="bi bi-box-seam me-2"></i>Stock Movement Summary
                                    </h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-center">
                                                <h4 class="text-success"><?= number_format($total_items) ?></h4>
                                                <small class="text-muted">Items Sold</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center">
                                                <h4 class="text-info"><?= count(array_unique(array_column($sales, 'product_id'))) ?></h4>
                                                <small class="text-muted">Products Sold</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-primary">
                                        <i class="bi bi-people me-2"></i>Customer Activity
                                    </h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-center">
                                                <h4 class="text-warning"><?= count(array_unique(array_column($sales, 'customer_name'))) ?></h4>
                                                <small class="text-muted">Unique Customers</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center">
                                                <h4 class="text-primary"><?= number_format($total_amount / max($total_sales, 1), 2) ?> PKR</h4>
                                                <small class="text-muted">Avg Sale Value</small>
                                            </div>
                                        </div>
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

<script>
// Table search functionality
document.getElementById('tableSearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const table = document.getElementById('saleTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

function showFilterSummary() {
    const filters = [];
    if (document.getElementById('customer_id').value) filters.push('Customer filter applied');
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
    const table = document.getElementById('saleTable');
    const rows = table.querySelectorAll('tbody tr');
    
    let excelData = 'Invoice No\tDate\tCustomer\tProduct\tCategory\tQuantity Sold\tSale Price\tTotal Price\tStock Before Sale\tCurrent Stock\tStatus\n';
    
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 1) { // Skip empty rows
            const rowData = [];
            cells.forEach((cell, cellIndex) => {
                if (cellIndex < 11) { // Include all columns
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
    a.download = 'sale_report_' + new Date().toISOString().split('T')[0] + '.tsv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function exportToCSV() {
    const table = document.getElementById('saleTable');
    const rows = table.querySelectorAll('tbody tr');
    
    let csv = 'Invoice No,Date,Customer,Product,Category,Quantity Sold,Sale Price,Total Price,Stock Before Sale,Current Stock,Status\n';
    
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 1) { // Skip empty rows
            const rowData = [];
            cells.forEach((cell, cellIndex) => {
                if (cellIndex < 11) { // Include all columns
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
    a.download = 'comprehensive_sale_report_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function printReport() {
    // Create a print-friendly version with only the filtered data
    const printWindow = window.open('', '_blank');
    const table = document.getElementById('saleTable');
    const title = document.querySelector('h1').textContent;
    const dateRange = `(${document.getElementById('from_date').value} to ${document.getElementById('to_date').value})`;
    
    // Get active filters for display
    const activeFilters = [];
    const customerSelect = document.getElementById('customer_id');
    const categorySelect = document.getElementById('category_id');
    const statusSelect = document.getElementById('status');
    const productSearch = document.getElementById('product_search');
    
    if (customerSelect.value) {
        const customerName = customerSelect.options[customerSelect.selectedIndex].text;
        activeFilters.push(`Customer: ${customerName}`);
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
    const totalSales = document.querySelector('.card.bg-primary h3').textContent;
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
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Invoice No</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Date</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Customer</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Product</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Category</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Quantity Sold</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Sale Price</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Total Price</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold;">Stock Before Sale</th>
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
                if (index < 11) { // Include all columns
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
                        <h5>Total Sales</h5>
                        <div class="value">${totalSales}</div>
                    </div>
                    <div class="summary-item">
                        <h5>Total Revenue</h5>
                        <div class="value">${totalAmount}</div>
                    </div>
                    <div class="summary-item">
                        <h5>Total Items Sold</h5>
                        <div class="value">${totalItems}</div>
                    </div>
                    <div class="summary-item">
                        <h5>Due Amount</h5>
                        <div class="value">${totalDue}</div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <h4 style="margin: 0 0 15px 0; color: #495057;">Sale Details (${visibleRows.length} Records)</h4>
                ${filteredTableHTML}
            </div>
            
            <div class="footer">
                <p><strong>Electronics Management System</strong> - Comprehensive Sale Report</p>
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
/* Enhanced styling for the comprehensive sale reports */
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

/* Stock analysis cards */
.card.bg-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border: 1px solid #dee2e6;
}

.card.bg-light .card-title {
    color: #495057;
    font-weight: 600;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .col-md-2, .col-md-3, .col-md-6 {
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
