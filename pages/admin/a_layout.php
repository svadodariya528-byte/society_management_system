<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Greenwood Society</title>
    <meta name="description" content="Admin dashboard for Greenwood Society management system with overview of payments, residents, and society operations.">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/style.css">

    <style>
        /* Active page highlighting styles */
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff !important;
            border-left: 3px solid #0d6efd;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff !important;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            transition: all 0.3s ease;
        }
    </style>
</head>

<body class="admin-dashboard">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <!-- Society Logo/Name -->
                    <div class="text-center mb-4">
                        <h5 class="text-white">
                            <i class="fas fa-user-shield me-2"></i>
                            Admin Panel
                        </h5>
                        <small class="text-light opacity-75">Greenwood Society</small>
                    </div>

                    <!-- Navigation Menu -->
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user-management.php' ? 'active' : ''; ?>" href="user-management.php">
                                <i class="fas fa-users"></i>
                                User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'maintenance-setup.php' ? 'active' : ''; ?>" href="maintenance-setup.php">
                                <i class="fas fa-cog"></i>
                                Maintenance Setup
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payment-tracking.php' ? 'active' : ''; ?>" href="payment-tracking.php">
                                <i class="fas fa-credit-card"></i>
                                Payment Tracking
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'poll-management.php' ? 'active' : ''; ?>" href="poll-management.php">
                                <i class="fas fa-vote-yea"></i>
                                Poll Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'visitors-log.php' ? 'active' : ''; ?>" href="visitors-log.php">
                                <i class="fas fa-clipboard-list"></i>
                                Visitor Log
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'guard-management.php' ? 'active' : ''; ?>" href="guard-management.php">
                                <i class="fas fa-shield-alt me-2"></i>
                                Guard Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'site-management.html' ? 'active' : ''; ?>" href="site-management.html">
                                <i class="fas fa-globe"></i>
                                Site Management
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

            <!-- JavaScript for active page highlighting -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Get current page filename
                    const currentPage = window.location.pathname.split('/').pop();
                    
                    // Remove active class from all links
                    const navLinks = document.querySelectorAll('.nav-link');
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                    });
                    
                    // Add active class to current page link
                    navLinks.forEach(link => {
                        const linkHref = link.getAttribute('href');
                        if (linkHref === currentPage) {
                            link.classList.add('active');
                        }
                    });
                    
                    // Special case for dashboard (index page)
                    if (currentPage === '' || currentPage === 'index.php' || currentPage === 'index.html') {
                        const dashboardLink = document.querySelector('a[href="dashboard.php"]');
                        if (dashboardLink) {
                            dashboardLink.classList.add('active');
                        }
                    }
                });
            </script>