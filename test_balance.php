<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$page_title = "Balance Test";
$activePage = "test_balance";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">🧪 Balance Calculation Test</h1>
            </div>

            <div class="row">
                <!-- Customer Balance Test -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">👤 Customer Balance Test</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Test customer balance calculation
                            $customer_query = "
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
                                LIMIT 5
                            ";
                            
                            try {
                                $customers = $pdo->query($customer_query)->fetchAll(PDO::FETCH_ASSOC);
                                if (!empty($customers)) {
                                    echo '<div class="table-responsive"><table class="table table-sm">';
                                    echo '<thead><tr><th>Customer</th><th>Opening</th><th>Sales</th><th>Payments</th><th>Balance</th></tr></thead><tbody>';
                                    
                                    foreach ($customers as $customer) {
                                        $balance_class = $customer['current_balance'] > 0 ? 'text-danger' : 'text-success';
                                        echo '<tr>';
                                        echo '<td><strong>' . htmlspecialchars($customer['name']) . '</strong></td>';
                                        echo '<td>PKR ' . number_format($customer['opening_balance'], 2) . '</td>';
                                        echo '<td>PKR ' . number_format($customer['total_sales'], 2) . '</td>';
                                        echo '<td>PKR ' . number_format($customer['total_payments'], 2) . '</td>';
                                        echo '<td class="fw-bold ' . $balance_class . '">PKR ' . number_format($customer['current_balance'], 2) . '</td>';
                                        echo '</tr>';
                                    }
                                    
                                    echo '</tbody></table></div>';
                                } else {
                                    echo '<p class="text-muted">No customers found.</p>';
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Supplier Balance Test -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">🏢 Supplier Balance Test</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Test supplier balance calculation
                            $supplier_query = "
                                SELECT 
                                    s.id,
                                    s.supplier_name,
                                    s.supplier_contact,
                                    COALESCE(s.opening_balance, 0) as opening_balance,
                                    COALESCE(SUM(p.total_amount), 0) as total_purchases,
                                    COALESCE(SUM(sp.payment_amount), 0) as total_payments,
                                    (COALESCE(SUM(p.total_amount), 0) - COALESCE(SUM(sp.payment_amount), 0) + COALESCE(s.opening_balance, 0)) as current_balance
                                FROM supplier s
                                LEFT JOIN purchase p ON s.id = p.supplier_id
                                LEFT JOIN supplier_payments sp ON s.id = sp.supplier_id
                                GROUP BY s.id, s.supplier_name, s.supplier_contact, s.opening_balance
                                ORDER BY s.supplier_name
                                LIMIT 5
                            ";
                            
                            try {
                                $suppliers = $pdo->query($supplier_query)->fetchAll(PDO::FETCH_ASSOC);
                                if (!empty($suppliers)) {
                                    echo '<div class="table-responsive"><table class="table table-sm">';
                                    echo '<thead><tr><th>Supplier</th><th>Opening</th><th>Purchases</th><th>Payments</th><th>Balance</th></tr></thead><tbody>';
                                    
                                    foreach ($suppliers as $supplier) {
                                        $balance_class = $supplier['current_balance'] > 0 ? 'text-danger' : 'text-success';
                                        echo '<tr>';
                                        echo '<td><strong>' . htmlspecialchars($supplier['supplier_name']) . '</strong></td>';
                                        echo '<td>PKR ' . number_format($supplier['opening_balance'], 2) . '</td>';
                                        echo '<td>PKR ' . number_format($supplier['total_purchases'], 2) . '</td>';
                                        echo '<td>PKR ' . number_format($supplier['total_payments'], 2) . '</td>';
                                        echo '<td class="fw-bold ' . $balance_class . '">PKR ' . number_format($supplier['current_balance'], 2) . '</td>';
                                        echo '</tr>';
                                    }
                                    
                                    echo '</tbody></table></div>';
                                } else {
                                    echo '<p class="text-muted">No suppliers found.</p>';
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Table Info -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">📊 Database Table Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Customer Related Tables:</h6>
                                    <ul>
                                        <li><strong>customer</strong> - Customer information with opening_balance</li>
                                        <li><strong>sale</strong> - Sales transactions (total_amount)</li>
                                        <li><strong>customer_payment</strong> - Customer payments (paid)</li>
                                    </ul>
                                    <p><strong>Balance Formula:</strong> Opening Balance + Total Sales - Total Payments</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Supplier Related Tables:</h6>
                                    <ul>
                                        <li><strong>supplier</strong> - Supplier information with opening_balance</li>
                                        <li><strong>purchase</strong> - Purchase transactions (total_amount)</li>
                                        <li><strong>supplier_payments</strong> - Supplier payments (payment_amount)</li>
                                    </ul>
                                    <p><strong>Balance Formula:</strong> Opening Balance + Total Purchases - Total Payments</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
