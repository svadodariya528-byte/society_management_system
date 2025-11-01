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
$success_message = '';
$error_message = '';

// Fetch resident data
try {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.name, u.email, u.mobile, u.role, u.status, u.created_at, u.updated_at,
               rd.block_name, rd.flat_no, rd.move_in_date, rd.additional_info
        FROM users u 
        LEFT JOIN resident_details rd ON u.user_id = rd.resident_id 
        WHERE u.user_id = ? AND u.role = 'resident'
    ");
    $stmt->execute([$user_id]);
    $resident = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resident) {
        $error_message = "Resident data not found!";
    }
    
    // Parse additional_info
    $additional_info = [];
    if (!empty($resident['additional_info'])) {
        $additional_info = json_decode($resident['additional_info'], true) ?? [];
    }
    
    // Generate profile initial and color
    $profile_initial = strtoupper(substr($resident['name'] ?? 'U', 0, 1));
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
    $name = trim($_POST['name']);
    $mobile = trim($_POST['mobile']);
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    
    try {
        // Update users table (without email)
        $update_user_stmt = $conn->prepare("
            UPDATE users 
            SET name = ?, mobile = ?, updated_at = NOW() 
            WHERE user_id = ?
        ");
        $update_user_stmt->execute([$name, $mobile, $user_id]);
        
        // Prepare additional_info data
        $additional_info_data = [
            'emergency_contact' => $emergency_contact
        ];
        $additional_info_json = json_encode($additional_info_data);
        
        // Update resident_details
        $check_rd_stmt = $conn->prepare("SELECT resident_id FROM resident_details WHERE resident_id = ?");
        $check_rd_stmt->execute([$user_id]);
        
        if ($check_rd_stmt->fetch()) {
            $update_rd_stmt = $conn->prepare("
                UPDATE resident_details 
                SET additional_info = ? 
                WHERE resident_id = ?
            ");
            $update_rd_stmt->execute([$additional_info_json, $user_id]);
        } else {
            $insert_rd_stmt = $conn->prepare("
                INSERT INTO resident_details (resident_id, additional_info) 
                VALUES (?, ?)
            ");
            $insert_rd_stmt->execute([$user_id, $additional_info_json]);
        }
        
        // Update session and refresh data
        $_SESSION['name'] = $name;
        $success_message = "Profile updated successfully!";
        
        $stmt->execute([$user_id]);
        $resident = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($resident['additional_info'])) {
            $additional_info = json_decode($resident['additional_info'], true) ?? [];
        }
        
    } catch (PDOException $e) {
        $error_message = "Error updating profile: " . $e->getMessage();
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
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

include 'r_layout.php';
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
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    background: <?php echo $selected_color; ?>;
    transition: all 0.3s ease;
}

.profile-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.avatar-initial {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}
</style>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
        <h1 class="h2"><i class="fas fa-user me-2"></i>My Profile</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
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
        <!-- Profile Sidebar -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <!-- Dynamic Profile Avatar with Initial -->
                    <div class="profile-avatar">
                        <span class="avatar-initial"><?php echo $profile_initial; ?></span>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($resident['name'] ?? 'N/A'); ?></h5>
                    <p class="text-muted mb-1">
                        Flat: <?php echo htmlspecialchars($resident['flat_no'] ?? 'N/A'); ?> 
                    </p>
                    <p class="text-muted">
                        Block: <?php echo htmlspecialchars($resident['block_name'] ?? 'N/A'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Personal Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($resident['name'] ?? 'N/A'); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($resident['email'] ?? 'N/A'); ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($resident['mobile'] ?? 'N/A'); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars(ucfirst($resident['role'] ?? 'N/A')); ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Flat Number</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($resident['flat_no'] ?? 'N/A'); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Block Name</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($resident['block_name'] ?? 'N/A'); ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Emergency Contact</label>
                            <input type="text" class="form-control bg-light" 
                                   value="<?php echo htmlspecialchars($additional_info['emergency_contact'] ?? 'Not set'); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Status</label>
                            <input type="text" class="form-control bg-light" 
                                   value="<?php echo ($resident['status'] == 1) ? 'Active' : 'Inactive'; ?>" readonly>
                        </div>
                    </div>
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
                               value="<?php echo htmlspecialchars($resident['name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control bg-light" 
                               value="<?php echo htmlspecialchars($resident['email'] ?? ''); ?>" readonly>
                        <small class="text-muted">Email cannot be changed</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mobile Number *</label>
                        <input type="tel" class="form-control" name="mobile" 
                               value="<?php echo htmlspecialchars($resident['mobile'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Emergency Contact *</label>
                        <input type="tel" class="form-control" name="emergency_contact" 
                               value="<?php echo htmlspecialchars($additional_info['emergency_contact'] ?? ''); ?>" required>
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

<!-- Bootstrap & jQuery -->
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