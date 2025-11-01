/**
 * Society Management System - Main JavaScript File
 * jQuery-based functionality for interactivity, form validation, and UI enhancements
 */

$(document).ready(function() {
    
    // Initialize all components
    initializeNavigation();
    initializeForms();
    initializeModals();
    initializeTables();
    initializeCharts();
    initializeAnimations();
    initializeUtilities();
    
    // Navigation Functions
    function initializeNavigation() {
        // Mobile menu toggle
        $('.navbar-toggler').on('click', function() {
            $('.navbar-collapse').toggleClass('show');
        });
        
        // Sidebar toggle for mobile
        $('.sidebar-toggle').on('click', function() {
            $('.sidebar').toggleClass('show');
        });
        
        // Close sidebar when clicking outside on mobile
        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar, .sidebar-toggle').length) {
                    $('.sidebar').removeClass('show');
                }
            }
        });
        
        // Active page highlighting
        highlightActivePage();
        
        // Smooth scrolling for anchor links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 1000);
            }
        });
    }
    
    function highlightActivePage() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        $('.nav-link').each(function() {
            const href = $(this).attr('href');
            if (href && href.includes(currentPage)) {
                $(this).addClass('active');
            }
        });
    }
    
    // Form Functions
    function initializeForms() {
        // Form validation
        $('form').each(function() {
            $(this).on('submit', function(e) {
                if (!validateForm(this)) {
                    e.preventDefault();
                }
            });
        });
        
        // Real-time validation
        $('input, textarea, select').on('blur', function() {
            validateField(this);
        });
        
        // Login role selection
        $('.role-btn').on('click', function() {
            $('.role-btn').removeClass('active');
            $(this).addClass('active');
            const role = $(this).data('role');
            $('#selectedRole').val(role);
            updateLoginForm(role);
        });
        
        // Contact form handling
        $('#contactForm').on('submit', function(e) {
            e.preventDefault();
            handleContactForm();
        });
        
        // Poll voting
        $('.poll-option').on('change', function() {
            const pollId = $(this).data('poll-id');
            const option = $(this).val();
            handlePollVote(pollId, option);
        });
        
        // File upload preview
        $('input[type="file"]').on('change', function() {
            handleFilePreview(this);
        });
    }
    
    function validateForm(form) {
        let isValid = true;
        $(form).find('input[required], textarea[required], select[required]').each(function() {
            if (!validateField(this)) {
                isValid = false;
            }
        });
        return isValid;
    }
    
    function validateField(field) {
        const $field = $(field);
        const value = $field.val().trim();
        const type = $field.attr('type');
        let isValid = true;
        let message = '';
        
        // Remove existing validation classes
        $field.removeClass('is-valid is-invalid');
        $field.siblings('.invalid-feedback, .valid-feedback').remove();
        
        // Required field check
        if ($field.attr('required') && !value) {
            isValid = false;
            message = 'This field is required.';
        }
        
        // Email validation
        else if (type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Please enter a valid email address.';
            }
        }
        
        // Password validation
        else if (type === 'password' && value) {
            if (value.length < 6) {
                isValid = false;
                message = 'Password must be at least 6 characters long.';
            }
        }
        
        // Phone validation
        else if ($field.attr('name') === 'phone' && value) {
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            if (!phoneRegex.test(value.replace(/\s/g, ''))) {
                isValid = false;
                message = 'Please enter a valid phone number.';
            }
        }
        
        // Add validation classes and messages
        if (isValid) {
            $field.addClass('is-valid');
            if (value) {
                $field.after('<div class="valid-feedback">Looks good!</div>');
            }
        } else {
            $field.addClass('is-invalid');
            $field.after(`<div class="invalid-feedback">${message}</div>`);
        }
        
        return isValid;
    }
    
    function updateLoginForm(role) {
        const $form = $('#loginForm');
        $form.find('.role-specific').remove();
        
        let additionalFields = '';
        switch (role) {
            case 'admin':
                additionalFields = `
                    <div class="mb-3 role-specific">
                        <label for="adminCode" class="form-label">Admin Code</label>
                        <input type="password" class="form-control" id="adminCode" required>
                    </div>
                `;
                break;
            case 'resident':
                additionalFields = `
                    <div class="mb-3 role-specific">
                        <label for="flatNumber" class="form-label">Flat Number</label>
                        <input type="text" class="form-control" id="flatNumber" required>
                    </div>
                `;
                break;
            case 'guard':
                additionalFields = `
                    <div class="mb-3 role-specific">
                        <label for="shiftCode" class="form-label">Shift Code</label>
                        <input type="text" class="form-control" id="shiftCode" required>
                    </div>
                `;
                break;
        }
        
        $form.find('.btn-primary').before(additionalFields);
    }
    
    function handleContactForm() {
        // Simulate form submission
        showAlert('Thank you for your message! We will get back to you soon.', 'success');
        $('#contactForm')[0].reset();
    }
    
    function handlePollVote(pollId, option) {
        // Simulate poll voting
        showAlert(`Your vote for "${option}" has been recorded!`, 'success');
        $(`.poll-${pollId} .poll-option`).prop('disabled', true);
    }
    
    function handleFilePreview(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = $(input).siblings('.file-preview');
                if (preview.length) {
                    preview.attr('src', e.target.result).show();
                } else {
                    $(input).after(`<img class="file-preview mt-2" src="${e.target.result}" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`);
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Modal Functions
    function initializeModals() {
        // Bootstrap modal events
        $('.modal').on('shown.bs.modal', function() {
            $(this).find('input:first').focus();
        });
        
        // Custom modal triggers
        $(document).on('click', '[data-bs-toggle="modal"]', function() {
            const target = $(this).data('bs-target');
            const title = $(this).data('modal-title');
            if (title) {
                $(target).find('.modal-title').text(title);
            }
        });
        
        // Gallery modal
        $('.gallery-item').on('click', function() {
            const imgSrc = $(this).find('img').attr('src');
            const imgAlt = $(this).find('img').attr('alt');
            showImageModal(imgSrc, imgAlt);
        });
    }
    
    function showImageModal(src, alt) {
        const modalHtml = `
            <div class="modal fade" id="imageModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${alt || 'Image'}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${src}" alt="${alt}" class="img-fluid rounded">
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#imageModal').remove();
        $('body').append(modalHtml);
        $('#imageModal').modal('show');
    }
    
    // Table Functions
    function initializeTables() {
        // Table sorting
        $('.sortable th').on('click', function() {
            const table = $(this).closest('table');
            const index = $(this).index();
            const isAsc = $(this).hasClass('asc');
            
            // Remove all sorting classes
            table.find('th').removeClass('asc desc');
            
            // Add appropriate class
            $(this).addClass(isAsc ? 'desc' : 'asc');
            
            // Sort table
            sortTable(table, index, !isAsc);
        });
        
        // Table search
        $('.table-search').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            const table = $(this).data('table');
            
            $(`${table} tbody tr`).filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
        
        // Row selection
        $('.select-all').on('change', function() {
            const checked = $(this).prop('checked');
            $(this).closest('table').find('.row-select').prop('checked', checked);
        });
        
        $('.row-select').on('change', function() {
            const table = $(this).closest('table');
            const totalRows = table.find('.row-select').length;
            const checkedRows = table.find('.row-select:checked').length;
            
            table.find('.select-all').prop('checked', totalRows === checkedRows);
        });
    }
    
    function sortTable(table, column, ascending) {
        const rows = table.find('tbody tr').get();
        
        rows.sort(function(a, b) {
            const aText = $(a).find('td').eq(column).text();
            const bText = $(b).find('td').eq(column).text();
            
            // Try to parse as numbers
            const aNum = parseFloat(aText);
            const bNum = parseFloat(bText);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return ascending ? aNum - bNum : bNum - aNum;
            }
            
            // String comparison
            if (ascending) {
                return aText.localeCompare(bText);
            } else {
                return bText.localeCompare(aText);
            }
        });
        
        table.find('tbody').append(rows);
    }
    
    // Chart Functions (using Chart.js if available)
    function initializeCharts() {
        // Payment overview chart
        if ($('#paymentChart').length) {
            createPaymentChart();
        }
        
        // Visitor statistics chart
        if ($('#visitorChart').length) {
            createVisitorChart();
        }
        
        // Maintenance fee chart
        if ($('#maintenanceChart').length) {
            createMaintenanceChart();
        }
    }
    
    function createPaymentChart() {
        const ctx = document.getElementById('paymentChart');
        if (ctx && typeof Chart !== 'undefined') {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Paid', 'Pending', 'Overdue'],
                    datasets: [{
                        data: [65, 25, 10],
                        backgroundColor: ['#27ae60', '#f39c12', '#e74c3c'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
    
    function createVisitorChart() {
        const ctx = document.getElementById('visitorChart');
        if (ctx && typeof Chart !== 'undefined') {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Visitors',
                        data: [12, 19, 8, 15, 25, 30, 22],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderWidth: 3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
    
    function createMaintenanceChart() {
        const ctx = document.getElementById('maintenanceChart');
        if (ctx && typeof Chart !== 'undefined') {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Collection (₹)',
                        data: [45000, 52000, 48000, 61000, 55000, 67000],
                        backgroundColor: '#3498db',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
    
    // Animation Functions
    function initializeAnimations() {
        // Fade in elements on scroll
        $(window).on('scroll', function() {
            $('.fade-in-scroll').each(function() {
                const elementTop = $(this).offset().top;
                const elementBottom = elementTop + $(this).outerHeight();
                const viewportTop = $(window).scrollTop();
                const viewportBottom = viewportTop + $(window).height();
                
                if (elementBottom > viewportTop && elementTop < viewportBottom) {
                    $(this).addClass('fade-in');
                }
            });
        });
        
        // Number counter animation
        $('.stat-number').each(function() {
            const $this = $(this);
            const target = parseInt($this.text());
            let current = 0;
            const increment = target / 50;
            
            const counter = setInterval(function() {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(counter);
                }
                $this.text(Math.floor(current));
            }, 50);
        });
        
        // Loading animation
        $(window).on('load', function() {
            $('.loading-overlay').fadeOut();
        });
    }
    
    // Utility Functions
    function initializeUtilities() {
        // Tooltip initialization
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        
        // Alert auto-dismiss
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
        
        // Back to top button
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                $('.back-to-top').fadeIn();
            } else {
                $('.back-to-top').fadeOut();
            }
        });
        
        $('.back-to-top').click(function() {
            $('html, body').animate({scrollTop: 0}, 800);
            return false;
        });
        
        // Print functionality
        $('.print-btn').on('click', function() {
            window.print();
        });
        
        // Export functionality
        $('.export-btn').on('click', function() {
            const format = $(this).data('format') || 'csv';
            exportTable($(this).data('table'), format);
        });
        
        // Theme toggle (if implemented)
        $('.theme-toggle').on('click', function() {
            $('body').toggleClass('dark-theme');
            localStorage.setItem('theme', $('body').hasClass('dark-theme') ? 'dark' : 'light');
        });
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            $('body').addClass('dark-theme');
        }
    }
    
    // Utility helper functions
    function showAlert(message, type = 'info', duration = 5000) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
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
    
    function exportTable(tableSelector, format) {
        const $table = $(tableSelector);
        let data = '';
        
        // Get headers
        const headers = [];
        $table.find('thead th').each(function() {
            headers.push($(this).text().trim());
        });
        
        // Get data
        const rows = [];
        $table.find('tbody tr').each(function() {
            const row = [];
            $(this).find('td').each(function() {
                row.push($(this).text().trim());
            });
            rows.push(row);
        });
        
        if (format === 'csv') {
            data = headers.join(',') + '\n';
            rows.forEach(row => {
                data += row.join(',') + '\n';
            });
        }
        
        // Download file
        const blob = new Blob([data], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `export_${Date.now()}.${format}`;
        a.click();
        window.URL.revokeObjectURL(url);
    }
    
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR'
        }).format(amount);
    }
    
    function formatDate(date) {
        return new Date(date).toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    // Global functions for external access
    window.SocietyManagement = {
        showAlert: showAlert,
        validateForm: validateForm,
        formatCurrency: formatCurrency,
        formatDate: formatDate,
        exportTable: exportTable
    };
});

// Service Worker registration for PWA capabilities (optional)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js').then(function(registration) {
            console.log('ServiceWorker registration successful');
        }).catch(function(err) {
            console.log('ServiceWorker registration failed');
        });
    });
}