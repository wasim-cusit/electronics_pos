<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'supplier_payment_details';

// Get payment ID from URL
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$payment_id) {
    header("Location: supplier_payment_list.php?error=invalid_id");
    exit;
}

// Fetch payment details with supplier information
$stmt = $pdo->prepare("
    SELECT sp.*, s.supplier_name, s.supplier_contact, s.supplier_email, s.supplier_contact, s.supplier_address
    FROM supplier_payments sp 
    LEFT JOIN supplier s ON sp.supplier_id = s.id 
    WHERE sp.id = ?
");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    header("Location: supplier_payment_list.php?error=payment_not_found");
    exit;
}

// Handle Edit Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_payment'])) {
    $payment_amount = $_POST['payment_amount'];
    $payment_date = $_POST['payment_date'];
    $payment_method = $_POST['payment_method'];
    $reference_no = trim($_POST['reference_no']);
    $notes = trim($_POST['notes'] ?? '');

    // Validate required fields
    if (empty($payment_amount) || empty($payment_date) || empty($payment_method)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE supplier_payments SET payment_amount=?, payment_date=?, payment_method=?, reference_no=?, notes=? WHERE id=?");
            $stmt->execute([$payment_amount, $payment_date, $payment_method, $reference_no, $notes, $payment_id]);
            header("Location: supplier_payment_details.php?id=" . $payment_id . "&success=updated");
            exit;
        } catch (Exception $e) {
            $error = "Error updating payment: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-file-text text-primary"></i> Payment Details</h2>
                <div class="d-flex">
                    <a href="supplier_payment_list.php" class="btn btn-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    <button class="btn btn-warning me-2" onclick="toggleEditForm()">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <a href="supplier_ledger.php?supplier_id=<?= $payment['supplier_id'] ?>" class="btn btn-info">
                        <i class="bi bi-journal-text"></i> View Ledger
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'updated') echo "Payment updated successfully!";
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Payment Details Card -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Payment ID:</strong> #<?= $payment['id'] ?></p>
                                                                         <p><strong>Amount:</strong> <span class="badge bg-success fs-6">Rs.<?= number_format($payment['payment_amount'], 2) ?></span></p>
                                    <p><strong>Date:</strong> <?= date('F d, Y', strtotime($payment['payment_date'])) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Method:</strong> <?= htmlspecialchars($payment['payment_method']) ?></p>
                                    <p><strong>Reference:</strong> <?= htmlspecialchars($payment['reference_no'] ?: 'N/A') ?></p>
                                    <p><strong>Created:</strong> <?= date('M d, Y H:i', strtotime($payment['created_at'])) ?></p>
                                </div>
                            </div>
                            
                            <?php if ($payment['notes']): ?>
                                <div class="mt-3">
                                    <strong>Notes:</strong>
                                    <p class="text-muted"><?= nl2br(htmlspecialchars($payment['notes'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Edit Form (Hidden by default) -->
                    <div class="card mb-4" id="editForm" style="display: none;">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Payment</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" id="editPaymentForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Amount *</label>
                                        <input type="number" step="0.01" name="payment_amount" class="form-control" required value="<?= htmlspecialchars($payment['payment_amount']) ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Date *</label>
                                        <input type="date" name="payment_date" class="form-control" required value="<?= htmlspecialchars($payment['payment_date']) ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Method *</label>
                                        <select name="payment_method" class="form-control" required>
                                            <option value="Cash" <?= $payment['payment_method'] == 'Cash' ? 'selected' : '' ?>>Cash</option>
                                            <option value="Bank Transfer" <?= $payment['payment_method'] == 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                            <option value="Check" <?= $payment['payment_method'] == 'Check' ? 'selected' : '' ?>>Check</option>
                                            <option value="Credit Card" <?= $payment['payment_method'] == 'Credit Card' ? 'selected' : '' ?>>Credit Card</option>
                                            <option value="Other" <?= $payment['payment_method'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Reference Number</label>
                                        <input type="text" name="reference_no" class="form-control" value="<?= htmlspecialchars($payment['reference_no']) ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($payment['notes']) ?></textarea>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-warning" name="edit_payment">
                                        <i class="bi bi-check-circle"></i> Update Payment
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="toggleEditForm()">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Supplier Information Card -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-truck"></i> Supplier Information</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($payment['supplier_name']) ?></h6>
                            
                                                         <?php if ($payment['supplier_contact']): ?>
                                 <p><strong>Contact:</strong> <?= htmlspecialchars($payment['supplier_contact']) ?></p>
                             <?php endif; ?>
                             
                             <?php if ($payment['supplier_email']): ?>
                                 <p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($payment['supplier_email']) ?>"><?= htmlspecialchars($payment['supplier_email']) ?></a></p>
                             <?php endif; ?>
                             
                             <?php if ($payment['supplier_contact']): ?>
                                 <p><strong>Phone:</strong> <a href="tel:<?= htmlspecialchars($payment['supplier_contact']) ?>"><?= htmlspecialchars($payment['supplier_contact']) ?></a></p>
                             <?php endif; ?>
                             
                             <?php if ($payment['supplier_address']): ?>
                                 <p><strong>Address:</strong><br>
                                 <small class="text-muted"><?= nl2br(htmlspecialchars($payment['supplier_address'])) ?></small></p>
                             <?php endif; ?>
                            
                            <div class="mt-3">
                                <a href="suppliers.php?edit=<?= $payment['supplier_id'] ?>" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-pencil"></i> Edit Supplier
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="supplier_payment.php?supplier_id=<?= $payment['supplier_id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> New Payment
                                </a>
                                <a href="supplier_ledger.php?supplier_id=<?= $payment['supplier_id'] ?>" class="btn btn-info btn-sm">
                                    <i class="bi bi-journal-text"></i> View Ledger
                                </a>
                                <a href="supplier_payment_list.php?supplier_filter=<?= $payment['supplier_id'] ?>" class="btn btn-secondary btn-sm">
                                    <i class="bi bi-list-ul"></i> All Payments
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function toggleEditForm() {
    const editForm = document.getElementById('editForm');
    if (editForm.style.display === 'none') {
        editForm.style.display = 'block';
    } else {
        editForm.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Form validation for edit form
    document.getElementById('editPaymentForm').addEventListener('submit', function(e) {
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
