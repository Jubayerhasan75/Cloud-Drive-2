<?php
session_start();
include 'db.php';
$user_id = $_SESSION['user_id'];
$type = $_POST['type']; // 'file' or 'folder'
$id = $_POST['id'];
$new_name = $_POST['new_name'];

if ($type === 'folder') {
    $sql = "UPDATE folders SET folder_name=? WHERE id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $new_name, $id, $user_id);
} else {
    $sql = "UPDATE files SET filename=? WHERE id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $new_name, $id, $user_id);
}

if ($stmt->execute()) {
    $_SESSION['success'] = ucfirst($type) . " renamed successfully to '$new_name'!";
} else {
    $_SESSION['error'] = "Error renaming $type. Please try again.";
}

header("Location: dashboard.php");
exit;
?>