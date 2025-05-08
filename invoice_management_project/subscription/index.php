<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Pro - Subscription Plans</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/subscription.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6 text-center text-lg-start">
                    <h1 class="display-4 fw-bold mb-4 text-gradient">Transform Your Invoicing Experience</h1>
                    <p class="lead mb-4">Streamline your billing process with our powerful invoicing solution. Choose the perfect plan for your business needs.</p>
                    <div class="d-flex gap-3 justify-content-center justify-content-lg-start">
                        <a href="#pricing" class="btn btn-primary btn-lg">View Plans</a>
                        <a href="#features" class="btn btn-outline-primary btn-lg">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="assets/images/hero-illustration.svg" alt="Invoicing Solution" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Why Choose Invoice Pro?</h2>
                <p class="section-subtitle">Everything you need to manage your invoicing efficiently</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3>Lightning Fast</h3>
                        <p>Generate professional invoices in seconds with our intuitive interface.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Secure & Reliable</h3>
                        <p>Your data is protected with enterprise-grade security measures.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Detailed Analytics</h3>
                        <p>Track your business growth with comprehensive reporting tools.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="pricing-section py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Choose Your Plan</h2>
                <p class="section-subtitle">Find the perfect plan for your business</p>
                <div class="pricing-toggle mt-4">
                    <span class="me-3">Monthly</span>
                    <label class="switch">
                        <input type="checkbox" id="billingToggle">
                        <span class="slider round"></span>
                    </label>
                    <span class="ms-3">Yearly <span class="badge bg-primary">Save 20%</span></span>
                </div>
            </div>
            <div class="row g-4 pricing-cards">
                <!-- Basic Plan -->
                <div class="col-md-4">
                    <div class="pricing-card">
                        <div class="pricing-header">
                            <h3>Basic</h3>
                            <div class="price">
                                <span class="currency">₹</span>
                                <span class="amount monthly">999</span>
                                <span class="amount yearly d-none">9,599</span>
                                <span class="period">/month</span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check"></i> Up to 100 invoices/month</li>
                                <li><i class="fas fa-check"></i> 2 Users</li>
                                <li><i class="fas fa-check"></i> Basic Templates</li>
                                <li><i class="fas fa-check"></i> Email Support</li>
                                <li><i class="fas fa-check"></i> Basic Reports</li>
                            </ul>
                        </div>
                        <div class="pricing-footer">
                            <a href="checkout.php?plan=basic" class="btn btn-outline-primary btn-block">Get Started</a>
                        </div>
                    </div>
                </div>
                <!-- Professional Plan -->
                <div class="col-md-4">
                    <div class="pricing-card popular">
                        <div class="popular-badge">Most Popular</div>
                        <div class="pricing-header">
                            <h3>Professional</h3>
                            <div class="price">
                                <span class="currency">₹</span>
                                <span class="amount monthly">1,999</span>
                                <span class="amount yearly d-none">19,199</span>
                                <span class="period">/month</span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check"></i> Unlimited invoices</li>
                                <li><i class="fas fa-check"></i> 5 Users</li>
                                <li><i class="fas fa-check"></i> All Templates</li>
                                <li><i class="fas fa-check"></i> Priority Support</li>
                                <li><i class="fas fa-check"></i> Advanced Reports</li>
                                <li><i class="fas fa-check"></i> Custom Branding</li>
                            </ul>
                        </div>
                        <div class="pricing-footer">
                            <a href="checkout.php?plan=professional" class="btn btn-primary btn-block">Get Started</a>
                        </div>
                    </div>
                </div>
                <!-- Custom Plan -->
                <div class="col-md-4">
                    <div class="pricing-card">
                        <div class="pricing-header">
                            <h3>Enterprise</h3>
                            <div class="price">
                                <span class="custom-price">Custom</span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check"></i> Custom Duration</li>
                                <li><i class="fas fa-check"></i> Unlimited Everything</li>
                                <li><i class="fas fa-check"></i> White Labeling</li>
                                <li><i class="fas fa-check"></i> API Access</li>
                                <li><i class="fas fa-check"></i> Dedicated Support</li>
                                <li><i class="fas fa-check"></i> Custom Features</li>
                            </ul>
                        </div>
                        <div class="pricing-footer">
                            <a href="custom_plan.php" class="btn btn-outline-primary btn-block">Contact Sales</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Frequently Asked Questions</h2>
                <p class="section-subtitle">Find answers to common questions about our service</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <!-- FAQ Item 1 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Can I change my plan later?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, you can upgrade or downgrade your plan at any time. Changes will be reflected in your next billing cycle.
                                </div>
                            </div>
                        </div>
                        <!-- FAQ Item 2 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    What payment methods do you accept?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We accept all major credit cards, debit cards, UPI, and net banking. All payments are processed securely through our payment gateway.
                                </div>
                            </div>
                        </div>
                        <!-- FAQ Item 3 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Is there a free trial available?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, we offer a 14-day free trial on all our plans. No credit card required during the trial period.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="mb-4">Ready to Get Started?</h2>
                    <p class="lead mb-4">Join thousands of businesses who trust Invoice Pro for their invoicing needs.</p>
                    <a href="#pricing" class="btn btn-primary btn-lg">Choose Your Plan</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p>&copy; 2024 Invoice Pro. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-muted me-3">Privacy Policy</a>
                    <a href="#" class="text-muted me-3">Terms of Service</a>
                    <a href="#" class="text-muted">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/subscription.js"></script>
</body>
</html> 