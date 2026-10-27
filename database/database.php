<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "tailor_swift_db";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
