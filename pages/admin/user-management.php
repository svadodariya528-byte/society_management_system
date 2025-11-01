<?php
ob_start();
session_start();
include 'a_layout.php';
require_once "../../db_connect.php"; // must provide PDO instance in $conn

// Helper to send JSON responses (removing this since we're not using AJAX)
// function json_resp($arr) {
//     header('Content-Type: application/json; charset=utf-8');
//     echo json_encode($arr);
//     exit;
// }

// =============================
// Handle Form Submissions (Traditional POST)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'addResident') {
            // required fields from form
            $firstName  = trim($_POST['firstName'] ?? '');
            $lastName   = trim($_POST['lastName'] ?? '');
            $email      = trim($_POST['email'] ?? '');
            $mobile     = trim($_POST['mobile'] ?? '');
            $blockName  = trim($_POST['blockName'] ?? 'Block-A');
            $flatNumber = trim($_POST['flatNumber'] ?? '');
            $joining    = trim($_POST['joiningDate'] ?? '');
            $status     = trim($_POST['status'] ?? 'active');

            // basic validation
            if ($firstName === '' || $email === '' || $mobile === '' || $flatNumber === '') {
                $_SESSION['message'] = 'Please fill required fields.';
                $_SESSION['message_type'] = 'danger';
            } else {
                $fullName = $firstName . ($lastName ? ' ' . $lastName : '');
                $password_hash = password_hash('123456', PASSWORD_DEFAULT); // default password

                $conn->beginTransaction();

                // Insert into users (use password_hash column name)
                $stmt = $conn->prepare("INSERT INTO users (name, email, mobile, password_hash, role, status, created_at) VALUES (:name, :email, :mobile, :pwd, 'resident', :status, NOW())");
                $stmt->execute([
                    ':name'   => $fullName,
                    ':email'  => $email,
                    ':mobile' => $mobile,
                    ':pwd'    => $password_hash,
                    ':status' => $status === 'active' ? 1 : 0
                ]);
                $user_id = $conn->lastInsertId();

                // Insert into resident_details
                $stmt2 = $conn->prepare("INSERT INTO resident_details (resident_id, block_name, flat_no, move_in_date, additional_info) VALUES (:rid, :block, :flat, :move_in, :info)");
                $stmt2->execute([
                    ':rid'     => $user_id,
                    ':block'   => $blockName ?: 'Block-A',
                    ':flat'    => $flatNumber,
                    ':move_in' => $joining ?: date('Y-m-d'),
                    ':info'    => ''
                ]);

                $conn->commit();

                $_SESSION['message'] = 'Resident added successfully';
                $_SESSION['message_type'] = 'success';
            }
            
            header("Location: user-management.php");
            exit;
        }

        if ($action === 'editResident') {
            $resident_id = (int)($_POST['resident_id'] ?? 0);
            $firstName   = trim($_POST['firstName'] ?? '');
            $lastName    = trim($_POST['lastName'] ?? '');
            $email       = trim($_POST['email'] ?? '');
            $mobile      = trim($_POST['mobile'] ?? '');
            $blockName   = trim($_POST['blockName'] ?? '');
            $flatNumber  = trim($_POST['flatNumber'] ?? '');
            $joining     = trim($_POST['joiningDate'] ?? '');
            $status      = trim($_POST['status'] ?? 'active');

            if (!$resident_id || $firstName === '' || $email === '') {
                $_SESSION['message'] = 'Missing required data.';
                $_SESSION['message_type'] = 'danger';
            } else {
                $fullName = $firstName . ($lastName ? ' ' . $lastName : '');

                $conn->beginTransaction();

                $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email, mobile = :mobile, status = :status, updated_at = NOW() WHERE user_id = :uid");
                $stmt->execute([
                    ':name'   => $fullName,
                    ':email'  => $email,
                    ':mobile' => $mobile,
                    ':status' => $status === 'active' ? 1 : 0,
                    ':uid'    => $resident_id
                ]);

                // Update resident_details (if row exists, else insert)
                $stmtCheck = $conn->prepare("SELECT resident_id FROM resident_details WHERE resident_id = :rid LIMIT 1");
                $stmtCheck->execute([':rid' => $resident_id]);
                if ($stmtCheck->fetch()) {
                    $stmt2 = $conn->prepare("UPDATE resident_details SET block_name = :block, flat_no = :flat, move_in_date = :move WHERE resident_id = :rid");
                    $stmt2->execute([':block' => $blockName, ':flat' => $flatNumber, ':move' => ($joining ?: date('Y-m-d')), ':rid' => $resident_id]);
                } else {
                    $stmt2 = $conn->prepare("INSERT INTO resident_details (resident_id, block_name, flat_no, move_in_date, additional_info) VALUES (:rid, :block, :flat, :move, '')");
                    $stmt2->execute([':rid' => $resident_id, ':block' => $blockName, ':flat' => $flatNumber, ':move' => ($joining ?: date('Y-m-d'))]);
                }

                $conn->commit();
                $_SESSION['message'] = 'Resident updated successfully';
                $_SESSION['message_type'] = 'success';
            }
            
            header("Location: user-management.php");
            exit;
        }

        if ($action === 'deleteResident') {
            $resident_id = (int)($_POST['resident_id'] ?? 0);
            if (!$resident_id) {
                $_SESSION['message'] = 'Invalid id.';
                $_SESSION['message_type'] = 'danger';
            } else {
                $conn->beginTransaction();

                $conn->prepare("DELETE FROM resident_details WHERE resident_id = :rid")->execute([':rid' => $resident_id]);
                $conn->prepare("DELETE FROM users WHERE user_id = :rid")->execute([':rid' => $resident_id]);

                $conn->commit();
                $_SESSION['message'] = 'Resident deleted successfully';
                $_SESSION['message_type'] = 'success';
            }
            
            header("Location: user-management.php");
            exit;
        }

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['message'] = 'Error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header("Location: user-management.php");
        exit;
    }
}

// =============================
// Fetch residents for display
// =============================
try {
    $stmt = $conn->query("
        SELECT u.user_id, u.name, u.email, u.mobile, u.status,
               r.block_name, r.flat_no, r.move_in_date
        FROM users u
        LEFT JOIN resident_details r ON u.user_id = r.resident_id
        WHERE u.role = 'resident'
        ORDER BY u.name ASC
    ");
    $residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $residents = [];
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
        <h1 class="h2"><i class="fas fa-users me-2"></i>Residents Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addResidentModal">
                <i class="fas fa-plus me-1"></i> Add Resident
            </button>
        </div>
    </div>

    <!-- Total Residents Card -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="stat-icon text-primary mb-2"><i class="fas fa-users"></i></div>
                    <div class="stat-number text-primary"><?= count($residents) ?></div>
                    <div class="stat-label">Total Residents</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label for="searchResidents" class="form-label">Search Residents</label>
                    <input type="text" class="form-control table-search" id="searchResidents" placeholder="Search by name, flat, or phone...">
                </div>
                <div class="col-md-2">
                    <label for="filterStatus" class="form-label">Status</label>
                    <select class="form-select" id="filterStatus">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-secondary d-block w-100" id="clearFilters">
                        <i class="fas fa-times me-1"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Residents Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Residents Directory</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="residentsTable">
                    <thead class="sortable">
                        <tr>
                            <th>Name</th>
                            <th>Block</th>
                            <th>Flat Number</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($residents)): ?>
                            <tr><td colspan="8" class="text-center text-muted p-3">No residents found.</td></tr>
                        <?php else: foreach ($residents as $r): ?>
                            <?php
                                $statusText = ($r['status'] == 1 || $r['status'] === 'active') ? 'active' : 'inactive';
                                $moveIn = !empty($r['move_in_date']) ? date('d M Y', strtotime($r['move_in_date'])) : '-';
                                // Split name for edit form
                                $nameParts = explode(' ', $r['name'], 2);
                                $firstName = $nameParts[0] ?? '';
                                $lastName = $nameParts[1] ?? '';
                            ?>
                            <tr data-status="<?= htmlspecialchars($statusText) ?>" data-id="<?= (int)$r['user_id'] ?>">
                                <td><?= htmlspecialchars($r['name']) ?></td>
                                <td><?= htmlspecialchars($r['block_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($r['flat_no'] ?? '') ?></td>
                                <td><?= htmlspecialchars($r['mobile'] ?? '') ?></td>
                                <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
                                <td><span class="badge <?= $statusText === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= ucfirst($statusText) ?></span></td>
                                <td><?= $moveIn ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary viewBtn" title="View" 
                                            data-name="<?= htmlspecialchars($r['name']) ?>"
                                            data-block="<?= htmlspecialchars($r['block_name'] ?? '') ?>"
                                            data-flat="<?= htmlspecialchars($r['flat_no'] ?? '') ?>"
                                            data-mobile="<?= htmlspecialchars($r['mobile'] ?? '') ?>"
                                            data-email="<?= htmlspecialchars($r['email'] ?? '') ?>"
                                            data-movein="<?= htmlspecialchars($r['move_in_date'] ?? '') ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <button class="btn btn-outline-success" data-bs-toggle="modal" 
                                            data-bs-target="#editResidentModal<?= $r['user_id'] ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this resident?');">
                                            <input type="hidden" name="action" value="deleteResident">
                                            <input type="hidden" name="resident_id" value="<?= $r['user_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>

<!-- Add Resident Modal -->
<div class="modal fade" id="addResidentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Add Resident</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="addResident">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">First Name *</label>
                <input name="firstName" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Last Name</label>
                <input name="lastName" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email *</label>
                <input name="email" type="email" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Mobile *</label>
                <input name="mobile" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Block Name</label>
                <input name="blockName" class="form-control" value="Block-A">
            </div>
            <div class="col-md-6">
                <label class="form-label">Flat Number *</label>
                <input name="flatNumber" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Joining Date</label>
                <input name="joiningDate" type="date" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Resident</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Resident Modals - One for each resident -->
<?php foreach ($residents as $r): ?>
<?php
    $nameParts = explode(' ', $r['name'], 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';
    $statusText = ($r['status'] == 1 || $r['status'] === 'active') ? 'active' : 'inactive';
?>
<div class="modal fade" id="editResidentModal<?= $r['user_id'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Resident</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="editResident">
        <input type="hidden" name="resident_id" value="<?= $r['user_id'] ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">First Name *</label>
                <input name="firstName" class="form-control" value="<?= htmlspecialchars($firstName) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Last Name</label>
                <input name="lastName" class="form-control" value="<?= htmlspecialchars($lastName) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email *</label>
                <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($r['email']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Mobile *</label>
                <input name="mobile" class="form-control" value="<?= htmlspecialchars($r['mobile']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Block Name</label>
                <input name="blockName" class="form-control" value="<?= htmlspecialchars($r['block_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Flat Number *</label>
                <input name="flatNumber" class="form-control" value="<?= htmlspecialchars($r['flat_no'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Joining Date</label>
                <input name="joiningDate" type="date" class="form-control" value="<?= htmlspecialchars($r['move_in_date'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $statusText === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $statusText === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<!-- View Modal -->
<div class="modal fade" id="viewResidentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title">Resident Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Name</dt><dd class="col-sm-8" id="view_name"></dd>
          <dt class="col-sm-4">Block</dt><dd class="col-sm-8" id="view_block"></dd>
          <dt class="col-sm-4">Flat</dt><dd class="col-sm-8" id="view_flat"></dd>
          <dt class="col-sm-4">Mobile</dt><dd class="col-sm-8" id="view_mobile"></dd>
          <dt class="col-sm-4">Email</dt><dd class="col-sm-8" id="view_email"></dd>
          <dt class="col-sm-4">Move-in Date</dt><dd class="col-sm-8" id="view_movein"></dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);

    // View details
    $(document).on('click', '.viewBtn', function(){
        $('#view_name').text($(this).data('name') || '-');
        $('#view_block').text($(this).data('block') || '-');
        $('#view_flat').text($(this).data('flat') || '-');
        $('#view_mobile').text($(this).data('mobile') || '-');
        $('#view_email').text($(this).data('email') || '-');
        
        let moveInDate = $(this).data('movein');
        if (moveInDate) {
            let date = new Date(moveInDate);
            $('#view_movein').text(date.toLocaleDateString());
        } else {
            $('#view_movein').text('-');
        }
        
        new bootstrap.Modal(document.getElementById('viewResidentModal')).show();
    });

    // Search + Filter client-side
    $('#searchResidents, #filterStatus').on('input change', function(){
        const q = $('#searchResidents').val().toLowerCase();
        const status = $('#filterStatus').val();
        $('#residentsTable tbody tr').each(function(){
            const rowText = $(this).text().toLowerCase();
            const rowStatus = $(this).data('status') || '';
            const match = (rowText.indexOf(q) !== -1) && (!status || rowStatus === status);
            $(this).toggle(match);
        });
    });

    $('#clearFilters').click(function(){
        $('#searchResidents').val('');
        $('#filterStatus').val('');
        $('#residentsTable tbody tr').show();
    });
});
</script>

</body>
</html>
<?php ob_end_flush(); ?>