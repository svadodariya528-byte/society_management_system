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
        if ($action === 'add_maintenance_period') {
            $month = (int)($_POST['month'] ?? 0);
            $year = (int)($_POST['year'] ?? 0);
            $dueDate = trim($_POST['due_date'] ?? '');
            $amount = floatval($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');

            if ($month > 0 && $year > 0 && $dueDate && $amount > 0) {
                // Check if period already exists
                $stmt = $conn->prepare("SELECT period_id FROM maintenance_periods WHERE month = ? AND year = ?");
                $stmt->execute([$month, $year]);
                
                if ($stmt->fetch()) {
                    $_SESSION['message'] = 'Maintenance period already exists for this month/year';
                    $_SESSION['message_type'] = 'danger';
                } else {
                    // Insert new maintenance period
                    $stmt = $conn->prepare("INSERT INTO maintenance_periods (month, year, due_date, amount, description, created_by_user_id, created_at) 
                                           VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $month, 
                        $year, 
                        $dueDate, 
                        $amount, 
                        $description,
                        1 // Default admin user_id (set from session if available)
                    ]);
                    
                    $_SESSION['message'] = 'Maintenance period added successfully';
                    $_SESSION['message_type'] = 'success';
                }
            } else {
                $_SESSION['message'] = 'Please fill all required fields correctly';
                $_SESSION['message_type'] = 'danger';
            }
            
            header("Location: maintenance-setup.php");
            exit;
        }

        if ($action === 'delete_period') {
            $periodId = (int)($_POST['period_id'] ?? 0);
            if ($periodId > 0) {
                // Check if there are payments for this period
                $stmt = $conn->prepare("SELECT COUNT(*) FROM payments WHERE period_id = ?");
                $stmt->execute([$periodId]);
                $paymentCount = $stmt->fetchColumn();
                
                if ($paymentCount > 0) {
                    $_SESSION['message'] = 'Cannot delete period with existing payments';
                    $_SESSION['message_type'] = 'danger';
                } else {
                    $stmt = $conn->prepare("DELETE FROM maintenance_periods WHERE period_id = ?");
                    $stmt->execute([$periodId]);
                    
                    $_SESSION['message'] = 'Maintenance period deleted successfully';
                    $_SESSION['message_type'] = 'success';
                }
            }
            
            header("Location: maintenance-setup.php");
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['message'] = 'Error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header("Location: maintenance-setup.php");
        exit;
    }
}

// Fetch maintenance periods for display
try {
    $stmt = $conn->query("
        SELECT mp.*, u.name as created_by_name,
               (SELECT COUNT(*) FROM payments p WHERE p.period_id = mp.period_id) as payment_count,
               (SELECT COUNT(*) FROM payments p WHERE p.period_id = mp.period_id AND p.status = 'paid') as paid_count
        FROM maintenance_periods mp
        LEFT JOIN users u ON mp.created_by_user_id = u.user_id
        ORDER BY mp.year DESC, mp.month DESC
    ");
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $periods = [];
}

// Fetch recent payments for display
try {
    $stmt = $conn->query("
        SELECT p.*, u.name as resident_name, rd.flat_no, mp.month, mp.year
        FROM payments p
        JOIN users u ON p.resident_user_id = u.user_id
        LEFT JOIN resident_details rd ON u.user_id = rd.resident_id
        JOIN maintenance_periods mp ON p.period_id = mp.period_id
        ORDER BY p.payment_date DESC
        LIMIT 10
    ");
    $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentPayments = [];
}
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
            <i class="fas fa-cog me-2"></i>
            Maintenance Fee Setup
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPeriodModal">
                    <i class="fas fa-plus me-1"></i>
                    Add Maintenance Period
                </button>
            </div>
        </div>
    </div>

    <!-- Current Month Status -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-primary mb-2">
                        <i class="fas fa-calendar fa-2x"></i>
                    </div>
                    <h4 class="text-primary"><?= date('F Y') ?></h4>
                    <p class="text-muted">Current Month</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-success mb-2">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <h4 class="text-success">
                        <?= array_reduce($periods, function($carry, $period) {
                            return $carry + ($period['paid_count'] ?? 0);
                        }, 0) ?>
                    </h4>
                    <p class="text-muted">Total Paid Bills</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-warning mb-2">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <h4 class="text-warning">
                        <?= array_reduce($periods, function($carry, $period) {
                            return $carry + ($period['payment_count'] - $period['paid_count']);
                        }, 0) ?>
                    </h4>
                    <p class="text-muted">Pending Payments</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Periods Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list-ul me-2"></i>
                Maintenance Periods
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Payments</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($periods)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted p-3">No maintenance periods found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($periods as $period): ?>
                                <?php
                                    $monthName = DateTime::createFromFormat('!m', $period['month'])->format('F');
                                    $dueDate = date('M d, Y', strtotime($period['due_date']));
                                    $isCurrent = ($period['month'] == date('n') && $period['year'] == date('Y'));
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= $monthName . ' ' . $period['year'] ?></strong>
                                        <?php if ($isCurrent): ?>
                                            <span class="badge bg-success ms-1">Current</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $dueDate ?></td>
                                    <td>₹<?= number_format($period['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($period['description'] ?? 'Maintenance Fee') ?></td>
                                    <td>
                                        <span class="badge bg-success"><?= $period['paid_count'] ?> Paid</span>
                                        <span class="badge bg-warning"><?= $period['payment_count'] - $period['paid_count'] ?> Pending</span>
                                    </td>
                                    <td><?= htmlspecialchars($period['created_by_name'] ?? 'Admin') ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($period['payment_count'] == 0): ?>
                                                <form method="POST" style="display:inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this maintenance period?');">
                                                    <input type="hidden" name="action" value="delete_period">
                                                    <input type="hidden" name="period_id" value="<?= $period['period_id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary" title="Cannot delete - has payments" disabled>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-receipt me-2"></i>
                Recent Payments
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Resident</th>
                            <th>Flat No</th>
                            <th>Period</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentPayments)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted p-3">No recent payments found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentPayments as $payment): ?>
                                <?php
                                    $paymentDate = date('M d, Y h:i A', strtotime($payment['payment_date']));
                                    $statusClass = $payment['status'] === 'paid' ? 'bg-success' : 
                                                 ($payment['status'] === 'pending' ? 'bg-warning' : 'bg-danger');
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($payment['resident_name']) ?></td>
                                    <td><?= htmlspecialchars($payment['flat_no'] ?? 'N/A') ?></td>
                                    <td><?= DateTime::createFromFormat('!m', $payment['month'])->format('F') . ' ' . $payment['year'] ?></td>
                                    <td>₹<?= number_format($payment['amount_paid'], 2) ?></td>
                                    <td><?= $paymentDate ?></td>
                                    <td><span class="badge <?= $statusClass ?>"><?= ucfirst($payment['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>

<!-- Add Maintenance Period Modal -->
<div class="modal fade" id="addPeriodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Maintenance Period</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_maintenance_period">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="month" class="form-label">Month *</label>
                            <select class="form-select" id="month" name="month" required>
                                <option value="">Select Month</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>
                                        <?= DateTime::createFromFormat('!m', $m)->format('F') ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="year" class="form-label">Year *</label>
                            <select class="form-select" id="year" name="year" required>
                                <option value="">Select Year</option>
                                <?php for ($y = date('Y'); $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="due_date" class="form-label">Due Date *</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required 
                                   min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount (₹) *</label>
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   step="0.01" min="0" required placeholder="0.00">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="2" placeholder="Optional description"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);

    // Set default due date to 10th of next month
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    nextMonth.setDate(10);
    const dueDateFormatted = nextMonth.toISOString().split('T')[0];
    $('#due_date').val(dueDateFormatted);
});
</script>

</body>
</html>
<?php ob_end_flush(); ?>