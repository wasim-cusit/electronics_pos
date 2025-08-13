<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';
require_once 'includes/settings.php';

$activePage = 'settings';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $success = true;
    $error = '';
    
    try {
        // Company Information
        set_setting('company_name', $_POST['company_name'], 'Company/Business Name');
        set_setting('company_tagline', $_POST['company_tagline'], 'Company Tagline or Description');
        set_setting('company_phone', $_POST['company_phone'], 'Company Phone Number');
        set_setting('company_email', $_POST['company_email'], 'Company Email Address');
        set_setting('company_address', $_POST['company_address'], 'Company Address');
        set_setting('company_website', $_POST['company_website'], 'Company Website');
        set_setting('company_logo', $_POST['company_logo'], 'Company Logo URL (optional)');
        
        // Currency Settings
        set_setting('currency_symbol', $_POST['currency_symbol'], 'Currency Symbol');
        set_setting('currency_name', $_POST['currency_name'], 'Currency Name');
        
        // Invoice Settings
        set_setting('invoice_prefix', $_POST['invoice_prefix'], 'Invoice Number Prefix');
        set_setting('purchase_prefix', $_POST['purchase_prefix'], 'Purchase Invoice Prefix');
        set_setting('sale_prefix', $_POST['sale_prefix'], 'Sale Invoice Prefix');
        
        // Business Settings
        set_setting('footer_text', $_POST['footer_text'], 'Footer Text for Invoices');
        set_setting('print_header', $_POST['print_header'], 'Print Header Text');
        set_setting('low_stock_threshold', $_POST['low_stock_threshold'], 'Low Stock Alert Threshold');
        set_setting('business_hours', $_POST['business_hours'], 'Business Hours');
        set_setting('business_days', $_POST['business_days'], 'Business Days');
        
        // Date/Time Settings
        set_setting('date_format', $_POST['date_format'], 'Date Format');
        set_setting('time_format', $_POST['time_format'], 'Time Format');
        
        header("Location: settings.php?success=updated");
        exit;
    } catch (Exception $e) {
        $error = "Error updating settings: " . $e->getMessage();
        $success = false;
    }
}

// Get current settings
$company_info = get_company_info();
$all_settings = get_all_settings();

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">System Settings</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'updated') echo "Settings updated successfully!";
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" id="settingsForm">
                <!-- Company Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Company Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($company_info['name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Tagline</label>
                                <input type="text" name="company_tagline" class="form-control" value="<?= htmlspecialchars($company_info['tagline']) ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="company_phone" class="form-control" value="<?= htmlspecialchars($company_info['phone']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="company_email" class="form-control" value="<?= htmlspecialchars($company_info['email']) ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="company_address" class="form-control" rows="3"><?= htmlspecialchars($company_info['address']) ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <input type="text" name="company_website" class="form-control" value="<?= htmlspecialchars($company_info['website']) ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Business Hours</label>
                                <input type="text" name="business_hours" class="form-control" value="<?= htmlspecialchars($company_info['business_hours']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Business Days</label>
                                <input type="text" name="business_days" class="form-control" value="<?= htmlspecialchars($company_info['business_days']) ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Company Logo URL (optional)</label>
                                <input type="url" name="company_logo" class="form-control" value="<?= htmlspecialchars($company_info['logo']) ?>" placeholder="https://example.com/logo.png">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Currency Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Currency Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Currency Symbol</label>
                                <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($company_info['currency_symbol']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Currency Name</label>
                                <input type="text" name="currency_name" class="form-control" value="<?= htmlspecialchars($company_info['currency_name']) ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Invoice Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Invoice Prefix</label>
                                <input type="text" name="invoice_prefix" class="form-control" value="<?= htmlspecialchars(get_setting('invoice_prefix', 'INV')) ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Purchase Invoice Prefix</label>
                                <input type="text" name="purchase_prefix" class="form-control" value="<?= htmlspecialchars(get_setting('purchase_prefix', 'PUR')) ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Sale Invoice Prefix</label>
                                <input type="text" name="sale_prefix" class="form-control" value="<?= htmlspecialchars(get_setting('sale_prefix', 'SALE')) ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Low Stock Threshold</label>
                                <input type="number" name="low_stock_threshold" class="form-control" value="<?= htmlspecialchars(get_setting('low_stock_threshold', '10')) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Footer Text for Invoices</label>
                                <textarea name="footer_text" class="form-control" rows="2"><?= htmlspecialchars(get_setting('footer_text', 'Thank you for your business!')) ?></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Print Header Text</label>
                                <textarea name="print_header" class="form-control" rows="2"><?= htmlspecialchars(get_setting('print_header', 'Computer Generated Invoice')) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date/Time Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Date & Time Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date Format</label>
                                <select name="date_format" class="form-control">
                                    <option value="d/m/Y" <?= get_setting('date_format') == 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                                    <option value="m/d/Y" <?= get_setting('date_format') == 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                                    <option value="Y-m-d" <?= get_setting('date_format') == 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                                    <option value="d-m-Y" <?= get_setting('date_format') == 'd-m-Y' ? 'selected' : '' ?>>DD-MM-YYYY</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Time Format</label>
                                <select name="time_format" class="form-control">
                                    <option value="H:i:s" <?= get_setting('time_format') == 'H:i:s' ? 'selected' : '' ?>>24 Hour (HH:MM:SS)</option>
                                    <option value="h:i:s A" <?= get_setting('time_format') == 'h:i:s A' ? 'selected' : '' ?>>12 Hour (HH:MM:SS AM/PM)</option>
                                    <option value="H:i" <?= get_setting('time_format') == 'H:i' ? 'selected' : '' ?>>24 Hour (HH:MM)</option>
                                    <option value="h:i A" <?= get_setting('time_format') == 'h:i A' ? 'selected' : '' ?>>12 Hour (HH:MM AM/PM)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg" name="update_settings">
                        <i class="bi bi-save me-2"></i>Save Settings
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
// Preview functionality for date/time formats
document.querySelectorAll('select[name="date_format"], select[name="time_format"]').forEach(select => {
    select.addEventListener('change', function() {
        const now = new Date();
        const format = this.value;
        const formatted = format.includes('Y') ? 
            now.toLocaleDateString() : 
            now.toLocaleTimeString();
        
        // You can add preview functionality here if needed
        console.log('Format changed to:', format, 'Example:', formatted);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
