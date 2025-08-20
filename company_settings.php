<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';
require_once 'includes/settings.php';

$activePage = 'company_settings';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_company'])) {
    try {
        // Company Information
        $company_name = trim($_POST['company_name']);
        $company_tagline = trim($_POST['company_tagline']);
        $company_phone = trim($_POST['company_phone']);
        $company_email = trim($_POST['company_email']);
        $company_address = trim($_POST['company_address']);
        $company_website = trim($_POST['company_website']);
        $company_logo = trim($_POST['company_logo']);
        
        // Business Settings
        $currency_symbol = trim($_POST['currency_symbol']);
        $currency_name = trim($_POST['currency_name']);
        $invoice_prefix = trim($_POST['invoice_prefix']);
        $purchase_prefix = trim($_POST['purchase_prefix']);
        $sale_prefix = trim($_POST['sale_prefix']);
        
        // Invoice Settings
        $footer_text = trim($_POST['footer_text']);
        $print_header = trim($_POST['print_header']);
        
        // Business Hours
        $business_hours = trim($_POST['business_hours']);
        $business_days = trim($_POST['business_days']);
        
        // System Settings
        $low_stock_threshold = trim($_POST['low_stock_threshold']);
        $date_format = trim($_POST['date_format']);
        $time_format = trim($_POST['time_format']);
        
        // Validate required fields
        if (empty($company_name)) {
            throw new Exception('Company name is required');
        }
        
        // Update settings
        $settings_to_update = [
            'company_name' => $company_name,
            'company_tagline' => $company_tagline,
            'company_phone' => $company_phone,
            'company_email' => $company_email,
            'company_address' => $company_address,
            'company_website' => $company_website,
            'company_logo' => $company_logo,
            'currency_symbol' => $currency_symbol,
            'currency_name' => $currency_name,
            'invoice_prefix' => $invoice_prefix,
            'purchase_prefix' => $purchase_prefix,
            'sale_prefix' => $sale_prefix,
            'footer_text' => $footer_text,
            'print_header' => $print_header,
            'business_hours' => $business_hours,
            'business_days' => $business_days,
            'low_stock_threshold' => $low_stock_threshold,
            'date_format' => $date_format,
            'time_format' => $time_format
        ];
        
        foreach ($settings_to_update as $key => $value) {
            set_setting($key, $value);
        }
        
        $success_msg = 'Company settings updated successfully!';
        
    } catch (Exception $e) {
        $error_msg = 'Error: ' . $e->getMessage();
    }
}

// Get current settings
$company_name = get_setting('company_name', 'WASEM WEARS');
$company_tagline = get_setting('company_tagline', 'Professional Electronics Services');
$company_phone = get_setting('company_phone', '+92 323 9507813');
$company_email = get_setting('company_email', 'info@electronics.com');
$company_address = get_setting('company_address', 'Address shop #1 hameed plaza main university road Pakistan');
$company_website = get_setting('company_website', 'www.electronics.com');
$company_logo = get_setting('company_logo', '');
$currency_symbol = get_setting('currency_symbol', 'PKR');
$currency_name = get_setting('currency_name', 'Pakistani Rupee');
$invoice_prefix = get_setting('invoice_prefix', 'INV');
$purchase_prefix = get_setting('purchase_prefix', 'PUR');
$sale_prefix = get_setting('sale_prefix', 'SALE');
$footer_text = get_setting('footer_text', 'Thank you for your business!');
$print_header = get_setting('print_header', 'Computer Generated Invoice');
$business_hours = get_setting('business_hours', '9:00 AM - 6:00 PM');
$business_days = get_setting('business_days', 'Monday - Saturday');
$low_stock_threshold = get_setting('low_stock_threshold', '10');
$date_format = get_setting('date_format', 'd/m/Y');
$time_format = get_setting('time_format', 'H:i:s');

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 " style="margin-top: 25px;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-building me-2"></i>Company Settings
                </h1>
            </div>

            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Company Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-building me-2"></i>Company Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Company Name *</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?= htmlspecialchars($company_name) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_tagline" class="form-label">Company Tagline</label>
                                    <input type="text" class="form-control" id="company_tagline" name="company_tagline" value="<?= htmlspecialchars($company_tagline) ?>" placeholder="Professional Electronics Services">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="company_phone" name="company_phone" value="<?= htmlspecialchars($company_phone) ?>" placeholder="+92 323 9507813">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="company_email" name="company_email" value="<?= htmlspecialchars($company_email) ?>" placeholder="info@electronics.com">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_website" class="form-label">Website</label>
                                    <input type="url" class="form-control" id="company_website" name="company_website" value="<?= htmlspecialchars($company_website) ?>" placeholder="www.electronics.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_logo" class="form-label">Logo URL</label>
                                    <input type="url" class="form-control" id="company_logo" name="company_logo" value="<?= htmlspecialchars($company_logo) ?>" placeholder="https://example.com/logo.png">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="company_address" class="form-label">Company Address</label>
                            <textarea class="form-control" id="company_address" name="company_address" rows="3" placeholder="Enter full company address"><?= htmlspecialchars($company_address) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Business Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-gear me-2"></i>Business Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency_symbol" class="form-label">Currency Symbol</label>
                                    <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?= htmlspecialchars($currency_symbol) ?>" placeholder="PKR">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency_name" class="form-label">Currency Name</label>
                                    <input type="text" class="form-control" id="currency_name" name="currency_name" value="<?= htmlspecialchars($currency_name) ?>" placeholder="Pakistani Rupee">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="invoice_prefix" class="form-label">Invoice Prefix</label>
                                    <input type="text" class="form-control" id="invoice_prefix" name="invoice_prefix" value="<?= htmlspecialchars($invoice_prefix) ?>" placeholder="INV">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="purchase_prefix" class="form-label">Purchase Prefix</label>
                                    <input type="text" class="form-control" id="purchase_prefix" name="purchase_prefix" value="<?= htmlspecialchars($purchase_prefix) ?>" placeholder="PUR">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="sale_prefix" class="form-label">Sale Prefix</label>
                                    <input type="text" class="form-control" id="sale_prefix" name="sale_prefix" value="<?= htmlspecialchars($sale_prefix) ?>" placeholder="SALE">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                                    <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" value="<?= htmlspecialchars($low_stock_threshold) ?>" min="1" placeholder="10">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_format" class="form-label">Date Format</label>
                                    <select class="form-select" id="date_format" name="date_format">
                                        <option value="d/m/Y" <?= $date_format === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                                        <option value="m/d/Y" <?= $date_format === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                                        <option value="Y-m-d" <?= $date_format === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                                        <option value="d-m-Y" <?= $date_format === 'd-m-Y' ? 'selected' : '' ?>>DD-MM-YYYY</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-receipt me-2"></i>Invoice Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="print_header" class="form-label">Print Header</label>
                                    <input type="text" class="form-control" id="print_header" name="print_header" value="<?= htmlspecialchars($print_header) ?>" placeholder="Computer Generated Invoice">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="time_format" class="form-label">Time Format</label>
                                    <select class="form-select" id="time_format" name="time_format">
                                        <option value="H:i:s" <?= $time_format === 'H:i:s' ? 'selected' : '' ?>>24 Hour (HH:MM:SS)</option>
                                        <option value="h:i:s A" <?= $time_format === 'h:i:s A' ? 'selected' : '' ?>>12 Hour (HH:MM:SS AM/PM)</option>
                                        <option value="H:i" <?= $time_format === 'H:i' ? 'selected' : '' ?>>24 Hour (HH:MM)</option>
                                        <option value="h:i A" <?= $time_format === 'h:i A' ? 'selected' : '' ?>>12 Hour (HH:MM AM/PM)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="footer_text" class="form-label">Invoice Footer Text</label>
                            <textarea class="form-control" id="footer_text" name="footer_text" rows="2" placeholder="Thank you for your business!"><?= htmlspecialchars($footer_text) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Business Hours -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-clock me-2"></i>Business Hours
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_hours" class="form-label">Business Hours</label>
                                    <input type="text" class="form-control" id="business_hours" name="business_hours" value="<?= htmlspecialchars($business_hours) ?>" placeholder="9:00 AM - 6:00 PM">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_days" class="form-label">Business Days</label>
                                    <input type="text" class="form-control" id="business_days" name="business_days" value="<?= htmlspecialchars($business_days) ?>" placeholder="Monday - Saturday">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" name="update_company" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Update Company Settings
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
// Auto-save functionality
let autoSaveTimer;
const form = document.querySelector('form');

form.addEventListener('input', function() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(function() {
        // Show auto-save indicator
        const saveBtn = document.querySelector('button[name="update_company"]');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="bi bi-clock me-2"></i>Auto-saving...';
        saveBtn.disabled = true;
        
        // Re-enable after 2 seconds
        setTimeout(function() {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }, 2000);
    }, 2000);
});

// Form validation
form.addEventListener('submit', function(e) {
    const companyName = document.getElementById('company_name').value.trim();
    if (!companyName) {
        e.preventDefault();
        alert('Company name is required!');
        document.getElementById('company_name').focus();
        return false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
