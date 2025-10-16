<?php
// db.php - single place to manage DB connection

// 1. Host and Port: Use 127.0.0.1 (IP) and explicitly set the non-standard port 3307.
$DB_HOST = "127.0.0.1"; 
$DB_PORT = 3307; 

// 2. User Credentials: Use the empty password ('') as per your phpMyAdmin config.
$DB_USER = "root";
$DB_PASS = ""; 

// 3. Database Name
$DB_NAME = "db_icsr_assessment_manuela";

// Attempt the connection using the $DB_PORT parameter
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT); 

// CRITICAL: Robust connection error check
if ($mysqli->connect_errno) {
    // Set a 500 status code to indicate a server-side error
    http_response_code(500); 
    
    // Display the error. Note the specific message to help diagnose the problem.
    die("❌ **DB Connection Failed** - Check XAMPP/WAMP Status and Port ($DB_PORT). Error: (" . 
        $mysqli->connect_errno . ") " . 
        htmlspecialchars($mysqli->connect_error)
    );
}

// Ensure proper character encoding for database interactions
$mysqli->set_charset("utf8mb4");

// If the script reaches this point, $mysqli is a valid connection object.
?>