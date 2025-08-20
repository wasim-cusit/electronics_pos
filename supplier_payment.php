<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'supplier_payment';

// Handle Add Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $supplier_id = $_POST['supplier_id'];
    $payment_amount = $_POST['payment_amount'];
    $payment_date = $_POST['payment_date'];
    $payment_method = $_POST['payment_method'];
    $reference_no = trim($_POST['reference_no']);
    $notes = trim($_POST['notes'] ?? '');

    // Validate required fields
    if (empty($supplier_id) || empty($payment_amount) || empty($payment_date) || empty($payment_method)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert payment record
            $stmt = $pdo->prepare("INSERT INTO supplier_payments (supplier_id, payment_amount, payment_date, payment_method, reference_no, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$supplier_id, $payment_amount, $payment_date, $payment_method, $reference_no, $notes]);
            
            $pdo->commit();
            header("Location: supplier_payment.php?success=added");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error adding payment: " . $e->getMessage();
        }
    }
}

// Fetch suppliers with their current balances
$suppliers_query = "
    SELECT 
        s.*,
        COALESCE(SUM(p.total_amount), 0) as total_purchases,
        COALESCE(SUM(sp.payment_amount), 0) as total_payments,
        (COALESCE(SUM(p.total_amount), 0) - COALESCE(SUM(sp.payment_amount), 0) + COALESCE(s.opening_balance, 0)) as current_balance
    FROM supplier s
    LEFT JOIN purchase p ON s.id = p.supplier_id
    LEFT JOIN supplier_payments sp ON s.id = sp.supplier_id
    GROUP BY s.id, s.supplier_name, s.supplier_contact, s.supplier_address, s.opening_balance
    ORDER BY s.supplier_name
";

try {
    $suppliers = $pdo->query($suppliers_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback if supplier_payments table doesn't exist
    $suppliers = $pdo->query("SELECT * FROM supplier ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($suppliers as &$supplier) {
        $supplier['total_purchases'] = 0;
        $supplier['total_payments'] = 0;
        $supplier['current_balance'] = $supplier['opening_balance'] ?? 0;
    }
}

// Calculate summary totals
$total_payables = 0;
$total_suppliers_with_balance = 0;
foreach ($suppliers as $supplier) {
    if ($supplier['current_balance'] > 0) {
        $total_payables += $supplier['current_balance'];
        $total_suppliers_with_balance++;
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4  style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-credit-card text-primary"></i> Add Supplier Payment</h2>
                <div class="d-flex">
                    <a href="supplier_payment_list.php" class="btn btn-info me-2">
                        <i class="bi bi-list-ul"></i> Payment List
                    </a>
                    <a href="supplier_ledger.php" class="btn btn-outline-info me-2">
                        <i class="bi bi-journal-text"></i> Supplier Ledger
                    </a>
                    <a href="suppliers.php" class="btn btn-secondary">
                        <i class="bi bi-truck"></i> View Suppliers
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo "Payment added successfully! <a href='supplier_payment_list.php'>View Payment List</a>";
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Payables</h6>
                                    <h4 class="mb-0">PKR <?= number_format($total_payables, 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-exclamation-triangle fs-1"></i>
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
                                    <h6 class="card-title">Suppliers with Balance</h6>
                                    <h4 class="mb-0"><?= $total_suppliers_with_balance ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-truck fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Suppliers</h6>
                                    <h4 class="mb-0"><?= count($suppliers) ?></h4>
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
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-credit-card"></i> Record New Payment</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" id="paymentForm">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Supplier *</label>
                                        <div class="supplier-dropdown-container">
                                            <button type="button" class="supplier-dropdown-btn" id="supplierDropdownBtn">
                                                <span class="supplier-selected-text">Select Supplier</span>
                                                <i class="bi bi-chevron-down dropdown-arrow"></i>
                                            </button>
                                            <div class="supplier-dropdown-list" id="supplierDropdownList">
                                                <div class="supplier-search-box">
                                                    <input type="text" id="supplierSearchInput" class="form-control form-control-sm" placeholder="🔍 Search suppliers...">
                                                </div>
                                                <div class="supplier-dropdown-separator"></div>
                                                <?php foreach ($suppliers as $supplier): ?>
                                                    <div class="supplier-option" data-value="<?= $supplier['id'] ?>" data-balance="<?= $supplier['current_balance'] ?>">
                                                        🏢 <?= htmlspecialchars($supplier['supplier_name']) ?>
                                                        <div class="supplier-balance-info">
                                                            <span class="balance-label">Balance:</span>
                                                            <span class="balance-amount <?= $supplier['current_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                                                PKR <?= number_format(abs($supplier['current_balance']), 2) ?>
                                                                <?= $supplier['current_balance'] > 0 ? '(Owes)' : '(Credit)' ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <input type="hidden" name="supplier_id" id="supplierSelect" required>
                                        </div>
                                    </div>
                                    
                                    <!-- Supplier Balance Display -->
                                    <div class="col-md-12 mb-3" id="supplierBalanceDisplay" style="display: none;">
                                        <div class="alert alert-info">
                                            <strong>Current Balance:</strong> 
                                            <span id="currentBalanceAmount" class="fw-bold"></span>
                                            <span id="balanceStatus" class="badge ms-2"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Amount *</label>
                                        <input type="number" step="0.01" name="payment_amount" id="paymentAmount" class="form-control" required placeholder="Payment Amount">
                                        <div class="form-text" id="paymentHelpText"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Date *</label>
                                        <input type="date" name="payment_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Method *</label>
                                        <select name="payment_method" class="form-control" required>
                                            <option value="">Select Method</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Check">Check</option>
                                            <option value="Credit Card">Credit Card</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Reference Number</label>
                                        <input type="text" name="reference_no" class="form-control" placeholder="Check/Transaction number">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="3" placeholder="Enter payment notes or description"></textarea>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" name="add_payment" id="submitPaymentBtn">
                                        <i class="bi bi-plus-circle"></i> Add Payment
                                    </button>
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Supplier Balances Summary -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Supplier Balances</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($suppliers)): ?>
                                <p class="text-muted text-center">No suppliers found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Supplier</th>
                                                <th>Balance</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($suppliers as $supplier): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($supplier['supplier_name']) ?></strong><br>
                                                        <small class="text-muted"><?= htmlspecialchars($supplier['supplier_contact'] ?: 'No contact') ?></small>
                                                    </td>
                                                    <td class="fw-bold <?= $supplier['current_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                                        PKR <?= number_format(abs($supplier['current_balance']), 2) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($supplier['current_balance'] > 0): ?>
                                                            <span class="badge bg-warning">Owes Money</span>
                                                        <?php elseif ($supplier['current_balance'] < 0): ?>
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
                                    <a href="supplier_ledger.php" class="btn btn-outline-info btn-sm">
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
/* Supplier dropdown styling */
.supplier-dropdown-container {
    position: relative;
    width: 100%;
}

.supplier-dropdown-btn {
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

.supplier-dropdown-btn:hover {
    border-color: #86b7fe;
}

.supplier-dropdown-btn:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.dropdown-arrow {
    transition: transform 0.2s ease;
}

.supplier-dropdown-btn.active .dropdown-arrow {
    transform: rotate(180deg);
}

.supplier-dropdown-list {
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

.supplier-dropdown-list.show {
    display: block;
}

.supplier-search-box {
    padding: 0.75rem;
    border-bottom: 1px solid #dee2e6;
}

.supplier-search-box input {
    width: 100%;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.supplier-dropdown-separator {
    height: 1px;
    background-color: #dee2e6;
    margin: 0;
}

.supplier-option {
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
    border-bottom: 1px solid #f8f9fa;
}

.supplier-option:hover {
    background-color: #f8f9fa;
}

.supplier-option.selected {
    background-color: #0d6efd;
    color: #fff;
}

.supplier-option.hidden {
    display: none;
}

.supplier-balance-info {
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

/* Card styling */
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.5rem;
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0 !important;
    border-bottom: none;
}
</style>

<script>
// Initialize supplier dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownBtn = document.getElementById('supplierDropdownBtn');
    const dropdownList = document.getElementById('supplierDropdownList');
    const supplierSelect = document.getElementById('supplierSelect');
    const supplierSearchInput = document.getElementById('supplierSearchInput');
    const selectedText = document.querySelector('.supplier-selected-text');
    const supplierBalanceDisplay = document.getElementById('supplierBalanceDisplay');
    const currentBalanceAmount = document.getElementById('currentBalanceAmount');
    const balanceStatus = document.getElementById('balanceStatus');
    const paymentAmount = document.getElementById('paymentAmount');
    const paymentHelpText = document.getElementById('paymentHelpText');
    const submitPaymentBtn = document.getElementById('submitPaymentBtn');
    
    // Toggle dropdown on click
    dropdownBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownList.classList.toggle('show');
        dropdownBtn.classList.toggle('active');
        
        if (dropdownList.classList.contains('show')) {
            supplierSearchInput.focus();
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
            dropdownList.classList.remove('show');
            dropdownBtn.classList.remove('active');
        }
    });
    
    // Handle supplier option selection
    dropdownList.addEventListener('click', function(e) {
        const supplierOption = e.target.closest('.supplier-option');
        if (supplierOption) {
            const value = supplierOption.dataset.value;
            const text = supplierOption.textContent.split('\n')[0]; // Get first line only
            const balance = parseFloat(supplierOption.dataset.balance) || 0;
            
            // Update hidden input and display text
            supplierSelect.value = value;
            selectedText.textContent = text;
            
            // Update visual selection
            dropdownList.querySelectorAll('.supplier-option').forEach(item => {
                item.classList.remove('selected');
            });
            supplierOption.classList.add('selected');
            
            // Show supplier balance
            showSupplierBalance(balance);
            
            // Close dropdown
            dropdownList.classList.remove('show');
            dropdownBtn.classList.remove('active');
        }
    });
    
    // Handle search functionality
    supplierSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const supplierOptions = dropdownList.querySelectorAll('.supplier-option');
        
        supplierOptions.forEach(option => {
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
        supplierSearchInput.value = '';
        dropdownList.querySelectorAll('.supplier-option').forEach(option => {
            option.classList.remove('hidden');
        });
    });
    
    // Handle payment amount input
    paymentAmount.addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        const balance = parseFloat(currentBalanceAmount.textContent.replace(/[^\d.-]/g, '')) || 0;
        
        if (amount > 0 && balance > 0) {
            const remaining = balance - amount;
            if (remaining < 0) {
                paymentHelpText.innerHTML = `<span class="text-warning">⚠️ Payment exceeds balance. Supplier will have credit of PKR ${Math.abs(remaining).toFixed(2)}</span>`;
                submitPaymentBtn.disabled = false;
            } else if (remaining === 0) {
                paymentHelpText.innerHTML = `<span class="text-success">✅ Payment will settle the balance completely</span>`;
                submitPaymentBtn.disabled = false;
            } else {
                paymentHelpText.innerHTML = `<span class="text-info">ℹ️ Remaining balance after payment: PKR ${remaining.toFixed(2)}</span>`;
                submitPaymentBtn.disabled = false;
            }
        } else {
            paymentHelpText.innerHTML = '';
            submitPaymentBtn.disabled = false;
        }
    });
    
    // Form validation
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        const supplierId = supplierSelect.value;
        const amount = parseFloat(paymentAmount.value) || 0;
        
        if (!supplierId) {
            e.preventDefault();
            alert('Please select a supplier');
            return;
        }
        
        if (amount <= 0) {
            e.preventDefault();
            alert('Payment amount must be greater than 0');
            return;
        }
        
        // Show confirmation for large payments
        const balance = parseFloat(currentBalanceAmount.textContent.replace(/[^\d.-]/g, '')) || 0;
        if (amount > balance * 1.5) {
            if (!confirm(`Are you sure you want to record a payment of PKR ${amount.toFixed(2)}? This is significantly higher than the current balance of PKR ${balance.toFixed(2)}.`)) {
                e.preventDefault();
                return;
            }
        }
    });
    
    function showSupplierBalance(balance) {
        if (balance !== 0) {
            supplierBalanceDisplay.style.display = 'block';
            currentBalanceAmount.textContent = `PKR ${Math.abs(balance).toFixed(2)}`;
            
            if (balance > 0) {
                balanceStatus.textContent = 'Owes Money';
                balanceStatus.className = 'badge bg-warning';
                currentBalanceAmount.className = 'fw-bold text-danger';
            } else {
                balanceStatus.textContent = 'Has Credit';
                balanceStatus.className = 'badge bg-info';
                currentBalanceAmount.className = 'fw-bold text-success';
            }
        } else {
            supplierBalanceDisplay.style.display = 'block';
            currentBalanceAmount.textContent = 'PKR 0.00';
            balanceStatus.textContent = 'Settled';
            balanceStatus.className = 'badge bg-success';
            currentBalanceAmount.className = 'fw-bold text-success';
        }
        
        // Update payment help text
        paymentHelpText.innerHTML = '';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
