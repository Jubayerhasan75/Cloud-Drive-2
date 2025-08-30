<?php
$conn = new mysqli("localhost", "root", "", "file_repo");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>