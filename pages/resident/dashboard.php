<?php
session_start();
require_once "../../db_connect.php"; 

// Check if user is logged in and is a resident
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Initialize variables
$flat_no = "N/A";
$due_amount = 0;
$due_date = "";
$paid_this_year = 0;
$payment_data = array_fill(0, 12, 0);
$visitor_logs = [];
$notification_count = 0;

// DEBUG: Start debugging
echo "<!-- DEBUG: Starting dashboard for user_id: $user_id, name: $user_name -->\n";

try {
    // DEBUG: Test database connection
    $test_stmt = $conn->query("SELECT 1");
    echo "<!-- DEBUG: Database connection successful -->\n";
    
    // 1. Get resident details and flat number
    echo "<!-- DEBUG: Getting flat number for user_id: $user_id -->\n";
    $stmt = $conn->prepare("SELECT flat_no FROM resident_details WHERE resident_id = ?");
    $stmt->execute([$user_id]);
    $resident_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resident_details) {
        $flat_no = $resident_details['flat_no'];
        echo "<!-- DEBUG: Found flat number: $flat_no -->\n";
    } else {
        echo "<!-- DEBUG: No resident_details found for user_id: $user_id -->\n";
        
        // Try alternative table/column names
        $stmt = $conn->prepare("SHOW TABLES LIKE '%resident%'");
        $stmt->execute();
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<!-- DEBUG: Resident tables found: " . implode(', ', $tables) . " -->\n";
    }

    // 2. Get due amount - SIMPLIFIED APPROACH
    echo "<!-- DEBUG: Getting due amount -->\n";
    $stmt = $conn->prepare("
        SELECT amount_paid, payment_date, status 
        FROM payments 
        WHERE resident_user_id = ? 
        AND status IN ('pending', 'overdue')
        ORDER BY payment_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $due_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!-- DEBUG: Found " . count($due_payments) . " due payments -->\n";
    
    if (count($due_payments) > 0) {
        // Use a default amount or calculate from maintenance setup
        $stmt = $conn->prepare("SELECT maintenance_amount FROM maintenance_setup ORDER BY created_at DESC LIMIT 1");
        $stmt->execute();
        $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($maintenance && $maintenance['maintenance_amount']) {
            $due_amount = $maintenance['maintenance_amount'];
            $due_date = date('jS M', strtotime('+15 days')); // Default due date
            echo "<!-- DEBUG: Using maintenance amount: $due_amount -->\n";
        } else {
            $due_amount = 4300; // Fallback amount
            echo "<!-- DEBUG: Using fallback amount: $due_amount -->\n";
        }
    }

    // 3. Get total paid this year - FIXED QUERY
    echo "<!-- DEBUG: Getting paid this year -->\n";
    $current_year = date('Y');
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount_paid), 0) as total_paid 
        FROM payments 
        WHERE resident_user_id = ? 
        AND YEAR(payment_date) = ? 
        AND status = 'paid'
    ");
    $stmt->execute([$user_id, $current_year]);
    $paid_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $paid_this_year = $paid_result['total_paid'] ?? 0;
    echo "<!-- DEBUG: Paid this year: $paid_this_year -->\n";
    
    // DEBUG: Check what payments exist
    $stmt = $conn->prepare("SELECT payment_id, amount_paid, payment_date, status FROM payments WHERE resident_user_id = ?");
    $stmt->execute([$user_id]);
    $all_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- DEBUG: All payments for user: " . count($all_payments) . " records -->\n";
    foreach ($all_payments as $payment) {
        echo "<!-- DEBUG Payment: ID {$payment['payment_id']}, Amount: {$payment['amount_paid']}, Date: {$payment['payment_date']}, Status: {$payment['status']} -->\n";
    }

    // 4. Get payment data for chart - SIMPLIFIED QUERY
    echo "<!-- DEBUG: Getting payment data for chart -->\n";
    $stmt = $conn->prepare("
        SELECT 
            MONTH(payment_date) as month,
            COALESCE(SUM(amount_paid), 0) as amount
        FROM payments 
        WHERE resident_user_id = ? 
        AND YEAR(payment_date) = YEAR(CURDATE())
        AND status = 'paid'
        GROUP BY MONTH(payment_date)
        ORDER BY month
    ");
    $stmt->execute([$user_id]);
    $payment_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!-- DEBUG: Payment results count: " . count($payment_results) . " -->\n";
    foreach ($payment_results as $payment) {
        echo "<!-- DEBUG Payment Data: Month {$payment['month']}, Amount {$payment['amount']} -->\n";
    }
    
    // Fill payment data array
    foreach ($payment_results as $payment) {
        $month_index = intval($payment['month']) - 1;
        if ($month_index >= 0 && $month_index < 12) {
            $payment_data[$month_index] = floatval($payment['amount']);
        }
    }
    echo "<!-- DEBUG: Final payment data: " . json_encode($payment_data) . " -->\n";

    // 5. Get visitor logs - FIXED QUERY
    echo "<!-- DEBUG: Getting visitor logs -->\n";
    $stmt = $conn->prepare("
        SELECT 
            name as visitor_name,
            mobile as contact_number,
            purpose,
            entry_time,
            exit_time
        FROM visitors 
        WHERE visiting_resident_user_id = ? 
        ORDER BY entry_time DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $visitor_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!-- DEBUG: Visitor logs count: " . count($visitor_logs) . " -->\n";
    foreach ($visitor_logs as $visitor) {
        echo "<!-- DEBUG Visitor: {$visitor['visitor_name']}, Purpose: {$visitor['purpose']} -->\n";
    }
    
    // DEBUG: Check all visitors to see column names
    $stmt = $conn->prepare("SHOW COLUMNS FROM visitors");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<!-- DEBUG: Visitors table columns: " . implode(', ', $columns) . " -->\n";

    // 6. Get notification count
    echo "<!-- DEBUG: Getting notification count -->\n";
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM payments 
        WHERE resident_user_id = ? 
        AND status IN ('pending', 'overdue')
    ");
    $stmt->execute([$user_id]);
    $notification_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $notification_count = $notification_result['count'] ?? 0;
    echo "<!-- DEBUG: Notification count: $notification_count -->\n";

} catch (PDOException $e) {
    echo "<!-- DEBUG: Database Error: " . $e->getMessage() . " -->\n";
    error_log("Dashboard error: " . $e->getMessage());
}

include 'r_layout.php';
?>

<!-- Main Content -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
   

    <!-- Top Navigation -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-tachometer-alt me-2"></i>
            Welcome, <?php echo htmlspecialchars($user_name); ?>!
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                
            </div>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-1"></i>
                    My Account
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="r_profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($due_amount > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Payment Due!</strong> Your maintenance fee for <?php echo date('F Y'); ?> (₹<?php echo number_format($due_amount, 2); ?>) is due on <?php echo $due_date; ?>. 
                <a href="maintenance_pay.php" class="alert-link">Pay now</a>.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <div class="stat-icon text-primary mb-2"><i class="fas fa-home"></i></div>
                    <div class="stat-number text-primary"><?php echo htmlspecialchars($flat_no); ?></div>
                    <div class="stat-label">My Flat</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <div class="stat-icon text-warning mb-2"><i class="fas fa-rupee-sign"></i></div>
                    <div class="stat-number text-warning">₹<?php echo number_format($due_amount, 2); ?></div>
                    <div class="stat-label">Due Amount</div>
                    <?php if ($due_date): ?>
                        <small class="text-danger">Due: <?php echo $due_date; ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <div class="stat-icon text-success mb-2"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number text-success">₹<?php echo number_format($paid_this_year, 2); ?></div>
                    <div class="stat-label">Paid This Year</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Summary Chart -->
    <div class="row mb-4">
        <div class="col-lg-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Payment Summary (<?php echo date('Y'); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="paymentSummaryChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Visitor Log Table -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Recent Visitor Log</h5>
                </div>
                <div class="card-body table-responsive">
                    <?php if (empty($visitor_logs)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No recent visitors found.</p>
                            <small class="text-info">User ID in database: <?php echo $user_id; ?></small>
                        </div>
                    <?php else: ?>
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Visitor Name</th>
                                    <th>Contact Number</th>
                                    <th>Purpose of Visit</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($visitor_logs as $index => $visitor): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($visitor['visitor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($visitor['contact_number']); ?></td>
                                        <td><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                                        <td><?php echo date('d-m-Y h:i A', strtotime($visitor['entry_time'])); ?></td>
                                        <td>
                                            <?php if ($visitor['exit_time']): ?>
                                                <span class="badge bg-success">Visited</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-3 border-top">
        <small class="text-muted">
            &copy; <?php echo date('Y'); ?> Greenwood Society. All rights reserved. | Resident Portal
        </small>
    </footer>
</main>
</div>
</div>

<!-- Bootstrap & jQuery -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // Payment Summary Chart with dynamic data
    const ctx = document.getElementById('paymentSummaryChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets: [{
                label: 'Payment Amount (₹)',
                data: <?php echo json_encode($payment_data); ?>,
                backgroundColor: '#27ae60',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            plugins: { 
                legend: { 
                    display: false 
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₹' + context.parsed.y.toLocaleString('en-IN');
                        }
                    }
                }
            },
            scales: { 
                y: { 
                    beginAtZero: true, 
                    ticks: { 
                        callback: function(value) {
                            return '₹' + value.toLocaleString('en-IN');
                        }
                    } 
                } 
            }
        }
    });
});
</script>
</body>
</html>