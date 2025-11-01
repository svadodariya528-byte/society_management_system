<?php
ob_start();
?>

    <!-- Page Header -->
    <section class="hero-section" style="padding: 8rem 0 4rem;">
        <div class="container">
            <div class="text-center hero-content">
                <h1 class="hero-title">Contact Us</h1>
                <p class="hero-subtitle">We're here to help and answer any questions you might have</p>
            </div>
        </div>
    </section>

    <!-- Contact Information -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-5">
                            <div class="stat-icon text-primary mb-3">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h4>Visit Us</h4>
                            <p class="text-muted mb-3">
                                Greenwood Society<br>
                                123 Green Avenue<br>
                                Sector 45, Gurgaon<br>
                                Haryana 122001
                            </p>
                            <a href="https://goo.gl/maps" target="_blank" class="btn btn-outline-primary">
                                <i class="fas fa-directions me-2"></i>Get Directions
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-5">
                            <div class="stat-icon text-success mb-3">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h4>Call Us</h4>
                            <p class="text-muted mb-3">
                                <strong>Main Office:</strong><br>
                                +91 98765 43210<br><br>
                                <strong>Emergency:</strong><br>
                                +91 98765 43211
                            </p>
                            <a href="tel:+919876543210" class="btn btn-outline-success">
                                <i class="fas fa-phone me-2"></i>Call Now
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body p-5">
                            <div class="stat-icon text-info mb-3">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h4>Email Us</h4>
                            <p class="text-muted mb-3">
                                <strong>General Inquiries:</strong><br>
                                info@greenwood.com<br><br>
                                <strong>Maintenance:</strong><br>
                                maintenance@greenwood.com
                            </p>
                            <a href="mailto:info@greenwood.com" class="btn btn-outline-info">
                                <i class="fas fa-envelope me-2"></i>Send Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card border-0 shadow-lg">
                        <div class="card-header bg-gradient-primary text-white">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-paper-plane me-2"></i>
                                Send us a Message
                            </h3>
                        </div>
                        <div class="card-body p-5">
                            <form id="contactForm" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="firstName" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="firstName" name="firstName" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lastName" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="lastName" name="lastName" required>
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label for="subject" class="form-label">Subject *</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">Choose a subject...</option>
                                        <option value="general">General Inquiry</option>
                                        <option value="maintenance">Maintenance Request</option>
                                        <option value="security">Security Issue</option>
                                        <option value="billing">Billing Question</option>
                                        <option value="complaint">Complaint</option>
                                        <option value="suggestion">Suggestion</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="mt-3">
                                    <label for="flatNumber" class="form-label">Flat Number (if applicable)</label>
                                    <input type="text" class="form-control" id="flatNumber" name="flatNumber" 
                                           placeholder="e.g., A-101, B-205">
                                </div>
                                <div class="mt-3">
                                    <label for="message" class="form-label">Message *</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" 
                                              placeholder="Please describe your inquiry or concern in detail..." required></textarea>
                                </div>
                                <div class="mt-3">
                                    <label for="priority" class="form-label">Priority Level</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="normal">Normal</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                                <div class="mt-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                                        <label class="form-check-label" for="newsletter">
                                            I would like to receive updates about society events and news
                                        </label>
                                    </div>
                                </div>
                                <div class="mt-4 d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Send Message
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Office Hours Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-5">
                            <h3 class="card-title mb-4">
                                <i class="fas fa-clock text-primary me-2"></i>
                                Office Hours
                            </h3>
                            <div class="row g-3">
                                <div class="col-6">
                                    <strong>Monday - Friday</strong><br>
                                    <span class="text-muted">9:00 AM - 7:00 PM</span>
                                </div>
                                <div class="col-6">
                                    <strong>Saturday</strong><br>
                                    <span class="text-muted">9:00 AM - 2:00 PM</span>
                                </div>
                                <div class="col-6">
                                    <strong>Sunday</strong><br>
                                    <span class="text-muted">10:00 AM - 1:00 PM</span>
                                </div>
                                <div class="col-6">
                                    <strong>Emergency</strong><br>
                                    <span class="text-success">24/7 Available</span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Note:</strong> For urgent matters outside office hours, please call our emergency number.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-5">
                            <h3 class="card-title mb-4">
                                <i class="fas fa-headset text-success me-2"></i>
                                Quick Support
                            </h3>
                            <div class="d-grid gap-2">
                                <a href="tel:+919876543210" class="btn btn-outline-success">
                                    <i class="fas fa-phone me-2"></i>
                                    Call Main Office
                                </a>
                                <a href="tel:+919876543211" class="btn btn-outline-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Emergency Hotline
                                </a>
                                <a href="https://wa.me/919876543210" target="_blank" class="btn btn-outline-success">
                                    <i class="fab fa-whatsapp me-2"></i>
                                    WhatsApp Support
                                </a>
                                <a href="login.php" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Resident Portal
                                </a>
                            </div>
                            <div class="mt-4">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Average response time: 2-4 hours during business hours
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Department Contacts -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-6 fw-bold">Department Contacts</h2>
                <p class="lead text-muted">Reach out to specific departments for faster assistance</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-tools fa-3x text-primary mb-3"></i>
                            <h5>Maintenance</h5>
                            <p class="text-muted small">Plumbing, electrical, repairs</p>
                            <hr>
                            <a href="tel:+919876543212" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-phone me-1"></i> Call
                            </a>
                            <a href="mailto:maintenance@greenwood.com" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fas fa-envelope me-1"></i> Email
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                            <h5>Security</h5>
                            <p class="text-muted small">Safety, visitor management</p>
                            <hr>
                            <a href="tel:+919876543213" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-phone me-1"></i> Call
                            </a>
                            <a href="mailto:security@greenwood.com" class="btn btn-sm btn-outline-success ms-2">
                                <i class="fas fa-envelope me-1"></i> Email
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-rupee-sign fa-3x text-warning mb-3"></i>
                            <h5>Accounts</h5>
                            <p class="text-muted small">Billing, payments, dues</p>
                            <hr>
                            <a href="tel:+919876543214" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-phone me-1"></i> Call
                            </a>
                            <a href="mailto:accounts@greenwood.com" class="btn btn-sm btn-outline-warning ms-2">
                                <i class="fas fa-envelope me-1"></i> Email
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-user-tie fa-3x text-info mb-3"></i>
                            <h5>Administration</h5>
                            <p class="text-muted small">General queries, complaints</p>
                            <hr>
                            <a href="tel:+919876543210" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-phone me-1"></i> Call
                            </a>
                            <a href="mailto:admin@greenwood.com" class="btn btn-sm btn-outline-info ms-2">
                                <i class="fas fa-envelope me-1"></i> Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-6 fw-bold">Find Us</h2>
                <p class="lead text-muted">Located in the heart of Gurgaon with excellent connectivity</p>
            </div>
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <!-- Embedded Google Map -->
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3507.4396745367153!2d77.02663931508394!3d28.451043782456916!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390d18708aaaaaab%3A0x44da0ff1f062ff4b!2sGurgaon%2C%20Haryana!5e0!3m2!1sen!2sin!4v1635678901234!5m2!1sen!2sin" 
                                width="100%" 
                                height="400" 
                                style="border:0; border-radius: 12px;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title">Location Benefits</h4>
                            <div class="mt-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-subway text-primary me-3"></i>
                                    <span>5 min to Metro Station</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-shopping-cart text-success me-3"></i>
                                    <span>2 min to Shopping Mall</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-hospital text-info me-3"></i>
                                    <span>10 min to Hospital</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-school text-warning me-3"></i>
                                    <span>3 min to Schools</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-plane text-primary me-3"></i>
                                    <span>45 min to Airport</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php
    $content=ob_get_clean();
    include './layout/layout.php';
?>