<?php
require_once 'includes/auth.php';
require_login();
$activePage = 'suppliers';
include 'includes/header.php';
?>
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
      <h2 class="mb-4">Suppliers</h2>
      <div class="alert alert-info">Supplier management coming soon...</div>
    </main>
  </div>
</div>
<?php include 'includes/footer.php'; ?>