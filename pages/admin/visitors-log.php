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
        if ($action === 'add_visitor') {
            $name = trim($_POST['visitorName'] ?? '');
            $contact = trim($_POST['visitorContact'] ?? '');
            $purpose = trim($_POST['visitorPurpose'] ?? '');
            $flat = trim($_POST['visitorFlat'] ?? '');
            $entryTime = trim($_POST['entryTime'] ?? '');
            $exitTime = trim($_POST['exitTime'] ?? '');

            if ($name && $contact && $purpose && $flat && $entryTime) {
                // Get resident user_id from flat number
                $stmt = $conn->prepare("SELECT resident_id FROM resident_details WHERE flat_no = ?");
                $stmt->execute([$flat]);
                $resident = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($resident) {
                    $residentUserId = $resident['resident_id'];
                    
                    // Insert into visitors table
                    $stmt = $conn->prepare("INSERT INTO visitors (name, mobile, purpose, visiting_resident_user_id, entry_time, exit_time, recorded_by_guard_user_id) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $name, 
                        $contact, 
                        $purpose, 
                        $residentUserId, 
                        $entryTime, 
                        $exitTime ?: null,
                        1 // Default guard user_id (you can set this from session if you have guard login)
                    ]);
                    
                    $_SESSION['message'] = 'Visitor added successfully';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error: No resident found for the specified flat';
                    $_SESSION['message_type'] = 'danger';
                }
            } else {
                $_SESSION['message'] = 'Please fill all required fields';
                $_SESSION['message_type'] = 'danger';
            }
            
            header("Location: visitors-log.php");
            exit;
        }

        if ($action === 'mark_exit') {
            $visitorId = (int)($_POST['visitor_id'] ?? 0);
            if ($visitorId > 0) {
                $exitTime = date('Y-m-d H:i:s'); // Current timestamp
                
                $stmt = $conn->prepare("UPDATE visitors SET exit_time = ? WHERE visitor_id = ?");
                $stmt->execute([$exitTime, $visitorId]);
                
                $_SESSION['message'] = 'Exit time recorded successfully';
                $_SESSION['message_type'] = 'success';
            }
            
            header("Location: visitors-log.php");
            exit;
        }

        if ($action === 'delete_visitor') {
            $visitorId = (int)($_POST['visitor_id'] ?? 0);
            if ($visitorId > 0) {
                $stmt = $conn->prepare("DELETE FROM visitors WHERE visitor_id = ?");
                $stmt->execute([$visitorId]);
                
                $_SESSION['message'] = 'Visitor deleted successfully';
                $_SESSION['message_type'] = 'success';
            }
            
            header("Location: visitors-log.php");
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['message'] = 'Error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header("Location: visitors-log.php");
        exit;
    }
}

// Fetch visitors for display
try {
    $stmt = $conn->query("
        SELECT v.visitor_id, v.name, v.mobile, v.purpose, v.entry_time, v.exit_time,
               r.flat_no, u.name as resident_name
        FROM visitors v
        LEFT JOIN resident_details r ON v.visiting_resident_user_id = r.resident_id
        LEFT JOIN users u ON r.resident_id = u.user_id
        ORDER BY v.entry_time DESC
    ");
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $visitors = [];
}

// Fetch available flats for dropdown
try {
    $stmt = $conn->query("SELECT DISTINCT flat_no FROM resident_details ORDER BY flat_no");
    $flats = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $flats = [];
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

    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-users me-2"></i>Visitors Log</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVisitorModal">
                    <i class="fas fa-plus me-1"></i>Add Visitor
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <form id="visitorFilterForm" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="filterName" placeholder="Visitor Name">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="filterFlat">
                        <option value="">All Flats</option>
                        <?php foreach ($flats as $flat): ?>
                            <option value="<?= htmlspecialchars($flat) ?>"><?= htmlspecialchars($flat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" id="filterPurpose" placeholder="Purpose">
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" id="filterDate">
                </div>
            </form>
        </div>
    </div>

    <!-- Visitors Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Visitor Entries</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="visitorsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Visitor Name</th>
                            <th>Contact</th>
                            <th>Purpose</th>
                            <th>Flat/Resident</th>
                            <th>Entry Time</th>
                            <th>Exit Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visitors)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted p-3">No visitor records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($visitors as $index => $v): ?>
                                <?php
                                    $entryTime = !empty($v['entry_time']) ? date('d M Y h:i A', strtotime($v['entry_time'])) : '-';
                                    $exitTime = !empty($v['exit_time']) ? date('d M Y h:i A', strtotime($v['exit_time'])) : '-';
                                    $hasExited = !empty($v['exit_time']);
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($v['name']) ?></td>
                                    <td><?= htmlspecialchars($v['mobile']) ?></td>
                                    <td><?= htmlspecialchars($v['purpose']) ?></td>
                                    <td><?= htmlspecialchars($v['flat_no'] ?? 'N/A') ?></td>
                                    <td><?= $entryTime ?></td>
                                    <td><?= $exitTime ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (!$hasExited): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="mark_exit">
                                                    <input type="hidden" name="visitor_id" value="<?= $v['visitor_id'] ?>">
                                                    <button type="submit" class="btn btn-outline-success" title="Mark Exit" 
                                                            onclick="return confirm('Mark exit for <?= htmlspecialchars($v['name']) ?>?')">
                                                        <i class="fas fa-sign-out-alt"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary" title="Already Exited" disabled>
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display:inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this visitor record?');">
                                                <input type="hidden" name="action" value="delete_visitor">
                                                <input type="hidden" name="visitor_id" value="<?= $v['visitor_id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
</main>

<!-- Add Visitor Modal -->
<div class="modal fade" id="addVisitorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Visitor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_visitor">
                    <div class="mb-3">
                        <label for="visitorName" class="form-label">Visitor Name *</label>
                        <input type="text" class="form-control" id="visitorName" name="visitorName" required>
                    </div>
                    <div class="mb-3">
                        <label for="visitorContact" class="form-label">Contact Number *</label>
                        <input type="text" class="form-control" id="visitorContact" name="visitorContact" required>
                    </div>
                    <div class="mb-3">
                        <label for="visitorPurpose" class="form-label">Purpose *</label>
                        <input type="text" class="form-control" id="visitorPurpose" name="visitorPurpose" required>
                    </div>
                    <div class="mb-3">
                        <label for="visitorFlat" class="form-label">Flat / Resident *</label>
                        <select class="form-select" id="visitorFlat" name="visitorFlat" required>
                            <option value="">Select Flat...</option>
                            <?php foreach ($flats as $flat): ?>
                                <option value="<?= htmlspecialchars($flat) ?>"><?= htmlspecialchars($flat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="entryTime" class="form-label">Entry Time *</label>
                        <input type="datetime-local" class="form-control" id="entryTime" name="entryTime" 
                               value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="exitTime" class="form-label">Exit Time</label>
                        <input type="datetime-local" class="form-control" id="exitTime" name="exitTime">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Visitor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$(document).ready(function(){
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);

    // Filter functionality
    $('#visitorFilterForm input, #visitorFilterForm select').on('input change', function(){
        let name = $('#filterName').val().toLowerCase();
        let flat = $('#filterFlat').val();
        let purpose = $('#filterPurpose').val().toLowerCase();
        let date = $('#filterDate').val();

        $('#visitorsTable tbody tr').each(function(){
            let rowName = $(this).find('td').eq(1).text().toLowerCase();
            let rowFlat = $(this).find('td').eq(4).text();
            let rowPurpose = $(this).find('td').eq(3).text().toLowerCase();
            let rowDate = $(this).find('td').eq(5).text().split(' ')[0]; // extract date part

            let show = true;
            if(name && !rowName.includes(name)) show = false;
            if(flat && rowFlat !== flat) show = false;
            if(purpose && !rowPurpose.includes(purpose)) show = false;
            if(date) {
                // Convert filter date to same format as displayed date (dd MMM yyyy)
                let filterDate = new Date(date);
                let formattedFilterDate = filterDate.toLocaleDateString('en-GB', { 
                    day: '2-digit', 
                    month: 'short', 
                    year: 'numeric'
                });
                if(rowDate !== formattedFilterDate) show = false;
            }

            $(this).toggle(show);
        });
    });

    // Set current datetime as default for entry time
    function setCurrentDateTime() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    
    $('#entryTime').val(setCurrentDateTime());
});
</script>

</body>
</html>
<?php ob_end_flush(); ?>