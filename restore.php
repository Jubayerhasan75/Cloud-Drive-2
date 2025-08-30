<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$file_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$redirect_folder = isset($_POST['folder_id']) ? (int)$_POST['folder_id'] : 0;

if ($file_id <= 0) {
    $_SESSION['error'] = 'Invalid file';
    header('Location: dashboard.php?trash=1');
    exit;
}

$stmt = $conn->prepare("UPDATE files SET deleted_at = NULL WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL");
$stmt->bind_param("ii", $file_id, $user_id);
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = 'File restored';
    } else {
        $_SESSION['error'] = 'File not found or not in Trash';
    }
} else {
    $_SESSION['error'] = 'Failed to restore file';
}
$stmt->close();

// Redirect back: to folder if provided, otherwise to trash view
if ($redirect_folder > 0) {
    header('Location: dashboard.php?folder_id=' . $redirect_folder);
} else {
    header('Location: dashboard.php?trash=1');
}
exit;
?>