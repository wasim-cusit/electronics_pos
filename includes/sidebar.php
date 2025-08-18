<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Get unread notification count
$unread_count = 0;
if (is_logged_in()) {
  $user_id = $_SESSION['user_id'];
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
  $stmt->execute([$user_id]);
  $unread_count = $stmt->fetchColumn();
}
?>
<nav class="sidebar" id="sidebar">
  <div class="p-3">
    <ul class="nav flex-column mb-4">
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'dashboard' ? ' active' : '' ?>" href="<?= $base_url ?>dashboard.php">
          <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'sales' || $activePage === 'add_sale' ? ' active' : '' ?>" href="#" data-bs-toggle="collapse" data-bs-target="#salesSubmenu" aria-expanded="<?= $activePage === 'sales' || $activePage === 'add_sale' ? 'true' : 'false' ?>" aria-controls="salesSubmenu">
          <i class="bi bi-cash-coin me-2"></i>Sales
          <i class="bi bi-chevron-right ms-auto"></i>
        </a>
        <div class="collapse<?= $activePage === 'sales' || $activePage === 'add_sale' ? ' show' : '' ?>" id="salesSubmenu">
          <ul class="nav flex-column ms-3">
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'add_sale' ? ' active' : '' ?>" href="<?= $base_url ?>add_sale.php">
                <i class="bi bi-cart-plus me-2"></i>Add Sale
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'sales' ? ' active' : '' ?>" href="<?= $base_url ?>sales.php">
                <i class="bi bi-list-ul me-2"></i>Sales Details
              </a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'purchases' || $activePage === 'purchase_details' || $activePage === 'add_purchase' ? ' active' : '' ?>" href="#" data-bs-toggle="collapse" data-bs-target="#purchasesSubmenu" aria-expanded="<?= $activePage === 'purchases' || $activePage === 'purchase_details' || $activePage === 'add_purchase' ? 'true' : 'false' ?>" aria-controls="purchasesSubmenu">
          <i class="bi bi-cart-plus me-2"></i>Purchases
          <i class="bi bi-chevron-right ms-auto"></i>
        </a>
        <div class="collapse<?= $activePage === 'purchases' || $activePage === 'purchase_details' || $activePage === 'add_purchase' ? ' show' : '' ?>" id="purchasesSubmenu">
          <ul class="nav flex-column ms-3">
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'add_purchase' ? ' active' : '' ?>" href="<?= $base_url ?>add_purchase.php">
                <i class="bi bi-plus-circle me-2"></i>Add Purchase
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'purchases' ? ' active' : '' ?>" href="<?= $base_url ?>purchases.php">
                <i class="bi bi-list-ul me-2"></i>Purchase Details
              </a>
            </li>
          </ul>
        </div>
      </li>
     
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'products' || $activePage === 'add_product' || $activePage === 'product_details' ? ' active' : '' ?>" href="#" data-bs-toggle="collapse" data-bs-target="#productsSubmenu" aria-expanded="<?= $activePage === 'products' || $activePage === 'add_product' || $activePage === 'product_details' ? 'true' : 'false' ?>" aria-controls="productsSubmenu">
          <i class="bi bi-box-seam me-2"></i>Products
          <i class="bi bi-chevron-right ms-auto"></i>
        </a>
        <div class="collapse<?= $activePage === 'products' || $activePage === 'add_product' || $activePage === 'product_details' ? ' show' : '' ?>" id="productsSubmenu">
          <ul class="nav flex-column ms-3">
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'add_product' ? ' active' : '' ?>" href="<?= $base_url ?>add_product.php">
                <i class="bi bi-plus-circle me-2"></i>Add Products
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'products' ? ' active' : '' ?>" href="<?= $base_url ?>products.php">
                <i class="bi bi-list-ul me-2"></i>Products Details
              </a>
            </li>
          </ul>
        </div>
      </li>

      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'categories' ? ' active' : '' ?>" href="<?= $base_url ?>categories.php">
          <i class="bi bi-tags me-2"></i>Categories
        </a>
      </li>



      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'customers' || $activePage === 'customer_payment' || $activePage === 'customer_payment_list' || $activePage === 'customer_payment_details' || $activePage === 'customer_ledger' ? ' active' : '' ?>" href="#" data-bs-toggle="collapse" data-bs-target="#customersSubmenu" aria-expanded="<?= $activePage === 'customers' || $activePage === 'customer_payment' || $activePage === 'customer_payment_list' || $activePage === 'customer_payment_details' || $activePage === 'customer_ledger' ? 'true' : 'false' ?>" aria-controls="customersSubmenu">
          <i class="bi bi-people me-2"></i>Customers
          <i class="bi bi-chevron-right ms-auto"></i>
        </a>
        <div class="collapse<?= $activePage === 'customers' || $activePage === 'customer_payment' || $activePage === 'customer_payment_list' || $activePage === 'customer_payment_details' || $activePage === 'customer_ledger' ? ' show' : '' ?>" id="customersSubmenu">
          <ul class="nav flex-column ms-3">
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'customers' ? ' active' : '' ?>" href="<?= $base_url ?>customers.php">
                <i class="bi bi-people me-2"></i>Customer
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'customer_payment' ? ' active' : '' ?>" href="<?= $base_url ?>customer_payment.php">
                <i class="bi bi-credit-card me-2"></i>Customer Payment
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'customer_payment_list' ? ' active' : '' ?>" href="<?= $base_url ?>customer_payment_list.php">
                <i class="bi bi-list-ul me-2"></i>Payment List
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'customer_payment_details' ? ' active' : '' ?>" href="<?= $base_url ?>customer_payment_details.php">
                <i class="bi bi-file-text me-2"></i>Payment Details
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'customer_ledger' ? ' active' : '' ?>" href="<?= $base_url ?>customer_ledger.php">
                <i class="bi bi-journal-text me-2"></i>Customer Ledger
              </a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'suppliers' || $activePage === 'supplier_payment' || $activePage === 'supplier_payment_list' || $activePage === 'supplier_payment_details' || $activePage === 'supplier_ledger' ? ' active' : '' ?>" href="#" data-bs-toggle="collapse" data-bs-target="#suppliersSubmenu" aria-expanded="<?= $activePage === 'suppliers' || $activePage === 'supplier_payment' || $activePage === 'supplier_payment_list' || $activePage === 'supplier_payment_details' || $activePage === 'supplier_ledger' ? 'true' : 'false' ?>" aria-controls="suppliersSubmenu">
          <i class="bi bi-truck me-2"></i>Suppliers
          <i class="bi bi-chevron-right ms-auto"></i>
        </a>
        <div class="collapse<?= $activePage === 'suppliers' || $activePage === 'supplier_payment' || $activePage === 'supplier_payment_list' || $activePage === 'supplier_payment_details' || $activePage === 'supplier_ledger' ? ' show' : '' ?>" id="suppliersSubmenu">
          <ul class="nav flex-column ms-3">
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'suppliers' ? ' active' : '' ?>" href="<?= $base_url ?>suppliers.php">
                <i class="bi bi-people me-2"></i>Supplier
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'supplier_payment' ? ' active' : '' ?>" href="<?= $base_url ?>supplier_payment.php">
                <i class="bi bi-credit-card me-2"></i>Payment
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'supplier_payment_list' ? ' active' : '' ?>" href="<?= $base_url ?>supplier_payment_list.php">
                <i class="bi bi-list-ul me-2"></i>Payment List
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'supplier_payment_details' ? ' active' : '' ?>" href="<?= $base_url ?>supplier_payment_details.php">
                <i class="bi bi-file-text me-2"></i>Payment Details
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'supplier_ledger' ? ' active' : '' ?>" href="<?= $base_url ?>supplier_ledger.php">
                <i class="bi bi-journal-text me-2"></i>Supplier Ledger
              </a>
            </li>
          </ul>
        </div>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'stock' ? ' active' : '' ?>" href="<?= $base_url ?>stock.php">
          <i class="bi bi-boxes me-2"></i>Stock Details
        </a>
      </li>
    
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'expenses' ? ' active' : '' ?>" href="<?= $base_url ?>expenses.php">
          <i class="bi bi-receipt me-2"></i>Expenses
        </a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'order' || $activePage === 'add_order' ? ' active' : '' ?>" href="#" data-bs-toggle="collapse" data-bs-target="#ordersSubmenu" aria-expanded="<?= $activePage === 'order' || $activePage === 'add_order' ? 'true' : 'false' ?>" aria-controls="ordersSubmenu">
          <i class="bi bi-clipboard-data me-2"></i>Orders
          <i class="bi bi-chevron-right ms-auto"></i>
        </a>
        <div class="collapse<?= $activePage === 'order' || $activePage === 'add_order' ? ' show' : '' ?>" id="ordersSubmenu">
          <ul class="nav flex-column ms-3">
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'add_order' ? ' active' : '' ?>" href="<?= $base_url ?>add_order.php">
                <i class="bi bi-plus-circle me-2"></i>Add Order
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'order' ? ' active' : '' ?>" href="<?= $base_url ?>order.php">
                <i class="bi bi-list-ul me-2"></i>Order Details
              </a>
            </li>
          </ul>
        </div>
      </li>
      
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'return_percale' ? ' active' : '' ?>" href="<?= $base_url ?>return_percale.php">
          <i class="bi bi-arrow-return-left me-2"></i>Return Percale
        </a>
      </li>
      
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'unit' || $activePage === 'add_unit' ? ' active' : '' ?>" href="#" data-bs-toggle="collapse" data-bs-target="#unitsSubmenu" aria-expanded="<?= $activePage === 'unit' || $activePage === 'add_unit' ? 'true' : 'false' ?>" aria-controls="unitsSubmenu">
          <i class="bi bi-rulers me-2"></i>Unit Prices
          <i class="bi bi-chevron-right ms-auto"></i>
        </a>
        <div class="collapse<?= $activePage === 'unit' || $activePage === 'add_unit' ? ' show' : '' ?>" id="unitsSubmenu">
          <ul class="nav flex-column ms-3">
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'add_unit' ? ' active' : '' ?>" href="<?= $base_url ?>add_unit.php">
                <i class="bi bi-plus-circle me-2"></i>Add Unit Price
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activePage === 'unit' ? ' active' : '' ?>" href="<?= $base_url ?>unit.php">
                <i class="bi bi-list-ul me-2"></i>View Unit Prices
              </a>
            </li>
          </ul>
        </div>
      </li>
      
   
    </ul>
    <ul class="nav flex-column mb-4">
      <li class="nav-item mb-2">
        <a class="nav-link position-relative<?= $activePage === 'notifications' ? ' active' : '' ?>" href="<?= $base_url ?>notifications.php">
          <i class="bi bi-bell me-2"></i>Notifications
          <?php if ($unread_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.7em; margin-left: 5px;">
              <?= $unread_count ?>
            </span>
          <?php endif; ?>
        </a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'daily_books' ? ' active' : '' ?>" href="<?= $base_url ?>daily_books.php">
          <i class="bi bi-journal-text me-2"></i>Daily Books
        </a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'reports' ? ' active' : '' ?>" href="<?= $base_url ?>reports.php">
          <i class="bi bi-graph-up-arrow me-2"></i>Reports
        </a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link<?= $activePage === 'backup' ? ' active' : '' ?>" href="<?= $base_url ?>backup.php">
          <i class="bi bi-cloud-arrow-up me-2"></i>System Backup
        </a>
      </li>
      <?php if (function_exists('has_role') && has_role('Admin')): ?>
        <li class="nav-item mb-2">
          <a class="nav-link<?= $activePage === 'users' ? ' active' : '' ?>" href="<?= $base_url ?>users.php">
            <i class="bi bi-person-gear me-2"></i>User Management
          </a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link<?= $activePage === 'settings' ? ' active' : '' ?>" href="<?= $base_url ?>settings.php">
            <i class="bi bi-gear me-2"></i>Settings
          </a>
        </li>
      <?php endif; ?>
      <li class="nav-item mt-3">
        <a class="nav-link text-danger<?= $activePage === 'logout' ? ' active' : '' ?>" href="<?= $base_url ?>logout.php">
          <i class="bi bi-box-arrow-right me-2"></i>Logout
        </a>
      </li>
    </ul>
  </div>
</nav>