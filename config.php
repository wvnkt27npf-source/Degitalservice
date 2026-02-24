<?php
// digitelservice/config.php
$host = 'localhost';
$user = 'degitals_service';
$pass = 'degitals_service';
$dbname = 'degitals_service';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

define('PHONEPE_MERCHANT_ID', 'M23AREFEEG85G'); 
define('PHONEPE_SALT_KEY', '8e88b49a-1b69-4e5d-8784-8d3e4c4f4652'); 
define('PHONEPE_SALT_INDEX', '1'); 
define('PHONEPE_ENV', 'PRODUCTION'); // Change to 'UAT' for testing
?>