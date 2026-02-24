<?php
session_start();
include './config.php';

// SEO Configuration
$page_title = "Contact Us | Digital Service";
$page_desc = "Get in touch with Digital Service for Web Design, CCTV Setup, and Tech Support. Visit us in Bhilwara or call now.";

// --- FORM HANDLING ---
$msg = '';
$msgClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $service = trim($_POST['service']);
    $user_msg = trim($_POST['message']);
    
    // Validate
    if (empty($name) || empty($phone) || empty($user_msg)) {
        $msg = "Please fill in all required fields.";
        $msgClass = "error";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $msg = "Please enter a valid 10-digit phone number.";
        $msgClass = "error";
    } else {
        // Combine Service + Message for storage
        $final_message = "I am interested in: [ $service ]\n\nDetails: " . $user_msg;

        // Check for duplicates (Spam Protection)
        $check = $conn->prepare("SELECT id FROM contact_form_submissions WHERE phone_number = ? AND status = 'pending' AND created_at > NOW() - INTERVAL 1 HOUR");
        $check->bind_param("s", $phone);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $msg = "We have already received your request. We will call you shortly.";
            $msgClass = "warning";
        } else {
            // Insert into Database
            $stmt = $conn->prepare("INSERT INTO contact_form_submissions (name, phone_number, message, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param("sss", $name, $phone, $final_message);
            
            if ($stmt->execute()) {
                $msg = "Thank you! Your inquiry has been submitted successfully.";
                $msgClass = "success";
                
                // Optional: Send Email Alert (Uncomment if mail server is configured)
                // mail("rahulhaled1545@gmail.com", "New Lead: $service", $final_message);
            } else {
                $msg = "Something went wrong. Please try again.";
                $msgClass = "error";
            }
            $stmt->close();
        }
        $check->close();
    }
}

include 'header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    /* --- PAGE STYLES --- */
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
        color: #334155;
        overflow-x: hidden;
    }
    
    #particles-js {
        position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: -1;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }

    /* Hero Section */
    .contact-hero {
        text-align: center; padding: 140px 20px 80px; color: white; position: relative;
    }
    .contact-hero h1 {
        font-size: 3.5rem; font-weight: 800; margin-bottom: 15px;
        background: linear-gradient(to right, #fff, #93c5fd); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .contact-hero p { font-size: 1.2rem; color: #cbd5e1; max-width: 600px; margin: 0 auto; }

    /* Main Container */
    .contact-wrapper {
        max-width: 1200px; margin: 0 auto 80px; padding: 0 20px;
        display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px;
        position: relative; z-index: 5;
    }

    /* Left Side: Info */
    .info-card {
        background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
        padding: 40px; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        display: flex; flex-direction: column; gap: 30px; height: fit-content;
    }
    .info-item { display: flex; gap: 20px; align-items: flex-start; }
    .info-icon {
        width: 50px; height: 50px; background: #eff6ff; color: #2563eb;
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; flex-shrink: 0;
    }
    .info-content h4 { margin: 0 0 5px; font-size: 1.1rem; color: #0f172a; font-weight: 700; }
    .info-content p, .info-content a { margin: 0; color: #64748b; text-decoration: none; font-size: 0.95rem; line-height: 1.6; }
    .info-content a:hover { color: #2563eb; }

    .map-container {
        border-radius: 16px; overflow: hidden; margin-top: 10px; height: 200px; border: 1px solid #e2e8f0;
    }

    /* Right Side: Form */
    .form-card {
        background: #1e293b; color: white; padding: 50px; border-radius: 24px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.4); border: 1px solid #334155;
    }
    .form-header { margin-bottom: 30px; }
    .form-header h2 { margin: 0 0 10px; font-size: 2rem; }
    .form-header p { color: #94a3b8; }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: #cbd5e1; font-weight: 500; }
    
    .form-control {
        width: 100%; padding: 14px 16px; background: #0f172a; border: 2px solid #334155;
        border-radius: 10px; color: white; font-size: 1rem; transition: 0.3s;
        font-family: 'Inter', sans-serif;
    }
    .form-control:focus {
        border-color: #3b82f6; outline: none; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
    }
    select.form-control { cursor: pointer; }

    .btn-submit {
        width: 100%; padding: 16px; background: #2563eb; color: white; border: none;
        border-radius: 10px; font-size: 1.1rem; font-weight: 700; cursor: pointer;
        transition: 0.3s; margin-top: 10px;
    }
    .btn-submit:hover { background: #1d4ed8; transform: translateY(-2px); }

    /* Messages */
    .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
    .alert.success { background: #d1fae5; color: #065f46; border: 1px solid #34d399; }
    .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
    .alert.warning { background: #fef3c7; color: #92400e; border: 1px solid #fbbf24; }

    @media (max-width: 900px) {
        .contact-wrapper { grid-template-columns: 1fr; }
        .contact-hero h1 { font-size: 2.5rem; }
    }
</style>

<div id="particles-js"></div>

<header class="contact-hero" data-aos="fade-down">
    <h1>Get In Touch</h1>
    <p>Have a project in mind or need technical support? We are just a message away.</p>
</header>

<div class="contact-wrapper">
    
    <div class="info-card" data-aos="fade-right">
        <div class="info-item">
            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div class="info-content">
                <h4>Visit Us</h4>
                <p>Digital Service HQ,<br>Bhilwara, Rajasthan, India - 311001</p>
            </div>
        </div>

        <div class="info-item">
            <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
            <div class="info-content">
                <h4>Call Support</h4>
                <p><a href="tel:+919921060207">+91 9921060207</a></p>
                <p style="font-size:0.85rem; color:#94a3b8; margin-top:5px;">Mon-Sat: 10 AM - 7 PM</p>
            </div>
        </div>

        <div class="info-item">
            <div class="info-icon"><i class="fas fa-envelope"></i></div>
            <div class="info-content">
                <h4>Email Us</h4>
                <p><a href="mailto:rahulhaled1545@gmail.com">rahulhaled1545@gmail.com</a></p>
                <p><a href="mailto:support@degitalservice.com">support@degitalservice.com</a></p>
            </div>
        </div>

        <a href="https://wa.me/919921060207?text=Hi, I need info about Digital Services" target="_blank" 
           style="background: #25D366; color: white; padding: 15px; border-radius: 10px; text-align: center; text-decoration: none; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.3s;">
            <i class="fab fa-whatsapp" style="font-size: 1.4rem;"></i> Chat on WhatsApp
        </a>

        <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d57903.024564539665!2d74.58620800631388!3d25.34771449830501!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3968c24749508f6d%3A0x6372c3882747123!2sBhilwara%2C%20Rajasthan!5e0!3m2!1sen!2sin!4v1701880000000!5m2!1sen!2sin" 
                    width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>

    <div class="form-card" data-aos="fade-left">
        <div class="form-header">
            <h2>Send a Request</h2>
            <p>Fill the form below and our team will get back to you within 24 hours.</p>
        </div>

        <?php if(!empty($msg)): ?>
            <div class="alert <?= $msgClass; ?>">
                <?= $msg; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Your Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="10-digit mobile number" pattern="[0-9]{10}" maxlength="10" required>
            </div>

            <div class="form-group">
                <label>Service Interested In</label>
                <select name="service" class="form-control" required>
                    <option value="" disabled selected>Select a Service</option>
                    <option value="Basic Plan (PC Repair)">Basic Plan - ₹499</option>
                    <option value="Advance Plan (CCTV Setup)">Advance Plan - ₹2,499</option>
                    <option value="Professional Plan (Web Design)">Professional Plan - ₹9,999</option>
                    <option value="Video Editing">Video Editing</option>
                    <option value="Graphic Design">Graphic Design</option>
                    <option value="Other Inquiry">Other / General Inquiry</option>
                </select>
            </div>

            <div class="form-group">
                <label>Message / Requirements</label>
                <textarea name="message" class="form-control" rows="4" placeholder="Describe your project..." required></textarea>
            </div>

            <button type="submit" name="submit_contact" class="btn-submit">Submit Request</button>
        </form>
    </div>

</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    AOS.init({ duration: 800, once: true });

    particlesJS("particles-js", {
        "particles": {
            "number": { "value": 60, "density": { "enable": true, "value_area": 800 } },
            "color": { "value": "#ffffff" },
            "shape": { "type": "circle" },
            "opacity": { "value": 0.4, "random": true },
            "size": { "value": 3, "random": true },
            "line_linked": { "enable": true, "distance": 150, "color": "#94a3b8", "opacity": 0.2, "width": 1 },
            "move": { "enable": true, "speed": 1.5, "direction": "none", "random": true, "out_mode": "out" }
        },
        "interactivity": {
            "detect_on": "window",
            "events": { "onhover": { "enable": true, "mode": "grab" } },
            "modes": { "grab": { "distance": 150, "line_linked": { "opacity": 0.6 } } }
        },
        "retina_detect": true
    });
</script>

<?php include 'footer.php'; ?>