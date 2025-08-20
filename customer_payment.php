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
        $pdo->beginTransaction();
        
        // Insert payment record
        $stmt = $pdo->prepare("INSERT INTO customer_payment (sale_id, customer_id, paid, payment_date, details, receipt, payment_method_id, created_at) VALUES (0, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$customer_id, $amount, $payment_date, $details, $receipt, $payment_method_id]);
        
        $pdo->commit();
        header("Location: customer_payment.php?success=added");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error adding payment: " . $e->getMessage();
    }
}

// Fetch all customers with their current balances
$customers_query = "
    SELECT 
        c.id,
        c.name,
        c.mobile,
        c.opening_balance,
        COALESCE(SUM(s.total_amount), 0) as total_sales,
        COALESCE(SUM(cp.paid), 0) as total_payments,
        (COALESCE(SUM(s.total_amount), 0) - COALESCE(SUM(cp.paid), 0) + COALESCE(c.opening_balance, 0)) as current_balance
    FROM customer c
    LEFT JOIN sale s ON c.id = s.customer_id
    LEFT JOIN customer_payment cp ON c.id = cp.customer_id
    GROUP BY c.id, c.name, c.mobile, c.opening_balance
    ORDER BY c.name
";
$customers = $pdo->query($customers_query)->fetchAll(PDO::FETCH_ASSOC);

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

// Calculate total customer balances
$total_receivables = 0;
$total_customers_with_balance = 0;
foreach ($customers as $customer) {
    if ($customer['current_balance'] > 0) {
        $total_receivables += $customer['current_balance'];
        $total_customers_with_balance++;
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
                <div class="col-md-3">
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
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Receivables</h6>
                                    <h4 class="mb-0">PKR <?= number_format($total_receivables, 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-exclamation-triangle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Customers with Balance</h6>
                                    <h4 class="mb-0"><?= $total_customers_with_balance ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-people fs-1"></i>
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
                            <form method="POST" id="paymentForm">
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
                                                    <div class="customer-option" data-value="<?= $customer['id'] ?>" data-balance="<?= $customer['current_balance'] ?>">
                                                        👤 <?= htmlspecialchars($customer['name']) ?> - <?= htmlspecialchars($customer['mobile']) ?>
                                                        <div class="customer-balance-info">
                                                            <span class="balance-label">Balance:</span>
                                                            <span class="balance-amount <?= $customer['current_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                                                PKR <?= number_format(abs($customer['current_balance']), 2) ?>
                                                                <?= $customer['current_balance'] > 0 ? '(Owes)' : '(Credit)' ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <input type="hidden" name="customer_id" id="customerSelect" required>
                                        </div>
                                    </div>
                                    
                                    <!-- Customer Balance Display -->
                                    <div class="col-md-12 mb-3" id="customerBalanceDisplay" style="display: none;">
                                        <div class="alert alert-info">
                                            <strong>Current Balance:</strong> 
                                            <span id="currentBalanceAmount" class="fw-bold"></span>
                                            <span id="balanceStatus" class="badge ms-2"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Amount</label>
                                        <input type="number" name="amount" id="paymentAmount" class="form-control" step="0.01" min="0" required>
                                        <div class="form-text" id="paymentHelpText"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Date</label>
                                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Method</label>
                                        <select name="payment_method_id" class="form-control" required>
                                            <option value="">Select Payment Method</option>
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
                                        <button type="submit" name="add_payment" class="btn btn-primary" id="submitPaymentBtn">
                                            <i class="bi bi-plus-circle"></i> Add Payment
                                        </button>
                                        <button type="reset" class="btn btn-secondary ms-2" id="resetFormBtn">
                                            <i class="bi bi-arrow-clockwise"></i> Reset Form
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Customer Balances Summary -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">📊 Customer Balances</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($customers)): ?>
                                <p class="text-muted text-center">No customers found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Customer</th>
                                                <th>Balance</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($customers as $customer): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($customer['name']) ?></strong><br>
                                                        <small class="text-muted"><?= htmlspecialchars($customer['mobile']) ?></small>
                                                    </td>
                                                    <td class="fw-bold <?= $customer['current_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                                        PKR <?= number_format(abs($customer['current_balance']), 2) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($customer['current_balance'] > 0): ?>
                                                            <span class="badge bg-warning">Owes Money</span>
                                                        <?php elseif ($customer['current_balance'] < 0): ?>
                                                            <span class="badge bg-info">Has Credit</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Settled</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="customer_ledger.php" class="btn btn-outline-primary btn-sm">
                                        View Detailed Ledger
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
    transition: all 0.15s ease-in-out;
    text-align: left;
}

.customer-dropdown-btn:hover {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.customer-dropdown-btn:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.customer-dropdown-btn.btn-success {
    background-color: #d1e7dd;
    border-color: #badbcc;
    color: #0f5132;
}

.customer-dropdown-btn.btn-success:hover {
    background-color: #c3e6cb;
    border-color: #a1d9a4;
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

.customer-balance-info {
    font-size: 0.8rem;
    margin-top: 0.25rem;
    opacity: 0.8;
}

.balance-label {
    font-weight: 500;
}

.balance-amount {
    font-weight: bold;
}

/* Alert styling */
.alert {
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* Table styling */
.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
}

.table td {
    vertical-align: middle;
}

/* Badge styling */
.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
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
    const customerBalanceDisplay = document.getElementById('customerBalanceDisplay');
    const currentBalanceAmount = document.getElementById('currentBalanceAmount');
    const balanceStatus = document.getElementById('balanceStatus');
    const paymentAmount = document.getElementById('paymentAmount');
    const paymentHelpText = document.getElementById('paymentHelpText');
    const submitPaymentBtn = document.getElementById('submitPaymentBtn');
    const resetFormBtn = document.getElementById('resetFormBtn');
    
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
            const text = customerOption.textContent.split('\n')[0]; // Get first line only
            const balance = parseFloat(customerOption.dataset.balance) || 0;
            
            // Update hidden input and display text
            customerSelect.value = value;
            selectedText.textContent = text;
            
            // Update visual selection
            dropdownList.querySelectorAll('.customer-option').forEach(item => {
                item.classList.remove('selected');
            });
            customerOption.classList.add('selected');
            
            // Show customer balance
            showCustomerBalance(balance);
            
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
    
    // Handle payment amount input
    paymentAmount.addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        const balanceElement = document.getElementById('currentBalanceAmount');
        
        // Only proceed if balance is displayed and customer is selected
        if (!balanceElement || balanceElement.textContent === '') {
            paymentHelpText.innerHTML = '';
            return;
        }
        
        const balance = parseFloat(balanceElement.textContent.replace(/[^\d.-]/g, '')) || 0;
        
        if (amount > 0 && balance > 0) {
            const remaining = balance - amount;
            if (remaining < 0) {
                paymentHelpText.innerHTML = `<span class="text-warning">⚠️ Payment exceeds balance. Customer will have credit of PKR ${Math.abs(remaining).toFixed(2)}</span>`;
                submitPaymentBtn.disabled = false;
            } else if (remaining === 0) {
                paymentHelpText.innerHTML = `<span class="text-success">✅ Payment will settle the balance completely</span>`;
                submitPaymentBtn.disabled = false;
            } else {
                paymentHelpText.innerHTML = `<span class="text-info">ℹ️ Remaining balance after payment: PKR ${remaining.toFixed(2)}</span>`;
                submitPaymentBtn.disabled = false;
            }
        } else if (amount > 0) {
            paymentHelpText.innerHTML = `<span class="text-info">ℹ️ Recording payment of PKR ${amount.toFixed(2)}</span>`;
            submitPaymentBtn.disabled = false;
        } else {
            paymentHelpText.innerHTML = '';
            submitPaymentBtn.disabled = false;
        }
    });
    
    // Form validation
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        const customerId = customerSelect.value;
        const amount = parseFloat(paymentAmount.value) || 0;
        const paymentMethod = document.querySelector('select[name="payment_method_id"]').value;
        
        if (!customerId) {
            e.preventDefault();
            alert('Please select a customer');
            return;
        }
        
        if (amount <= 0) {
            e.preventDefault();
            alert('Payment amount must be greater than 0');
            return;
        }
        
        if (!paymentMethod) {
            e.preventDefault();
            alert('Please select a payment method');
            return;
        }
        
        // Show confirmation for large payments only if balance is displayed
        const balanceElement = document.getElementById('currentBalanceAmount');
        if (balanceElement && balanceElement.textContent !== '') {
            const balance = parseFloat(balanceElement.textContent.replace(/[^\d.-]/g, '')) || 0;
            if (balance > 0 && amount > balance * 1.5) {
                if (!confirm(`Are you sure you want to record a payment of PKR ${amount.toFixed(2)}? This is significantly higher than the current balance of PKR ${balance.toFixed(2)}.`)) {
                    e.preventDefault();
                    return;
                }
            }
        }
    });

    // Reset form on button click
    resetFormBtn.addEventListener('click', function() {
        // Reset the form
        document.getElementById('paymentForm').reset();
        
        // Reset customer selection
        customerSelect.value = '';
        selectedText.textContent = 'Select Customer';
        
        // Hide balance display
        customerBalanceDisplay.style.display = 'none';
        
        // Clear payment help text
        paymentHelpText.innerHTML = '';
        
        // Re-enable submit button
        submitPaymentBtn.disabled = false;
        
        // Remove selected class from all customer options
        dropdownList.querySelectorAll('.customer-option').forEach(item => {
            item.classList.remove('selected');
        });
        
        // Restore original dropdown button styling
        dropdownBtn.classList.remove('btn-success');
        dropdownBtn.classList.add('btn-outline-secondary');
    });
    
    function showCustomerBalance(balance) {
        if (balance !== 0) {
            customerBalanceDisplay.style.display = 'block';
            currentBalanceAmount.textContent = `PKR ${Math.abs(balance).toFixed(2)}`;
            
            if (balance > 0) {
                balanceStatus.textContent = 'Owes Money';
                balanceStatus.className = 'badge bg-warning';
                currentBalanceAmount.className = 'fw-bold text-danger';
                // Add visual feedback to dropdown button
                dropdownBtn.classList.add('btn-success');
                dropdownBtn.classList.remove('btn-outline-secondary');
            } else {
                balanceStatus.textContent = 'Has Credit';
                balanceStatus.className = 'badge bg-info';
                currentBalanceAmount.className = 'fw-bold text-success';
                // Add visual feedback to dropdown button
                dropdownBtn.classList.add('btn-success');
                dropdownBtn.classList.remove('btn-outline-secondary');
            }
        } else {
            customerBalanceDisplay.style.display = 'block';
            currentBalanceAmount.textContent = 'PKR 0.00';
            balanceStatus.textContent = 'Settled';
            balanceStatus.className = 'badge bg-success';
            currentBalanceAmount.className = 'fw-bold text-success';
            // Add visual feedback to dropdown button
            dropdownBtn.classList.add('btn-success');
            dropdownBtn.classList.remove('btn-outline-secondary');
        }
        
        // Update payment help text
        paymentHelpText.innerHTML = '';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
