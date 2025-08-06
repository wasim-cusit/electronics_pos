<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'products';

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $unit = $_POST['unit'];
    $size = $_POST['size'];
    $color = $_POST['color'];
    $brand = $_POST['brand'];
    $cost_price = $_POST['cost_price'];
    $sale_price = $_POST['sale_price'];
    $stock_quantity = $_POST['stock_quantity'];
    $barcode = $_POST['barcode'];

    $stmt = $pdo->prepare("INSERT INTO products (name, category_id, unit, size, color, brand, cost_price, sale_price, stock_quantity, barcode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $category_id, $unit, $size, $color, $brand, $cost_price, $sale_price, $stock_quantity, $barcode]);
    header("Location: products.php?success=added");
    exit;
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
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $unit = $_POST['unit'];
    $size = $_POST['size'];
    $color = $_POST['color'];
    $brand = $_POST['brand'];
    $cost_price = $_POST['cost_price'];
    $sale_price = $_POST['sale_price'];
    $stock_quantity = $_POST['stock_quantity'];
    $barcode = $_POST['barcode'];

    $stmt = $pdo->prepare("UPDATE products SET name=?, category_id=?, unit=?, size=?, color=?, brand=?, cost_price=?, sale_price=?, stock_quantity=?, barcode=? WHERE id=?");
    $stmt->execute([$name, $category_id, $unit, $size, $color, $brand, $cost_price, $sale_price, $stock_quantity, $barcode, $id]);
    header("Location: products.php?success=updated");
    exit;
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all products
$products = $pdo->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);

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
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_product['name'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= (isset($edit_product['category_id']) && $edit_product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Unit</label>
                                <select name="unit" class="form-control" required>
                                    <option value="piece" <?= (isset($edit_product['unit']) && $edit_product['unit'] == 'piece') ? 'selected' : '' ?>>Piece</option>
                                    <option value="meter" <?= (isset($edit_product['unit']) && $edit_product['unit'] == 'meter') ? 'selected' : '' ?>>Meter</option>
                                    <option value="set" <?= (isset($edit_product['unit']) && $edit_product['unit'] == 'set') ? 'selected' : '' ?>>Set</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Size</label>
                                <input type="text" name="size" class="form-control" value="<?= htmlspecialchars($edit_product['size'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Color</label>
                                <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($edit_product['color'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($edit_product['brand'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cost Price</label>
                                <input type="number" step="0.01" name="cost_price" class="form-control" required value="<?= htmlspecialchars($edit_product['cost_price'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Sale Price</label>
                                <input type="number" step="0.01" name="sale_price" class="form-control" required value="<?= htmlspecialchars($edit_product['sale_price'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stock Quantity</label>
                                <input type="number" step="0.01" name="stock_quantity" class="form-control" required value="<?= htmlspecialchars($edit_product['stock_quantity'] ?? '') ?>">
                                <small class="text-muted">Use decimals for meters (e.g., 2.5)</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Barcode</label>
                                <input type="text" name="barcode" class="form-control" value="<?= htmlspecialchars($edit_product['barcode'] ?? '') ?>">
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
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Size</th>
                                <th>Color</th>
                                <th>Brand</th>
                                <th>Cost Price</th>
                                <th>Sale Price</th>
                                <th>Stock</th>
                                <th>Barcode</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                                    <td><?= htmlspecialchars($product['unit']) ?></td>
                                    <td><?= htmlspecialchars($product['size']) ?></td>
                                    <td><?= htmlspecialchars($product['color']) ?></td>
                                    <td><?= htmlspecialchars($product['brand']) ?></td>
                                    <td><?= htmlspecialchars($product['cost_price']) ?></td>
                                    <td><?= htmlspecialchars($product['sale_price']) ?></td>
                                    <td><?= htmlspecialchars($product['stock_quantity']) ?></td>
                                    <td><?= htmlspecialchars($product['barcode']) ?></td>
                                    <td>
                                        <a href="products.php?edit=<?= $product['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="products.php?delete=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($products)): ?>
                                <tr><td colspan="11" class="text-center">No products found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include 'includes/footer.php'; ?>