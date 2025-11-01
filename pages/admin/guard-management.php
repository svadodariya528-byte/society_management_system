<?php
ob_start();
include 'a_layout.php';
require_once '../../db_connect.php';

// Ensure $conn is PDO
if (!isset($conn) || !($conn instanceof PDO)) {
    die("Database connection error");
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add_guard') {
            $name = trim($_POST['guardName'] ?? '');
            $contact = trim($_POST['guardContact'] ?? '');
            $email = trim($_POST['guardEmail'] ?? '');
            $password_raw = trim($_POST['guardPassword'] ?? '');
            $shift = trim($_POST['guardShift'] ?? '');
            $address = trim($_POST['guardAddress'] ?? '');
            $joiningDate = trim($_POST['joiningDate'] ?? '');

            if ($name && $contact && $email && $shift && $joiningDate) {
                $password_hash = password_hash($password_raw ?: 'guard123', PASSWORD_DEFAULT);
                
                $conn->beginTransaction();
                
                // Insert into users
                $stmt = $conn->prepare("INSERT INTO users (name, email, mobile, password_hash, role, status, created_at, updated_at) 
                                        VALUES (?, ?, ?, ?, 'guard', 1, NOW(), NOW())");
                $stmt->execute([$name, $email, $contact, $password_hash]);
                $userId = $conn->lastInsertId();
                
                // Insert into guard_details
                $stmt2 = $conn->prepare("INSERT INTO guard_details (guard_id, shift, joining_date, remarks) VALUES (?, ?, ?, ?)");
                $stmt2->execute([$userId, $shift, $joiningDate, $address]);
                
                $conn->commit();
                $_SESSION['message'] = 'Guard added successfully';
                $_SESSION['message_type'] = 'success';
            }
        }

        if ($action === 'edit_guard') {
            $id = intval($_POST['guardId'] ?? 0);
            $name = trim($_POST['guardName'] ?? '');
            $contact = trim($_POST['guardContact'] ?? '');
            $email = trim($_POST['guardEmail'] ?? '');
            $password_raw = trim($_POST['guardPassword'] ?? '');
            $shift = trim($_POST['guardShift'] ?? '');
            $address = trim($_POST['guardAddress'] ?? '');
            $joiningDate = trim($_POST['joiningDate'] ?? '');

            if ($id > 0 && $name && $contact && $email && $shift && $joiningDate) {
                $conn->beginTransaction();
                
                // Update users table
                if ($password_raw) {
                    $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, mobile=?, password_hash=?, updated_at=NOW() WHERE user_id=?");
                    $stmt->execute([$name, $email, $contact, $password_hash, $id]);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, mobile=?, updated_at=NOW() WHERE user_id=?");
                    $stmt->execute([$name, $email, $contact, $id]);
                }
                
                // Update guard_details
                $stmt2 = $conn->prepare("SELECT COUNT(*) FROM guard_details WHERE guard_id = ?");
                $stmt2->execute([$id]);
                $exists = $stmt2->fetchColumn() > 0;
                
                if ($exists) {
                    $stmt3 = $conn->prepare("UPDATE guard_details SET shift=?, joining_date=?, remarks=? WHERE guard_id=?");
                    $stmt3->execute([$shift, $joiningDate, $address, $id]);
                } else {
                    $stmt3 = $conn->prepare("INSERT INTO guard_details (guard_id, shift, joining_date, remarks) VALUES (?, ?, ?, ?)");
                    $stmt3->execute([$id, $shift, $joiningDate, $address]);
                }
                
                $conn->commit();
                $_SESSION['message'] = 'Guard updated successfully';
                $_SESSION['message_type'] = 'success';
            }
        }

        if ($action === 'delete_guard') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $conn->beginTransaction();
                $conn->prepare("DELETE FROM guard_details WHERE guard_id = ?")->execute([$id]);
                $conn->prepare("DELETE FROM users WHERE user_id = ?")->execute([$id]);
                $conn->commit();
                $_SESSION['message'] = 'Guard deleted successfully';
                $_SESSION['message_type'] = 'success';
            }
        }
        
        // Redirect to avoid form resubmission
        header("Location: guard-management.php");
        exit;
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['message'] = 'Error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header("Location: guard-management.php");
        exit;
    }
}

// Fetch guards for display
try {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.name, u.mobile, u.email, u.status,
               gd.shift, gd.joining_date, gd.remarks
        FROM users u
        LEFT JOIN guard_details gd ON u.user_id = gd.guard_id
        WHERE u.role = 'guard'
        ORDER BY u.name ASC
    ");
    $stmt->execute();
    $guards = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $guards = [];
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
        <h1 class="h2"><i class="fas fa-user-shield me-2"></i>Guard Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGuardModal">
                <i class="fas fa-plus me-1"></i>Add Guard
            </button>
        </div>
    </div>

    <!-- Total Guards Card -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <div class="stat-icon text-primary mb-2">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-number text-primary"><?= count($guards) ?></div>
                    <div class="stat-label">Total Guards</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Guards Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Guard List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Shift</th>
                            <th>Joining Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($guards) > 0): ?>
                            <?php foreach ($guards as $index => $g): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($g['name']) ?></td>
                                    <td><?= htmlspecialchars($g['mobile']) ?></td>
                                    <td><?= htmlspecialchars($g['email']) ?></td>
                                    <td><?= htmlspecialchars($g['shift'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($g['joining_date'] ?? '') ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-warning" data-bs-toggle="modal" 
                                                data-bs-target="#editGuardModal<?= $g['user_id'] ?>" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this guard?');">
                                                <input type="hidden" name="action" value="delete_guard">
                                                <input type="hidden" name="id" value="<?= $g['user_id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No guards found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add Guard Modal -->
<div class="modal fade" id="addGuardModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-shield me-2"></i>Add Guard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_guard">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="guardName" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="guardName" name="guardName" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="guardContact" class="form-label">Contact Number *</label>
                            <input type="text" class="form-control" id="guardContact" name="guardContact" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="guardEmail" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="guardEmail" name="guardEmail" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="guardPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="guardPassword" name="guardPassword" placeholder="Leave blank for default password">
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="guardShift" class="form-label">Shift *</label>
                            <select class="form-select" id="guardShift" name="guardShift" required>
                                <option value="">Select Shift...</option>
                                <option value="Morning">Morning</option>
                                <option value="Evening">Evening</option>
                                <option value="Night">Night</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="joiningDate" class="form-label">Joining Date *</label>
                            <input type="date" class="form-control" id="joiningDate" name="joiningDate" required>
                        </div>
                        <div class="mb-3 col-12">
                            <label for="guardAddress" class="form-label">Address / Remarks</label>
                            <textarea class="form-control" id="guardAddress" name="guardAddress"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Guard</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Guard Modals - One for each guard -->
<?php foreach ($guards as $g): ?>
<div class="modal fade" id="editGuardModal<?= $g['user_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Guard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_guard">
                    <input type="hidden" name="guardId" value="<?= $g['user_id'] ?>">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="editGuardName<?= $g['user_id'] ?>" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="editGuardName<?= $g['user_id'] ?>" name="guardName" value="<?= htmlspecialchars($g['name']) ?>" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="editGuardContact<?= $g['user_id'] ?>" class="form-label">Contact Number *</label>
                            <input type="text" class="form-control" id="editGuardContact<?= $g['user_id'] ?>" name="guardContact" value="<?= htmlspecialchars($g['mobile']) ?>" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="editGuardEmail<?= $g['user_id'] ?>" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="editGuardEmail<?= $g['user_id'] ?>" name="guardEmail" value="<?= htmlspecialchars($g['email']) ?>" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="editGuardPassword<?= $g['user_id'] ?>" class="form-label">Password</label>
                            <input type="password" class="form-control" id="editGuardPassword<?= $g['user_id'] ?>" name="guardPassword" placeholder="Leave blank to keep current password">
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="editGuardShift<?= $g['user_id'] ?>" class="form-label">Shift *</label>
                            <select class="form-select" id="editGuardShift<?= $g['user_id'] ?>" name="guardShift" required>
                                <option value="Morning" <?= ($g['shift'] ?? '') == 'Morning' ? 'selected' : '' ?>>Morning</option>
                                <option value="Evening" <?= ($g['shift'] ?? '') == 'Evening' ? 'selected' : '' ?>>Evening</option>
                                <option value="Night" <?= ($g['shift'] ?? '') == 'Night' ? 'selected' : '' ?>>Night</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="editJoiningDate<?= $g['user_id'] ?>" class="form-label">Joining Date *</label>
                            <input type="date" class="form-control" id="editJoiningDate<?= $g['user_id'] ?>" name="joiningDate" value="<?= htmlspecialchars($g['joining_date'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3 col-12">
                            <label for="editGuardAddress<?= $g['user_id'] ?>" class="form-label">Address / Remarks</label>
                            <textarea class="form-control" id="editGuardAddress<?= $g['user_id'] ?>" name="guardAddress"><?= htmlspecialchars($g['remarks'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Guard</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

</body>
</html>
<?php ob_end_flush(); ?>