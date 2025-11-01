<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Greenwood Society - Premium Residential Community</title>
    <meta name="description" content="Experience luxury living at Greenwood Society - A premium residential community with modern amenities, 24/7 security, and professional management.">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-building me-2"></i>
                Greenwood Society
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gallery.php">Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<?php
        if (isset($content)) {
            echo "$content";    
        }
?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Greenwood Society</h5>
                    <p class="text-muted">A premium residential community committed to providing the best living experience with modern amenities and professional management.</p>
                    <div class="social-links">
                        <a href="#" class="me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Services</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Maintenance</a></li>
                        <li><a href="#">Security</a></li>
                        <li><a href="#">Visitor Management</a></li>
                        <li><a href="#">Community Events</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i> +91 98765 43210</li>
                        <li><i class="fas fa-envelope me-2"></i> info@greenwood.com</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Sector 45, Gurgaon</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Greenwood Society. All rights reserved. | Designed for premium living experience.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button class="btn btn-primary back-to-top" style="display: none; position: fixed; bottom: 20px; right: 20px; z-index: 999; border-radius: 50%; width: 50px; height: 50px;">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Custom JS -->
    <script src="js/script.js"></script>

    <!-- Login specific JavaScript -->
    <script>
        $(document).ready(function() {
            // Password toggle functionality
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                
                const icon = $(this).find('i');
                icon.toggleClass('fa-eye fa-eye-slash');
            });

            // Login form submission
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const role = $('#selectedRole').val();
                const username = $('#username').val();
                const password = $('#password').val();
                
                // Demo login validation
                if (validateDemoCredentials(username, password, role)) {
                    // Redirect based on role
                    let redirectUrl = '';
                    switch(role) {
                        case 'admin':
                            redirectUrl = 'admin/dashboard.php';
                            break;
                        case 'resident':
                            redirectUrl = 'resident/dashboard.php';
                            break;
                        case 'guard':
                            redirectUrl = 'guard/dashboard.php';
                            break;
                    }
                    
                    // Show success message and redirect
                    showAlert('Login successful! Redirecting...', 'success');
                    
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, 1500);
                } else {
                    showAlert('Invalid credentials. Please check your username and password.', 'danger');
                }
            });

            // Forgot password form
            $('#sendResetLink').on('click', function() {
                const email = $('#resetEmail').val();
                const role = $('#resetRole').val();
                
                if (!email || !role) {
                    showAlert('Please fill in all fields.', 'warning');
                    return;
                }
                
                // Simulate sending reset link
                showAlert('Password reset link sent to your email!', 'success');
                $('#forgotPasswordModal').modal('hide');
                
                // Clear form
                $('#forgotPasswordForm')[0].reset();
            });

            // Auto-fill demo credentials on role change
            $('.role-btn').on('click', function() {
                const role = $(this).data('role');
                
                // Auto-fill demo credentials
                switch(role) {
                    case 'admin':
                        $('#username').val('admin@greenwood.com');
                        $('#password').val('admin123');
                        break;
                    case 'resident':
                        $('#username').val('resident@greenwood.com');
                        $('#password').val('resident123');
                        break;
                    case 'guard':
                        $('#username').val('guard@greenwood.com');
                        $('#password').val('guard123');
                        break;
                }
            });
        });

        // Demo credentials validation
        function validateDemoCredentials(username, password, role) {
            const demoCredentials = {
                admin: {
                    email: 'admin@greenwood.com',
                    password: 'admin123'
                },
                resident: {
                    email: 'resident@greenwood.com',
                    password: 'resident123'
                },
                guard: {
                    email: 'guard@greenwood.com',
                    password: 'guard123'
                }
            };
            
            const credentials = demoCredentials[role];
            return credentials && 
                   (username === credentials.email || username === role) && 
                   password === credentials.password;
        }

        // Alert function
        function showAlert(message, type = 'info', duration = 5000) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                     style="top: 100px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            $('body').append(alertHtml);
            
            setTimeout(function() {
                $('.alert').fadeOut(function() {
                    $(this).remove();
                });
            }, duration);
        }
    </script>
</body>
</html>