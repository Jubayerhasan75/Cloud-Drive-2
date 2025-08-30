<?php
session_start();
include 'db.php';
$user_id = $_SESSION['user_id'];
$folder_id = $_GET['id'];

// Get all files in this folder
$sql1 = "SELECT filepath FROM files WHERE folder_id=? AND user_id=?";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("ii", $folder_id, $user_id);
$stmt1->execute();
$result = $stmt1->get_result();

// Delete files from server
while ($row = $result->fetch_assoc()) {
    if (file_exists($row['filepath'])) {
        unlink($row['filepath']);
    }
}

// Delete folder directory
$folder_dir = "uploads/user_$user_id/folder_$folder_id/";
if (is_dir($folder_dir)) {
    // Remove all files in directory
    $files = glob($folder_dir . '*');
    foreach ($files as $file) {
        if (is_file($file)) unlink($file);
    }
    // Remove directory
    rmdir($folder_dir);
}

// Delete from database
$del_files = $conn->prepare("DELETE FROM files WHERE folder_id=? AND user_id=?");
$del_files->bind_param("ii", $folder_id, $user_id);
$del_files->execute();

$del_folder = $conn->prepare("DELETE FROM folders WHERE id=? AND user_id=?");
$del_folder->bind_param("ii", $folder_id, $user_id);
$del_folder->execute();

header("Location: dashboard.php");
exit;
?>