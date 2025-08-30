<?php
session_start();
include 'db.php';
$user_id = $_SESSION['user_id'];
$file_id = $_POST['file_id'];
$folder_id = $_POST['folder_id'];
$sql = "UPDATE files SET folder_id=? WHERE id=? AND user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $folder_id, $file_id, $user_id);
$stmt->execute();
header("Location: dashboard.php");
?>