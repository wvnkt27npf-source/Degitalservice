<?php
session_start();
include './config.php';

// 1. SABSE PEHLE: Admin check aur Redirect Logic (HTML se pehle)

// Ensure the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Handle the form submission to update the download link
$error_message = ""; // Variable initialize karein
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $service_id = $_POST['service_id'];
    $download_link = $_POST['download_link'];

    // Validate the input data (ensure valid URL)
    if (filter_var($download_link, FILTER_VALIDATE_URL)) {
        // Update the service with the new download link
        $stmt_update = $conn->prepare("UPDATE services SET download_link = ? WHERE id = ?");
        $stmt_update->bind_param("si", $download_link, $service_id);

        if ($stmt_update->execute()) {
            // Success! Ab redirect karein (Kyunki abhi tak koi HTML print nahi hua hai)
            header("Location: admin_set_download_link.php?success=1");
            exit;
        } else {
            $error_message = "Error updating the download link.";
        }
    } else {
        $error_message = "Please enter a valid download link.";
    }
}

// 2. AB HTML WALI FILES INCLUDE KAREIN
include './header.php';

// Fetch services with download links (non-empty download_link)
$stmt = $conn->prepare("SELECT id, name, download_link FROM services WHERE download_link IS NOT NULL AND download_link != ''");
$stmt->execute();
$services = $stmt->get_result();

// Fetch services without download links for selection
$stmt_no_link = $conn->prepare("SELECT id, name FROM services WHERE download_link IS NULL OR download_link = ''");
$stmt_no_link->execute();
$services_no_link = $stmt_no_link->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services with Download Links</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* General Reset & Fonts */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            line-height: 1.6;
        }

        .container {
            max-width: 1100px;
            width: 95%;
            margin: 30px auto;
            padding: 25px;
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        h2, h3 {
            text-align: center;
            font-weight: 600;
            color: #111827;
        }
        h2 { font-size: 26px; margin-bottom: 20px; }
        h3 { font-size: 20px; margin-top: 30px; margin-bottom: 15px; text-align: left; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }

        /* Messages */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }
        .message.error { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .message.success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            color: white;
            transition: 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn:hover { transform: translateY(-1px); }

        .btn-primary { background-color: #4f46e5; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); margin-bottom: 20px; } 
        .btn-primary:hover { background-color: #4338ca; }

        .edit-btn { background-color: #f59e0b; padding: 6px 12px; font-size: 12px; }
        .edit-btn:hover { background-color: #d97706; }

        .delete-btn { background-color: #ef4444; padding: 6px 12px; font-size: 12px; }
        .delete-btn:hover { background-color: #dc2626; }

        /* Table Styles */
        .table-responsive { overflow-x: auto; border-radius: 8px; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; background-color: white; min-width: 600px; }

        th, td {
            padding: 14px;
            text-align: left;
            border: 1px solid #d1d5db;
            font-size: 14px;
            vertical-align: middle;
        }

        th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.05em;
        }

        tr:hover td { background-color: #f9fafb; }
        
        /* Links in table */
        td a.link { color: #2563eb; text-decoration: none; }
        td a.link:hover { text-decoration: underline; }

        /* Popup Overlay */
        .popup-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(3px);
        }

        .popup-content {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            position: relative;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

        .popup-content h3 { margin-top: 0; text-align: center; border: none; font-size: 22px; }

        label { display: block; margin-bottom: 8px; font-weight: 500; color: #374151; }
        
        input[type="url"], select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background-color: #f9fafb;
        }
        input:focus, select:focus { outline: 2px solid #4f46e5; border-color: transparent; background-color: white; }

        .popup-actions { display: flex; justify-content: flex-end; gap: 10px; }

        .btn-save { background-color: #10b981; width: 100%; margin-bottom: 10px; }
        .btn-save:hover { background-color: #059669; }

        .btn-close { background-color: #6b7280; width: 100%; }
        .btn-close:hover { background-color: #4b5563; }

    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
<div class="container">
    <h2>Manage Services with Download Links</h2>

    <?php if (!empty($error_message)): ?>
        <div class="message error"><?php echo $error_message; ?></div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="message success">Download link updated successfully!</div>
    <?php endif; ?>

    <div style="text-align: center;">
        <button id="showPopupBtn" class="btn btn-primary">+ Set Custom Download Link</button>
    </div>

    <div class="popup-overlay" id="popupOverlay">
        <div class="popup-content">
            <h3>Set Download Link</h3>
            <form action="admin_set_download_link.php" method="POST">
                <label for="service_id">Select Service:</label>
                <select name="service_id" id="service_id" required>
                    <option value="">-- Choose a Service --</option>
                    <?php while ($service = $services_no_link->fetch_assoc()): ?>
                        <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="download_link">Custom Download Link:</label>
                <input type="url" name="download_link" id="download_link" required placeholder="https://example.com/file.zip">

                <button type="submit" class="btn btn-save">Save Link</button>
                <button type="button" id="closePopupBtn" class="btn btn-close">Cancel</button>
            </form>
        </div>
    </div>

    <h3>Existing Services with Download Links</h3>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Service Name</th>
                    <th>Download Link</th>
                    <th style="width: 150px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($services->num_rows > 0): ?>
                    <?php while ($service = $services->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($service['name']); ?></td>
                            <td><a href="<?php echo htmlspecialchars($service['download_link']); ?>" target="_blank" class="link"><?php echo htmlspecialchars($service['download_link']); ?></a></td>
                            <td style="text-align: center;">
                                <a href="edit_download_link.php?id=<?php echo $service['id']; ?>" class="btn edit-btn">Edit</a>
                                <a href="delete_download_link.php?id=<?php echo $service['id']; ?>" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this download link?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align:center; color: #6b7280;">No services with download links found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Show popup
    document.getElementById('showPopupBtn').addEventListener('click', function() {
        document.getElementById('popupOverlay').style.display = 'flex';
    });

    // Close popup
    document.getElementById('closePopupBtn').addEventListener('click', function() {
        document.getElementById('popupOverlay').style.display = 'none';
    });
    
    // Close when clicking outside
    document.getElementById('popupOverlay').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
</script>

</body>
</html>
<?php include 'footer.php'; ?>