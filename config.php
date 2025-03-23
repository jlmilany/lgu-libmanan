<?php
// config.php
$host = 'localhost';
$user = 'root';
$pass = ''; // Update as needed
$dbname = 'office_management';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
   die("Connection failed: " . $conn->connect_error);
}
?>
