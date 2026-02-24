<?php
session_start();
include './config.php';

if (isset($_POST['import']) && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];

    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("File upload failed with error code: " . $file['error']);
    }

    // Check if the file is a CSV or TXT
    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if ($file_ext !== 'csv' && $file_ext !== 'txt') {
        die("Please upload a CSV or TXT file.");
    }

    // Open the uploaded file
    if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
        // Skip the header row if present
        fgetcsv($handle);

        // Loop through each row and insert/update data in the database
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Assuming CSV format: ID, Service Name, Category, Price, SEO Title, SEO Description, SEO Keywords, Image Path
            $service_name = $conn->real_escape_string($data[1]);
            $category_name = $conn->real_escape_string($data[2]);
            $price = $conn->real_escape_string($data[3]);
            $seo_title = $conn->real_escape_string($data[4]);
            $seo_description = $conn->real_escape_string($data[5]);
            $seo_keywords = $conn->real_escape_string($data[6]);
            $image_path = $conn->real_escape_string($data[7]);

            // First, check if the service already exists in the database by name
            $query_check = "SELECT id FROM services WHERE name = '$service_name' LIMIT 1";
            $result = $conn->query($query_check);

            if ($result->num_rows > 0) {
                // If the service already exists, update the existing record
                $existing_service = $result->fetch_assoc();
                $service_id = $existing_service['id'];

                // Update the service
                $update_query = "UPDATE services 
                                 SET category_id = (SELECT id FROM categories WHERE name = '$category_name' LIMIT 1),
                                     price = '$price',
                                     seo_title = '$seo_title',
                                     seo_description = '$seo_description',
                                     seo_keywords = '$seo_keywords',
                                     image = '$image_path' 
                                 WHERE id = $service_id";

                if (!$conn->query($update_query)) {
                    echo "Error updating service: " . $conn->error;
                }
            } else {
                // If the service doesn't exist, insert a new service
                $insert_query = "INSERT INTO services (name, category_id, price, seo_title, seo_description, seo_keywords, image) 
                                 VALUES ('$service_name', (SELECT id FROM categories WHERE name = '$category_name' LIMIT 1), '$price', '$seo_title', '$seo_description', '$seo_keywords', '$image_path')";
                
                if (!$conn->query($insert_query)) {
                    echo "Error inserting service: " . $conn->error;
                }
            }
        }

        // Close the file
        fclose($handle);
    }

    echo "Bulk import completed!";
}
?>