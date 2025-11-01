<?php
require_once "../../db_connect.php";

// Check if user is logged in and is a resident
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Fetch resident data for sidebar
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
    // If there's an error, set default values
    $profile_initial = 'U';
    $selected_color = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
    $resident = ['name' => 'User', 'flat_no' => 'N/A'];
}

// Get current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Dashboard - Greenwood Society</title>
    <meta name="description" content="Resident portal for Greenwood Society with payment status, notices, and community information.">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/style.css">
    
    <style>
    .sidebar-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: bold;
        color: white;
        margin: 0 auto 10px auto;
        border: 3px solid white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        background: <?php echo $selected_color; ?>;
    }
    
    .avatar-initial {
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    }
    
    .nav-link.active {
        background-color: rgba(255, 255, 255, 0.1);
        border-left: 4px solid #fff;
        font-weight: 600;
    }
    
    .nav-link:hover:not(.active) {
        background-color: rgba(255, 255, 255, 0.05);
    }
    </style>
</head>
<body class="resident-dashboard">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <!-- Resident Info -->
                    <div class="text-center mb-4">
                        <div class="sidebar-avatar">
                            <span class="avatar-initial"><?php echo $profile_initial; ?></span>
                        </div>
                        <h6 class="text-white"><?php echo htmlspecialchars($resident['name']); ?></h6>
                        <small class="text-light opacity-75">Flat <?php echo htmlspecialchars($resident['flat_no']); ?></small>
                    </div>

                    <!-- Navigation Menu -->
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'maintenance-pay.php') ? 'active' : ''; ?>" href="maintenance-pay.php">
                                <i class="fas fa-credit-card"></i>
                                Maintenance Payment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'r_poll.php') ? 'active' : ''; ?>" href="r_poll.php">
                                <i class="fas fa-vote-yea"></i>
                                Polls & Voting
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'r_profile.php') ? 'active' : ''; ?>" href="r_profile.php">
                                <i class="fas fa-user"></i>
                                Profile
                            </a>
                        </li>
                    </ul>

                    <!-- Logout -->
                    <div class="mt-5">
                        <a href="../../logout.php" class="nav-link text-danger">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </nav>
      
    
