<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$file_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$force = isset($_GET['force']) && $_GET['force'] === '1';

if ($file_id <= 0) {
    $_SESSION['error'] = 'Invalid file';
    header('Location: dashboard.php');
    exit;
}

if ($force) {
    // Permanent delete: remove physical file (safely) and DB row
    $stmt = $conn->prepare("SELECT filepath FROM files WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($filepath);
    if ($stmt->fetch()) {
        $stmt->close();
        // Safe unlink: ensure file is inside uploads directory
        if ($filepath) {
            $base = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads');
            $real = realpath($filepath);
            if ($real && $base && strpos($real, $base) === 0 && file_exists($real)) {
                @unlink($real);
            }
        }
        $del = $conn->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
        $del->bind_param("ii", $file_id, $user_id);
        $del->execute();
        $del->close();
        $_SESSION['success'] = 'File permanently deleted';
    } else {
        $stmt->close();
        $_SESSION['error'] = 'File not found';
    }
    header('Location: dashboard.php?trash=1');
    exit;
} else {
    // Soft delete: mark deleted_at
    $stmt = $conn->prepare("UPDATE files SET deleted_at = NOW() WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
    $stmt->bind_param("ii", $file_id, $user_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = 'Moved to Trash';
        } else {
            $_SESSION['error'] = 'File not found or already in Trash';
        }
    } else {
        $_SESSION['error'] = 'Failed to delete file';
    }
    $stmt->close();
    header('Location: dashboard.php');
    exit;
}
?>