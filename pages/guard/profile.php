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

// Fetch guard data from database
try {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.name, u.email, u.mobile, u.role, u.status, u.created_at, u.updated_at,
               gd.shift, gd.joining_date, gd.remarks
        FROM users u 
        LEFT JOIN guard_details gd ON u.user_id = gd.guard_id 
        WHERE u.user_id = ? AND u.role = 'guard'
    ");
    $stmt->execute([$user_id]);
    $guard = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$guard) {
        $error_message = "Guard data not found!";
    }

    // Generate profile initial and color
    $profile_initial = strtoupper(substr($guard['name'] ?? 'G', 0, 1));
    $color_variants = [
        'primary' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'success' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'warning' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'info' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'danger' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
        'secondary' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
    ];

    // Select color based on first letter for consistency
    $color_keys = array_keys($color_variants);
    $color_index = ord($profile_initial) % count($color_keys);
    $selected_color = $color_variants[$color_keys[$color_index]];
} catch (PDOException $e) {
    $error_message = "Error fetching profile data: " . $e->getMessage();
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');

    try {
        // Update users table
        $update_user_stmt = $conn->prepare("
            UPDATE users 
            SET name = ?, mobile = ?, updated_at = NOW() 
            WHERE user_id = ?
        ");
        $update_user_stmt->execute([$name, $mobile, $user_id]);

        // Update session data
        $_SESSION['name'] = $name;

        $success_message = "Profile updated successfully!";

        // Refresh guard data
        $stmt->execute([$user_id]);
        $guard = $stmt->fetch(PDO::FETCH_ASSOC);

        // Regenerate profile initial and color
        $profile_initial = strtoupper(substr($guard['name'] ?? 'G', 0, 1));
        $color_index = ord($profile_initial) % count($color_keys);
        $selected_color = $color_variants[$color_keys[$color_index]];
    } catch (PDOException $e) {
        $error_message = "Error updating profile: " . $e->getMessage();
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        // Verify current password
        $check_stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $check_stmt->execute([$user_id]);
        $user = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['password_hash'])) {
            $error_message = "Current password is incorrect!";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New password and confirm password do not match!";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters long!";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password_stmt = $conn->prepare("
                UPDATE users 
                SET password_hash = ?, updated_at = NOW() 
                WHERE user_id = ?
            ");
            $update_password_stmt->execute([$hashed_password, $user_id]);

            $success_message = "Password changed successfully!";
        }
    } catch (PDOException $e) {
        $error_message = "Error changing password: " . $e->getMessage();
    }
}

include 'g_layout.php';
?>

<style>
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: bold;
        color: white;
        margin: 0 auto 20px auto;
        border: 4px solid white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        background: <?php echo $selected_color; ?>;
        transition: all 0.3s ease;
    }

    .profile-avatar:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .avatar-initial {
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }
</style>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
    <div class="pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-user-shield me-2"></i>Guard Profile</h1>
        <div class="btn-toolbar mb-2 mb-md-0 justify-content-end">
            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                <i class="fas fa-edit me-1"></i>Edit Profile
            </button>
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                <i class="fas fa-key me-1"></i>Change Password
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

    <div class="row">
        <!-- Profile Card -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <!-- Dynamic Profile Avatar -->
                    <div class="profile-avatar">
                        <span class="avatar-initial"><?php echo $profile_initial; ?></span>
                    </div>
                    <h4 class="card-title mb-1"><?php echo htmlspecialchars($guard['name'] ?? 'N/A'); ?></h4>
                    <p class="text-muted mb-2">Security Guard</p>
                    <p class="text-muted mb-2"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($guard['email'] ?? 'N/A'); ?></p>
                    <p class="text-muted"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($guard['mobile'] ?? 'N/A'); ?></p>

                    <!-- Shift Badge -->
                    <?php if (!empty($guard['shift'])): ?>
                        <div class="mt-3">
                            <span class="badge bg-primary"><?php echo ucfirst($guard['shift']); ?> Shift</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Emergency Contact</h6>
                </div>
                <div class="card-body">
                    <p><strong>Supervisor:</strong> Admin Office</p>
                    <p><strong>Phone:</strong> +91 98765 43210</p>
                    <p><strong>Email:</strong> admin@greenwoodsociety.com</p>
                </div>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Profile Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">Full Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($guard['name'] ?? 'N/A'); ?>" readonly>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">Employee ID</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control bg-light" value="G-<?php echo str_pad($guard['user_id'] ?? '001', 3, '0', STR_PAD_LEFT); ?>" readonly>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">Email</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($guard['email'] ?? 'N/A'); ?>" readonly>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">Mobile</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($guard['mobile'] ?? 'N/A'); ?>" readonly>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">Role</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars(ucfirst($guard['role'] ?? 'N/A')); ?>" readonly>
                        </div>
                    </div>
                    <?php if (!empty($guard['shift'])): ?>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">Shift</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars(ucfirst($guard['shift'])); ?>" readonly>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">Status</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control bg-light" value="<?php echo ($guard['status'] == 1) ? 'Active' : 'Inactive'; ?>" readonly>
                        </div>
                    </div>
                    <?php if (!empty($guard['joining_date'])): ?>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">Joining Date</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control bg-light" value="<?php echo date('d M Y', strtotime($guard['joining_date'])); ?>" readonly>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">Account Created</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control bg-light" value="<?php echo !empty($guard['created_at']) ? date('d M Y', strtotime($guard['created_at'])) : 'N/A'; ?>" readonly>
                        </div>
                    </div>
                    <?php if (!empty($guard['updated_at'])): ?>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">Last Updated</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control bg-light" value="<?php echo date('d M Y, h:i A', strtotime($guard['updated_at'])); ?>" readonly>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Profile
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="name"
                            value="<?php echo htmlspecialchars($guard['name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control bg-light"
                            value="<?php echo htmlspecialchars($guard['email'] ?? ''); ?>" readonly>
                        <small class="text-muted">Email cannot be changed</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mobile Number *</label>
                        <input type="tel" class="form-control" name="mobile"
                            value="<?php echo htmlspecialchars($guard['mobile'] ?? ''); ?>" required>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Fields marked with * are required. Email cannot be changed for security reasons.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">
                        <i class="fas fa-key me-2"></i>Change Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <small class="text-muted">Password must be at least 6 characters long</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password *</label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    $(document).ready(function() {
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Show modals if there are errors
        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile']) && !empty($error_message)): ?>
            $('#editProfileModal').modal('show');
        <?php endif; ?>

        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password']) && !empty($error_message)): ?>
            $('#changePasswordModal').modal('show');
        <?php endif; ?>

        // Password confirmation validation
        $('form').on('submit', function() {
            if ($(this).find('input[name="new_password"]').length) {
                const newPassword = $('input[name="new_password"]').val();
                const confirmPassword = $('input[name="confirm_password"]').val();

                if (newPassword !== confirmPassword) {
                    alert('New password and confirm password do not match!');
                    return false;
                }
            }
            return true;
        });
    });
</script>
</body>

</html>

<?php
// Function to calculate shift duration (for demo purposes)
function calculateShiftDuration()
{
    $start_time = strtotime('6:00 AM');
    $current_time = time();
    $duration = $current_time - $start_time;

    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);

    return $hours . ' hours ' . $minutes . ' mins';
}
?>