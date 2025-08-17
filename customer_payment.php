<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

// Handle Add Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $customer_id = $_POST['customer_id'];
    $amount = $_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $payment_method_id = $_POST['payment_method_id'];
    $receipt = $_POST['receipt'];
    $details = $_POST['details'];

    try {
                    $stmt = $pdo->prepare("INSERT INTO customer_payment (sale_id, customer_id, paid, payment_date, details, receipt, payment_method_id, created_at) VALUES (0, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$customer_id, $amount, $payment_date, $details, $receipt, $payment_method_id]);
        
        header("Location: customer_payment.php?success=added");
        exit;
    } catch (Exception $e) {
        $error = "Error adding payment: " . $e->getMessage();
    }
}

// Fetch all customers for dropdown
$customers = $pdo->query("SELECT id, name, mobile FROM customer ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent payments
$recent_payments = $pdo->query("
    SELECT cp.*, c.name as customer_name, pm.method as payment_method_name
    FROM customer_payment cp 
    LEFT JOIN customer c ON cp.customer_id = c.id 
    LEFT JOIN payment_method pm ON cp.payment_method_id = pm.id
    ORDER BY cp.payment_date DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_payments = 0;
$today_payments = 0;
$month_payments = 0;

foreach ($recent_payments as $payment) {
    $total_payments += $payment['paid'];
    if ($payment['payment_date'] == date('Y-m-d')) {
        $today_payments += $payment['paid'];
    }
    if (date('Y-m', strtotime($payment['payment_date'])) == date('Y-m')) {
        $month_payments += $payment['paid'];
    }
}

$page_title = "Customer Payment";
$activePage = "customer_payment";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">💰 Customer Payment</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="customer_payment_list.php" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-list-ul"></i> Payment List
                    </a>
                    <a href="customer_ledger.php" class="btn btn-outline-info">
                        <i class="bi bi-journal-text"></i> Customer Ledger
                    </a>
                </div>
            </div>

            <?php include 'includes/flash.php'; ?>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Today's Payments</h6>
                                    <h4 class="mb-0">PKR <?= number_format($today_payments, 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-cash-coin fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">This Month</h6>
                                    <h4 class="mb-0">PKR <?= number_format($month_payments, 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-calendar-month fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Payments</h6>
                                    <h4 class="mb-0">PKR <?= number_format($total_payments, 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-wallet2 fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Add Payment Form -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">➕ Add Customer Payment</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Customer</label>
                                        <div class="customer-dropdown-container">
                                            <button type="button" class="customer-dropdown-btn" id="customerDropdownBtn">
                                                <span class="customer-selected-text">Select Customer</span>
                                                <i class="bi bi-chevron-down dropdown-arrow"></i>
                                            </button>
                                            <div class="customer-dropdown-list" id="customerDropdownList">
                                                <div class="customer-search-box">
                                                    <input type="text" id="customerSearchInput" class="form-control form-control-sm" placeholder="🔍 Search customers...">
                                                </div>
                                                <div class="customer-dropdown-separator"></div>
                                                <?php foreach ($customers as $customer): ?>
                                                    <div class="customer-option" data-value="<?= $customer['id'] ?>">
                                                        👤 <?= htmlspecialchars($customer['name']) ?> - <?= htmlspecialchars($customer['mobile']) ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <input type="hidden" name="customer_id" id="customerSelect" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Amount</label>
                                        <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Date</label>
                                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Method</label>
                                        <select name="payment_method_id" class="form-control" required>
                                            <option value="1">Cash</option>
                                            <option value="2">Online</option>
                                            <option value="3">Credit Card</option>
                                            <option value="4">Debit Card</option>
                                            <option value="5">Bank Transfer</option>
                                            <option value="6">Mobile Payment</option>
                                            <option value="7">Check</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Receipt</label>
                                        <input type="text" name="receipt" class="form-control" placeholder="Optional">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Details</label>
                                        <textarea name="details" class="form-control" rows="3" placeholder="Optional details about the payment"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" name="add_payment" class="btn btn-primary">
                                            <i class="bi bi-plus-circle"></i> Add Payment
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Recent Payments -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">📋 Recent Payments</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_payments)): ?>
                                <p class="text-muted text-center">No payments recorded yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_payments as $payment): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y', strtotime($payment['payment_date'])) ?></td>
                                                    <td><?= htmlspecialchars($payment['customer_name']) ?></td>
                                                                                                <td class="fw-bold text-success">PKR <?= number_format($payment['paid'], 2) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($payment['payment_method_name'] ?: 'N/A') ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="customer_payment_list.php" class="btn btn-outline-primary btn-sm">
                                        View All Payments
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Customer dropdown styling */
.customer-dropdown-container {
    position: relative;
    width: 100%;
}

.customer-dropdown-btn {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    text-align: left;
}

.customer-dropdown-btn:hover {
    border-color: #86b7fe;
}

.customer-dropdown-btn:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.dropdown-arrow {
    transition: transform 0.2s ease;
}

.customer-dropdown-btn.active .dropdown-arrow {
    transform: rotate(180deg);
}

.customer-dropdown-list {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
    display: none;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-height: 300px;
    overflow-y: auto;
    margin-top: 2px;
}

.customer-dropdown-list.show {
    display: block;
}

.customer-search-box {
    padding: 0.75rem;
    border-bottom: 1px solid #dee2e6;
}

.customer-search-box input {
    width: 100%;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.customer-dropdown-separator {
    height: 1px;
    background-color: #dee2e6;
    margin: 0;
}

.customer-option {
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
    border-bottom: 1px solid #f8f9fa;
}

.customer-option:hover {
    background-color: #f8f9fa;
}

.customer-option.selected {
    background-color: #0d6efd;
    color: #fff;
}

.customer-option.hidden {
    display: none;
}
</style>

<script>
// Initialize customer dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownBtn = document.getElementById('customerDropdownBtn');
    const dropdownList = document.getElementById('customerDropdownList');
    const customerSelect = document.getElementById('customerSelect');
    const customerSearchInput = document.getElementById('customerSearchInput');
    const selectedText = document.querySelector('.customer-selected-text');
    
    // Toggle dropdown on click
    dropdownBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownList.classList.toggle('show');
        dropdownBtn.classList.toggle('active');
        
        if (dropdownList.classList.contains('show')) {
            customerSearchInput.focus();
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
            dropdownList.classList.remove('show');
            dropdownBtn.classList.remove('active');
        }
    });
    
    // Handle customer option selection
    dropdownList.addEventListener('click', function(e) {
        const customerOption = e.target.closest('.customer-option');
        if (customerOption) {
            const value = customerOption.dataset.value;
            const text = customerOption.textContent;
            
            // Update hidden input and display text
            customerSelect.value = value;
            selectedText.textContent = text;
            
            // Update visual selection
            dropdownList.querySelectorAll('.customer-option').forEach(item => {
                item.classList.remove('selected');
            });
            customerOption.classList.add('selected');
            
            // Close dropdown
            dropdownList.classList.remove('show');
            dropdownBtn.classList.remove('active');
        }
    });
    
    // Handle search functionality
    customerSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const customerOptions = dropdownList.querySelectorAll('.customer-option');
        
        customerOptions.forEach(option => {
            const optionText = option.textContent.toLowerCase();
            if (optionText.includes(searchTerm)) {
                option.classList.remove('hidden');
            } else {
                option.classList.add('hidden');
            }
        });
    });
    
    // Clear search when dropdown opens
    dropdownBtn.addEventListener('click', function() {
        customerSearchInput.value = '';
        dropdownList.querySelectorAll('.customer-option').forEach(option => {
            option.classList.remove('hidden');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
