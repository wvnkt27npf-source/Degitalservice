<?php
session_start();
include './config.php';

// --- 1. DATA PROCESSING ---

$successMessage = $errorMessage = '';
$isContactUsSuccess = false;
$isPendingSubmission = false;
$previousMessage = '';

// Helper Function for Email
function sendEmailAlert($to, $subject, $body) {
    $headers = "From: no-reply@degitalservice.com\r\n";
    $headers .= "Reply-To: no-reply@degitalservice.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    // return mail($to, $subject, $body, $headers); // Uncomment to enable
    return true; 
}

// Handle Contact Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = trim($_POST['name']);
    $phone_number = trim($_POST['number']);
    $message = trim($_POST['message']);

    if (empty($name) || empty($phone_number) || empty($message)) {
        $errorMessage = 'All fields are required!';
    } elseif (!preg_match("/^\d{10}$/", $phone_number)) {
        $errorMessage = 'Please enter a valid 10-digit phone number.';
    } else {
        $stmt = $conn->prepare("SELECT message FROM contact_form_submissions WHERE phone_number = ? AND status = 'pending'");
        $stmt->bind_param("s", $phone_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $isPendingSubmission = true;
            $previousMessage = $result->fetch_assoc()['message'];
        } else {
            $stmt = $conn->prepare("INSERT INTO contact_form_submissions (name, phone_number, message, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param("sss", $name, $phone_number, $message);
            if ($stmt->execute()) {
                $isContactUsSuccess = true;
                $body = "New Inquiry:\nName: $name\nPhone: $phone_number\nMessage: $message";
                sendEmailAlert("rahulhaled1545@gmail.com", "New Contact Form Submission", $body);
            } else {
                $errorMessage = 'Error saving message. Please try again.';
            }
        }
        $stmt->close();
    }
}

// --- 2. DATA FETCHING ---

// Fetch Feedbacks (LIMIT 3)
$feedbackQuery = "SELECT f.feedback, f.feedback_score, u.username, u.profile_image, s.name AS service_name
                  FROM feedbacks f
                  JOIN users u ON f.user_id = u.id
                  JOIN services s ON f.service_id = s.id
                  ORDER BY f.created_at DESC LIMIT 3";
$feedbackResults = $conn->query($feedbackQuery);

// Fetch Statistics (with fallback)
$user_count = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0;
$order_count = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'] ?? 0;
$feedback_count = $conn->query("SELECT COUNT(*) as c FROM feedbacks")->fetch_assoc()['c'] ?? 0;
$service_count = $conn->query("SELECT COUNT(*) as c FROM services")->fetch_assoc()['c'] ?? 0;

$stats = [
    'users' => $user_count + 100,
    'orders' => $order_count + 100,
    'feedback' => $feedback_count + 100,
    'services' => $service_count + 100
];

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Digital Service | Plans & Professional Tech Support</title>
    <meta name="description" content="Affordable plans for Website Design, Camera Setup, and PC Support. Choose Basic, Advance, or Professional packages suited for your business.">
    <meta name="keywords" content="Service Plans, Web Development Cost, CCTV Installation Price, PC Repair Rates, Digital Marketing Packages">
    <meta name="author" content="Digital Service Team">
    <link rel="canonical" href="https://degitalservice.com/">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
</head>
<body>

<div id="particles-js"></div>
<header class="hero">
    <div class="hero-content" data-aos="fade-up">
        
        <div class="welcome-badge" data-aos="zoom-in">Welcome to Degital Service</div>

        <h1>
            Smart Solutions For<br>
            <span class="text-purple">Your Tech Needs</span>
        </h1>

        <p data-aos="fade-up" data-aos-delay="100">
            Your trusted partner for Camera Setup, Web Development, PC Support, and Creative Design. We make technology simple, secure, and helpful for your business.
        </p>

        <a href="#plans-section" class="hero-btn" data-aos="zoom-in" data-aos-delay="200">
            GET STARTED
        </a>

    </div>
</header>

<section class="partner-section partner-section-left-fade">
    <div class="swiper swiperPartner">
        <div class="swiper-wrapper">
            <div class="swiper-slide swiper-slide-partner">
                <img src="https://cdn-icons-png.flaticon.com/512/681/681667.png" alt="CCTV">
                <span>CCTV Setup</span>
            </div>
            <div class="swiper-slide swiper-slide-partner">
                <img src="https://cdn-icons-png.flaticon.com/512/1005/1005141.png" alt="Web">
                <span>Web Development</span>
            </div>
            <div class="swiper-slide swiper-slide-partner">
                <img src="https://cdn-icons-png.flaticon.com/512/2933/2933245.png" alt="PC">
                <span>PC Support</span>
            </div>
            <div class="swiper-slide swiper-slide-partner">
                <img src="https://cdn-icons-png.flaticon.com/512/3094/3094358.png" alt="Network">
                <span>Networking</span>
            </div>
            <div class="swiper-slide swiper-slide-partner">
                <img src="https://cdn-icons-png.flaticon.com/512/2808/2808391.png" alt="Design">
                <span>Graphic Design</span>
            </div>
            <div class="swiper-slide swiper-slide-partner">
                <img src="https://cdn-icons-png.flaticon.com/512/1162/1162486.png" alt="Ecommerce">
                <span>E-Commerce</span>
            </div>
            <div class="swiper-slide swiper-slide-partner">
                <img src="https://cdn-icons-png.flaticon.com/512/2920/2920323.png" alt="Video">
                <span>Video Editing</span>
            </div>
        </div>
    </div>
</section>

<div class="stats-container" data-aos="fade-up">
    
    <div class="stat-item">
        <div class="stat-icon-box">
            <img src="/uploads/Banner/Icon1.webp" alt="Experience">
        </div>
        <span class="stat-number">5+</span>
        <span class="stat-label">Years of Experience</span>
    </div>

    <div class="stat-item">
        <div class="stat-icon-box">
            <img src="/uploads/Banner/Icon2.webp" alt="Clients">
        </div>
        <span class="stat-number" id="users-count">0+</span>
        <span class="stat-label">Happy Clients</span>
    </div>

    <div class="stat-item">
        <div class="stat-icon-box">
            <img src="/uploads/Banner/Icon3.webp" alt="Projects">
        </div>
        <span class="stat-number" id="orders-count">0+</span>
        <span class="stat-label">Projects Done</span>
    </div>

    <div class="stat-item">
        <div class="stat-icon-box">
            <img src="/uploads/Banner/Icon4.webp" alt="Services">
        </div>
        <span class="stat-number" id="services-count">0+</span>
        <span class="stat-label">Services Available</span>
    </div>

</div>

<section class="content-section industry-section-container" data-aos="fade-up">
    <div class="section-header">
        <span class="section-tag">Clients</span>
        <h2 class="section-title-lg" style="color: #ffffff;">Who We Serve</h2>
        <p style="color: #cbd5e1;">We provide specialized technical and creative support for various sectors.</p>
    </div>
    
    <div class="industry-grid">
        <div class="industry-card" data-aos="fade-up" data-aos-delay="0">
            <span class="industry-icon">🏭</span>
            <div class="industry-title">Manufacturing</div>
            <p>Factories, Mills, and Production Units looking for high-end Network & Camera security.</p>
        </div>
        
        <div class="industry-card" data-aos="fade-up" data-aos-delay="100">
            <span class="industry-icon">🏢</span>
            <div class="industry-title">Corporate</div>
            <p>Modern IT Solutions, Networking infrastructure, and professional Data Management.</p>
        </div>
        
        <div class="industry-card" data-aos="fade-up" data-aos-delay="200">
            <span class="industry-icon">🛍️</span>
            <div class="industry-title">Retail Shops</div>
            <p>Intelligent Billing Software, remote monitoring, and digital Inventory management.</p>
        </div>
        
        <div class="industry-card" data-aos="fade-up" data-aos-delay="300">
            <span class="industry-icon">🏠</span>
            <div class="industry-title">Home Users</div>
            <p>Personalized PC Support, Mesh WiFi Setup, and Smart Home automation planning.</p>
        </div>
    </div>
</section>

<section class="content-section industry-section-container" data-aos="fade-up">
    <div class="section-header">
        <span class="section-tag">Expertise</span>
        <h2 class="section-title-lg" style="color: #ffffff;">Our Service Catalogue</h2>
        <p style="color: #cbd5e1;">Explore our range of professional digital services.</p>
    </div>

    <div class="overview-grid">
        
        <div class="card-service" data-aos="fade-up">
            <div class="service-icon-box">
                <img src="/uploads/Banner/Icon1.webp" alt="CCTV">
            </div>
            <h4>CCTV Camera Setup</h4>
            <p>Complete installation of security cameras, remote mobile view configuration (Static IP), and DVR maintenance.</p>
            <div class="service-cta">
                <a href="contact.php" class="service-link" style="color:#fff; text-decoration:none;">Get Quote</a>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>

        <div class="card-service" data-aos="fade-up" data-aos-delay="100">
            <div class="service-icon-box">
                <img src="/uploads/Banner/Icon2.webp" alt="Office">
            </div>
            <h4>MS Office Work</h4>
            <p>Professional document formatting, Excel data automation, and PowerPoint design services for businesses.</p>
            <div class="service-cta">
                <a href="contact.php" class="service-link" style="color:#fff; text-decoration:none;">View Details</a>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>

        <div class="card-service" data-aos="fade-up" data-aos-delay="200">
            <div class="service-icon-box">
                <img src="/uploads/Banner/Icon3.webp" alt="Networking">
            </div>
            <h4>Networking Setup</h4>
            <p>Router configuration, LAN/WAN setup, and Wi-Fi signal optimization for seamless office connectivity.</p>
            <div class="service-cta">
                <a href="contact.php" class="service-link" style="color:#fff; text-decoration:none;">Connect Now</a>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>

        <div class="card-service" data-aos="fade-up">
            <div class="service-icon-box">
                <img src="/uploads/Banner/Icon4.webp" alt="Ecommerce">
            </div>
            <h4>E-Commerce Store</h4>
            <p>Launch your online store with secure payment gateways, inventory tracking, and mobile-responsive design.</p>
            <div class="service-cta">
                <a href="contact.php" class="service-link" style="color:#fff; text-decoration:none;">Start Selling</a>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>

        <div class="card-service" data-aos="fade-up" data-aos-delay="100">
            <div class="service-icon-box">
                <img src="/uploads/Banner/Icon1.webp" alt="Design">
            </div>
            <h4>Graphic Design</h4>
            <p>Creative Logo design, Social Media banners, Visiting cards, and Marketing material design.</p>
            <div class="service-cta">
                <a href="contact.php" class="service-link" style="color:#fff; text-decoration:none;">Order Design</a>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>

        <div class="card-service" data-aos="fade-up" data-aos-delay="200">
            <div class="service-icon-box">
                <img src="/uploads/Banner/Icon2.webp" alt="Web">
            </div>
            <h4>Website Design</h4>
            <p>Modern, SEO-friendly websites tailored to showcase your business and attract more customers.</p>
            <div class="service-cta">
                <a href="contact.php" class="service-link" style="color:#fff; text-decoration:none;">Create Site</a>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>

    </div>
</section>

<section id="plans-section" class="content-section">
    <div class="section-header" data-aos="fade-up">
        <span class="section-tag">Pricing</span>
        <h2 class="section-title-lg" style="color: #ffffff;">Choose Your Plan</h2>
        <p style="color: #cbd5e1;">Transparent pricing for every stage of your business growth.</p>
    </div>
    
    <div class="pricing-grid">
        <div class="pricing-card" data-aos="fade-up" data-aos-delay="0">
            <div class="plan-name">Basic</div>
            <div class="plan-price">₹499 <span>/ visit</span></div>
            <ul class="plan-features">
                <li><span class="check-icon">✓</span> PC Health Checkup</li>
                <li><span class="check-icon">✓</span> Software Installation</li>
                <li><span class="check-icon">✓</span> Virus Removal</li>
                <li><span class="check-icon">✓</span> Basic Driver Updates</li>
            </ul>
            <a href="#contact-section" class="plan-btn-new">Select Basic</a>
        </div>

        <div class="pricing-card popular" data-aos="fade-up" data-aos-delay="100">
            <div class="popular-badge">Popular</div>
            <div class="plan-name">Advance</div>
            <div class="plan-price">₹2,499 <span>/ project</span></div>
            <ul class="plan-features">
                <li><span class="check-icon">✓</span> 4-Channel Camera Setup</li>
                <li><span class="check-icon">✓</span> WiFi Router Configuration</li>
                <li><span class="check-icon">✓</span> Mobile View Setup</li>
                <li><span class="check-icon">✓</span> 1 Month Free Support</li>
            </ul>
            <a href="#contact-section" class="plan-btn-new">Select Advance</a>
        </div>

        <div class="pricing-card" data-aos="fade-up" data-aos-delay="200">
            <div class="plan-name">Professional</div>
            <div class="plan-price">₹9,999 <span>/ start</span></div>
            <ul class="plan-features">
                <li><span class="check-icon">✓</span> 5-Page Business Website</li>
                <li><span class="check-icon">✓</span> Logo & Graphic Design</li>
                <li><span class="check-icon">✓</span> Full Office Networking</li>
                <li><span class="check-icon">✓</span> 24/7 Priority Support</li>
            </ul>
            <a href="#contact-section" class="plan-btn-new">Select Professional</a>
        </div>
    </div>
</section>


<section id="services-overview" class="content-section">
    <div class="section-header" data-aos="fade-up">
        <span class="section-tag">Expertise</span>
        <h2 class="section-title-lg" style="color: #ffffff;">Our Service Catalogue</h2>
        <p style="color: #cbd5e1;">Explore our range of professional digital services.</p>
    </div>

    <div class="overview-grid">
        
        <div class="card-service" data-aos="fade-up">
            <img src="/uploads/Banner/Icon1.webp" alt="CCTV">
            <h4>CCTV Camera Setup</h4>
            <p>Complete installation of security cameras, remote mobile view configuration, and DVR maintenance.</p>
            <div class="service-cta">
                <a href="contact.php" class="service-link">Get Quote</a>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>

        <div class="card-service" data-aos="fade-up" data-aos-delay="100">
            <img src="/uploads/Banner/Icon2.webp" alt="Office">
            <h4>MS Office Work</h4>
            <p>Professional document formatting, Excel data automation, and PowerPoint design for businesses.</p>
            <div class="service-cta">
                <a href="contact.php" class="service-link">View Details</a>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>

        <div class="card-service" data-aos="fade-up" data-aos-delay="200">
            <img src="/uploads/Banner/Icon3.webp" alt="Networking">
            <h4>Networking Setup</h4>
            <p>Router configuration, LAN/WAN setup, and Wi-Fi signal optimization for seamless connectivity.</p>
            <div class="service-cta">
                <a href="contact.php" class="service-link">Connect Now</a>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>

        </div>
</section>


<section class="content-section faq-section">
    <div class="section-header" data-aos="fade-up">
        <span class="section-tag">Help Desk</span>
        <h2 class="section-title-lg" style="color: #ffffff;">Frequently Asked Questions</h2>
        <p style="color: #cbd5e1;">Quick answers to common queries about our services.</p>
    </div>
    
    <div class="faq-container">
        <div class="faq-item" data-aos="fade-up">
            <div class="faq-question" onclick="toggleFaq(this)">
                How do I order a website design?
                <span class="faq-icon"><i class="fa-solid fa-chevron-down"></i></span>
            </div>
            <div class="faq-answer">
                Simply contact us via the form below or WhatsApp. We will schedule a free discovery call to discuss your business requirements and provide a tailored quote.
            </div>
        </div>

        <div class="faq-item" data-aos="fade-up" data-aos-delay="100">
            <div class="faq-question" onclick="toggleFaq(this)">
                Can you repair my PC remotely?
                <span class="faq-icon"><i class="fa-solid fa-chevron-down"></i></span>
            </div>
            <div class="faq-answer">
                Yes! Most software-related issues like virus removal, slow performance, and driver installations can be handled securely via remote tools like AnyDesk or TeamViewer.
            </div>
        </div>

        <div class="faq-item" data-aos="fade-up" data-aos-delay="200">
            <div class="faq-question" onclick="toggleFaq(this)">
                Do you provide AMC for CCTV cameras?
                <span class="faq-icon"><i class="fa-solid fa-chevron-down"></i></span>
            </div>
            <div class="faq-answer">
                Absolutely. We offer Annual Maintenance Contracts (AMC) for factories and offices to ensure your surveillance systems are working 24/7 without any downtime.
            </div>
        </div>
    </div>
</section>

<section class="cta-section" data-aos="zoom-in">
    <div style="position: relative; z-index: 2;">
        <h2>Ready to Upgrade <br><span class="text-purple">Your Digital Life?</span></h2>
        
        <p>
            Join hundreds of satisfied business owners and home users who trust 
            <strong>Degital Service</strong> for secure, professional, and lightning-fast solutions.
        </p>

        <a href="#plans-section" class="cta-btn-new">
            EXPLORE OUR PLANS <i class="fa-solid fa-rocket"></i>
        </a>
    </div>
</section>

<div class="content-section feedback-section" style="background: transparent; box-shadow: none; border: none;">
    <div class="section-header" data-aos="fade-up">
        <span class="section-tag">Testimonials</span>
        <h2 class="section-title-lg" style="color: #ffffff;">What Clients Say</h2>
        <p style="color: #cbd5e1;">Trusted by business owners and home users alike.</p>
    </div>
    
    <div class="feedback-grid">
        <?php if ($feedbackResults->num_rows > 0): ?>
            <?php while ($feedback = $feedbackResults->fetch_assoc()): ?>
                <div class="feedback-card" data-aos="fade-up">
                    <div class="feedback-user-info">
                        <img src="<?php echo !empty($feedback['profile_image']) ? $feedback['profile_image'] : 'uploads/profile_images/default_profile.png'; ?>" 
                             alt="User">
                        <div>
                            <h4><?= htmlspecialchars($feedback['username']) ?></h4>
                            <span><?= htmlspecialchars($feedback['service_name']) ?></span>
                        </div>
                    </div>
                    
                    <div class="feedback-stars">
                        <?= str_repeat("★", $feedback['feedback_score']) . str_repeat("☆", 5 - $feedback['feedback_score']); ?>
                    </div>
                    
                    <p class="feedback-text">
                        "<?= htmlspecialchars($feedback['feedback']) ?>"
                    </p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color:#64748b; text-align:center; width:100%;">No reviews yet. Be the first to review!</p>
        <?php endif; ?>
    </div>
</div>

<section class="content-section blog-section">
    <div class="section-header">
        <span class="section-tag">Blog</span>
        <h2 class="section-title-lg" style="color: #ffffff;">Latest Insights</h2>
        <p style="color: #cbd5e1;">Stay updated with the latest trends in technology and digital tools.</p>
    </div>
    
    <div class="overview-grid">
        <?php
        $blog_query = "SELECT * FROM blogs ORDER BY created_at DESC LIMIT 3";
        $blog_res = $conn->query($blog_query);

        if ($blog_res && $blog_res->num_rows > 0) {
            while ($post = $blog_res->fetch_assoc()) {
                $img_src = !empty($post['image']) ? $post['image'] : 'uploads/default_blog.jpg'; 
                $post_link = "blog_view.php?slug=" . $post['slug'];
                $post_date = date("M j, Y", strtotime($post['created_at']));
                $excerpt = substr(strip_tags($post['content']), 0, 100) . "...";
        ?>
            <div class="blog-card" data-aos="fade-up">
                <div class="blog-image-wrapper">
                    <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                </div>
                <div class="blog-content">
                    <span class="blog-date">📅 <?= $post_date ?></span>
                    <h3 class="blog-title"><?= htmlspecialchars($post['title']) ?></h3>
                    <p class="blog-excerpt"><?= $excerpt ?></p>
                    <a href="<?= $post_link ?>" class="blog-link">
                        READ ARTICLE <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        <?php 
            }
        } else {
            echo "<p style='text-align:center; width:100%; color:#cbd5e1;'>No blog posts available at the moment.</p>";
        }
        ?>
    </div>
    
    <div style="text-align:center; margin-top:50px;">
        <a href="blog.php" class="hero-btn" style="padding: 15px 40px;">VIEW ALL ARTICLES</a>
    </div>
</section>

<div id="contact-section" class="content-section contact-section-container" data-aos="fade-up">
    <div class="section-header">
        <span class="section-tag">Get in Touch</span>
        <h2 class="section-title-lg" style="color: #ffffff;">Let's Build Something <br><span class="text-purple">Legendary</span></h2>
        <p style="color: #cbd5e1;">Have a custom requirement? Fill the form below and our team will get back to you within 24 hours.</p>
    </div>

    <form action="" method="POST" class="contact-form-glass">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="number" placeholder="Phone Number" required maxlength="10" pattern="\d{10}">
        </div>
        
        <textarea name="message" placeholder="Describe your project, query, or technical issue..." required rows="5"></textarea>
        
        <button type="submit" name="contact_submit" class="submit-btn-new">
            SEND MESSAGE <i class="fa-solid fa-paper-plane" style="margin-left: 10px;"></i>
        </button>
    </form>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>

<script>
    // --- 1. GOLDEN PARTICLES CONFIGURATION ---
    particlesJS("particles-js", {
      "particles": {
        "number": { "value": 140, "density": { "enable": true, "value_area": 800 } },
        "color": { "value": "#ffd700" }, /* CHANGED TO GOLD */
        "shape": { "type": "circle" },
        "opacity": { "value": 0.5, "random": true },
        "size": { "value": 4, "random": true },
        "line_linked": { 
            "enable": true, 
            "distance": 150, 
            "color": "#eab308", /* DARKER GOLD LINE */
            "opacity": 0.4, 
            "width": 1 
        },
        "move": { "enable": true, "speed": 2, "direction": "none", "random": true, "out_mode": "out" }
      },
      "interactivity": {
        "detect_on": "window",
        "events": {
          "onhover": { "enable": true, "mode": "grab" },
          "onclick": { "enable": true, "mode": "push" }
        },
        "modes": {
          "grab": { "distance": 180, "line_linked": { "opacity": 0.8 } }
        }
      },
      "retina_detect": true
    });

    // --- 2. GOLDEN CURSOR LOGIC ---
    const cursor = document.getElementById('goldCursor');

    // Move cursor with mouse
    document.addEventListener('mousemove', (e) => {
        cursor.style.left = e.clientX + 'px';
        cursor.style.top = e.clientY + 'px';
    });

    // Add hover effect for links and buttons
    const clickables = document.querySelectorAll('a, button, .industry-card, .card-service');
    
    clickables.forEach(el => {
        el.addEventListener('mouseenter', () => {
            cursor.classList.add('active'); // Expands the cursor
        });
        el.addEventListener('mouseleave', () => {
            cursor.classList.remove('active'); // Shrinks back
        });
    });

    // --- 3. EXISTING ANIMATIONS (Keep your existing AOS init here) ---
    AOS.init({ duration: 800, once: true, offset: 100 });

    // FAQ Toggle
    function toggleFaq(element) {
        const parent = element.parentElement;
        parent.classList.toggle('active');
    }

    // Stats Counter
    const counters = {
        'users': document.getElementById('users-count'),
        'orders': document.getElementById('orders-count'),
        'services': document.getElementById('services-count')
    };
    // Note: PHP variables from your original code are used here
    const limits = { 'users': <?= $stats['users'] ?>, 'orders': <?= $stats['orders'] ?>, 'services': <?= $stats['services'] ?> };

    for (let key in counters) {
        let count = 0;
        let interval = setInterval(() => {
            if (count < limits[key]) {
                count += Math.ceil(limits[key] / 100);
                if(count > limits[key]) count = limits[key];
                counters[key].innerText = count + "+";
            } else {
                clearInterval(interval);
            }
        }, 20);
    }
</script>

<?php if ($isContactUsSuccess): ?>
    <script>alert("Thank you! Your message has been sent successfully.");</script>
<?php elseif (!empty($errorMessage)): ?>
    <script>alert("Error: <?= $errorMessage ?>");</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    // Initialize Swiper for Partner/Service Slider
    var swiper = new Swiper(".swiperPartner", {
        slidesPerView: "auto",      // Adjusts based on width of items
        spaceBetween: 0,            // Space managed by CSS margin
        loop: true,                 // Infinite loop
        speed: 3000,                // Speed of the scrolling (3 seconds)
        autoplay: {
            delay: 0,               // Continuous movement
            disableOnInteraction: false,
        },
        allowTouchMove: false,      // Makes it act like a marquee
    });
</script>

</body>
</html>

<?php include 'footer.php'; ?>