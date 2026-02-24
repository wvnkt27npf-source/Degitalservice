<?php 
// 404.php
include 'header.php'; 
?>
<style>
    .error-container { text-align: center; padding: 100px 20px; }
    .error-code { font-size: 8rem; font-weight: 800; color: #2563eb; }
    .error-msg { font-size: 2rem; color: #334155; margin-bottom: 20px; }
    .back-home { background: #2563eb; color: white; padding: 15px 30px; border-radius: 50px; text-decoration: none; font-weight: bold; }
    .back-home:hover { background: #1d4ed8; }
</style>

<div class="error-container">
    <div class="error-code">404</div>
    <div class="error-msg">Oops! Page Not Found</div>
    <p style="margin-bottom: 40px; color: #64748b;">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
    <a href="index.php" class="back-home">Go Back Home</a>
</div>

<?php include 'footer.php'; ?>