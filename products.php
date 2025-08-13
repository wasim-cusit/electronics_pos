<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'products';

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $unit = $_POST['unit'];
    $brand = trim($_POST['brand']);
    $low_stock_threshold = $_POST['low_stock_threshold'];
    $description = trim($_POST['description'] ?? '');
    $product_code = trim($_POST['product_code']);

    // Validate required fields
    if (empty($name) || empty($category_id) || empty($unit) || empty($low_stock_threshold)) {
        $error = "Please fill in all required fields.";
    } else {
        // Set defaults for optional fields
        $brand = $brand ?: 'Generic';
        $product_code = $product_code ?: 'PROD-' . time();

        try {
            $pdo->beginTransaction();
            
            // Insert product
            $stmt = $pdo->prepare("INSERT INTO products (product_name, category_id, product_unit, brand, alert_quantity, description, product_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category_id, $unit, $brand, $low_stock_threshold, $description, $product_code]);
            $product_id = $pdo->lastInsertId();
            
            $pdo->commit();
            header("Location: products.php?success=added");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error adding product: " . $e->getMessage();
        }
    }
}

// Handle Delete Product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: products.php?success=deleted");
    exit;
}

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $unit = $_POST['unit'];
    $brand = trim($_POST['brand']);
    $low_stock_threshold = $_POST['low_stock_threshold'];
    $description = trim($_POST['description'] ?? '');
    $product_code = trim($_POST['product_code']);

    // Validate required fields
    if (empty($name) || empty($category_id) || empty($unit) || empty($low_stock_threshold)) {
        $error = "Please fill in all required fields.";
    } else {
        // Set defaults for optional fields
        $brand = $brand ?: 'Generic';
        $product_code = $product_code ?: 'PROD-' . time();

        try {
            $stmt = $pdo->prepare("UPDATE products SET product_name=?, category_id=?, product_unit=?, brand=?, alert_quantity=?, description=?, product_code=? WHERE id=?");
            $stmt->execute([$name, $category_id, $unit, $brand, $low_stock_threshold, $description, $product_code, $id]);
            header("Location: products.php?success=updated");
            exit;
        } catch (Exception $e) {
            $error = "Error updating product: " . $e->getMessage();
        }
    }
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all products
$products = $pdo->query("
    SELECT p.*, c.category AS category_name
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

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
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">Products</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo "Product added successfully!";
                    if ($_GET['success'] === 'updated') echo "Product updated successfully!";
                    if ($_GET['success'] === 'deleted') echo "Product deleted successfully!";
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_POST) && !empty($_POST)): ?>
                <div class="alert alert-info">
                    <strong>Debug Info:</strong><br>
                    POST Data: <?= htmlspecialchars(print_r($_POST, true)) ?>
                </div>
            <?php endif; ?>

            <!-- Add/Edit Product Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <?= $edit_product ? "Edit Product" : "Add Product" ?>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?php if ($edit_product): ?>
                            <input type="hidden" name="id" value="<?= $edit_product['id'] ?>">
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_product['product_name'] ?? '') ?>">
                            </div>
                            <div class="mb-3" style="width: 20%;">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= (isset($edit_product['category_id']) && $edit_product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['category']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3" style="width: 15%;">
                                <label class="form-label">Unit</label>
                                <select name="unit" class="form-control" required>
                                    <option value="meter" <?= (isset($edit_product['product_unit']) && $edit_product['product_unit'] == 'meter') ? 'selected' : '' ?>>Meter</option>
                                    <option value="piece" <?= (isset($edit_product['product_unit']) && $edit_product['product_unit'] == 'piece') ? 'selected' : '' ?>>Piece</option>
                                    <option value="set" <?= (isset($edit_product['product_unit']) && $edit_product['product_unit'] == 'set') ? 'selected' : '' ?>>Set</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3" >
                                <label class="form-label">Product Code</label>
                                <input type="text" name="product_code" class="form-control" value="<?= htmlspecialchars($edit_product['product_code'] ?? '') ?>" placeholder="Enter product code or barcode">
                            </div>
                           
                            <div class="mb-3" style="width: 20%;">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($edit_product['brand'] ?? '') ?>">
                            </div>
                            <div class="mb-3" style="width: 15%;">
                                <label class="form-label">Low Stock Alert</label>
                                <input type="number" step="0.01" name="low_stock_threshold" class="form-control" required value="<?= htmlspecialchars($edit_product['alert_quantity'] ?? '') ?>">
                                <small class="text-muted">Alert when stock falls below this value</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2" ><?= htmlspecialchars($edit_product['description'] ?? '') ?></textarea>
                            </div>

                        </div>
                        <button type="submit" class="btn btn-primary" name="<?= $edit_product ? 'edit_product' : 'add_product' ?>">
                            <?= $edit_product ? "Update Product" : "Add Product" ?>
                        </button>
                        <?php if ($edit_product): ?>
                            <a href="products.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>



            <!-- Product List Table -->
            <div class="card">
                <div class="card-header">Product List</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Product Code</th>
                                <th>Brand</th>
                                <th>Description</th>
                                <th>Low Stock Alert</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                                    <td><?= htmlspecialchars($product['product_unit']) ?></td>
                                    <td><?= htmlspecialchars($product['product_code'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($product['brand']) ?></td>
                                    <td><?= htmlspecialchars(substr($product['description'] ?? '', 0, 50)) ?><?= (strlen($product['description'] ?? '') > 50 ? '...' : '') ?></td>
                                    <td><?= htmlspecialchars($product['alert_quantity']) ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php if ($product['created_at']): ?>
                                                <?= date('d M Y', strtotime($product['created_at'])) ?><br>
                                                <span class="text-secondary"><?= date('H:i', strtotime($product['created_at'])) ?></span>
                                            <?php else: ?>
                                                <span class="text-secondary">N/A</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <a href="products.php?edit=<?= $product['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="products.php?delete=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include 'includes/footer.php'; ?>