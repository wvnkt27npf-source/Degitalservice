<?php
session_start();
include './config.php';

// SEO Settings
$page_title = "Privacy Policy & Terms | Digital Service";
$page_desc = "Official Privacy Policy, Refund Guidelines, and Data Protection standards of Digital Service. Compliant with Indian IT Act regulations.";

include 'header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --primary: #2563eb;
        --dark: #0f172a;
        --light: #f8fafc;
        --text-main: #334155;
        --text-muted: #64748b;
        --border: #e2e8f0;
    }

    /* --- GLOBAL SETTINGS --- */
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f1f5f9;
        color: var(--text-main);
        line-height: 1.7;
        overflow-x: hidden;
    }

    /* Particle Background Layer */
    #particles-js {
        position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: -1;
        background: linear-gradient(10deg, #0f172a 100%, #1e293b 0%);
    }

    /* --- HERO SECTION --- */
    .policy-hero {
        text-align: center;
        padding: 80px 20px 60px;
        position: relative;
        z-index: 1;
    }
    .policy-hero h1 {
        font-size: 3.5rem;
        font-weight: 700;
        letter-spacing: -1px;
        margin-bottom: 15px;
        background: linear-gradient(to right, #ffffff, #94a3b8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .last-updated {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.1);
        padding: 8px 16px;
        border-radius: 50px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #cbd5e1;
        font-size: 0.9rem;
        backdrop-filter: blur(5px);
    }

    /* --- MAIN LAYOUT (SIDEBAR + CONTENT) --- */
    .wrapper {
        max-width: 1200px;
        margin: 0 auto 80px;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 280px 1fr; /* Sidebar width + Content width */
        gap: 40px;
        position: relative;
        z-index: 2;
    }

    /* Sticky Sidebar Navigation */
    .table-of-contents {
        position: sticky;
        top: 100px; /* Adjust based on your header height */
        height: fit-content;
        background: rgba(255, 255, 255, 0.95);
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1);
        border: 1px solid var(--border);
    }
    .toc-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-bottom: 20px;
        font-weight: 700;
    }
    .toc-links a {
        display: block;
        color: var(--text-main);
        text-decoration: none;
        padding: 10px 15px;
        margin-bottom: 5px;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }
    .toc-links a:hover, .toc-links a.active {
        background: #eff6ff;
        color: var(--primary);
        border-left-color: var(--primary);
        font-weight: 600;
    }

    /* Content Area */
    .legal-content-box {
        background: #ffffff;
        border-radius: 16px;
        padding: 60px;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
    }

    /* Typography & Sections */
    .legal-section {
        margin-bottom: 60px;
        scroll-margin-top: 120px; /* Offset for sticky header */
    }
    .legal-section h2 {
        font-size: 1.8rem;
        color: var(--dark);
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .legal-section h2 i { color: var(--primary); font-size: 1.4rem; }
    
    .legal-section p { margin-bottom: 15px; color: var(--text-main); text-align: justify; }
    
    .legal-section h3 {
        font-size: 1.2rem;
        color: var(--dark);
        margin: 30px 0 15px;
        font-weight: 600;
    }

    /* Styled Lists */
    ul.check-list { list-style: none; padding: 0; }
    ul.check-list li {
        position: relative; padding-left: 30px; margin-bottom: 12px; color: var(--text-main);
    }
    ul.check-list li::before {
        content: "\f00c"; /* FontAwesome Check */
        font-family: "Font Awesome 6 Free"; font-weight: 900;
        position: absolute; left: 0; top: 2px; color: var(--primary);
    }

    /* Refund Box */
    .refund-card {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 12px;
        padding: 30px;
        margin-top: 20px;
    }
    .refund-card strong { color: #0369a1; }

    /* Contact Grid Redesigned */
    .contact-wrapper {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }
    .contact-item {
        background: #f8fafc;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid var(--border);
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }
    .contact-icon {
        background: #eff6ff;
        color: var(--primary);
        width: 40px; height: 40px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .contact-info strong { display: block; color: var(--dark); margin-bottom: 5px; }
    .contact-info span, .contact-info a { font-size: 0.95rem; color: var(--text-muted); display: block; }
    .contact-info a:hover { color: var(--primary); }

    /* Footer Disclaimer */
    .consent-text {
        text-align: center;
        margin-top: 40px;
        font-size: 0.9rem;
        color: #94a3b8;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }

    /* --- RESPONSIVE DESIGN --- */
    @media (max-width: 992px) {
        .wrapper { grid-template-columns: 1fr; gap: 0; }
        .table-of-contents { display: none; /* Hide sidebar on tablet/mobile */ }
        .legal-content-box { padding: 40px 30px; }
    }

    @media (max-width: 600px) {
        .policy-hero h1 { font-size: 2.5rem; }
        .legal-content-box { padding: 30px 20px; border-radius: 12px; }
        .legal-section h2 { font-size: 1.5rem; }
    }
</style>

<div id="particles-js"></div>

<header class="policy-hero">
    <h1>Privacy Policy & Terms</h1>
    <div class="last-updated">
        <i class="fa-regular fa-clock"></i> Last Updated: December 06, 2025
    </div>
</header>

<main class="wrapper">
    
    <aside>
        <nav class="table-of-contents">
            <div class="toc-title">Table of Contents</div>
            <div class="toc-links">
                <a href="#intro" class="active">Introduction</a>
                <a href="#collection">1. Data Collection</a>
                <a href="#usage">2. Usage of Info</a>
                <a href="#security">3. Security</a>
                <a href="#refund">4. Refunds & Cancellation</a>
                <a href="#disclosure">5. Disclosure</a>
                <a href="#grievance">6. Grievance Officer</a>
            </div>
        </nav>
    </aside>

    <section class="legal-content-box">
        
        <div id="intro" class="legal-section">
            <p><strong>Digital Service</strong> ("Company", "We", "Us", or "Our") is committed to protecting your privacy. This Privacy Policy outlines how we collect, use, disclose, and safeguard your information when you visit our website <strong>degitalservice.com</strong>.</p>
            <p>This document is electronically generated in accordance with the <strong>Information Technology Act, 2000</strong> and requires no physical or digital signatures.</p>
        </div>

        <div id="collection" class="legal-section">
            <h2><i class="fa-solid fa-database"></i> 1. Collection of Information</h2>
            <p>We collect information to provide better services to all our users. The information includes:</p>
            
            <h3>Personal Data</h3>
            <p>Personally identifiable information such as Name, Shipping Address, Email Address, Phone Number, and Age that you voluntarily give to us during registration or service usage.</p>
            
            <h3>Derivative Data</h3>
            <p>Information automatically collected by our servers, such as IP Address, Browser Type, OS, and Access Times.</p>
        </div>

        <div id="usage" class="legal-section">
            <h2><i class="fa-solid fa-rocket"></i> 2. Use of Your Information</h2>
            <p>We use the collected data for specific purposes to ensure a seamless experience:</p>
            <ul class="check-list">
                <li>Create and manage your user account.</li>
                <li>Process payments and government license applications securely.</li>
                <li>Send administrative emails regarding order status.</li>
                <li>Prevent fraudulent transactions and theft.</li>
                <li>Comply with legal obligations and law enforcement requests.</li>
            </ul>
        </div>

        <div id="security" class="legal-section">
            <h2><i class="fa-solid fa-shield-halved"></i> 3. Data Security</h2>
            <p>We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that despite our efforts, no security measures are perfect or impenetrable.</p>
        </div>

        <div id="refund" class="legal-section">
            <h2><i class="fa-solid fa-hand-holding-dollar"></i> 4. Refund & Cancellation</h2>
            <div class="refund-card">
                <p>We strive to ensure 100% satisfaction. However, specific terms apply:</p>
                <ul class="check-list" style="margin-bottom: 0;">
                    <li><strong>Cancellation Window:</strong> Within 24 hours of order placement, if execution has not begun.</li>
                    <li><strong>Processing Time:</strong> Approved refunds are credited within <strong>5-7 working days</strong>.</li>
                    <li><strong>Non-Refundable:</strong> Government fees paid for licensing are strictly non-refundable once submitted.</li>
                </ul>
            </div>
        </div>

        <div id="disclosure" class="legal-section">
            <h2><i class="fa-solid fa-share-nodes"></i> 5. Disclosure of Information</h2>
            <p>We may share information solely when necessary: to respond to legal processes, protect rights, or remedy potential violations of our policies as permitted by Indian Law.</p>
        </div>

        <div id="grievance" class="legal-section">
            <h2><i class="fa-solid fa-gavel"></i> 6. Grievance Redressal</h2>
            <p>In accordance with the IT Act 2000, details of the Grievance Officers are provided below:</p>
            
            <div class="contact-wrapper">
                <div class="contact-item">
                    <div class="contact-icon"><i class="fa-solid fa-user-tie"></i></div>
                    <div class="contact-info">
                        <strong>Rahul Haled</strong>
                        <span>Officer (Rajasthan)</span>
                        <a href="tel:+919351545935">+91 9351545935</a>
                        <a href="mailto:rahulhaled1545@gmail.com">rahulhaled1545@gmail.com</a>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon"><i class="fa-solid fa-user-tie"></i></div>
                    <div class="contact-info">
                        <strong>Ganesh Gapat</strong>
                        <span>Officer (Maharashtra)</span>
                        <a href="tel:+919921060207">+91 9921060207</a>
                        <a href="mailto:ganeshgapat1@gmail.com">ganeshgapat1@gmail.com</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="consent-text">
            By using our website, you hereby consent to our Privacy Policy and agree to its Terms.
        </div>

    </section>
</main>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    // Particles Configuration (Optimized)
    document.addEventListener("DOMContentLoaded", function() {
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 40, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#ffffff" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.1, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#ffffff", "opacity": 0.1, "width": 1 },
                "move": { "enable": true, "speed": 1, "direction": "top", "random": true, "out_mode": "out" }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": { "onhover": { "enable": true, "mode": "bubble" }, "onclick": { "enable": true, "mode": "push" } },
                "modes": { "bubble": { "distance": 200, "size": 6, "duration": 2, "opacity": 0.1 } }
            },
            "retina_detect": true
        });

        // Smooth Scrolling for Sidebar
        document.querySelectorAll('.toc-links a').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Highlight Active Section on Scroll
        const sections = document.querySelectorAll('.legal-section');
        const navLi = document.querySelectorAll('.toc-links a');

        window.onscroll = () => {
            var current = "";
            sections.forEach((section) => {
                const sectionTop = section.offsetTop;
                if (pageYOffset >= sectionTop - 150) {
                    current = section.getAttribute("id");
                }
            });
            navLi.forEach((li) => {
                li.classList.remove("active");
                if (li.getAttribute("href").includes(current)) {
                    li.classList.add("active");
                }
            });
        };
    });
</script>

<?php include 'footer.php'; ?>