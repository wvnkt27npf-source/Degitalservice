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

$userId = $_SESSION['user_id'];

// Query to fetch services user has ordered and document count
$stmt = $conn->prepare("
    SELECT 
        s.id AS service_id,
        s.name AS service_name,
        s.image AS service_image,
        (
            SELECT COUNT(DISTINCT rd.id)
            FROM service_document_assignments sda
            JOIN required_documents rd ON sda.document_id = rd.id
            WHERE sda.service_id = s.id
        ) AS document_count
    FROM services s
    JOIN orders o ON s.id = o.service_id
    WHERE o.user_id = ?
    GROUP BY s.id, s.name, s.image
    HAVING document_count > 0
    ORDER BY s.name ASC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Ordered Services</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            max-width: 1200px;
            width: 95%;
            margin: 40px auto;
            padding: 30px;
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            border: 1px solid #e5e7eb;
        }

        h1 {
            text-align: center;
            margin-bottom: 40px;
            font-size: 32px;
            font-weight: 700;
            color: #111827;
        }

        /* Grid Layout */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        /* Service Card */
        .service-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: #d1d5db;
        }

        .service-image {
            width: 100%;
            height: 160px;
            object-fit: contain;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        h3 {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 10px;
        }

        p {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* Button */
        .btn {
            display: inline-block;
            background-color: #4f46e5; /* Indigo */
            color: white;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
            width: 100%;
        }

        .btn:hover {
            background-color: #4338ca;
            transform: translateY(-1px);
        }

        /* Empty State */
        .empty-message {
            text-align: center;
            font-size: 18px;
            color: #9ca3af;
            margin-top: 50px;
            padding: 40px;
            background: #f9fafb;
            border-radius: 12px;
            border: 1px dashed #d1d5db;
        }

        @media (max-width: 768px) {
            .container { padding: 20px; margin: 20px auto; }
            h1 { font-size: 24px; margin-bottom: 30px; }
            .services-grid { grid-template-columns: 1fr; gap: 20px; }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Your Ordered Services</h1>

    <?php if ($result->num_rows > 0): ?>
        <div class="services-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="service-card">
                    <img 
                        src="<?= htmlspecialchars($row['service_image']) ?>" 
                        alt="<?= htmlspecialchars($row['service_name']) ?>" 
                        class="service-image"
                        loading="lazy"
                    >
                    <h3><?= htmlspecialchars($row['service_name']) ?></h3>
                    <p>Required Documents: <span style="color:#4f46e5; font-weight:700;"><?= htmlspecialchars($row['document_count']) ?></span></p>
                    <a href="upload_documents.php?service_id=<?= $row['service_id'] ?>" class="btn">Upload Documents</a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-message">
            <p>No ordered services found requiring documents.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

<?php $stmt->close(); ?>