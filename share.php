
<?php
include 'db.php';

if (!isset($_GET['id'])) {
    echo "Invalid share link.";
    exit;
}

$file_id = (int)$_GET['id'];
$file = $conn->query("SELECT * FROM files WHERE id=$file_id")->fetch_assoc();

if (!$file) {
    echo "File not found.";
    exit;
}

// Optional: You can add access control or expiry logic here

$is_image = strpos($file['file_type'], 'image/') === 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shared File: <?php echo htmlspecialchars($file['filename']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1500&q=80') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255,255,255,0.85);
            border-radius: 1rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.18);
            margin-top: 80px;
        }
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
        <div class="glass-card p-4 text-center">
            <h3 class="mb-3 text-primary">Shared File</h3>
            <h5 class="mb-4"><?php echo htmlspecialchars($file['filename']); ?></h5>
            <?php if ($is_image): ?>
                <img src="<?php echo $file['filepath']; ?>" alt="<?php echo htmlspecialchars($file['filename']); ?>" class="preview-image mb-3">
            <?php else: ?>
                <div class="mb-3">
                    <i class="fas fa-file fa-4x text-secondary"></i>
                </div>
            <?php endif; ?>
            <a href="<?php echo $file['filepath']; ?>" download class="btn btn-success">
                <i class="fas fa-download"></i> Download
            </a>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>