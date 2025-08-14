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

// Fetch suppliers for dropdown
$suppliers = $pdo->query("SELECT * FROM supplier ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-credit-card text-primary"></i> Add Supplier Payment</h2>
                <div class="d-flex">
                    <a href="supplier_payment_list.php" class="btn btn-info me-2">
                        <i class="bi bi-list-ul"></i> Payment List
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

            <!-- Add Payment Form -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Record New Payment</h5>
                </div>
                <div class="card-body">
                    <form method="post" id="paymentForm">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Supplier *</label>
                                <select name="supplier_id" class="form-control" required>
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['supplier_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Payment Amount *</label>
                                <input type="number" step="0.01" name="payment_amount" class="form-control" required placeholder="Payment Amount">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Payment Date *</label>
                                <input type="date" name="payment_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Payment Method *</label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="">Select Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <!-- <option value="Check">Check</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Other">Other</option> -->
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_no" class="form-control" placeholder="Check/Transaction number">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Enter payment notes or description"></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" name="add_payment">
                                <i class="bi bi-plus-circle"></i> Add Payment
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Remove validation styling on input
    document.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
