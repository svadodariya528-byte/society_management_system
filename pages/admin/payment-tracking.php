<?php
ob_start();
session_start();
include 'a_layout.php';
require_once "../../db_connect.php";

// Ensure $conn is PDO
if (!isset($conn) || !($conn instanceof PDO)) {
    die("Database connection error");
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'record_payment') {
            $residentUserId = (int)($_POST['resident_user_id'] ?? 0);
            $periodId = (int)($_POST['period_id'] ?? 0);
            $amountPaid = floatval($_POST['amount_paid'] ?? 0);
            $paymentMode = trim($_POST['payment_mode'] ?? '');
            $transactionRef = trim($_POST['transaction_ref'] ?? '');
            $paymentDate = trim($_POST['payment_date'] ?? '');

            if ($residentUserId > 0 && $periodId > 0 && $amountPaid > 0 && $paymentMode && $paymentDate) {
                // Get maintenance period amount for validation
                $stmt = $conn->prepare("SELECT amount FROM maintenance_periods WHERE period_id = ?");
                $stmt->execute([$periodId]);
                $period = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($period) {
                    $status = ($amountPaid >= $period['amount']) ? 'paid' : 'partial';
                    
                    // Insert payment record
                    $stmt = $conn->prepare("INSERT INTO payments (resident_user_id, period_id, amount_paid, payment_date, payment_mode, transaction_ref, status) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $residentUserId,
                        $periodId,
                        $amountPaid,
                        $paymentDate,
                        $paymentMode,
                        $transactionRef,
                        $status
                    ]);
                    
                    $_SESSION['message'] = 'Payment recorded successfully';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Invalid maintenance period';
                    $_SESSION['message_type'] = 'danger';
                }
            } else {
                $_SESSION['message'] = 'Please fill all required fields correctly';
                $_SESSION['message_type'] = 'danger';
            }
            
            header("Location: payment-tracking.php");
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['message'] = 'Error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header("Location: payment-tracking.php");
        exit;
    }
}

// Fetch payments for display
try {
    $stmt = $conn->query("
        SELECT 
            p.*,
            u.name as resident_name,
            u.email as resident_email,
            rd.flat_no,
            mp.month,
            mp.year,
            mp.amount as period_amount
        FROM payments p
        JOIN users u ON p.resident_user_id = u.user_id
        LEFT JOIN resident_details rd ON u.user_id = rd.resident_id
        JOIN maintenance_periods mp ON p.period_id = mp.period_id
        ORDER BY p.payment_date DESC
    ");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $payments = [];
}

// Fetch residents for dropdown
try {
    $stmt = $conn->query("
        SELECT u.user_id, u.name, rd.flat_no
        FROM users u
        JOIN resident_details rd ON u.user_id = rd.resident_id
        WHERE u.role = 'resident' AND u.status = 1
        ORDER BY rd.flat_no
    ");
    $residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $residents = [];
}

// Fetch maintenance periods for dropdown
try {
    $stmt = $conn->query("
        SELECT period_id, month, year, amount
        FROM maintenance_periods
        ORDER BY year DESC, month DESC
    ");
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $periods = [];
}

// Calculate statistics - FIXED THE ARRAY_FILTER ISSUE
$totalCollected = 0;
$pendingPayments = 0;
$overduePayments = 0;

foreach ($payments as $payment) {
    $totalCollected += $payment['amount_paid'];
    if ($payment['status'] === 'pending') $pendingPayments++;
    if ($payment['status'] === 'overdue') $overduePayments++;
}

$totalResidents = count($residents);
// FIXED: Use $p instead of $payment in the array_filter callback
$paidResidents = array_filter($payments, function($p) { 
    return $p['status'] === 'paid'; 
});
$collectionRate = $totalResidents > 0 ? (count($paidResidents) / $totalResidents * 100) : 0;
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
    <!-- Status Message -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert" style="position:fixed; top:20px; right:20px; z-index:1060;">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Top Navigation -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-credit-card me-2"></i>
            Payment Tracking
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                    <i class="fas fa-plus me-1"></i>
                    Record Payment
                </button>
            </div>
        </div>
    </div>

    <!-- Payment Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="stat-icon text-success mb-2">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number text-success">₹<?= number_format($totalCollected, 2) ?></div>
                    <div class="stat-label">Total Collected</div>
                    <small class="text-success"><?= count($payments) ?> payments</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="stat-icon text-warning mb-2">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number text-warning"><?= $pendingPayments ?></div>
                    <div class="stat-label">Pending Payments</div>
                    <small class="text-muted">Need attention</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="stat-icon text-danger mb-2">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-number text-danger"><?= $overduePayments ?></div>
                    <div class="stat-label">Overdue Payments</div>
                    <small class="text-danger">Immediate action</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="stat-icon text-info mb-2">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-number text-info"><?= number_format($collectionRate, 1) ?>%</div>
                    <div class="stat-label">Collection Rate</div>
                    <small class="text-success">Based on <?= $totalResidents ?> residents</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label for="searchPayments" class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchPayments" placeholder="Search by flat, name...">
                </div>
                <div class="col-md-3">
                    <label for="filterStatus" class="form-label">Status</label>
                    <select class="form-select" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="overdue">Overdue</option>
                        <option value="partial">Partial</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterPaymentMode" class="form-label">Payment Method</label>
                    <select class="form-select" id="filterPaymentMode">
                        <option value="">All Methods</option>
                        <option value="cash">Cash</option>
                        <option value="online">Online</option>
                        <option value="cheque">Cheque</option>
                        <option value="upi">UPI</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters">
                        <i class="fas fa-times me-1"></i>Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Payment Records
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>Flat</th>
                            <th>Resident Name</th>
                            <th>Period</th>
                            <th>Bill Amount</th>
                            <th>Paid Amount</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted p-3">No payment records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <?php
                                    $monthName = DateTime::createFromFormat('!m', $payment['month'])->format('F');
                                    $paymentDate = $payment['payment_date'] ? date('M d, Y', strtotime($payment['payment_date'])) : '-';
                                    $statusClass = $payment['status'] === 'paid' ? 'bg-success' : 
                                                 ($payment['status'] === 'pending' ? 'bg-warning' : 
                                                 ($payment['status'] === 'overdue' ? 'bg-danger' : 'bg-info'));
                                    $statusText = ucfirst($payment['status']);
                                ?>
                                <tr>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($payment['flat_no'] ?? 'N/A') ?></span></td>
                                    <td>
                                        <strong><?= htmlspecialchars($payment['resident_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($payment['resident_email']) ?></small>
                                    </td>
                                    <td><?= $monthName . ' ' . $payment['year'] ?></td>
                                    <td>₹<?= number_format($payment['period_amount'], 2) ?></td>
                                    <td>₹<?= number_format($payment['amount_paid'], 2) ?></td>
                                    <td><?= $paymentDate ?></td>
                                    <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                    <td><?= ucfirst($payment['payment_mode'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-rupee-sign me-2"></i>
                    Record Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="record_payment">
                    
                    <div class="mb-3">
                        <label for="resident_user_id" class="form-label">Resident *</label>
                        <select class="form-select" id="resident_user_id" name="resident_user_id" required>
                            <option value="">Select resident...</option>
                            <?php foreach ($residents as $resident): ?>
                                <option value="<?= $resident['user_id'] ?>">
                                    <?= htmlspecialchars($resident['flat_no']) ?> - <?= htmlspecialchars($resident['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="period_id" class="form-label">Maintenance Period *</label>
                        <select class="form-select" id="period_id" name="period_id" required>
                            <option value="">Select period...</option>
                            <?php foreach ($periods as $period): ?>
                                <?php $monthName = DateTime::createFromFormat('!m', $period['month'])->format('F'); ?>
                                <option value="<?= $period['period_id'] ?>" data-amount="<?= $period['amount'] ?>">
                                    <?= $monthName . ' ' . $period['year'] ?> - ₹<?= number_format($period['amount'], 2) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="period_amount" class="form-label">Bill Amount (₹)</label>
                        <input type="number" class="form-control" id="period_amount" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="amount_paid" class="form-label">Paid Amount (₹) *</label>
                        <input type="number" class="form-control" id="amount_paid" name="amount_paid" 
                               step="0.01" min="0" required placeholder="0.00">
                    </div>

                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date *</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="payment_mode" class="form-label">Payment Method *</label>
                        <select class="form-select" id="payment_mode" name="payment_mode" required>
                            <option value="">Select method...</option>
                            <option value="cash">Cash</option>
                            <option value="online">Online Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="upi">UPI</option>
                            <option value="card">Card</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="transaction_ref" class="form-label">Transaction Reference</label>
                        <input type="text" class="form-control" id="transaction_ref" name="transaction_ref" 
                               placeholder="Cheque number / Transaction ID">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);

    // Update bill amount when period is selected
    $('#period_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const amount = selectedOption.data('amount') || 0;
        $('#period_amount').val(amount);
    });

    // Filter functionality
    $('#searchPayments, #filterStatus, #filterPaymentMode').on('input change', function(){
        const search = $('#searchPayments').val().toLowerCase();
        const status = $('#filterStatus').val();
        const paymentMode = $('#filterPaymentMode').val();

        $('#paymentsTable tbody tr').each(function(){
            const rowText = $(this).text().toLowerCase();
            const rowStatus = $(this).find('.badge').text().toLowerCase();
            const rowPaymentMode = $(this).find('td').eq(7).text().toLowerCase();

            let show = true;
            if (search && !rowText.includes(search)) show = false;
            if (status && rowStatus !== status.toLowerCase()) show = false;
            if (paymentMode && rowPaymentMode !== paymentMode.toLowerCase()) show = false;

            $(this).toggle(show);
        });
    });

    $('#clearFilters').click(function(){
        $('#searchPayments').val('');
        $('#filterStatus').val('');
        $('#filterPaymentMode').val('');
        $('#paymentsTable tbody tr').show();
    });
});
</script>

</body>
</html>
<?php ob_end_flush(); ?>