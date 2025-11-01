<?php
session_start();
require_once "db_connect.php"; // Database connection

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (empty($email) || empty($password) || empty($role)) {
        $error_message = "Please fill all fields.";
    } else {
        try {
            // Find user by email and role
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND role = :role AND status = 1 LIMIT 1");
            $stmt->execute(['email' => $email, 'role' => $role]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // ONLY use password_verify for bcrypt passwords
                if (password_verify($password, $user['password_hash'])) {
                    // Set session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];

                    // Redirect by role with proper paths
                    if ($role === 'admin') {
                        header("Location: pages/admin/dashboard.php");
                    } elseif ($role === 'guard') {
                        header("Location: pages/guard/dashboard.php");
                    } elseif ($role === 'resident') {
                        header("Location: pages/resident/dashboard.php");
                    }
                    exit;
                } else {
                    $error_message = "Invalid password!";
                }
            } else {
                $error_message = "No active account found with this email and role.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!-- Your HTML form remains exactly the same -->
<div class="login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <h3 class="mb-1">Greenwood Society</h3>
                        <p class="mb-0 opacity-75">Welcome to Greenwood Society</p>
                    </div>

                    <div class="login-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger py-2 text-center">
                                <?= htmlspecialchars($error_message) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                                <input type="email" class="form-control" id="username" name="username"
                                       placeholder="Enter your registered email" required
                                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password"
                                           placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">
                                    <i class="fas fa-user-tag me-2"></i>Select Role
                                </label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">-- Select Role --</option>
                                    <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                                    <option value="guard" <?= (isset($_POST['role']) && $_POST['role'] == 'guard') ? 'selected' : '' ?>>Guard</option>
                                    <option value="resident" <?= (isset($_POST['role']) && $_POST['role'] == 'resident') ? 'selected' : '' ?>>Resident</option>
                                </select>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                                <label class="form-check-label" for="rememberMe">Remember me on this device</label>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>

                            <div class="text-center">
                                <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                                    <i class="fas fa-question-circle me-1"></i>Forgot your password?
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Need Help?</h6>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="contact.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-phone me-1"></i>Contact Support
                                </a>
                                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-home me-1"></i>Back to Home
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Enter your email address to get reset instructions.</p>
                <form id="forgotPasswordForm">
                    <div class="mb-3">
                        <label for="resetEmail" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="resetEmail" name="resetEmail" required 
                               placeholder="Enter your registered email">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sendResetLink">
                    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.getElementById("togglePassword").addEventListener("click", function() {
        const passInput = document.getElementById("password");
        const icon = this.querySelector("i");
        if (passInput.type === "password") {
            passInput.type = "text";
            icon.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            passInput.type = "password";
            icon.classList.replace("fa-eye-slash", "fa-eye");
        }
    });
</script>
<?php
include "layout/layout.php";
?>