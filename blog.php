<?php
// Page ka Title Set karein (Header include hone se pehle)
$page_title = "Latest Blog & Insights | Digital Services";
$page_desc = "Read our latest articles on Web Development, SEO trends, and Digital Marketing tips to grow your business.";

include './config.php';
include './header.php';
?>

<style>
    /* Inline CSS for Blog Page */
    .blog-header {
        background: #111827;
        color: white;
        text-align: center;
        padding: 60px 20px;
        margin-bottom: 40px;
    }
    .blog-header h1 { font-size: 2.5rem; margin-bottom: 10px; }
    .blog-header p { font-size: 1.1rem; color: #9ca3af; }

    .blog-container {
        max-width: 1200px;
        margin: 0 auto 60px;
        padding: 0 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    .blog-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .blog-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .blog-thumb {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .blog-content { padding: 20px; }
    .blog-date { font-size: 0.85rem; color: #6b7280; margin-bottom: 10px; display: block; }
    .blog-title {
        font-size: 1.25rem;
        margin-bottom: 10px;
        line-height: 1.4;
        color: #111827;
    }
    .blog-title a { text-decoration: none; color: inherit; }
    
    .blog-excerpt {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .read-more-btn {
        display: inline-block;
        padding: 8px 20px;
        background: #2563eb;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-weight: 500;
        transition: background 0.2s;
    }
    .read-more-btn:hover { background: #1d4ed8; }
</style>

<div class="blog-header">
    <h1>Our Latest Insights</h1>
    <p>Tips, Trends, and Guides for your Digital Success</p>
</div>

<div class="blog-container">
    <?php
    $sql = "SELECT * FROM blogs ORDER BY created_at DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Agar image nahi hai toh default placeholder use karein
            $image = !empty($row['image']) ? $row['image'] : 'https://via.placeholder.com/400x250?text=Digital+Service';
            // Strip tags taaki HTML code excerpt mein na dikhe
            $excerpt = substr(strip_tags($row['content']), 0, 120) . "...";
            $date = date("F j, Y", strtotime($row['created_at']));
            $link = "blog_view.php?slug=" . $row['slug'];
    ?>
        <article class="blog-card">
            <a href="<?= $link ?>">
                <img src="<?= $image ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="blog-thumb">
            </a>
            <div class="blog-content">
                <span class="blog-date">📅 <?= $date ?></span>
                <h2 class="blog-title"><a href="<?= $link ?>"><?= htmlspecialchars($row['title']) ?></a></h2>
                <p class="blog-excerpt"><?= $excerpt ?></p>
                <a href="<?= $link ?>" class="read-more-btn">Read More →</a>
            </div>
        </article>
    <?php 
        }
    } else {
        echo "<p style='text-align:center; width:100%;'>No blog posts found yet. Check back soon!</p>";
    }
    ?>
</div>

<?php include 'footer.php'; ?>