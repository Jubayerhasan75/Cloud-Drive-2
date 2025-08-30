<?php
session_start();
include 'db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$file_id = isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0;

if ($file_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file id']);
    exit;
}

/* Ensure file exists */
$stmt = $conn->prepare("SELECT id FROM files WHERE id = ?");
$stmt->bind_param("i", $file_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'File not found']);
    exit;
}
$stmt->close();

/* Check if already favorited */
$stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND file_id = ?");
$stmt->bind_param("ii", $user_id, $file_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($fav_id);
    $stmt->fetch();
    $stmt->close();
    $del = $conn->prepare("DELETE FROM favorites WHERE id = ?");
    $del->bind_param("i", $fav_id);
    if ($del->execute()) {
        echo json_encode(['status' => 'removed', 'message' => 'Removed from favorites']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove favorite']);
    }
    $del->close();
    exit;
}
$stmt->close();

/* Add to favorites */
$ins = $conn->prepare("INSERT INTO favorites (user_id, file_id) VALUES (?, ?)");
$ins->bind_param("ii", $user_id, $file_id);
if ($ins->execute()) {
    echo json_encode(['status' => 'added', 'message' => 'Added to favorites']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add favorite']);
}
$ins->close();
?>