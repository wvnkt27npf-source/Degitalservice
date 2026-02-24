<?php
include './config.php';

if (isset($_GET['service_id'])) {
    $service_id = $_GET['service_id'];

    // Query to fetch assigned documents for the given service ID
    $query = "SELECT d.document_name 
              FROM required_documents d 
              JOIN service_document_assignments sda 
              ON d.id = sda.document_id 
              WHERE sda.service_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If no documents found, return message
    if ($result->num_rows === 0) {
        echo "No documents assigned to this service.";
    } else {
        // Return documents as an <li> list
        while ($row = $result->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($row['document_name']) . "</li>";
        }
    }
}
?>
