<?php
// Is file ko XML ki tarah browser treat kare
header("Content-Type: application/xml; charset=utf-8");

include './config.php';

// Base URL (Apni website ka naam yahan likhein, last mein slash '/' zaroori hai)
$base_url = "https://degitalservice.com/";

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <url>
        <loc><?= $base_url; ?></loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= $base_url; ?>index.php</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= $base_url; ?>blog.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= $base_url; ?>category.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= $base_url; ?>login.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?= $base_url; ?>register.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <?php
    $sql = "SELECT slug, created_at FROM blogs ORDER BY created_at DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Slug se URL banayein
            $url = $base_url . "blog_view.php?slug=" . $row['slug'];
            // Date format karein (YYYY-MM-DD)
            $date = date("Y-m-d", strtotime($row['created_at']));
    ?>
    <url>
        <loc><?= $url ?></loc>
        <lastmod><?= $date ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php 
        }
    }
    ?>

    </urlset>