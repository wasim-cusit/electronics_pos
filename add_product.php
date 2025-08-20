<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'add_product';

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
        header("Location: add_product.php?error=" . urlencode($error));
        exit;
    }
    
    $id = intval($_POST['id']);
    $name = sanitize_input(trim($_POST['name']));
    $category_id = intval($_POST['category_id']);
    $unit = sanitize_input($_POST['unit']);
    $low_stock_threshold = intval($_POST['low_stock_threshold']);
    $description = sanitize_input(trim($_POST['description'] ?? ''));
    $product_code = sanitize_input(trim($_POST['product_code']));

    // Validate required fields
    if (empty($name) || empty($category_id) || empty($unit) || empty($low_stock_threshold)) {
        $error = "Please fill in all required fields.";
    } elseif ($low_stock_threshold < 0) {
        $error = "Low stock threshold must be a positive number.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE products SET product_name=?, category_id=?, product_unit=?, alert_quantity=?, description=?, product_code=? WHERE id=?");
            $stmt->execute([$name, $category_id, $unit, $low_stock_threshold, $description, $product_code, $id]);
            header("Location: add_product.php?success=updated&product_id=" . $id);
            exit;
        } catch (Exception $e) {
            $error = "Error updating product: " . $e->getMessage();
        }
    }
}

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
        header("Location: add_product.php?error=" . urlencode($error));
        exit;
    }
    
    $name = sanitize_input(trim($_POST['name']));
    $category_id = intval($_POST['category_id']);
    $unit = sanitize_input($_POST['unit']);
    $low_stock_threshold = intval($_POST['low_stock_threshold']);
    $description = sanitize_input(trim($_POST['description'] ?? ''));
    $product_code = sanitize_input(trim($_POST['product_code']));

    // Validate required fields
    if (empty($name) || empty($category_id) || empty($unit) || empty($low_stock_threshold)) {
        $error = "Please fill in all required fields.";
    } elseif ($low_stock_threshold < 0) {
        $error = "Low stock threshold must be a positive number.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert product
            $stmt = $pdo->prepare("INSERT INTO products (product_name, category_id, product_unit, alert_quantity, description, product_code) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category_id, $unit, $low_stock_threshold, $description, $product_code]);
            $product_id = $pdo->lastInsertId();
            
            $pdo->commit();
            header("Location: add_product.php?success=added&product_id=" . $product_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error adding product: " . $e->getMessage();
        }
    }
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// If editing, fetch product
$edit_product = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 " style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-box-seam text-primary"></i> <?= $edit_product ? "Edit Electronics Product" : "Add New Electronics Product" ?></h2>
                <div class="d-flex">
                    <!-- <a href="products.php" class="btn btn-info me-2">
                        <i class="bi bi-eye"></i> Product Details
                    </a> -->
                    <!-- <a href="products.php" class="btn btn-secondary">
                        <i class="bi bi-list-ul"></i> View All Products
                    </a> -->
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    if ($_GET['success'] === 'added') echo "Product added successfully! <a href='products.php' target='_blank'>View All Products</a>";
                    if ($_GET['success'] === 'updated') echo "Product updated successfully! <a href='products.php' target='_blank'>View All Products</a>";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Add/Edit Product Form -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-box-seam"></i> <?= $edit_product ? "Edit Electronics Product" : "Create New Electronics Product" ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" id="addProductForm">
                        <!-- CSRF Protection -->
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <?php if ($edit_product): ?>
                            <input type="hidden" name="id" value="<?= $edit_product['id'] ?>">
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Product Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="Enter product name" value="<?= htmlspecialchars($edit_product['product_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Category *</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= (isset($edit_product['category_id']) && $edit_product['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['category']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Unit *</label>
                                <select name="unit" class="form-control" required>
                                    <option value="">Select Unit</option>
                                    <option value="piece" <?= (isset($edit_product['product_unit']) && $edit_product['product_unit'] == 'piece') ? 'selected' : '' ?>>Piece</option>
                                    <option value="unit" <?= (isset($edit_product['product_unit']) && $edit_product['product_unit'] == 'unit') ? 'selected' : '' ?>>Unit</option>
                                    <option value="set" <?= (isset($edit_product['product_unit']) && $edit_product['product_unit'] == 'set') ? 'selected' : '' ?>>Set</option>
                                    <option value="box" <?= (isset($edit_product['product_unit']) && $edit_product['product_unit'] == 'box') ? 'selected' : '' ?>>Box</option>
                                    <option value="pack" <?= (isset($edit_product['product_unit']) && $edit_product['product_unit'] == 'pack') ? 'selected' : '' ?>>Pack</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Product Code</label>
                                <input type="text" name="product_code" class="form-control" placeholder="Enter product code or barcode" value="<?= htmlspecialchars($edit_product['product_code'] ?? '') ?>">
                            </div>

                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Low Stock Alert *</label>
                                <input type="number" step="0.01" name="low_stock_threshold" class="form-control" required placeholder="Enter threshold value" value="<?= htmlspecialchars($edit_product['alert_quantity'] ?? '') ?>">
                                <small class="text-muted">Alert when stock falls below this value</small>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Enter product description, features, specifications, or technical details"><?= htmlspecialchars($edit_product['description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" name="<?= $edit_product ? 'edit_product' : 'add_product' ?>">
                                <i class="bi bi-<?= $edit_product ? 'check-circle' : 'plus-circle' ?>"></i> <?= $edit_product ? 'Update Electronics Product' : 'Add Electronics Product' ?>
                            </button>
                            <?php if ($edit_product): ?>
                                <a href="products.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            <?php else: ?>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="bi bi-arrow-clockwise"></i> Reset Form
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>


        </main>
    </div>
</div>

<script>
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

document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    document.getElementById('addProductForm').addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showNotification('Please fill in all required fields.', 'warning');
        }
    });
    
    // Remove validation styling on input
    document.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
