<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_login();

$tab = $_GET['tab'] ?? 'add';
$activePage = 'expenses';
include 'includes/header.php';
?>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
  <h2 class="mb-4">Expenses</h2>
  <ul class="nav nav-tabs mb-3" id="expenseTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link<?= $tab === 'add' ? ' active' : '' ?>" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button" role="tab" aria-controls="add" aria-selected="<?= $tab === 'add' ? 'true' : 'false' ?>">Add Expense</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link<?= $tab === 'list' ? ' active' : '' ?>" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab" aria-controls="list" aria-selected="<?= $tab === 'list' ? 'true' : 'false' ?>">Expense List</button>
    </li>
  </ul>
  <div class="tab-content" id="expenseTabsContent">
    <div class="tab-pane fade<?= $tab === 'add' ? ' show active' : '' ?>" id="add" role="tabpanel" aria-labelledby="add-tab">
      <?php include 'expense_entry.php'; ?>
    </div>
    <div class="tab-pane fade<?= $tab === 'list' ? ' show active' : '' ?>" id="list" role="tabpanel" aria-labelledby="list-tab">
      <div class="card p-4">
        <h5>Expense List (Coming Soon)</h5>
        <table class="table table-bordered mt-3">
          <thead>
            <tr>
              <th>Date</th><th>Category</th><th>Amount</th><th>Description</th><th>Attachment</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="5" class="text-center">No data yet.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>