<?php
include './config.php';

if (isset($_GET['service_id'])) {
    $service_id = $_GET['service_id'];

    // Fetch documents related to the service
    $documents_query = "SELECT d.document_name
                        FROM service_document_assignments a
                        JOIN required_documents d ON a.document_id = d.id
                        WHERE a.service_id = ?";
    $stmt = $conn->prepare($documents_query);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }

    // Return the documents as a JSON response
    echo json_encode(['documents' => $documents]);
}
?>
