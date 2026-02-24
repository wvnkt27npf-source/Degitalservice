<?php
session_start();
include './config.php';
include './header.php';

// Check Admin Access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }

// --- DELETE POST ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM blogs WHERE id=$id");
    echo "<script>alert('Post Deleted!'); window.location='admin_blog.php';</script>";
}

// --- ADD / EDIT POST ---
$edit_mode = false;
$post_data = ['title'=>'', 'slug'=>'', 'content'=>'', 'meta_title'=>'', 'meta_desc'=>'', 'meta_keywords'=>''];

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $res = $conn->query("SELECT * FROM blogs WHERE id=$id");
    $post_data = $res->fetch_assoc();
}

if (isset($_POST['save_post'])) {
    $title = $conn->real_escape_string($_POST['title']);
    // Slug generation: spaces ko dash (-) se replace karein
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $content = $conn->real_escape_string($_POST['content']);
    $meta_title = $_POST['meta_title'];
    $meta_desc = $_POST['meta_desc'];
    $meta_keywords = $_POST['meta_keywords'];
    
    // Image Upload
    $image_path = $post_data['image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $target = "uploads/blog_" . time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
        $image_path = $target;
    }

    if ($edit_mode) {
        $id = $_POST['post_id'];
        $sql = "UPDATE blogs SET title='$title', slug='$slug', image='$image_path', content='$content', meta_title='$meta_title', meta_desc='$meta_desc', meta_keywords='$meta_keywords' WHERE id=$id";
    } else {
        $sql = "INSERT INTO blogs (title, slug, image, content, meta_title, meta_desc, meta_keywords) VALUES ('$title', '$slug', '$image_path', '$content', '$meta_title', '$meta_desc', '$meta_keywords')";
    }
    
    if($conn->query($sql)){
        echo "<script>alert('Blog Post Saved Successfully!'); window.location='admin_blog.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}
?>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
    <div style="max-width:1000px; margin:30px auto; padding:20px; background:white; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:2px solid #eee; padding-bottom:15px;">
        <h2 style="color:#111827; margin:0;">📝 Manage Blog Posts</h2>
        <?php if($edit_mode): ?>
            <a href="admin_blog.php" style="background:#6b7280; color:white; text-decoration:none; padding:8px 15px; border-radius:5px;">Cancel Edit</a>
        <?php endif; ?>
    </div>

    <form method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
        
        <div style="display:flex; flex-direction:column; gap:15px;">
            <input type="hidden" name="post_id" value="<?= $post_data['id'] ?? '' ?>">
            
            <div>
                <label style="font-weight:bold;">Article Title:</label>
                <input type="text" name="title" value="<?= htmlspecialchars($post_data['title']) ?>" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
            </div>

            <div>
                <label style="font-weight:bold;">Content (HTML supported):</label>
                <textarea name="content" rows="15" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; font-family:sans-serif;"><?= htmlspecialchars($post_data['content']) ?></textarea>
                <small style="color:gray;">Tips: Use &lt;h2&gt; for headings, &lt;p&gt; for paragraphs.</small>
            </div>
        </div>

        <div style="background:#f9fafb; padding:15px; border-radius:8px; border:1px solid #e5e7eb; display:flex; flex-direction:column; gap:15px;">
            <h4 style="margin-top:0; color:#2563eb;">SEO Settings</h4>
            
            <div>
                <label style="font-weight:bold; font-size:13px;">Featured Image:</label>
                <input type="file" name="image" style="font-size:13px;">
                <?php if(!empty($post_data['image'])): ?>
                    <img src="<?= $post_data['image'] ?>" style="width:100%; height:auto; margin-top:5px; border-radius:5px;">
                <?php endif; ?>
            </div>

            <div>
                <label style="font-weight:bold; font-size:13px;">Meta Title:</label>
                <input type="text" name="meta_title" value="<?= $post_data['meta_title'] ?>" placeholder="SEO Title" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px;">
            </div>

            <div>
                <label style="font-weight:bold; font-size:13px;">Meta Description:</label>
                <textarea name="meta_desc" rows="3" placeholder="Short summary for Google..." style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px;"><?= $post_data['meta_desc'] ?></textarea>
            </div>

            <div>
                <label style="font-weight:bold; font-size:13px;">Keywords:</label>
                <input type="text" name="meta_keywords" value="<?= $post_data['meta_keywords'] ?>" placeholder="seo, tips, guide" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px;">
            </div>

            <button type="submit" name="save_post" style="background:#2563eb; color:white; border:none; padding:12px; border-radius:5px; font-weight:bold; cursor:pointer; margin-top:10px;">
                <?= $edit_mode ? 'Update Post' : 'Publish Post' ?>
            </button>
        </div>
    </form>

    <h3 style="margin-top:40px; border-top:2px solid #eee; padding-top:20px;">📚 Existing Posts</h3>
    <table style="width:100%; border-collapse:collapse; margin-top:10px;">
        <tr style="background:#f3f4f6; text-align:left;">
            <th style="padding:10px; border:1px solid #ddd;">Image</th>
            <th style="padding:10px; border:1px solid #ddd;">Title</th>
            <th style="padding:10px; border:1px solid #ddd;">Date</th>
            <th style="padding:10px; border:1px solid #ddd;">Actions</th>
        </tr>
        <?php
        $posts = $conn->query("SELECT * FROM blogs ORDER BY created_at DESC");
        while($row = $posts->fetch_assoc()):
        ?>
        <tr>
            <td style="padding:10px; border:1px solid #ddd; width:80px;">
                <?php if($row['image']): ?><img src="<?= $row['image'] ?>" width="60" style="border-radius:4px;"><?php endif; ?>
            </td>
            <td style="padding:10px; border:1px solid #ddd;">
                <strong><?= htmlspecialchars($row['title']) ?></strong><br>
                <small style="color:gray;">/<?= $row['slug'] ?></small>
            </td>
            <td style="padding:10px; border:1px solid #ddd; font-size:13px;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
            <td style="padding:10px; border:1px solid #ddd;">
                <a href="blog_view.php?slug=<?= $row['slug'] ?>" target="_blank" style="color:#10b981; text-decoration:none; margin-right:10px; font-weight:bold;">View |</a>
                
                <a href="admin_blog.php?edit=<?= $row['id'] ?>" style="color:#2563eb; text-decoration:none; margin-right:10px;">Edit |</a>
                <a href="admin_blog.php?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')" style="color:#dc2626; text-decoration:none;">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php include 'footer.php'; ?>