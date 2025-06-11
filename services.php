<?php
session_start();
require_once 'config.php';
include 'includes/header.php';
?>

<!-- Services Header -->
<section class="hero-section text-center" style="padding: 100px 0;">
    <div class="container">
        <h1 class="display-4 mb-4 animate-fadeInUp">Our Services</h1>
        <p class="lead mb-4 animate-fadeInUp" style="animation-delay: 0.2s;">Professional grooming services for the modern gentleman</p>
    </div>
</section>

<!-- Services List -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <?php
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $sql = "SELECT * FROM services ORDER BY price ASC";
            $result = $conn->query($sql);

            while ($service = $result->fetch_assoc()):
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card service-card h-100">
                    <div class="card-body">
                        <div class="feature-icon">
                            <i class="fas fa-cut"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($service['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($service['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                <p class="mb-0"><i class="far fa-clock me-2"></i><?php echo $service['duration']; ?> min</p>
                                <p class="mb-0"><i class="fas fa-tag me-2"></i>$<?php echo number_format($service['price'], 2); ?></p>
                            </div>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="book.php?service=<?php echo $service['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-calendar-plus me-2"></i>Book Now
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Book
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- Additional Information -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3 class="mb-4">
                            <i class="fas fa-star text-primary me-2"></i>
                            What to Expect
                        </h3>
                        <ul class="list-unstyled">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="fas fa-check-circle text-primary me-3"></i>
                                <span>Professional consultation with experienced barbers</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="fas fa-check-circle text-primary me-3"></i>
                                <span>Premium quality grooming products</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="fas fa-check-circle text-primary me-3"></i>
                                <span>Relaxing and comfortable atmosphere</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="fas fa-check-circle text-primary me-3"></i>
                                <span>Expert barbers with years of experience</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3 class="mb-4">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Booking Information
                        </h3>
                        <ul class="list-unstyled">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="fas fa-calendar-check text-primary me-3"></i>
                                <span>Easy online booking system</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="fas fa-clock text-primary me-3"></i>
                                <span>Same-day appointments available</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="fas fa-ban text-primary me-3"></i>
                                <span>24-hour cancellation policy</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="fas fa-door-open text-primary me-3"></i>
                                <span>Walk-ins always welcome</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 text-center" style="background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), url('assets/images/barbershop-bg.jpg') center/cover fixed;">
    <div class="container">
        <h2 class="text-white mb-4">Ready to Book Your Appointment?</h2>
        <p class="lead text-white mb-4">Join our community of well-groomed gentlemen today.</p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="appointments.php" class="btn btn-primary btn-lg">
                <i class="fas fa-calendar-plus me-2"></i>Book Now
            </a>
        <?php else: ?>
            <a href="register.php" class="btn btn-primary btn-lg">
                <i class="fas fa-user-plus me-2"></i>Get Started
            </a>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?> 