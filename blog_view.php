<?php
include './config.php';

// Get Slug from URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$slug = $conn->real_escape_string($slug);

// Fetch Post Data
$sql = "SELECT * FROM blogs WHERE slug = '$slug'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $post = $result->fetch_assoc();

    // --- DYNAMIC SEO SETTINGS ---
    $page_title = $post['meta_title'] ? $post['meta_title'] : $post['title'];
    $page_desc = $post['meta_desc'];
    $page_keys = $post['meta_keywords'];
} else {
    $page_title = "Post Not Found";
    $page_desc = "The requested article could not be found.";
}

include './header.php';
?>

<div id="particles-js"></div>

<style>
    /* --- PAGE STYLING --- */
    body {
        font-family: 'Inter', sans-serif;
        color: #334155;
    }

    /* Particles Background (Fixed) */
    #particles-js {
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        z-index: -1; /* Behind everything */
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }

    /* Main Container Card */
    .blog-view-container {
        max-width: 900px;
        margin: 60px auto;
        background: rgba(255, 255, 255, 0.95); /* Slight transparency */
        backdrop-filter: blur(10px);
        padding: 50px;
        border-radius: 20px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        position: relative;
        z-index: 10;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Typography */
    .post-header {
        text-align: center;
        margin-bottom: 40px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 30px;
    }

    .post-meta {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 15px;
        display: inline-block;
        background: #f1f5f9;
        padding: 5px 15px;
        border-radius: 50px;
        font-weight: 600;
    }

    .post-title {
        font-size: 2.8rem;
        color: #0f172a;
        margin-bottom: 20px;
        line-height: 1.2;
        font-weight: 800;
        letter-spacing: -1px;
    }

    /* Featured Image */
    .post-image-wrapper {
        margin-bottom: 40px;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .post-image {
        width: 100%;
        height: auto;
        max-height: 500px;
        object-fit: cover;
        display: block;
        transition: transform 0.3s;
    }
    .post-image:hover {
        transform: scale(1.02);
    }

    /* Content Area */
    .post-content {
        font-size: 1.15rem;
        line-height: 1.8;
        color: #374151;
    }
    
    /* Content Headings & Lists */
    .post-content h2 {
        margin-top: 40px;
        margin-bottom: 20px;
        color: #1e293b;
        font-size: 1.8rem;
        font-weight: 700;
        border-left: 5px solid #2563eb;
        padding-left: 15px;
    }
    
    .post-content p {
        margin-bottom: 25px;
    }

    .post-content ul, .post-content ol {
        margin-bottom: 25px;
        padding-left: 25px;
        background: #f8fafc;
        padding: 25px 25px 25px 45px;
        border-radius: 10px;
        border-left: 4px solid #94a3b8;
    }
    
    .post-content li {
        margin-bottom: 10px;
    }

    /* Share Box */
    .share-section {
        margin-top: 60px;
        padding-top: 30px;
        border-top: 1px solid #e2e8f0;
        text-align: center;
    }
    
    .share-title {
        font-weight: 700;
        margin-bottom: 20px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.9rem;
    }

    .share-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        margin: 0 10px;
        border-radius: 8px;
        text-decoration: none;
        color: white;
        font-weight: 600;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .share-btn:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
    
    .btn-whatsapp { background: #25D366; }
    .btn-facebook { background: #1877F2; }
    .btn-twitter { background: #1DA1F2; }

    /* Navigation */
    .nav-buttons {
        margin-top: 40px;
        display: flex;
        justify-content: center;
    }
    
    .back-btn {
        display: inline-block;
        padding: 12px 30px;
        background: #0f172a;
        color: white;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        transition: 0.3s;
    }
    .back-btn:hover { background: #334155; transform: translateX(-5px); }

    /* Responsive */
    @media (max-width: 768px) {
        .blog-view-container { margin: 20px; padding: 25px; }
        .post-title { font-size: 2rem; }
        .post-content { font-size: 1rem; }
        .share-btn { width: 100%; margin: 5px 0; justify-content: center; }
    }
</style>

<div class="blog-view-container">
    <?php if ($result->num_rows > 0): ?>
        
        <div class="post-header">
            <div class="post-meta">
                📅 Published on <?= date("F j, Y", strtotime($post['created_at'])) ?>
            </div>
            <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>
        </div>

        <?php if(!empty($post['image'])): ?>
            <div class="post-image-wrapper">
                <img src="<?= $post['image'] ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="post-image">
            </div>
        <?php endif; ?>

        <article class="post-content">
            <?= htmlspecialchars_decode($post['content']) ?>
        </article>

        <div class="share-section">
            <div class="share-title">Share this Article</div>
            <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:10px;">
                <a href="https://wa.me/?text=Read this: <?= "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>" target="_blank" class="share-btn btn-whatsapp">
                    📱 WhatsApp
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>" target="_blank" class="share-btn btn-facebook">
                    📘 Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?= "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>" target="_blank" class="share-btn btn-twitter">
                    🐦 Twitter
                </a>
            </div>
        </div>

        <div class="nav-buttons">
            <a href="blog.php" class="back-btn">← Back to All Articles</a>
        </div>

    <?php else: ?>
        <div style="text-align:center; padding:50px;">
            <h2 style="color:#ef4444;">404 - Article Not Found</h2>
            <p style="color:#64748b; margin-bottom:20px;">The article you are looking for does not exist or has been removed.</p>
            <a href="blog.php" class="back-btn">Go Back Home</a>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
      "particles": {
        "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
        "color": { "value": "#ffffff" },
        "shape": { "type": "circle" },
        "opacity": { "value": 0.3, "random": true },
        "size": { "value": 3, "random": true },
        "line_linked": { "enable": true, "distance": 150, "color": "#a5b4fc", "opacity": 0.2, "width": 1 },
        "move": { "enable": true, "speed": 2, "direction": "none", "random": true, "out_mode": "out" }
      },
      "interactivity": {
        "detect_on": "window",
        "events": {
          "onhover": { "enable": true, "mode": "grab" },
          "onclick": { "enable": true, "mode": "push" }
        },
        "modes": {
          "grab": { "distance": 180, "line_linked": { "opacity": 0.6 } }
        }
      },
      "retina_detect": true
    });
</script>

<?php include './footer.php'; ?>