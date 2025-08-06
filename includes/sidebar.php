<?php
require_once __DIR__ . '/config.php';
?>
<nav class="sidebar" id="sidebar">
  <div class="p-3">
    <ul class="nav flex-column mb-4">
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage==='dashboard'?' active':'' ?>" href="<?= $base_url ?>dashboard.php">
          <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage==='products'?' active':'' ?>" href="<?= $base_url ?>products.php">
          <i class="bi bi-box-seam me-2"></i>Products
        </a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage==='purchases'?' active':'' ?>" href="<?= $base_url ?>purchases.php">
          <i class="bi bi-cart-plus me-2"></i>Purchases
        </a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage==='sales'?' active':'' ?>" href="<?= $base_url ?>sales.php">
          <i class="bi bi-cash-coin me-2"></i>Sales
        </a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage==='customers'?' active':'' ?>" href="<?= $base_url ?>customers.php">
          <i class="bi bi-people me-2"></i>Customers
        </a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage==='suppliers'?' active':'' ?>" href="<?= $base_url ?>suppliers.php">
          <i class="bi bi-truck me-2"></i>Suppliers
        </a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage==='expenses'?' active':'' ?>" href="<?= $base_url ?>expenses.php">
          <i class="bi bi-receipt me-2"></i>Expenses
        </a>
      </li>
    </ul>
    <ul class="nav flex-column mb-4">
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage==='notifications'?' active':'' ?>" href="<?= $base_url ?>notifications.php">
          <i class="bi bi-bell me-2"></i>Notifications
        </a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage==='reports'?' active':'' ?>" href="<?= $base_url ?>reports.php">
          <i class="bi bi-graph-up-arrow me-2"></i>Reports
        </a>
      </li>
      <?php if (function_exists('has_role') && has_role('Admin')): ?>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage==='users'?' active':'' ?>" href="<?= $base_url ?>users.php">
          <i class="bi bi-person-gear me-2"></i>User Management
        </a>
      </li>
      <?php endif; ?>
      <li class="nav-item mt-3">
        <a class="nav-link text-danger<?= $activePage==='logout'?' active':'' ?>" href="<?= $base_url ?>logout.php">
          <i class="bi bi-box-arrow-right me-2"></i>Logout
        </a>
      </li>
    </ul>
  </div>
</nav>