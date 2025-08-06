<?php
require_once 'includes/auth.php';
require_login();
$activePage = 'dashboard';
include 'includes/header.php';
?>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
  <h2 class="mb-4">Dashboard</h2>
  <div class="row g-4">
    <div class="col-md-4">
      <div class="card text-bg-primary mb-3">
        <div class="card-body">
          <h5 class="card-title">Today's Sales</h5>
          <p class="card-text display-6">PKR 0.00</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-bg-success mb-3">
        <div class="card-body">
          <h5 class="card-title">Total Stock Value</h5>
          <p class="card-text display-6">PKR 0.00</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-bg-warning mb-3">
        <div class="card-body">
          <h5 class="card-title">Upcoming Deliveries</h5>
          <p class="card-text display-6">0</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-bg-danger mb-3">
        <div class="card-body">
          <h5 class="card-title">Low Stock Alerts</h5>
          <p class="card-text display-6">0</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-bg-info mb-3">
        <div class="card-body">
          <h5 class="card-title">Today's Expenses</h5>
          <p class="card-text display-6">PKR 0.00</p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>