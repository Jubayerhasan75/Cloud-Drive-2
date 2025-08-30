<?php
session_start();
include 'db.php';
$user_id = $_SESSION['user_id'];
$filename = $_POST['filename'];
$folder_id = ($_POST['folder_id'] === '') ? null : (int)$_POST['folder_id'];
$file = $_FILES['file'];

// Create user-specific directory
$user_dir = "uploads/user_$user_id/";
if (!is_dir($user_dir)) {
    mkdir($user_dir, 0777, true);
}

// Create folder-specific directory if selected
if ($folder_id) {
    $folder_dir = $user_dir . "folder_$folder_id/";
    if (!is_dir($folder_dir)) {
        mkdir($folder_dir, 0777, true);
    }
    $target_dir = $folder_dir;
} else {
    $target_dir = $user_dir;
}

// Handle filename conflicts
$original_name = basename($file["name"]);
$file_ext = pathinfo($original_name, PATHINFO_EXTENSION);
$file_base = pathinfo($original_name, PATHINFO_FILENAME);
$counter = 1;

while (file_exists($target_dir . $original_name)) {
    $original_name = $file_base . '_' . $counter . '.' . $file_ext;
    $counter++;
}

$filepath = $target_dir . $original_name;

if (move_uploaded_file($file["tmp_name"], $filepath)) {
    $file_size = filesize($filepath);
    $file_type = mime_content_type($filepath);
    
    $sql = "INSERT INTO files (user_id, folder_id, filename, filepath, file_size, file_type) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissis", $user_id, $folder_id, $filename, $filepath, $file_size, $file_type);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "File '$filename' uploaded successfully!";
    } else {
        $_SESSION['error'] = "Error saving file to database.";
    }
} else {
    $_SESSION['error'] = "Error uploading file. Please try again.";
}

header("Location: dashboard.php");
exit;
?>