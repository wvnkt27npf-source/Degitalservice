<?php
// footer.php - Glassy Design with provided content
?>
<style>
    /* Glassy Footer Styling */
    .site-footer {
        background: rgb(30 41 59 / 0%); /* Dark slate with transparency */
        backdrop-filter: blur(12px); /* Premium Glass Effect */
        -webkit-backdrop-filter: blur(12px);
        color: #f8fafc;
        padding: 60px 0 30px;
        font-family: 'Inter', sans-serif;
        margin-top: 50px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        position: relative;
        overflow: hidden;
    }

    /* Subtle Animated Glow in background */
    .site-footer::before {
        content: "";
        position: absolute;
        top: -150px;
        left: -100px;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, rgba(15, 23, 42, 0) 70%);
        z-index: 0;
        pointer-events: none;
    }

    .footer-container {
        position: relative;
        z-index: 1;
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 40px;
        padding: 0 25px;
    }

    .footer-section h3 {
        color: #60a5fa; /* Vibrant blue */
        font-size: 1.1rem;
        margin-bottom: 25px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.2px;
    }

    .footer-links {
        list-style: none;
        padding: 0;
    }

    .footer-links li {
        margin-bottom: 12px;
    }

    .footer-links a {
        color: #94a3b8;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 0.95rem;
        display: inline-block;
    }

    .footer-links a:hover {
        color: #ffffff;
        transform: translateX(5px);
    }

    .contact-info p {
        font-size: 0.95rem;
        line-height: 1.6;
        color: #cbd5e1;
        margin-bottom: 15px;
    }

    .contact-info strong {
        color: #60a5fa;
        display: block;
        margin-bottom: 4px;
        font-size: 0.85rem;
        text-transform: uppercase;
    }

    /* Compliance Notice Section */
    .compliance-box {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        padding: 20px;
        border-radius: 12px;
        margin-top: 20px;
    }

    .compliance-box p {
        font-size: 0.85rem;
        color: #94a3b8;
        line-height: 1.6;
        margin: 0;
    }

    .compliance-box strong {
        color: #f8fafc;
    }

    .footer-bottom {
        text-align: center;
        margin-top: 50px;
        padding-top: 25px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        font-size: 0.85rem;
        color: #64748b;
    }

    @media (max-width: 768px) {
        .footer-container {
            grid-template-columns: 1fr;
            text-align: center;
        }
        .footer-links a:hover {
            transform: translateY(-3px);
        }
    }
</style>

<footer class="site-footer">
    <div class="footer-container">
        
        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul class="footer-links">
                <li><a href="terms.php">Terms and Conditions</a></li>
                <li><a href="disclaimer.php">Disclaimer</a></li>
                <li><a href="privacy_policy.php">Privacy Policy</a></li>
                <li><a href="accessibility.php">Accessibility Statement</a></li>
                <li><a href="refund_cancellation.php">Refund and Cancellation</a></li>
                <li><a href="cookie_policy.php">Cookie Policy</a></li>
            </ul>
        </div>

        <div class="footer-section contact-info">
            <h3>Contact Us</h3>
            <p>
                <strong>Email:</strong>
                <a href="mailto:support@degitalservice.com" style="color: #cbd5e1; text-decoration: none;">support@degitalservice.com</a>
            </p>
            <p>
                <strong>Phone:</strong>
                +91 9921060207<br>
                +91 9351545935
            </p>
        </div>

        <div class="footer-section">
            <h3>Compliance Notice</h3>
            <div class="compliance-box">
                <p><strong>Notice:</strong> We are a Digital Services agency. We do not engage in lottery, betting, or gambling activities.</p>
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Digital Services. All rights reserved.</p>
    </div>
</footer>

</body>
</html>