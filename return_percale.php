<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/settings.php';
require_login();

$activePage = 'return_percale';

// Handle Add Return Percale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_return'])) {
    $return_no = 'RET-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $return_type = trim($_POST['return_type']);
    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
    $fabric_name = trim($_POST['fabric_name']);
    $fabric_type = trim($_POST['fabric_type']);
    $color = trim($_POST['color']);
    $quantity = floatval($_POST['quantity']);
    $unit = trim($_POST['unit']);
    $original_price = floatval($_POST['original_price']);
    $return_price = floatval($_POST['return_price']);
    $return_reason = trim($_POST['return_reason']);
    $return_date = trim($_POST['return_date']);
    $notes = trim($_POST['notes']);

    // Validate required fields
    $missing_fields = [];
    if (empty($fabric_name)) $missing_fields[] = 'Fabric Name';
    if (empty($quantity)) $missing_fields[] = 'Quantity';
    if (empty($original_price)) $missing_fields[] = 'Original Price';
    if (empty($return_price)) $missing_fields[] = 'Return Price';
    if (empty($return_date)) $missing_fields[] = 'Return Date';

    if (!empty($missing_fields)) {
        $error_msg = 'Missing required fields: ' . implode(', ', $missing_fields);
        header("Location: return_percale.php?error=missing_fields&message=" . urlencode($error_msg));
        exit;
    }

    // Validate return type specific fields
    if ($return_type === 'customer_return' && empty($customer_id)) {
        header("Location: return_percale.php?error=missing_customer");
        exit;
    }

    if ($return_type === 'supplier_return' && empty($supplier_id)) {
        header("Location: return_percale.php?error=missing_supplier");
        exit;
    }

    // Validate prices
    if ($original_price <= 0 || $return_price <= 0) {
        header("Location: return_percale.php?error=invalid_prices");
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO return_percale (return_no, return_type, customer_id, supplier_id, fabric_name, fabric_type, color, quantity, unit, original_price, return_price, return_reason, return_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$return_no, $return_type, $customer_id, $supplier_id, $fabric_name, $fabric_type, $color, $quantity, $unit, $original_price, $return_price, $return_reason, $return_date, $notes, $_SESSION['user_id']]);

    header("Location: return_percale.php?success=added");
    exit;
}

// Handle Edit Return Percale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_return'])) {
    $id = intval($_POST['id']);
    $return_type = trim($_POST['return_type']);
    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
    $fabric_name = trim($_POST['fabric_name']);
    $fabric_type = trim($_POST['fabric_type']);
    $color = trim($_POST['color']);
    $quantity = floatval($_POST['quantity']);
    $unit = trim($_POST['unit']);
    $original_price = floatval($_POST['original_price']);
    $return_price = floatval($_POST['return_price']);
    $return_reason = trim($_POST['return_reason']);
    $return_date = trim($_POST['return_date']);
    $notes = trim($_POST['notes']);

    // Validate required fields
    $missing_fields = [];
    if (empty($fabric_name)) $missing_fields[] = 'Fabric Name';
    if (empty($quantity)) $missing_fields[] = 'Quantity';
    if (empty($original_price)) $missing_fields[] = 'Original Price';
    if (empty($return_price)) $missing_fields[] = 'Return Price';
    if (empty($return_date)) $missing_fields[] = 'Return Date';

    if (!empty($missing_fields)) {
        $error_msg = 'Missing required fields: ' . implode(', ', $missing_fields);
        header("Location: return_percale.php?error=missing_fields&message=" . urlencode($error_msg));
        exit;
    }

    $stmt = $pdo->prepare("UPDATE return_percale SET return_type=?, customer_id=?, supplier_id=?, fabric_name=?, fabric_type=?, color=?, quantity=?, unit=?, original_price=?, return_price=?, return_reason=?, return_date=?, notes=? WHERE id=?");
    $stmt->execute([$return_type, $customer_id, $supplier_id, $fabric_name, $fabric_type, $color, $quantity, $unit, $original_price, $return_price, $return_reason, $return_date, $notes, $id]);

    header("Location: return_percale.php?success=updated");
    exit;
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = intval($_POST['id']);
    $status = trim($_POST['status']);
    $approved_by = $_SESSION['user_id'];
    $approved_date = date('Y-m-d');

    $stmt = $pdo->prepare("UPDATE return_percale SET status=?, approved_by=?, approved_date=? WHERE id=?");
    $stmt->execute([$status, $approved_by, $approved_date, $id]);

    header("Location: return_percale.php?success=status_updated");
    exit;
}

// Handle Delete Return Percale
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Verify return exists before deletion
    $stmt = $pdo->prepare("SELECT id FROM return_percale WHERE id = ?");
    $stmt->execute([$id]);
    $return = $stmt->fetch();

    if (!$return) {
        header("Location: return_percale.php?error=not_found");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM return_percale WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: return_percale.php?success=deleted");
    exit;
}

// Build search and filter conditions
$where_conditions = [];
$params = [];

if (!empty($_GET['date_from'])) {
    $where_conditions[] = "rp.return_date >= ?";
    $params[] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $where_conditions[] = "rp.return_date <= ?";
    $params[] = $_GET['date_to'];
}

if (!empty($_GET['return_type_filter'])) {
    $where_conditions[] = "rp.return_type = ?";
    $params[] = $_GET['return_type_filter'];
}

if (!empty($_GET['status_filter'])) {
    $where_conditions[] = "rp.status = ?";
    $params[] = $_GET['status_filter'];
}

if (!empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_conditions[] = "(rp.fabric_name LIKE ? OR rp.return_no LIKE ? OR rp.fabric_type LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Fetch filtered returns with customer/supplier info
$query = "SELECT rp.*, 
          c.name as customer_name, 
          s.supplier_name,
          u.name as created_by_name,
          au.name as approved_by_name
          FROM return_percale rp 
          LEFT JOIN customer c ON rp.customer_id = c.id 
          LEFT JOIN supplier s ON rp.supplier_id = s.id
          LEFT JOIN system_users u ON rp.created_by = u.id
          LEFT JOIN system_users au ON rp.approved_by = au.id
          $where_clause 
          ORDER BY rp.return_date DESC, rp.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_returns = count($returns);
$pending_returns = 0;
$approved_returns = 0;
$rejected_returns = 0;
$total_return_value = 0;

foreach ($returns as $return) {
    $total_return_value += $return['return_price'];

    switch ($return['status']) {
        case 'pending':
            $pending_returns++;
            break;
        case 'approved':
            $approved_returns++;
            break;
        case 'rejected':
            $rejected_returns++;
            break;
    }
}

// If editing, fetch return
$edit_return = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM return_percale WHERE id = ?");
    $stmt->execute([$id]);
    $edit_return = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch customers for dropdown
$customers = $pdo->query("SELECT id, name as customer_name FROM customer WHERE status = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch suppliers for dropdown
$suppliers = $pdo->query("SELECT id, supplier_name FROM supplier WHERE status = 1 ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">Return Percale Management</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" id="successAlert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php
                    if ($_GET['success'] === 'added') echo "Return percale added successfully!";
                    if ($_GET['success'] === 'updated') echo "Return percale updated successfully!";
                    if ($_GET['success'] === 'deleted') echo "Return percale deleted successfully!";
                    if ($_GET['success'] === 'status_updated') echo "Status updated successfully!";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" id="errorAlert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php
                    if ($_GET['error'] === 'invalid_prices') echo "Prices must be greater than 0!";
                    if ($_GET['error'] === 'missing_fields') {
                        echo isset($_GET['message']) ? htmlspecialchars($_GET['message']) : "Please fill in all required fields!";
                    }
                    if ($_GET['error'] === 'missing_customer') echo "Please select a customer for customer return!";
                    if ($_GET['error'] === 'missing_supplier') echo "Please select a supplier for supplier return!";
                    if ($_GET['error'] === 'not_found') echo "Return percale not found!";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Return Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Returns</h5>
                            <p class="card-text display-6"><?= $total_returns ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Pending</h5>
                            <p class="card-text display-6"><?= $pending_returns ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Approved</h5>
                            <p class="card-text display-6"><?= $approved_returns ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Total Value</h5>
                            <p class="card-text display-6"><?= format_currency($total_return_value) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Return Form -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?= $edit_return ? 'Edit Return Percale' : 'Add New Return Percale' ?>
                    </h5>
                    <?php if ($edit_return): ?>
                        <a href="return_percale.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="return_type" class="form-label">Return Type *</label>
                                    <select class="form-select" id="return_type" name="return_type" required>
                                        <option value="">Select Return Type</option>
                                        <option value="customer_return" <?= ($edit_return && $edit_return['return_type'] === 'customer_return') ? 'selected' : '' ?>>Customer Return</option>
                                        <option value="supplier_return" <?= ($edit_return && $edit_return['return_type'] === 'supplier_return') ? 'selected' : '' ?>>Supplier Return</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="return_date" class="form-label">Return Date *</label>
                                    <input type="date" class="form-control" id="return_date" name="return_date" value="<?= $edit_return ? $edit_return['return_date'] : date('Y-m-d') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label customer-field">Customer</label>
                                    <select class="form-select" id="customer_id" name="customer_id">
                                        <option value="">Select Customer</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= $customer['id'] ?>" <?= ($edit_return && $edit_return['customer_id'] == $customer['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($customer['customer_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="supplier_id" class="form-label supplier-field" style="display: none;">Supplier</label>
                                    <select class="form-select" id="supplier_id" name="supplier_id" style="display: none;">
                                        <option value="">Select Supplier</option>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?= $supplier['id'] ?>" <?= ($edit_return && $edit_return['supplier_id'] == $supplier['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($supplier['supplier_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="fabric_name" class="form-label">Fabric Name *</label>
                                    <input type="text" class="form-control" id="fabric_name" name="fabric_name" value="<?= $edit_return ? htmlspecialchars($edit_return['fabric_name']) : '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="fabric_type" class="form-label">Fabric Type</label>
                                    <input type="text" class="form-control" id="fabric_type" name="fabric_type" value="<?= $edit_return ? htmlspecialchars($edit_return['fabric_type']) : '' ?>" placeholder="e.g., Cotton, Silk, Polyester">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="color" class="form-label">Color</label>
                                    <input type="text" class="form-control" id="color" name="color" value="<?= $edit_return ? htmlspecialchars($edit_return['color']) : '' ?>" placeholder="e.g., Red, Blue, White">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity *</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="<?= $edit_return ? $edit_return['quantity'] : '' ?>" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="unit" class="form-label">Unit</label>
                                    <select class="form-select" id="unit" name="unit">
                                        <option value="meters" <?= ($edit_return && $edit_return['unit'] === 'meters') ? 'selected' : '' ?>>Meters</option>
                                        <!-- <option value="yards" <?= ($edit_return && $edit_return['unit'] === 'yards') ? 'selected' : '' ?>>Yards</option> -->
                                        <option value="pieces" <?= ($edit_return && $edit_return['unit'] === 'pieces') ? 'selected' : '' ?>>Pieces</option>
                                        <option value="rolls" <?= ($edit_return && $edit_return['unit'] === 'rolls') ? 'selected' : '' ?>>Rolls</option>
                                    </select>
                                </div>
                            </div>
                        </div>



                        <div class="row">
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="original_price" class="form-label">Original Price *</label>
                                    <input type="number" class="form-control" id="original_price" name="original_price" value="<?= $edit_return ? $edit_return['original_price'] : '' ?>" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="return_price" class="form-label">Return Price *</label>
                                    <input type="number" class="form-control" id="return_price" name="return_price" value="<?= $edit_return ? $edit_return['return_price'] : '' ?>" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="return_reason" class="form-label">Return Reason</label>
                                <textarea class="form-control" id="return_reason" name="return_reason" rows="3" placeholder="Describe the reason for return..."><?= $edit_return ? htmlspecialchars($edit_return['return_reason']) : '' ?></textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="notes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any additional notes..."><?= $edit_return ? htmlspecialchars($edit_return['notes']) : '' ?></textarea>
                            </div>
                        </div>





                        <?php if ($edit_return): ?>
                            <input type="hidden" name="id" value="<?= $edit_return['id'] ?>">
                            <button type="submit" name="edit_return" class="btn btn-primary">Update Return</button>
                        <?php else: ?>
                            <button type="submit" name="add_return" class="btn btn-primary">Add Return</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?= $_GET['search'] ?? '' ?>" placeholder="Fabric name, return no...">
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $_GET['date_from'] ?? '' ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $_GET['date_to'] ?? '' ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="return_type_filter" class="form-label">Return Type</label>
                            <select class="form-select" id="return_type_filter" name="return_type_filter">
                                <option value="">All Types</option>
                                <option value="customer_return" <?= ($_GET['return_type_filter'] ?? '') === 'customer_return' ? 'selected' : '' ?>>Customer Return</option>
                                <option value="supplier_return" <?= ($_GET['return_type_filter'] ?? '') === 'supplier_return' ? 'selected' : '' ?>>Supplier Return</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status_filter" class="form-label">Status</label>
                            <select class="form-select" id="status_filter" name="status_filter">
                                <option value="">All Status</option>
                                <option value="pending" <?= ($_GET['status_filter'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="approved" <?= ($_GET['status_filter'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="rejected" <?= ($_GET['status_filter'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                <option value="completed" <?= ($_GET['status_filter'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Returns Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Return Percale Records</h5>
                    <div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i>Print
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="exportToCSV()">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="returnsTable">
                            <thead>
                                <tr>
                                    <th>Return No</th>
                                    <th>Type</th>
                                    <th>Customer/Supplier</th>
                                    <th>Fabric Name</th>
                                    <th>Quantity</th>
                                    <th>Original Price</th>
                                    <th>Return Price</th>
                                    <th>Return Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($returns)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">No return percale records found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($returns as $return): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($return['return_no']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge <?= $return['return_type'] === 'customer_return' ? 'bg-primary' : 'bg-info' ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $return['return_type'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($return['return_type'] === 'customer_return' && $return['customer_name']): ?>
                                                    <span class="text-primary"><?= htmlspecialchars($return['customer_name']) ?></span>
                                                <?php elseif ($return['return_type'] === 'supplier_return' && $return['supplier_name']): ?>
                                                    <span class="text-info"><?= htmlspecialchars($return['supplier_name']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($return['fabric_name']) ?></strong>
                                                    <?php if ($return['fabric_type']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($return['fabric_type']) ?></small>
                                                    <?php endif; ?>
                                                    <?php if ($return['color']): ?>
                                                        <br><small class="text-muted">Color: <?= htmlspecialchars($return['color']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?= $return['quantity'] ?> <?= $return['unit'] ?>
                                            </td>
                                            <td><?= format_currency($return['original_price']) ?></td>
                                            <td><?= format_currency($return['return_price']) ?></td>
                                            <td><?= date('M j, Y', strtotime($return['return_date'])) ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($return['status']) {
                                                    case 'pending':
                                                        $status_class = 'bg-warning';
                                                        break;
                                                    case 'approved':
                                                        $status_class = 'bg-success';
                                                        break;
                                                    case 'rejected':
                                                        $status_class = 'bg-danger';
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'bg-info';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?= $status_class ?>">
                                                    <?= ucfirst($return['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?edit=<?= $return['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#statusModal<?= $return['id'] ?>">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                    <a href="?delete=<?= $return['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this return?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
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

<!-- Status Update Modals -->
<?php foreach ($returns as $return): ?>
    <div class="modal fade" id="statusModal<?= $return['id'] ?>" tabindex="-1" aria-labelledby="statusModalLabel<?= $return['id'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel<?= $return['id'] ?>">Update Status - <?= htmlspecialchars($return['return_no']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $return['id'] ?>">
                        <div class="mb-3">
                            <label for="status<?= $return['id'] ?>" class="form-label">Status</label>
                            <select class="form-select" id="status<?= $return['id'] ?>" name="status" required>
                                <option value="pending" <?= $return['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="approved" <?= $return['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="rejected" <?= $return['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                <option value="completed" <?= $return['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
    // Toggle customer/supplier fields based on return type
    document.getElementById('return_type').addEventListener('change', function() {
        const returnType = this.value;
        const customerField = document.querySelector('.customer-field');
        const supplierField = document.querySelector('.supplier-field');
        const customerSelect = document.getElementById('customer_id');
        const supplierSelect = document.getElementById('supplier_id');

        if (returnType === 'customer_return') {
            customerField.style.display = 'block';
            supplierField.style.display = 'none';
            customerSelect.style.display = 'block';
            supplierSelect.style.display = 'none';
            customerSelect.required = true;
            supplierSelect.required = false;
        } else if (returnType === 'supplier_return') {
            customerField.style.display = 'none';
            supplierField.style.display = 'block';
            customerSelect.style.display = 'none';
            supplierSelect.style.display = 'block';
            customerSelect.required = false;
            supplierSelect.required = true;
        } else {
            customerField.style.display = 'none';
            supplierField.style.display = 'none';
            customerSelect.style.display = 'none';
            supplierSelect.style.display = 'none';
            customerSelect.required = false;
            supplierSelect.required = false;
        }
    });

    // Export to CSV function
    function exportToCSV() {
        const table = document.getElementById('returnsTable');
        const rows = table.querySelectorAll('tbody tr');

        let csv = 'Return No,Type,Customer/Supplier,Fabric Name,Quantity,Original Price,Return Price,Return Date,Status\n';

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 1) { // Skip empty rows
                const rowData = [];
                cells.forEach((cell, index) => {
                    if (index < 9) { // Only export first 9 columns
                        let text = cell.textContent.trim();
                        // Remove HTML tags and clean up
                        text = text.replace(/<[^>]*>/g, '');
                        text = text.replace(/"/g, '""');
                        rowData.push('"' + text + '"');
                    }
                });
                csv += rowData.join(',') + '\n';
            }
        });

        const blob = new Blob([csv], {
            type: 'text/csv'
        });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'return_percale_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    }

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>

<?php include 'includes/footer.php'; ?>