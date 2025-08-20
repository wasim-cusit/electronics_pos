<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'categories';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
        header("Location: categories.php?error=" . urlencode($error));
        exit;
    }
    
    $name = sanitize_input(trim($_POST['name']));
    $description = sanitize_input(trim($_POST['description'] ?? ''));
    
    // Validate input
    if (empty($name)) {
        $error = "Category name is required!";
    } else {
        // Check if category name already exists
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE category = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $error = "Category name already exists!";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (category, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                header("Location: categories.php?success=added");
                exit;
            } catch (Exception $e) {
                $error = "Error adding category: " . $e->getMessage();
            }
        }
    }
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
        header("Location: categories.php?error=" . urlencode($error));
        exit;
    }
    
    $id = intval($_POST['id']);
    $name = sanitize_input(trim($_POST['name']));
    $description = sanitize_input(trim($_POST['description'] ?? ''));
    
    // Validate input
    if (empty($name)) {
        $error = "Category name is required!";
    } else {
        // Check if category name already exists (excluding current category)
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE category = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetch()) {
            $error = "Category name already exists!";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET category = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $id]);
                header("Location: categories.php?success=updated");
                exit;
            } catch (Exception $e) {
                $error = "Error updating category: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    // CSRF Protection for GET requests (using referrer check)
    if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) === false) {
        $error = "Invalid request. Please try again.";
        header("Location: categories.php?error=" . urlencode($error));
        exit;
    }
    
    $id = intval($_GET['delete']);
    
    // Check if category is being used by any products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    $product_count = $stmt->fetchColumn();
    
    if ($product_count > 0) {
        $error = "Cannot delete category. It is being used by $product_count product(s).";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: categories.php?success=deleted");
            exit;
        } catch (Exception $e) {
            $error = "Error deleting category: " . $e->getMessage();
        }
    }
}

// Fetch all categories with product count
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id, c.category, c.description 
    ORDER BY c.category
")->fetchAll(PDO::FETCH_ASSOC);

// If editing, fetch category
$edit_category = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 " style="margin-top: 25px;">
            <h2 class="mb-4">📂 Product Categories</h2>
             <!-- Quick Stats -->
             <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card text-bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Categories</h5>
                            <p class="card-text display-6"><?= count($categories) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Categories with Products</h5>
                            <p class="card-text display-6">
                                <?= count(array_filter($categories, function($cat) { return $cat['product_count'] > 0; })) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Empty Categories</h5>
                            <p class="card-text display-6">
                                <?= count(array_filter($categories, function($cat) { return $cat['product_count'] == 0; })) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    if ($_GET['success'] === 'added') echo "Category added successfully!";
                    if ($_GET['success'] === 'updated') echo "Category updated successfully!";
                    if ($_GET['success'] === 'deleted') echo "Category deleted successfully!";
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


            <!-- Add/Edit Category Form -->
            <div class="card mb-4 mt-2">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?= $edit_category ? "✏️ Edit Category" : "➕ Add New Category" ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <!-- CSRF Protection -->
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <?php if ($edit_category): ?>
                            <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?= htmlspecialchars($edit_category['category'] ?? '') ?>"
                                       placeholder="e.g., Smartphones, Laptops, Accessories">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Description (Optional)</label>
                                <input type="text" name="description" class="form-control" 
                                       value="<?= htmlspecialchars($edit_category['description'] ?? '') ?>"
                                       placeholder="Brief description of the category">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" name="<?= $edit_category ? 'edit_category' : 'add_category' ?>">
                            <?= $edit_category ? "Update Category" : "Add Category" ?>
                        </button>
                        <?php if ($edit_category): ?>
                            <a href="categories.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Categories List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📋 All Categories</h5>
                    
                </div>
                
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Products Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?= $category['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($category['category']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($category['description'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= $category['product_count'] ?> product(s)
                                        </span>
                                    </td>
                                    <td>
                                        <a href="categories.php?edit=<?= $category['id'] ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <?php if ($category['product_count'] == 0): ?>
                                            <a href="categories.php?delete=<?= $category['id'] ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this category?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled 
                                                    title="Cannot delete - category has products">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        <i class="bi bi-inbox"></i> No categories found. Add your first category above!
                                    </td>
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
