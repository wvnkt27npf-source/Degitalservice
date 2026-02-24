<?php
session_start();
include './config.php';

// Authentication Check
$loggedInUserRoles = ['user', 'client', 'customer'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? 'guest', $loggedInUserRoles)) {
    header("Location: login.php");
    exit();
}

include 'header.php';

// Handle custom request submission
$message = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $description = trim($_POST['description']);

    if (!empty($description)) {
        $stmt = $conn->prepare("INSERT INTO custom_requests (user_id, request_description, status) VALUES (?, ?, 'Pending')");
        $stmt->bind_param("is", $user_id, $description);

        if ($stmt->execute()) {
            $message = 'Custom request submitted successfully!';
            $msg_type = "success";
        } else {
            $message = 'Failed to submit custom request!';
            $msg_type = "error";
        }
    } else {
        $message = "Please enter a description.";
        $msg_type = "error";
    }
}

// Fetch user's custom requests
$user_id = $_SESSION['user_id'];
$requests = $conn->query("SELECT * FROM custom_requests WHERE user_id = $user_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Requests</title>
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
            max-width: 1000px;
            width: 95%;
            margin: 30px auto;
            padding: 25px;
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        h2, h3 {
            color: #111827;
            font-weight: 600;
        }
        h2 { text-align: center; margin-bottom: 25px; font-size: 26px; }
        h3 { font-size: 20px; margin-top: 40px; margin-bottom: 15px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }

        /* Message Box */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }
        .message.success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .message.error { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

        /* Form Styles */
        form {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            min-height: 100px;
            margin-bottom: 15px;
            background-color: white;
        }
        textarea:focus { outline: 2px solid #4f46e5; border-color: transparent; }

        button.button {
            padding: 10px 20px;
            background-color: #4f46e5; /* Indigo */
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        button.button:hover { background-color: #4338ca; transform: translateY(-1px); }

        /* Table Styles */
        .table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            min-width: 700px;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border: 1px solid #d1d5db;
            font-size: 14px;
            vertical-align: top;
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

        /* Links and Badges */
        a { color: #2563eb; text-decoration: none; font-weight: 500; }
        a:hover { text-decoration: underline; }

        .status-text {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            background-color: #f3f4f6;
            color: #374151;
        }

        @media (max-width: 768px) {
            .container { padding: 15px; margin: 15px auto; }
            th, td { padding: 10px; }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Submit Custom Request</h2>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST">
        <textarea name="description" placeholder="Describe your request here..." required></textarea>
        <button type="submit" class="button">Submit Request</button>
    </form>

    <h3>Your Custom Requests</h3>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Description</th>
                    <th style="width: 100px;">Status</th>
                    <th>Admin Response</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $requests->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= nl2br(htmlspecialchars($row['request_description'])) ?></td>
                    <td><span class="status-text"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td>
                        <?php if ($row['admin_message']): ?>
                            <div style="margin-bottom:5px;"><strong>Msg:</strong> <?= htmlspecialchars($row['admin_message']) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($row['response_link']): ?>
                            <div style="margin-bottom:5px;"><strong>Link:</strong> <a href="<?= htmlspecialchars($row['response_link']) ?>" target="_blank">Open Link</a></div>
                        <?php endif; ?>
                        
                        <?php if ($row['response_file']): ?>
                            <div><strong>File:</strong> <a href="download.php?file=<?= urlencode($row['response_file']) ?>" target="_blank">Download</a></div>
                        <?php endif; ?>

                        <?php if (empty($row['admin_message']) && empty($row['response_link']) && empty($row['response_file'])): ?>
                            <span style="color:#9ca3af; font-size:13px;">Pending response...</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if ($requests->num_rows == 0): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #6b7280; padding: 20px;">You have not made any custom requests yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php include 'footer.php'; ?>