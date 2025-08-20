<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'stock';

// Handle search functionality
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category_filter'] ?? '';
$stock_status_filter = $_GET['stock_status_filter'] ?? '';

// Build the stock query with search filters
$stock_query = "
    SELECT 
        p.id,
        p.product_name,
        p.product_code,
        p.product_unit,
        p.alert_quantity,
        p.description,
        c.category,
        COALESCE(SUM(si.quantity), 0) as total_stock,
        COALESCE(SUM(CASE WHEN si.status = 'available' THEN si.quantity ELSE 0 END), 0) as available_stock,
        COALESCE(SUM(CASE WHEN si.status = 'reserved' THEN si.quantity ELSE 0 END), 0) as reserved_stock,
        COALESCE(SUM(CASE WHEN si.status = 'sold' THEN si.quantity ELSE 0 END), 0) as sold_stock,
        COALESCE(AVG(CASE WHEN si.status = 'available' THEN si.purchase_price END), 0) as avg_purchase_price,
        COALESCE(AVG(CASE WHEN si.status = 'available' THEN si.sale_price END), 0) as avg_sale_price,
        CASE 
            WHEN SUM(CASE WHEN si.status = 'available' THEN si.quantity ELSE 0 END) > 0 
            THEN COALESCE(SUM(CASE WHEN si.status = 'available' THEN si.quantity * si.purchase_price ELSE 0 END) / SUM(CASE WHEN si.status = 'available' THEN si.quantity ELSE 0 END), 0)
            ELSE 0 
        END as weighted_avg_purchase_price,
        CASE 
            WHEN SUM(CASE WHEN si.status = 'available' THEN si.quantity ELSE 0 END) > 0 
            THEN COALESCE(SUM(CASE WHEN si.status = 'available' THEN si.quantity * si.sale_price ELSE 0 END) / SUM(CASE WHEN si.status = 'available' THEN si.quantity ELSE 0 END), 0)
            ELSE 0 
        END as weighted_avg_sale_price,
        p.status as product_status
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN stock_items si ON p.id = si.product_id
    WHERE 1=1";

$params = [];

if (!empty($search)) {
    $stock_query .= " AND (p.product_name LIKE ? OR p.product_code LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($category_filter)) {
    $stock_query .= " AND c.id = ?";
    $params[] = $category_filter;
}

if (!empty($stock_status_filter)) {
    switch ($stock_status_filter) {
        case 'in_stock':
            $stock_query .= " AND COALESCE(SUM(si.quantity), 0) > p.alert_quantity";
            break;
        case 'low_stock':
            $stock_query .= " AND COALESCE(SUM(si.quantity), 0) <= p.alert_quantity AND COALESCE(SUM(si.quantity), 0) > 0";
            break;
        case 'out_of_stock':
            $stock_query .= " AND COALESCE(SUM(si.quantity), 0) = 0";
            break;
    }
}

$stock_query .= " GROUP BY p.id, p.product_name, p.product_code, p.product_unit, p.alert_quantity, p.description, c.category, p.status ORDER BY p.product_name";

// Execute the query with parameters
$stmt = $pdo->prepare($stock_query);
$stmt->execute($params);
$stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for filter dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY category")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<?php
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 " style="margin-top: 25px;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Electronics Stock Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToCSV()">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="printStock()">
                            <i class="bi bi-printer me-1"></i>Print
                        </button>
                    </div>
                </div>
            </div>



            <!-- Stock Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Products</h6>
                                    <h3 class="mb-0"><?= count($stock_items) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-boxes fs-1"></i>
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
                                    <h6 class="card-title">Available Stock</h6>
                                    <h3 class="mb-0"><?= array_sum(array_column($stock_items, 'available_stock')) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-check-circle fs-1"></i>
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
                                    <h6 class="card-title">Low Stock Items</h6>
                                    <h3 class="mb-0"><?= count(array_filter($stock_items, function($item) { return $item['total_stock'] <= $item['alert_quantity'] && $item['total_stock'] > 0; })) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-exclamation-triangle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Out of Stock</h6>
                                    <h3 class="mb-0"><?= count(array_filter($stock_items, function($item) { return $item['total_stock'] == 0; })) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-x-circle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-search me-2"></i>Search & Filter Stock Items
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Stock Items</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Search by product name, code, or description..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="category_filter" class="form-label">Filter by Category</label>
                            <select class="form-select" id="category_filter" name="category_filter">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= ($category_filter == $category['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['category']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="stock_status_filter" class="form-label">Filter by Stock Status</label>
                            <select class="form-select" id="stock_status_filter" name="stock_status_filter">
                                <option value="">All Statuses</option>
                                <option value="in_stock" <?= ($stock_status_filter === 'in_stock') ? 'selected' : '' ?>>In Stock (Good)</option>
                                <option value="low_stock" <?= ($stock_status_filter === 'low_stock') ? 'selected' : '' ?>>Low Stock (Warning)</option>
                                <option value="out_of_stock" <?= ($stock_status_filter === 'out_of_stock') ? 'selected' : '' ?>>Out of Stock</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="bi bi-search me-2"></i>Search
                                </button>
                                <a href="stock.php" class="btn btn-secondary flex-fill">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Search Results Summary -->
                    <?php if (!empty($search) || !empty($category_filter) || !empty($stock_status_filter)): ?>
                        <div class="mt-3 p-3 bg-info bg-opacity-10 border border-info rounded">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle text-info me-2"></i>
                                <div>
                                    <strong>Search Results:</strong>
                                    <?php if (!empty($search)): ?>
                                        <span class="badge bg-primary ms-2">Search: "<?= htmlspecialchars($search) ?>"</span>
                                    <?php endif; ?>
                                    <?php if (!empty($category_filter)): ?>
                                        <?php 
                                        $selected_category = array_filter($categories, function($c) use ($category_filter) { 
                                            return $c['id'] == $category_filter; 
                                        });
                                        $category_name = !empty($selected_category) ? reset($selected_category)['category'] : 'Unknown';
                                        ?>
                                        <span class="badge bg-success ms-2">Category: <?= htmlspecialchars($category_name) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($stock_status_filter)): ?>
                                        <span class="badge bg-warning ms-2">
                                            Status Filter: 
                                            <?php 
                                            switch($stock_status_filter) {
                                                case 'in_stock': echo 'In Stock (Good)'; break;
                                                case 'low_stock': echo 'Low Stock (Warning)'; break;
                                                case 'out_of_stock': echo 'Out of Stock'; break;
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge bg-secondary ms-2">Found: <?= count($stock_items) ?> items</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stock Details Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Electronics Stock Inventory Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="stockTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>S.No</th>
                                    <th>Product</th>
                                    <th>Product Code</th>
                                    <th>Category</th>
                                    <th>Unit</th>
                                    <th>Alert Quantity</th>
                                    <th>Total Stock</th>
                                    <th>Available</th>
                                    <th>Reserved</th>
                                    <th>Sold</th>
                                    <th>Avg Purchase Price</th>
                                    <th>Avg Sale Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stock_items)): ?>
                                    <tr>
                                        <td colspan="13" class="text-center">No stock items found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($stock_items as $index => $item): ?>
                                        <tr class="<?= $item['total_stock'] <= $item['alert_quantity'] && $item['total_stock'] > 0 ? 'table-warning' : ($item['total_stock'] == 0 ? 'table-danger' : 'table-light') ?>">
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                                <?php if ($item['description']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($item['description']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                                                    <td><code><?= htmlspecialchars($item['product_code']) ?></code></td>
                                            <td><?= htmlspecialchars($item['category']) ?></td>
                                            <td><?= htmlspecialchars($item['product_unit']) ?></td>
                                            <td>
                                                <span class="badge bg-info text-white px-3 py-2"><?= $item['alert_quantity'] ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary text-white fs-6 px-3 py-2"><?= $item['total_stock'] ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success text-white px-3 py-2"><?= $item['available_stock'] ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark px-3 py-2"><?= $item['reserved_stock'] ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary text-white px-3 py-2"><?= $item['sold_stock'] ?></span>
                                            </td>
                                            <td>
                                                <span class="text-success"><?= number_format($item['avg_purchase_price'], 2) ?> PKR </span>
                                                <br><small class="text-muted">(Simple avg)</small>
                                            </td>
                                            <td>
                                                <span class="text-primary"> <?= number_format($item['avg_sale_price'], 2) ?> PKR </span>
                                                <br><small class="text-muted">(Simple avg)</small>
                                            </td>
                                            <td>
                                                <?php if ($item['total_stock'] == 0): ?>
                                                    <span class="badge bg-danger text-white px-3 py-2">Out of Stock</span>
                                                <?php elseif ($item['total_stock'] <= $item['alert_quantity']): ?>
                                                    <span class="badge bg-warning text-dark px-3 py-2">Low Stock</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success text-white px-3 py-2">In Stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="products.php?edit=<?= $item['id'] ?>" class="btn btn-outline-primary" title="Edit Product">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-info" onclick="viewStockHistory(<?= $item['id'] ?>)" title="View History">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success" onclick="addStock(<?= $item['id'] ?>)" title="Add Stock">
                                                        <i class="bi bi-plus-circle"></i>
                                                    </button>
                                                </div>
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

        <!-- Stock History Modal -->
<div class="modal fade" id="stockHistoryModal" tabindex="-1" aria-labelledby="stockHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockHistoryModalLabel">
                    <i class="bi bi-clock-history me-2"></i>Stock History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="stockHistoryContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportStockHistory()">
                        <i class="bi bi-download me-1"></i>Export CSV
                    </button>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStockModalLabel">Add Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addStockForm">
                    <!-- CSRF Protection -->
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <input type="hidden" id="productId" name="product_id">
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" required min="1">
                    </div>
                    <div class="mb-3">
                        <label for="purchasePrice" class="form-label">Purchase Price</label>
                        <input type="number" class="form-control" id="purchasePrice" name="purchase_price" required step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="salePrice" class="form-label">Sale Price</label>
                        <input type="number" class="form-control" id="salePrice" name="sale_price" required step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="stockDate" class="form-label">Stock Date</label>
                        <input type="date" class="form-control" id="stockDate" name="stock_date" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveStock()">Add Stock</button>
            </div>
        </div>
    </div>
</div>

<script>
// Set today's date as default for stock date
document.getElementById('stockDate').valueAsDate = new Date();

// Notification function to replace alerts
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
    
    // Allow manual close
    notification.querySelector('.btn-close').addEventListener('click', () => {
        notification.remove();
    });
}

function viewStockHistory(productId) {
    // Show loading state
    document.getElementById('stockHistoryContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading stock history...</p>
        </div>
    `;
    
    // Show modal first
    new bootstrap.Modal(document.getElementById('stockHistoryModal')).show();
    
    // Fetch stock history data
    fetch(`get_stock_history.php?product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStockHistory(data.history, data.product_info);
            } else {
                document.getElementById('stockHistoryContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error loading stock history: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('stockHistoryContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Failed to load stock history. Please try again.
                </div>
            `;
        });
}

function displayStockHistory(history, productInfo) {
    let html = `
        <div class="mb-3">
            <h6 class="text-primary mb-2">
                <i class="bi bi-box me-2"></i>${productInfo.product_name}
            </h6>
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">Product Code: <strong>${productInfo.product_code}</strong></small>
                </div>
                <div class="col-md-6">
                    <small class="text-muted">Category: <strong>${productInfo.category}</strong></small>
                </div>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body p-2 text-center">
                        <small class="card-title">Total Added</small>
                        <h6 class="mb-0">${productInfo.summary ? productInfo.summary.total_added : 0}</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body p-2 text-center">
                        <small class="card-title">Total Sold</small>
                        <h6 class="mb-0">${productInfo.summary ? productInfo.summary.total_sold : 0}</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body p-2 text-center">
                        <small class="card-title">Reserved</small>
                        <h6 class="mb-0">${productInfo.summary ? productInfo.summary.total_reserved : 0}</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body p-2 text-center">
                        <small class="card-title">Current Stock</small>
                        <h6 class="mb-0">${productInfo.summary ? productInfo.summary.current_stock : 0}</h6>
                    </div>
                </div>
            </div>
        </div>
        <hr>
    `;
    
    if (history.length === 0) {
        html += `
            <div class="text-center text-muted">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2">No stock movements found for this product.</p>
            </div>
        `;
    } else {
        html += `
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Value</th>
                            <th>Product Code</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        history.forEach(item => {
            const statusBadge = getStatusBadge(item.status);
            const purchaseValue = (item.quantity * item.purchase_price).toFixed(2);
            const saleValue = (item.quantity * item.sale_price).toFixed(2);
            
            html += `
                <tr>
                    <td>
                        <small class="text-muted">${formatDate(item.stock_date)}</small>
                    </td>
                    <td>
                        <span class="badge ${getStatusBadge(item.status)}">
                            ${item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                        </span>
                    </td>
                    <td>
                        <strong>${item.quantity}</strong>
                    </td>
                    <td>
                        <div>
                            <small class="text-muted">Purchase: ${item.purchase_price} PKR</small><br>
                            <small class="text-success">Sale: ${item.sale_price} PKR</small>
                        </div>
                    </td>
                    <td>
                        <div>
                            <small class="text-muted">Purchase: ${purchaseValue} PKR</small><br>
                            <small class="text-success">Sale: ${saleValue} PKR</small>
                        </div>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        <small class="text-muted">${item.product_code || '-'}</small>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
    }
    
    document.getElementById('stockHistoryContent').innerHTML = html;
}



function getStatusBadge(status) {
    const badges = {
        'available': 'bg-success',
        'reserved': 'bg-warning',
        'sold': 'bg-primary'
    };
    return `<span class="badge ${badges[status] || 'bg-secondary'}">${status}</span>`;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function exportStockHistory() {
    const table = document.querySelector('#stockHistoryContent table');
    if (!table) {
        showNotification('No stock history data to export', 'warning');
        return;
    }
    
    const rows = table.querySelectorAll('tbody tr');
    let csv = 'Date,Status,Quantity,Unit Price,Total Value,Product Code\n';
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 1) {
            const rowData = [];
            cells.forEach(cell => {
                let text = cell.textContent.trim();
                // Remove HTML tags and clean up
                text = text.replace(/<[^>]*>/g, '');
                text = text.replace(/,/g, ';');
                rowData.push(`"${text}"`);
            });
            csv += rowData.join(',') + '\n';
        }
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'stock_history_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function addStock(productId) {
    document.getElementById('productId').value = productId;
    new bootstrap.Modal(document.getElementById('addStockModal')).show();
}

function saveStock() {
    const form = document.getElementById('addStockForm');
    const formData = new FormData(form);
    
    // Send data to PHP endpoint
    fetch('add_stock_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message in a more user-friendly way
            showNotification(data.message, 'success');
            // Reload the page to show updated stock
            location.reload();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('An error occurred while adding stock', 'error');
    })
    .finally(() => {
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('addStockModal')).hide();
    });
}

function exportToCSV() {
    const table = document.getElementById('stockTable');
    const rows = table.querySelectorAll('tbody tr');
    
            let csv = 'S.No,Product,Product Code,Category,Unit,Alert Quantity,Total Stock,Available,Reserved,Sold,Simple Avg Purchase Price,Simple Avg Sale Price,Status\n';
    
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 1) { // Skip empty rows
            const rowData = [];
            cells.forEach((cell, cellIndex) => {
                if (cellIndex < 14) { // Include all columns except Actions
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
    a.download = 'stock_details_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function printStock() {
    window.print();
}
</script>

<style>
/* Search and filter section styling */
.card-header.bg-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-bottom: 1px solid #dee2e6;
}

.search-results-summary {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    border: 1px solid #bee5eb;
    border-radius: 8px;
}

/* Enhanced form controls */
.form-control:focus, .form-select:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.input-group-text {
    background-color: #f8f9fa;
    border-color: #ced4da;
    color: #6c757d;
}

/* Enhanced table styling */
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

/* Enhanced button styles */
.btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Badge styling */
.badge {
    font-weight: 500;
    letter-spacing: 0.3px;
}

/* Summary cards enhancement */
.card.bg-primary, .card.bg-success, .card.bg-warning, .card.bg-danger {
    border: none;
    transition: all 0.3s ease;
}

.card.bg-primary:hover, .card.bg-success:hover, .card.bg-warning:hover, .card.bg-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

/* Responsive improvements */
@media (max-width: 768px) {
    .col-md-2, .col-md-3, .col-md-4 {
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
}
</style>

<?php include 'includes/footer.php'; ?>

<style>
/* Enhanced stock table styling */
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



/* Badge improvements */
.badge {
    font-weight: 600;
    letter-spacing: 0.3px;
    border-radius: 6px;
}

.badge.bg-primary {
    background-color: #007bff !important;
}

.badge.bg-success {
    background-color: #28a745 !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.badge.bg-danger {
    background-color: #dc3545 !important;
}

.badge.bg-info {
    background-color: #17a2b8 !important;
}

.badge.bg-secondary {
    background-color: #6c757d !important;
}

/* Table row hover effects */
.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,0.05) !important;
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

/* Summary cards improvements */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card.bg-primary {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}

.card.bg-success {
    background: linear-gradient(135deg, #28a745, #1e7e34) !important;
}

.card.bg-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800) !important;
}

.card.bg-danger {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
}

/* Button improvements */
.btn-outline-primary:hover {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.btn-outline-info:hover {
    background-color: #17a2b8;
    border-color: #17a2b8;
    color: white;
}

.btn-outline-success:hover {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 4px 8px !important;
    }
    

}

/* Print styles */
@media print {
    .btn-toolbar,
    .btn-group,
    .modal {
        display: none !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }
}

/* Stock History Modal Styles */
.modal-xl {
    max-width: 95%;
}

#stockHistoryContent .card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#stockHistoryContent .card-body {
    padding: 0.5rem;
}

#stockHistoryContent .table {
    font-size: 0.85rem;
}

#stockHistoryContent .table th {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

#stockHistoryContent .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Summary cards in modal */
#stockHistoryContent .card.bg-success,
#stockHistoryContent .card.bg-primary,
#stockHistoryContent .card.bg-warning,
#stockHistoryContent .card.bg-info {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

#stockHistoryContent .card .card-title {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

#stockHistoryContent .card h6 {
    font-weight: 700;
    margin: 0;
}
</style>
