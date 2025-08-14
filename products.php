<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'products';

// Handle Delete Product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: products.php?success=deleted");
    exit;
}

// Fetch all products
$products = $pdo->query("
    SELECT p.*, c.category AS category_name
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-box-seam text-primary"></i> Product Details</h2>
                <a href="add_product.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Product
                </a>
            </div>

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



            <!-- Product List Table -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="bi bi-list-ul"></i> Product List</h6>
                </div>
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
                                        <a href="add_product.php?edit=<?= $product['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
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