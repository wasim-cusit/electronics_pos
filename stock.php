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
        COALESCE(AVG(si.purchase_price), 0) as avg_purchase_price,
        COALESCE(AVG(si.sale_price), 0) as avg_sale_price,
        p.status as product_status,
        p.created_at
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN stock_items si ON p.id = si.product_id
    GROUP BY p.id, p.product_name, p.product_code, p.product_unit, p.alert_quantity, p.description, p.color, c.category, p.status, p.created_at
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
                                            </td>
                                            <td>
                                                <span class="text-primary"> <?= number_format($item['avg_sale_price'], 2) ?> PKR </span>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockHistoryModalLabel">Stock History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="stockHistoryContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
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

function viewStockHistory(productId) {
    // This would typically load stock history from the database
    document.getElementById('stockHistoryContent').innerHTML = `
        <div class="text-center">
            <p>Stock history for product ID: ${productId}</p>
            <p class="text-muted">This feature will show detailed stock movement history.</p>
        </div>
    `;
    new bootstrap.Modal(document.getElementById('stockHistoryModal')).show();
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
            alert(data.message);
            // Reload the page to show updated stock
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding stock');
    })
    .finally(() => {
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('addStockModal')).hide();
    });
}

function exportToCSV() {
    const table = document.getElementById('stockTable');
    const rows = table.querySelectorAll('tbody tr');
    
    let csv = 'S.No,Product,Product Code,Color,Category,Unit,Alert Quantity,Total Stock,Available,Reserved,Sold,Avg Purchase Price,Avg Sale Price,Status\n';
    
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 1) { // Skip empty rows
            const rowData = [];
            cells.forEach((cell, cellIndex) => {
                if (cellIndex < 14) { // Exclude Actions column, include Color column
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
    a.download = 'stock_details.csv';
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
</style>
