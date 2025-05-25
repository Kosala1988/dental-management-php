<?php
$host = $_ENV['MYSQLHOST'] ?? 'mysql.railway.internal';
$user = $_ENV['MYSQLUSER'] ?? 'root';
$pass = $_ENV['MYSQLPASSWORD'] ?? 'tpUIlkAzENJxUIPmnBvHXQumimrQyysD';
$dbname = $_ENV['MYSQLDATABASE'] ?? 'railway';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
