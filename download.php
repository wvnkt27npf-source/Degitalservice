<?php
// Check if file parameter is provided in the URL
if (isset($_GET['file'])) {
    // Decode the file name (handle URL-encoded characters like %20 for space)
    $file_name = urldecode($_GET['file']);
    
    // Define the path to the uploads folder
    $file_path = './uploads/' . $file_name;

    // Check if the file exists
    if (file_exists($file_path)) {
        // Set headers to force download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));

        // Read and output the file content
        readfile($file_path);
        exit; // Terminate the script after sending the file
    } else {
        // If file doesn't exist, show an error message
        echo "Error: File not found!";
    }
} else {
    // If no file parameter is specified, show an error
    echo "Error: No file specified!";
}
?>
