<?php
// Database configuration
$host = "localhost"; 
$username = "root";  
$password = "";    
$dbname = "u917342879_maison";
// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);
// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
