<?php
session_start();
include 'db.php';
$user_id = $_SESSION['user_id'];
$folder_name = $_POST['folder_name'];
$parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

$sql = "INSERT INTO folders (user_id, folder_name, parent_id) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isi", $user_id, $folder_name, $parent_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Folder '$folder_name' created successfully!";
} else {
    $_SESSION['error'] = "Folder already exists or could not be created.";
}

header("Location: dashboard.php" . ($parent_id ? "?folder_id=$parent_id" : ""));
exit;
?>