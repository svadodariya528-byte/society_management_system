<?php
ob_start();
?>

    <!-- Page Header -->
    <section class="hero-section" style="padding: 8rem 0 4rem;">
        <div class="container">
            <div class="text-center hero-content">
                <h1 class="hero-title">Gallery</h1>
                <p class="hero-subtitle">Take a visual tour of our beautiful community</p>
            </div>
        </div>
    </section>

    <!-- Gallery Filter -->
    <section class="py-4 bg-light">
        <div class="container">
            <div class="text-center">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary gallery-filter active" data-filter="all">
                        <i class="fas fa-th me-2"></i>All Photos
                    </button>
                    <button type="button" class="btn btn-outline-primary gallery-filter" data-filter="building">
                        <i class="fas fa-building me-2"></i>Building
                    </button>
                    <button type="button" class="btn btn-outline-primary gallery-filter" data-filter="amenities">
                        <i class="fas fa-swimming-pool me-2"></i>Amenities
                    </button>
                    <button type="button" class="btn btn-outline-primary gallery-filter" data-filter="events">
                        <i class="fas fa-calendar me-2"></i>Events
                    </button>
                    <button type="button" class="btn btn-outline-primary gallery-filter" data-filter="gardens">
                        <i class="fas fa-leaf me-2"></i>Gardens
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Grid -->
    <section class="py-5">
        <div class="container">
            <div class="gallery-grid" id="galleryGrid">
                <!-- Building Images -->
                <div class="gallery-item" data-category="building">
                    <img src="https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Main Building Exterior" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Main Building</h5>
                            <p>Modern architecture with premium finishes</p>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="building">
                    <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Building Lobby" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Elegant Lobby</h5>
                            <p>Spacious and welcoming entrance area</p>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="building">
                    <img src="https://images.unsplash.com/photo-1574362848149-11496d93a7c7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Building Corridor" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Corridor & Hallways</h5>
                            <p>Well-lit and maintained common areas</p>
                        </div>
                    </div>
                </div>

                <!-- Amenities Images -->
                <div class="gallery-item" data-category="amenities">
                    <img src="https://images.unsplash.com/photo-1571055107559-3e67626fa8be?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Swimming Pool" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Swimming Pool</h5>
                            <p>Olympic-size pool with crystal clear water</p>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="amenities">
                    <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Gymnasium" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Fully-Equipped Gym</h5>
                            <p>Modern fitness equipment for all ages</p>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="amenities">
                    <img src="https://images.unsplash.com/photo-1551698618-1dfe5d97d256?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Community Hall" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Community Hall</h5>
                            <p>Spacious venue for events and gatherings</p>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="amenities">
                    <img src="https://images.unsplash.com/photo-1593376893114-1aed528d80cf?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Kids Play Area" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Children's Play Area</h5>
                            <p>Safe and fun playground for kids</p>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="amenities">
                    <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Parking Area" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Covered Parking</h5>
                            <p>Secure parking with CCTV surveillance</p>
                        </div>
                    </div>
                </div>

                <!-- Garden Images -->
                <div class="gallery-item" data-category="gardens">
                    <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Landscaped Garden" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Landscaped Gardens</h5>
                            <p>Beautiful green spaces for relaxation</p>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="gardens">
                    <img src="https://images.unsplash.com/photo-1416879595882-3373a0480b5b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Walking Path" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Walking Paths</h5>
                            <p>Peaceful pathways through the gardens</p>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="gardens">
                    <img src="https://images.unsplash.com/photo-1574180045827-681f8a1a9622?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Garden Seating" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Garden Seating</h5>
                            <p>Comfortable seating areas in nature</p>
                        </div>
                    </div>
                </div>

                <!-- Events Images -->
                <div class="gallery-item" data-category="events">
                    <img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Diwali Celebration" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Diwali Celebration</h5>
                            <p>Annual festival celebration with residents</p>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="events">
                    <img src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Cultural Event" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Cultural Programs</h5>
                            <p>Regular cultural events and performances</p>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="events">
                    <img src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Sports Event" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Sports Tournament</h5>
                            <p>Community sports events and competitions</p>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="events">
                    <img src="https://images.unsplash.com/photo-1557804506-669a67965ba0?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="New Year Party" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>New Year Celebration</h5>
                            <p>Community New Year party and festivities</p>
                        </div>
                    </div>
                </div>

                <!-- Additional Mixed Images -->
                <div class="gallery-item" data-category="building">
                    <img src="https://images.unsplash.com/photo-1582268611958-ebfd161ef9cf?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Night View" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Night Illumination</h5>
                            <p>Beautiful lighting makes evenings magical</p>
                        </div>
                    </div>
                </div>

                <div class="gallery-item" data-category="amenities">
                    <img src="https://images.unsplash.com/photo-1555774698-0b77e0d5fac6?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Security Cabin" class="img-fluid">
                    <div class="gallery-overlay">
                        <div class="gallery-content">
                            <h5>Security Checkpoint</h5>
                            <p>24/7 manned security for resident safety</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4 text-center">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-primary">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Photos Captured</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-success">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Events Organized</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-warning">
                            <i class="fas fa-smile"></i>
                        </div>
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Happy Moments</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-info">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Community Members</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <div class="card border-0 bg-primary text-white">
                        <div class="card-body p-5">
                            <h3 class="card-title">Want to Share Your Photos?</h3>
                            <p class="card-text lead">We love showcasing the vibrant life of our community! Share your favorite moments with us.</p>
                            <div class="mt-4">
                                <a href="contact.php" class="btn btn-light btn-lg me-3">
                                    <i class="fas fa-envelope me-2"></i>
                                    Contact Us
                                </a>
                                <a href="mailto:gallery@greenwood.com" class="btn btn-outline-light btn-lg">
                                    <i class="fas fa-camera me-2"></i>
                                    Submit Photos
                                </a>
                            </div>
                            <div class="mt-4">
                                <small>
                                    <i class="fas fa-info-circle me-2"></i>
                                    Photos are updated monthly. Last updated: December 2024
                                </small>
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