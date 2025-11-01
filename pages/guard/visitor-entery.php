<?php
session_start();
require_once "../../db_connect.php";

// Check if user is logged in and is a guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guard') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$guard_name = $_SESSION['name'];

// Initialize variables
$success_message = '';
$error_message = '';
$today_visitors = [];
$available_flats = [];

// Fetch available flats for dropdown
try {
    $flats_stmt = $conn->prepare("
        SELECT rd.resident_id, rd.flat_no, rd.block_name, u.name as resident_name 
        FROM resident_details rd 
        JOIN users u ON rd.resident_id = u.user_id 
        WHERE u.status = 1 
        ORDER BY rd.block_name, rd.flat_no
    ");
    $flats_stmt->execute();
    $available_flats = $flats_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching flats: " . $e->getMessage();
}

// Handle form submission for adding new visitor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_visitor'])) {
    $visitor_name = trim($_POST['visitor_name'] ?? '');
    $visitor_mobile = trim($_POST['visitor_mobile'] ?? '');
    $visitor_purpose = trim($_POST['visitor_purpose'] ?? '');
    $selected_flat = trim($_POST['selected_flat'] ?? '');
    $check_in_time = trim($_POST['check_in_time'] ?? '');
    $check_out_time = trim($_POST['check_out_time'] ?? '');
    
    try {
        // Validate required fields
        if (empty($visitor_name) || empty($visitor_mobile) || empty($visitor_purpose) || empty($selected_flat) || empty($check_in_time)) {
            $error_message = "Please fill all required fields!";
        } else {
            // Extract resident user_id and flat_no from the selected value
            $flat_parts = explode('|', $selected_flat);
            if (count($flat_parts) === 2) {
                $resident_user_id = $flat_parts[0];
                $flat_no = $flat_parts[1];
                
                // Insert visitor into database
                $stmt = $conn->prepare("
                    INSERT INTO visitors (name, mobile, purpose, visiting_resident_user_id, entry_time, exit_time, recorded_by_guard_user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $visitor_name,
                    $visitor_mobile,
                    $visitor_purpose,
                    $resident_user_id,
                    $check_in_time,
                    $check_out_time ?: NULL,
                    $user_id
                ]);
                
                $success_message = "Visitor added successfully for Flat $flat_no!";
                
                // Refresh today's visitors to show the new entry
                $visitors_stmt = $conn->prepare("
                    SELECT 
                        v.visitor_id,
                        v.name as visitor_name,
                        v.mobile,
                        v.purpose,
                        v.entry_time,
                        v.exit_time,
                        v.recorded_by_guard_user_id,
                        rd.flat_no,
                        rd.block_name,
                        u.name as guard_name,
                        ru.name as resident_name
                    FROM visitors v 
                    LEFT JOIN users u ON v.recorded_by_guard_user_id = u.user_id 
                    LEFT JOIN users ru ON v.visiting_resident_user_id = ru.user_id
                    LEFT JOIN resident_details rd ON v.visiting_resident_user_id = rd.resident_id
                    WHERE DATE(v.entry_time) = CURDATE() 
                    ORDER BY v.entry_time DESC
                ");
                $visitors_stmt->execute();
                $today_visitors = $visitors_stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } else {
                $error_message = "Invalid flat selection!";
            }
        }
    } catch (PDOException $e) {
        $error_message = "Error adding visitor: " . $e->getMessage();
    }
}

// Handle check-out action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_out_visitor'])) {
    $visitor_id = intval($_POST['visitor_id'] ?? 0);
    $check_out_time = trim($_POST['check_out_time'] ?? '');
    
    try {
        $stmt = $conn->prepare("
            UPDATE visitors 
            SET exit_time = ? 
            WHERE visitor_id = ? AND exit_time IS NULL
        ");
        
        $stmt->execute([$check_out_time, $visitor_id]);
        
        if ($stmt->rowCount() > 0) {
            $success_message = "Visitor checked out successfully!";
            
            // Refresh today's visitors
            $visitors_stmt = $conn->prepare("
                SELECT 
                    v.visitor_id,
                    v.name as visitor_name,
                    v.mobile,
                    v.purpose,
                    v.entry_time,
                    v.exit_time,
                    v.recorded_by_guard_user_id,
                    rd.flat_no,
                    rd.block_name,
                    u.name as guard_name,
                    ru.name as resident_name
                FROM visitors v 
                LEFT JOIN users u ON v.recorded_by_guard_user_id = u.user_id 
                LEFT JOIN users ru ON v.visiting_resident_user_id = ru.user_id
                LEFT JOIN resident_details rd ON v.visiting_resident_user_id = rd.resident_id
                WHERE DATE(v.entry_time) = CURDATE() 
                ORDER BY v.entry_time DESC
            ");
            $visitors_stmt->execute();
            $today_visitors = $visitors_stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error_message = "Visitor not found or already checked out!";
        }
    } catch (PDOException $e) {
        $error_message = "Error checking out visitor: " . $e->getMessage();
    }
}

// Fetch today's visitors (if not already fetched above)
if (empty($today_visitors)) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                v.visitor_id,
                v.name as visitor_name,
                v.mobile,
                v.purpose,
                v.entry_time,
                v.exit_time,
                v.recorded_by_guard_user_id,
                rd.flat_no,
                rd.block_name,
                u.name as guard_name,
                ru.name as resident_name
            FROM visitors v 
            LEFT JOIN users u ON v.recorded_by_guard_user_id = u.user_id 
            LEFT JOIN users ru ON v.visiting_resident_user_id = ru.user_id
            LEFT JOIN resident_details rd ON v.visiting_resident_user_id = rd.resident_id
            WHERE DATE(v.entry_time) = CURDATE() 
            ORDER BY v.entry_time DESC
        ");
        $stmt->execute();
        $today_visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching visitors: " . $e->getMessage();
    }
}

include 'g_layout.php';
?>

<!-- Main Content -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">

    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-user-check me-2"></i>Visitors Entry</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVisitorModal">
                <i class="fas fa-plus me-1"></i> Add Visitor
            </button>
        </div>
    </div>

    <!-- Messages -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">
                                <?php 
                                    $checked_in = array_filter($today_visitors, function($v) { 
                                        return empty($v['exit_time']); 
                                    });
                                    echo count($checked_in);
                                ?>
                            </h4>
                            <small>Currently Checked-in</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">
                                <?php 
                                    $checked_out = array_filter($today_visitors, function($v) { 
                                        return !empty($v['exit_time']); 
                                    });
                                    echo count($checked_out);
                                ?>
                            </h4>
                            <small>Checked-out Today</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?php echo count($today_visitors); ?></h4>
                            <small>Total Today</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?php echo count($available_flats); ?></h4>
                            <small>Total Flats</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-building fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Visitors Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Today's Visitors</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($today_visitors)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No Visitors Today</h5>
                            <p class="text-muted">No visitors have been registered for today yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="visitorsTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Mobile</th>
                                        <th>Purpose</th>
                                        <th>Flat/Apartment</th>
                                        <th>Resident</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($today_visitors as $visitor): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($visitor['visitor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($visitor['mobile']); ?></td>
                                            <td><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                                            <td>
                                                <?php 
                                                    $flat_display = $visitor['flat_no'] ?? 'N/A';
                                                    if ($visitor['block_name']) {
                                                        $flat_display .= ' (' . $visitor['block_name'] . ')';
                                                    }
                                                    echo htmlspecialchars($flat_display);
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($visitor['resident_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('h:i A', strtotime($visitor['entry_time'])); ?></td>
                                            <td>
                                                <?php if ($visitor['exit_time']): ?>
                                                    <?php echo date('h:i A', strtotime($visitor['exit_time'])); ?>
                                                <?php else: ?>
                                                    --
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (empty($visitor['exit_time'])): ?>
                                                    <span class="badge bg-warning">Checked-in</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Checked-out</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (empty($visitor['exit_time'])): ?>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#checkOutModal"
                                                            data-visitor-id="<?php echo $visitor['visitor_id']; ?>"
                                                            data-visitor-name="<?php echo htmlspecialchars($visitor['visitor_name']); ?>">
                                                        <i class="fas fa-sign-out-alt"></i> Check Out
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">Completed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</main>

<!-- Add Visitor Modal -->
<div class="modal fade" id="addVisitorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" id="visitorForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Visitor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="visitorName" class="form-label">Visitor Name *</label>
                            <input type="text" class="form-control" id="visitorName" name="visitor_name" 
                                   value="<?php echo htmlspecialchars($_POST['visitor_name'] ?? ''); ?>" 
                                   data-validate="required|minlength:2">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="visitorMobile" class="form-label">Mobile Number *</label>
                            <input type="text" class="form-control" id="visitorMobile" name="visitor_mobile" 
                                   value="<?php echo htmlspecialchars($_POST['visitor_mobile'] ?? ''); ?>" 
                                   data-validate="required|number|minlength:10|maxlength:10">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="visitorPurpose" class="form-label">Purpose *</label>
                            <select class="form-select" id="visitorPurpose" name="visitor_purpose" data-validate="required">
                                <option value="">Select Purpose</option>
                                <option value="Delivery" <?php echo ($_POST['visitor_purpose'] ?? '') == 'Delivery' ? 'selected' : ''; ?>>Delivery</option>
                                <option value="Guest" <?php echo ($_POST['visitor_purpose'] ?? '') == 'Guest' ? 'selected' : ''; ?>>Guest</option>
                                <option value="Maintenance" <?php echo ($_POST['visitor_purpose'] ?? '') == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="Service" <?php echo ($_POST['visitor_purpose'] ?? '') == 'Service' ? 'selected' : ''; ?>>Service</option>
                                <option value="Other" <?php echo ($_POST['visitor_purpose'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="selectedFlat" class="form-label">Select Flat/Apartment *</label>
                            <select class="form-select" id="selectedFlat" name="selected_flat" data-validate="required">
                                <option value="">Select Flat</option>
                                <?php foreach ($available_flats as $flat): ?>
                                    <option value="<?php echo $flat['resident_id'] . '|' . $flat['flat_no']; ?>" 
                                        <?php echo ($_POST['selected_flat'] ?? '') == ($flat['resident_id'] . '|' . $flat['flat_no']) ? 'selected' : ''; ?>>
                                        <?php 
                                            $display = $flat['flat_no'];
                                            if ($flat['block_name']) {
                                                $display .= ' - ' . $flat['block_name'];
                                            }
                                            if ($flat['resident_name']) {
                                                $display .= ' (' . $flat['resident_name'] . ')';
                                            }
                                            echo htmlspecialchars($display);
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="checkInTime" class="form-label">Check-in Time *</label>
                            <input type="datetime-local" class="form-control" id="checkInTime" name="check_in_time" 
                                   value="<?php echo htmlspecialchars($_POST['check_in_time'] ?? date('Y-m-d\TH:i')); ?>" 
                                   data-validate="required">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="checkOutTime" class="form-label">Check-out Time</label>
                            <input type="datetime-local" class="form-control" id="checkOutTime" name="check_out_time" 
                                   value="<?php echo htmlspecialchars($_POST['check_out_time'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_visitor" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Visitor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Check Out Modal -->
<div class="modal fade" id="checkOutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" id="checkOutForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-sign-out-alt me-2"></i>Check Out Visitor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="checkOutVisitorId" name="visitor_id">
                    <div class="mb-3">
                        <label class="form-label">Visitor Name</label>
                        <input type="text" class="form-control bg-light" id="checkOutVisitorName" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="checkOutTimeModal" class="form-label">Check-out Time *</label>
                        <input type="datetime-local" class="form-control" id="checkOutTimeModal" name="check_out_time" 
                               value="<?php echo date('Y-m-d\TH:i'); ?>" data-validate="required">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="check_out_visitor" class="btn btn-primary">
                        <i class="fas fa-sign-out-alt me-2"></i>Check Out
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Include your validation.js file -->
<script src="../../js/validateform.js"></script>

<script>
$(document).ready(function(){
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Check Out Modal functionality
    $('#checkOutModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var visitorId = button.data('visitor-id');
        var visitorName = button.data('visitor-name');
        
        var modal = $(this);
        modal.find('#checkOutVisitorId').val(visitorId);
        modal.find('#checkOutVisitorName').val(visitorName);
    });

    // Mobile number validation - only allow numbers
    $('#visitorMobile').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Set current datetime as default for check-in
    if (!$('#checkInTime').val()) {
        var now = new Date();
        var year = now.getFullYear();
        var month = (now.getMonth() + 1).toString().padStart(2, '0');
        var day = now.getDate().toString().padStart(2, '0');
        var hours = now.getHours().toString().padStart(2, '0');
        var minutes = now.getMinutes().toString().padStart(2, '0');
        $('#checkInTime').val(year + '-' + month + '-' + day + 'T' + hours + ':' + minutes);
    }

    // Auto-refresh page after successful form submission to show new data
    <?php if (!empty($success_message)): ?>
        setTimeout(function() {
            window.location.href = window.location.href.split('?')[0];
        }, 2000);
    <?php endif; ?>
});
</script>
</body>
</html>