<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'stock';

// Fetch stock details with product information
$stock_query = "
    SELECT 
        p.id,
        p.product_name,
        p.product_code,
        p.product_unit,
        p.alert_quantity,
        p.description,
        p.color,
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
    GROUP BY p.id, p.product_name, p.product_code, p.product_unit, p.alert_quantity, p.description, p.color, c.category, p.status
    ORDER BY p.product_name
";

$stock_items = $pdo->query($stock_query)->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<?php
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Stock Details</h1>
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

            <!-- Stock Details Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Stock Inventory Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="stockTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>S.No</th>
                                    <th>Product</th>
                                                    <th>Product Code</th>
                <th>Color</th>
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
                                        <td colspan="14" class="text-center">No stock items found.</td>
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
                        <td>
                            <?php if (!empty($item['color'])): ?>
                                <div class="d-flex align-items-center">
                                    <div class="color-swatch me-2" style="background-color: <?= htmlspecialchars($item['color']) ?>; width: 20px; height: 20px; border-radius: 50%; border: 2px solid #dee2e6;"></div>
                                    <span class="color-name"><?= htmlspecialchars($item['color']) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
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
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total Value</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        history.forEach(item => {
            const movementType = getMovementTypeLabel(item.movement_type);
            const statusBadge = getStatusBadge(item.status);
            const totalValue = (item.quantity * item.price).toFixed(2);
            
            html += `
                <tr>
                    <td>
                        <small class="text-muted">${formatDate(item.movement_date)}</small>
                    </td>
                    <td>
                        <span class="badge ${getMovementTypeBadge(item.movement_type)}">
                            ${movementType}
                        </span>
                    </td>
                    <td>
                        <strong>${item.quantity}</strong>
                    </td>
                    <td>
                        <span class="text-success">${item.price} PKR</span>
                    </td>
                    <td>
                        <span class="text-primary fw-bold">${totalValue} PKR</span>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        <small class="text-muted">${item.notes || '-'}</small>
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

function getMovementTypeLabel(type) {
    const labels = {
        'added': 'Stock Added',
        'sold': 'Sold',
        'reserved': 'Reserved',
        'returned': 'Returned',
        'adjusted': 'Adjusted'
    };
    return labels[type] || type;
}

function getMovementTypeBadge(type) {
    const badges = {
        'added': 'bg-success',
        'sold': 'bg-primary',
        'reserved': 'bg-warning',
        'returned': 'bg-info',
        'adjusted': 'bg-secondary'
    };
    return badges[type] || 'bg-secondary';
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
    let csv = 'Date,Type,Quantity,Price,Total Value,Status,Notes\n';
    
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
    
    let csv = 'S.No,Product,Product Code,Color,Category,Unit,Alert Quantity,Total Stock,Available,Reserved,Sold,Simple Avg Purchase Price,Simple Avg Sale Price,Status\n';
    
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

/* Color swatch styling */
.color-swatch {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.color-swatch:hover {
    transform: scale(1.1);
}

.color-name {
    font-weight: 500;
    color: #495057;
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
    
    .color-swatch {
        width: 16px !important;
        height: 16px !important;
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
