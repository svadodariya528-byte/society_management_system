<?php
session_start();
require_once "../../db_connect.php";

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$result = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = trim($_POST['password']);
    $hash_from_db = trim($_POST['hash_from_db']);
    
    if (empty($password) || empty($hash_from_db)) {
        $error = "Please enter both password and hash";
    } else {
        // Test different verification methods
        $results = [];
        
        // Method 1: password_verify (for bcrypt)
        $password_verify_result = password_verify($password, $hash_from_db);
        $results[] = [
            'method' => 'password_verify()',
            'result' => $password_verify_result ? '✅ MATCH' : '❌ NO MATCH',
            'details' => $password_verify_result ? 'Password matches the hash' : 'Password does not match using password_verify'
        ];
        
        // Method 2: SHA256 hash
        $sha256_hash = hash('sha256', $password);
        $sha256_result = ($sha256_hash === $hash_from_db);
        $results[] = [
            'method' => 'SHA256',
            'result' => $sha256_result ? '✅ MATCH' : '❌ NO MATCH',
            'details' => $sha256_result ? 
                "Password matches SHA256 hash: $sha256_hash" : 
                "SHA256 hash: $sha256_hash"
        ];
        
        // Method 3: MD5 (less common, but included for completeness)
        $md5_hash = md5($password);
        $md5_result = ($md5_hash === $hash_from_db);
        $results[] = [
            'method' => 'MD5',
            'result' => $md5_result ? '✅ MATCH' : '❌ NO MATCH',
            'details' => $md5_result ? 
                "Password matches MD5 hash: $md5_hash" : 
                "MD5 hash: $md5_hash"
        ];
        
        // Method 4: Direct comparison (for plain text)
        $direct_result = ($password === $hash_from_db);
        $results[] = [
            'method' => 'Direct Comparison',
            'result' => $direct_result ? '✅ MATCH' : '❌ NO MATCH',
            'details' => $direct_result ? 
                "Password matches directly (plain text)" : 
                "Direct comparison failed"
        ];
        
        $result = $results;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Verifier - Admin Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
            border-radius: 10px;
        }
        .result-card {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .match {
            color: #28a745;
            font-weight: bold;
        }
        .no-match {
            color: #dc3545;
        }
        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            font-size: 14px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-key me-2"></i>
                            Password Hash Verifier Tool
                        </h4>
                        <small>For debugging password authentication issues</small>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock me-2"></i>Test Password
                                        </label>
                                        <input type="text" class="form-control" id="password" name="password" 
                                               placeholder="Enter password to test" required>
                                        <div class="form-text">Enter the password you want to test</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="hash_from_db" class="form-label">
                                            <i class="fas fa-database me-2"></i>Hash from Database
                                        </label>
                                        <textarea class="form-control" id="hash_from_db" name="hash_from_db" 
                                                  rows="3" placeholder="Paste the hash from your database" required></textarea>
                                        <div class="form-text">Copy the password_hash from your users table</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-search me-2"></i>Verify Password
                                </button>
                            </div>
                        </form>

                        <?php if (!empty($result)): ?>
                            <div class="mt-4">
                                <h5 class="border-bottom pb-2">
                                    <i class="fas fa-clipboard-check me-2"></i>
                                    Verification Results
                                </h5>
                                
                                <?php foreach ($result as $test): ?>
                                    <div class="card result-card mb-3">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-md-3">
                                                    <strong><?= $test['method'] ?></strong>
                                                </div>
                                                <div class="col-md-2">
                                                    <span class="<?= strpos($test['result'], 'MATCH') !== false ? 'match' : 'no-match' ?>">
                                                        <?= $test['result'] ?>
                                                    </span>
                                                </div>
                                                <div class="col-md-7">
                                                    <small class="text-muted"><?= $test['details'] ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Test Section -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-vial me-2"></i>
                            Quick Test Common Passwords
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Test with these common passwords:</h6>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <?php 
                                    $common_passwords = ['password', '123456', 'admin123', 'test123', 'resident123', 'guard123'];
                                    foreach ($common_passwords as $pwd): 
                                    ?>
                                        <button type="button" class="btn btn-outline-primary btn-sm set-password" data-password="<?= $pwd ?>">
                                            <?= $pwd ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Get Hash from Database:</h6>
                                <pre class="small">SELECT user_id, name, email, password_hash FROM users WHERE email = 'user@example.com';</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hash Information -->
                <div class="card mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            About Password Hashes
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>password_hash() (Bcrypt) Examples:</h6>
                                <ul class="small">
                                    <li>Starts with <code>$2y$</code></li>
                                    <li>Example: <code>$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi</code></li>
                                    <li>Used by: <code>password_verify()</code></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>SHA256 Examples:</h6>
                                <ul class="small">
                                    <li>64 characters long</li>
                                    <li>Example: <code>ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f</code></li>
                                    <li>Hex characters only (0-9, a-f)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set common passwords on click
        document.querySelectorAll('.set-password').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('password').value = this.getAttribute('data-password');
            });
        });

        // Auto-focus on password field
        document.getElementById('password').focus();
    </script>
</body>
</html>