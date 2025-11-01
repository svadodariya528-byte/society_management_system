<?php
ob_start();
session_start();
include 'a_layout.php';
require_once "../../db_connect.php";

// Ensure $conn is PDO
if (!isset($conn) || !($conn instanceof PDO)) {
    die("Database connection error");
}

// Fetch dashboard statistics
try {
    // Total Residents
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'resident' AND status = 1");
    $totalResidents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // This Month Collection
    $currentMonth = date('Y-m');
    $stmt = $conn->prepare("
        SELECT SUM(amount_paid) as total_collected 
        FROM payments 
        WHERE DATE_FORMAT(payment_date, '%Y-%m') = ? AND status = 'paid'
    ");
    $stmt->execute([$currentMonth]);
    $monthlyCollection = $stmt->fetch(PDO::FETCH_ASSOC)['total_collected'] ?? 0;

    // Pending Payments
    $stmt = $conn->query("SELECT COUNT(*) as pending FROM payments WHERE status IN ('pending', 'overdue')");
    $pendingPayments = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

    // Today's Visitors
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as visitors FROM visitors WHERE DATE(entry_time) = ?");
    $stmt->execute([$today]);
    $todaysVisitors = $stmt->fetch(PDO::FETCH_ASSOC)['visitors'];

    // Current Visitors Inside
    $stmt = $conn->query("SELECT COUNT(*) as inside FROM visitors WHERE exit_time IS NULL");
    $currentVisitors = $stmt->fetch(PDO::FETCH_ASSOC)['inside'];

    // Payment Status Distribution
    $stmt = $conn->query("
        SELECT status, COUNT(*) as count 
        FROM payments 
        GROUP BY status
    ");
    $paymentStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Current Year Collection Data (All months)
    $currentYear = date('Y');
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(payment_date, '%Y-%m') as month,
            MONTH(payment_date) as month_number,
            SUM(amount_paid) as amount
        FROM payments 
        WHERE YEAR(payment_date) = ?
        GROUP BY DATE_FORMAT(payment_date, '%Y-%m'), MONTH(payment_date)
        ORDER BY month_number ASC
    ");
    $stmt->execute([$currentYear]);
    $yearlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Activities
    $stmt = $conn->query("
        (SELECT 
            'payment' as type,
            p.payment_date as date,
            CONCAT('Payment Received - Flat ', rd.flat_no, ' - ₹', p.amount_paid) as description,
            u.name as resident_name
        FROM payments p
        JOIN users u ON p.resident_user_id = u.user_id
        LEFT JOIN resident_details rd ON u.user_id = rd.resident_id
        ORDER BY p.payment_date DESC
        LIMIT 2)
        
        UNION ALL
        
        (SELECT 
            'resident' as type,
            u.created_at as date,
            CONCAT('New Resident Registration - Flat ', rd.flat_no) as description,
            u.name as resident_name
        FROM users u
        JOIN resident_details rd ON u.user_id = rd.resident_id
        WHERE u.role = 'resident'
        ORDER BY u.created_at DESC
        LIMIT 2)
        
        UNION ALL
        
        (SELECT 
            'poll' as type,
            p.created_at as date,
            CONCAT('New Poll Created - ', SUBSTRING(p.question, 1, 30)) as description,
            u.name as created_by
        FROM polls p
        JOIN users u ON p.created_by_user_id = u.user_id
        ORDER BY p.created_at DESC
        LIMIT 1)
        
        ORDER BY date DESC
        LIMIT 5
    ");
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Default values if query fails
    $totalResidents = 0;
    $monthlyCollection = 0;
    $pendingPayments = 0;
    $todaysVisitors = 0;
    $currentVisitors = 0;
    $paymentStatus = [];
    $yearlyData = [];
    $recentActivities = [];
}

// Prepare chart data
$paymentStatusLabels = [];
$paymentStatusData = [];
$paymentStatusColors = ['#27ae60', '#f39c12', '#e74c3c', '#9b59b6'];

foreach ($paymentStatus as $status) {
    $paymentStatusLabels[] = ucfirst($status['status']);
    $paymentStatusData[] = $status['count'];
}

// Yearly chart data - All months of current year
$monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthlyAmounts = array_fill(0, 12, 0); // Initialize all months with 0

// Fill in actual data for months that have payments
foreach ($yearlyData as $data) {
    $monthNumber = intval($data['month_number']) - 1; // Convert to 0-based index
    if ($monthNumber >= 0 && $monthNumber < 12) {
        $monthlyAmounts[$monthNumber] = $data['amount'] ?? 0;
    }
}

// Calculate yearly total and growth
$yearlyTotal = array_sum($monthlyAmounts);
$previousYear = $currentYear - 1;

// Get previous year total for growth calculation
try {
    $stmt = $conn->prepare("
        SELECT SUM(amount_paid) as total 
        FROM payments 
        WHERE YEAR(payment_date) = ? AND status = 'paid'
    ");
    $stmt->execute([$previousYear]);
    $previousYearTotal = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Calculate growth percentage
    if ($previousYearTotal > 0) {
        $growthPercentage = (($yearlyTotal - $previousYearTotal) / $previousYearTotal) * 100;
    } else {
        $growthPercentage = $yearlyTotal > 0 ? 100 : 0;
    }
} catch (Exception $e) {
    $previousYearTotal = 0;
    $growthPercentage = 0;
}

// Determine the scale for chart display
$maxAmount = max($monthlyAmounts);
$chartScale = 'normal';
$scaleFactor = 1;
$scaleSuffix = '';

if ($maxAmount > 1000000) { // Above 10 lakhs
    $chartScale = 'lakhs';
    $scaleFactor = 100000;
    $scaleSuffix = 'L';
} elseif ($maxAmount > 100000) { // Above 1 lakh
    $chartScale = 'thousands';
    $scaleFactor = 1000;
    $scaleSuffix = 'K';
}

// Scale the amounts for better chart display
$scaledAmounts = array_map(function($amount) use ($scaleFactor) {
    return $amount / $scaleFactor;
}, $monthlyAmounts);

// Format the yearly total with appropriate scale
if ($chartScale === 'lakhs') {
    $formattedYearlyTotal = number_format($yearlyTotal / 100000, 2) . 'L';
} elseif ($chartScale === 'thousands') {
    $formattedYearlyTotal = number_format($yearlyTotal / 1000, 2) . 'K';
} else {
    $formattedYearlyTotal = number_format($yearlyTotal, 2);
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
    <!-- Top Navigation -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-tachometer-alt me-2"></i>
            Admin Dashboard
        </h1>
        
    </div>

    <!-- Welcome Message -->
    <div class="alert alert-primary mb-4">
        <h5 class="alert-heading">
            <i class="fas fa-hand-wave me-2"></i>
            Welcome back, Admin!
        </h5>
        <p class="mb-0">Here's an overview of today's activities and key metrics for Greenwood Society.</p>
        <hr>
        <small class="mb-0">Last login: Today at <?= date('g:i A') ?></small>
    </div>

    <!-- Statistics Cards -->
    <div class="row dashboard-stats">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="stat-icon text-primary mb-3">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="stat-number text-primary"><?= $totalResidents ?></div>
                    <div class="stat-label">Total Residents</div>
                    <small class="text-muted">Active residents</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="stat-icon text-success mb-3">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-number text-success">₹<?= number_format($monthlyCollection, 2) ?></div>
                    <div class="stat-label">This Month Collection</div>
                    <small class="text-success">Current month</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="stat-icon text-warning mb-3">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-number text-warning"><?= $pendingPayments ?></div>
                    <div class="stat-label">Pending Payments</div>
                    <small class="text-muted">Need attention</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="stat-icon text-info mb-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number text-info"><?= $todaysVisitors ?></div>
                    <div class="stat-label">Today's Visitors</div>
                    <small class="text-muted"><?= $currentVisitors ?> currently inside</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        <?= $currentYear ?> Yearly Collection Overview
                        <?php if ($chartScale !== 'normal'): ?>
                            <small class="ms-2 opacity-75">(Amounts in <?= $scaleSuffix ?>)</small>
                        <?php endif; ?>
                    </h5>
                    <div class="year-stats">
                        <span class="badge bg-light text-dark me-2">
                            Total: ₹<?= $formattedYearlyTotal ?>
                        </span>
                        <span class="badge <?= $growthPercentage >= 0 ? 'bg-success' : 'bg-danger' ?>">
                            <i class="fas fa-arrow-<?= $growthPercentage >= 0 ? 'up' : 'down' ?> me-1"></i>
                            <?= number_format(abs($growthPercentage), 1) ?>%
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="monthlyCollectionChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Payment Status
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="paymentStatusChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities & Quick Actions -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Recent Activities
                    </h5>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        <?php if (empty($recentActivities)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No recent activities
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <?php
                                    $activityIcon = '';
                                    $activityColor = '';
                                    switch($activity['type']) {
                                        case 'payment':
                                            $activityIcon = 'fa-rupee-sign';
                                            $activityColor = 'bg-success';
                                            break;
                                        case 'resident':
                                            $activityIcon = 'fa-user-plus';
                                            $activityColor = 'bg-primary';
                                            break;
                                        case 'poll':
                                            $activityIcon = 'fa-vote-yea';
                                            $activityColor = 'bg-info';
                                            break;
                                        default:
                                            $activityIcon = 'fa-bell';
                                            $activityColor = 'bg-warning';
                                    }
                                    $timeAgo = time_elapsed_string($activity['date']);
                                ?>
                                <div class="activity-item d-flex align-items-center mb-3">
                                    <div class="activity-icon <?= $activityColor ?> text-white rounded-circle me-3">
                                        <i class="fas <?= $activityIcon ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong><?= htmlspecialchars($activity['description']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= $timeAgo ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="text-center">
                        <a href="#" class="btn btn-sm btn-outline-primary">View All Activities</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="user-management.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-2"></i>
                            Add New Resident
                        </a>
                        <a href="maintenance-setup.php" class="btn btn-outline-success">
                            <i class="fas fa-rupee-sign me-2"></i>
                            Set Maintenance Fee
                        </a>
                        <a href="poll-management.php" class="btn btn-outline-info">
                            <i class="fas fa-vote-yea me-2"></i>
                            Create New Poll
                        </a>
                        <a href="visitor-log.php" class="btn btn-outline-warning">
                            <i class="fas fa-clipboard-list me-2"></i>
                            View Visitor Log
                        </a>
                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#emergencyModal">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Emergency Alert
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Items & Alerts -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tasks me-2"></i>
                        Pending Tasks
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div>
                                <strong>Monthly Maintenance Bills</strong>
                                <br>
                                <small class="text-muted">Generate and send bills for <?= date('F Y') ?></small>
                            </div>
                            <span class="badge bg-warning rounded-pill">High</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div>
                                <strong>Pending Payments Follow-up</strong>
                                <br>
                                <small class="text-muted"><?= $pendingPayments ?> payments pending</small>
                            </div>
                            <span class="badge bg-primary rounded-pill">Medium</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div>
                                <strong>Visitor Log Review</strong>
                                <br>
                                <small class="text-muted">Review today's <?= $todaysVisitors ?> visitors</small>
                            </div>
                            <span class="badge bg-success rounded-pill">Low</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bell me-2"></i>
                        System Alerts
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($pendingPayments > 0): ?>
                    <div class="alert alert-danger alert-sm mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Pending Payments:</strong> <?= $pendingPayments ?> payments need attention
                    </div>
                    <?php endif; ?>
                    <?php if ($currentVisitors > 0): ?>
                    <div class="alert alert-warning alert-sm mb-2">
                        <i class="fas fa-users me-2"></i>
                        <strong>Current Visitors:</strong> <?= $currentVisitors ?> visitors currently inside
                    </div>
                    <?php endif; ?>
                    <div class="alert alert-info alert-sm mb-0">
                        <i class="fas fa-calendar me-2"></i>
                        <strong>System Status:</strong> All systems operational
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-3 border-top">
        <small class="text-muted">
            &copy; <?= date('Y') ?> Greenwood Society Admin Panel. All rights reserved.
        </small>
    </footer>
</main>

<!-- Emergency Alert Modal -->
<div class="modal fade" id="emergencyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Send Emergency Alert
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="emergencyAlertForm">
                    <div class="mb-3">
                        <label for="alertType" class="form-label">Alert Type</label>
                        <select class="form-select" id="alertType" required>
                            <option value="">Select alert type...</option>
                            <option value="fire">Fire Emergency</option>
                            <option value="security">Security Alert</option>
                            <option value="maintenance">Maintenance Emergency</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="alertMessage" class="form-label">Alert Message</label>
                        <textarea class="form-control" id="alertMessage" rows="3" required 
                                  placeholder="Enter emergency alert message..."></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sendSMS">
                            <label class="form-check-label" for="sendSMS">
                                Send SMS to all residents
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sendEmail">
                            <label class="form-check-label" for="sendEmail">
                                Send Email notification
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="sendEmergencyAlert">
                    <i class="fas fa-paper-plane me-2"></i>
                    Send Alert
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Dashboard specific JavaScript -->
<script>
    $(document).ready(function() {
        // Initialize Charts with real data
        initializeDashboardCharts();
        
        // Emergency Alert Handler
        $('#sendEmergencyAlert').on('click', function() {
            const alertType = $('#alertType').val();
            const alertMessage = $('#alertMessage').val();
            
            if (!alertType || !alertMessage) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Simulate sending alert
            alert('Emergency alert sent successfully to all residents!');
            $('#emergencyModal').modal('hide');
            $('#emergencyAlertForm')[0].reset();
        });
        
        // Auto-refresh data every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    });

    function initializeDashboardCharts() {
        // Monthly Collection Chart - Dynamic scaling
        const ctx1 = document.getElementById('monthlyCollectionChart');
        
        // Get the scale information from PHP
        const chartScale = '<?= $chartScale ?>';
        const scaleSuffix = '<?= $scaleSuffix ?>';
        const scaledAmounts = <?= json_encode($scaledAmounts) ?>;
        
        // Determine Y-axis configuration based on scale
        let yAxisConfig = {
            beginAtZero: true,
            ticks: {
                callback: function(value) {
                    if (chartScale === 'lakhs') {
                        return '₹' + value.toLocaleString() + 'L';
                    } else if (chartScale === 'thousands') {
                        return '₹' + value.toLocaleString() + 'K';
                    } else {
                        return '₹' + value.toLocaleString();
                    }
                }
            },
            grid: {
                drawBorder: false
            }
        };

        // Adjust step size based on data range
        const maxScaledValue = Math.max(...scaledAmounts);
        if (maxScaledValue > 50) {
            yAxisConfig.ticks.stepSize = Math.ceil(maxScaledValue / 10);
        }

        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthlyLabels) ?>,
                datasets: [{
                    label: 'Collection Amount' + (scaleSuffix ? ` (${scaleSuffix})` : ''),
                    data: scaledAmounts,
                    backgroundColor: [
                        '#3498db', '#2980b9', '#1f618d', '#1a5276', '#154360',
                        '#2874a6', '#2e86c1', '#3498db', '#5dade2', '#85c1e9',
                        '#aed6f1', '#d6eaf8'
                    ],
                    borderColor: '#2c3e50',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.parsed.y;
                                let originalValue = value;
                                
                                // Convert back to original amount for tooltip
                                if (chartScale === 'lakhs') {
                                    originalValue = value * 100000;
                                } else if (chartScale === 'thousands') {
                                    originalValue = value * 1000;
                                }
                                
                                return '₹' + originalValue.toLocaleString('en-IN');
                            },
                            afterLabel: function(context) {
                                if (chartScale !== 'normal') {
                                    return `(Displayed in ${scaleSuffix} for better visualization)`;
                                }
                                return '';
                            }
                        }
                    }
                },
                scales: {
                    y: yAxisConfig,
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                // Add animation configuration for smoother rendering
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });

        // Payment Status Chart with real data
        const ctx2 = document.getElementById('paymentStatusChart');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($paymentStatusLabels) ?>,
                datasets: [{
                    data: <?= json_encode($paymentStatusData) ?>,
                    backgroundColor: <?= json_encode($paymentStatusColors) ?>,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
</script>

</body>
</html>

<?php
// Helper function to format time ago - FIXED VERSION
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Extract the total days from DateInterval
    $totalDays = $diff->days;
    
    // Calculate weeks and remaining days manually
    $weeks = floor($totalDays / 7);
    $days = $totalDays - ($weeks * 7);
    
    // Build the time periods array
    $periods = array(
        'year'   => $diff->y,
        'month'  => $diff->m,
        'week'   => $weeks,
        'day'    => $days,
        'hour'   => $diff->h,
        'minute' => $diff->i,
        'second' => $diff->s
    );
    
    // Build the output string
    $parts = array();
    foreach ($periods as $name => $value) {
        if ($value > 0) {
            $parts[] = $value . ' ' . $name . ($value > 1 ? 's' : '');
        }
    }

    if (!$full) {
        $parts = array_slice($parts, 0, 1);
    }
    
    return $parts ? implode(', ', $parts) . ' ago' : 'just now';
}
?>
<?php ob_end_flush(); ?>