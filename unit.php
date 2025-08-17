<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'units';

// Ensure table exists and load units
$pdo->exec(
  "CREATE TABLE IF NOT EXISTS unit_prices (
      id INT AUTO_INCREMENT PRIMARY KEY,
      unit_name VARCHAR(100) NOT NULL UNIQUE,
      unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
);

$units = $pdo->query("SELECT id, unit_name, unit_price FROM unit_prices ORDER BY unit_name")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Unit List</h2>
    <a href="add_unit.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Unit</a>
  </div>

  <div class="card">
    <div class="card-header">Units</div>
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Unit</th>
            <th>Price</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($units as $row): ?>
            <tr>
              <td data-label="Unit"><strong><?= htmlspecialchars($row['unit_name']) ?></strong></td>
              <td data-label="Price"><?= htmlspecialchars(number_format((float)$row['unit_price'], 2)) ?></td>
              <td data-label="Actions">
                <div class="d-flex flex-wrap gap-1">
                  <a href="add_unit.php?edit=<?= (int)$row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                  <a href="add_unit.php?delete=<?= (int)$row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this unit? This will remove it from allowed units.');">Delete</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($units)): ?>
            <tr>
              <td colspan="3" class="text-center">No units found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>


