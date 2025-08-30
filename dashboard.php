<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}
include 'db.php';
$user_id = (int)$_SESSION['user_id'];
$current_folder = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$favorite_view = isset($_GET['favorite']) && $_GET['favorite'] == '1';
$trash_view = isset($_GET['trash']) && $_GET['trash'] == '1'; // new

/* Load user's favorite file ids */
$fav_files = [];
$fav_res = $conn->query("SELECT file_id FROM favorites WHERE user_id=" . $user_id);
if ($fav_res) {
    while ($r = $fav_res->fetch_assoc()) {
        $fav_files[(int)$r['file_id']] = true;
    }
}

/* Search behavior: if single match -> redirect to its folder and highlight; if none -> show message */
$no_search_results = false;
if ($q !== '') {
    $like = "%$q%";
    if ($favorite_view) {
        // favorite + search: respect trash_view (unlikely together but handle)
        if ($trash_view) {
            $stmt = $conn->prepare("SELECT f.id, f.folder_id FROM files f JOIN favorites fav ON f.id = fav.file_id WHERE fav.user_id = ? AND f.filename LIKE ? AND f.deleted_at IS NOT NULL");
        } else {
            $stmt = $conn->prepare("SELECT f.id, f.folder_id FROM files f JOIN favorites fav ON f.id = fav.file_id WHERE fav.user_id = ? AND f.filename LIKE ? AND f.deleted_at IS NULL");
        }
        $stmt->bind_param("is", $user_id, $like);
    } else {
        if ($trash_view) {
            $stmt = $conn->prepare("SELECT id, folder_id FROM files WHERE user_id = ? AND filename LIKE ? AND deleted_at IS NOT NULL");
        } else {
            $stmt = $conn->prepare("SELECT id, folder_id FROM files WHERE user_id = ? AND filename LIKE ? AND deleted_at IS NULL");
        }
        $stmt->bind_param("is", $user_id, $like);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $count = $res ? $res->num_rows : 0;

    if ($count === 0) {
        $no_search_results = true;
        $stmt->close();
    } elseif ($count === 1) {
        $row = $res->fetch_assoc();
        $target_folder = isset($row['folder_id']) && $row['folder_id'] !== null ? (int)$row['folder_id'] : null;
        $file_id = (int)$row['id'];
        $stmt->close();

        $params = [];
        if ($target_folder) $params[] = 'folder_id=' . $target_folder;
        if ($favorite_view) $params[] = 'favorite=1';
        if ($trash_view) $params[] = 'trash=1';
        $params[] = 'highlight=' . $file_id;
        $redirect = 'dashboard.php' . (count($params) ? ('?' . implode('&', $params)) : '');
        header("Location: $redirect");
        exit;
    } else {
        $stmt->close();
        // multiple results -> continue render search results
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      /* Unique Dual-Tone Gradient Background */
      background: radial-gradient(circle at 50% 0, #36aee0, #734e9e);
      min-height: 100vh;
      position: relative;
    }
    .overlay {
      background: none;
    }
    .main-content { position: relative; z-index:1; }
    .card {
      /* Enhanced Glassmorphism Effect */
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 1rem;
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
      transition: box-shadow .25s, transform .25s;
    }
    .card:hover {
      box-shadow: 0 8px 40px rgba(0, 0, 0, 0.2);
      transform: translateY(-4px);
    }
    .preview-image { max-width:100%; max-height:140px; object-fit:contain; border-radius:8px; }
    .item-name { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:1.05rem; }
    .favorite-btn { min-width:44px; }
    .highlighted { box-shadow: 0 0 0 4px rgba(255,193,7,0.25) !important; transform: scale(1.02); }
  </style>
</head>
<body>
  <div class="main-content">
    <div class="container py-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 border-bottom pb-2 gap-3">
        <div>
          <h2 class="fw-bold text-light mb-2">Welcome to Your File Manager</h2>
          <form class="d-flex" method="GET" role="search" style="gap:.5rem;">
            <input type="hidden" name="folder_id" value="<?php echo $current_folder ? (int)$current_folder : ''; ?>">
            <?php if ($favorite_view): ?><input type="hidden" name="favorite" value="1"><?php endif; ?>
            <?php if ($trash_view): ?><input type="hidden" name="trash" value="1"><?php endif; ?>
            <input class="form-control" type="search" name="q" placeholder="Search your files..." value="<?php echo htmlspecialchars($q); ?>">
            <button class="btn btn-outline-light" type="submit"><i class="fas fa-search"></i></button>
            <a href="dashboard.php" class="btn btn-outline-light" title="Clear search"><i class="fas fa-times"></i></a>
          </form>
        </div>
        <div class="d-flex align-items-center gap-2">
          
          <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#folderModal"><i class="fas fa-folder-plus"></i> New Folder</button>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal"><i class="fas fa-file-upload"></i> Upload File</button>
          <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
      </div>

      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success text-center"><?php echo $_SESSION['success']; ?></div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger text-center"><?php echo $_SESSION['error']; ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <?php if ($no_search_results): ?>
        <div class="alert alert-warning text-center">No files found matching "<?php echo htmlspecialchars($q); ?>"</div>
      <?php endif; ?>

      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb justify-content-center">
          <li class="breadcrumb-item"><a href="dashboard.php" class="text-light">My Drive</a></li>
          <?php
          if ($current_folder) {
              $folder_result = $conn->query("SELECT folder_name FROM folders WHERE id=$current_folder");
              if ($folder_result && $folder_result->num_rows > 0) {
                  $folder_row = $folder_result->fetch_assoc();
                  echo "<li class='breadcrumb-item active text-white' aria-current='page'>" . htmlspecialchars($folder_row['folder_name']) . "</li>";
              }
          }
          ?>
        </ol>
      </nav>

      <h3 class="text-center text-light mb-3">Your Folders</h3>
      <div class="row g-3 mb-4">
        <?php
        $folder_query = $current_folder
            ? "SELECT * FROM folders WHERE user_id=$user_id AND parent_id=$current_folder"
            : "SELECT * FROM folders WHERE user_id=$user_id AND parent_id IS NULL";
        $folders = $conn->query($folder_query);

        // Single Favorite folder card
        echo "<div class='col-12 col-sm-6 col-md-4 col-lg-3'>";
        echo "<div class='card h-100 border-warning'>";
        echo "<div class='card-body text-center'>";
        echo "<i class='fas fa-star fa-3x text-warning mb-2'></i>";
        echo "<div class='item-name fw-semibold'>Favorite</div>";
        echo "</div>";
        echo "<div class='card-footer d-flex justify-content-around bg-light'>";
        echo "<a href='dashboard.php?favorite=1' class='btn btn-sm btn-outline-primary' title='Open'><i class='fas fa-folder-open'></i></a>";
        echo "</div></div></div>";

        // Trash folder card
        echo "<div class='col-12 col-sm-6 col-md-4 col-lg-3'>";
        echo "<div class='card h-100 border-secondary'>";
        echo "<div class='card-body text-center'>";
        echo "<i class='fas fa-trash-alt fa-3x text-secondary mb-2'></i>";
        echo "<div class='item-name fw-semibold'>Trash</div>";
        echo "</div>";
        echo "<div class='card-footer d-flex justify-content-around bg-light'>";
        echo "<a href='dashboard.php?trash=1' class='btn btn-sm btn-outline-secondary' title='Open'><i class='fas fa-folder-open'></i></a>";
        echo "</div></div></div>";

        if ($folders && $folders->num_rows > 0) {
            while ($folder = $folders->fetch_assoc()) {
              echo "<div class='col-12 col-sm-6 col-md-4 col-lg-3'>";
              echo "<div class='card h-100'>";
              echo "<div class='card-body text-center'>";
              echo "<i class='fas fa-folder fa-3x text-warning mb-2'></i>";
              echo "<div class='item-name fw-semibold'>" . htmlspecialchars($folder['folder_name']) . "</div>";
              echo "</div>";
              echo "<div class='card-footer d-flex justify-content-around bg-light'>";
              echo "<a href='dashboard.php?folder_id=".(int)$folder['id']."' class='btn btn-sm btn-outline-primary' title='Open'><i class='fas fa-folder-open'></i></a>";
              echo "<button class='btn btn-sm btn-outline-secondary' title='Rename' onclick='openRenameFolderModal(".(int)$folder['id'].", \"".addslashes($folder['folder_name'])."\")'><i class='fas fa-edit'></i></button>";
              echo "<a href='delete_folder.php?id=".(int)$folder['id']."' class='btn btn-sm btn-outline-danger' title='Delete'><i class='fas fa-trash'></i></a>";
              echo "</div></div></div>";
            }
        }
        ?>
      </div>

      <h3 class="text-center text-light mt-5 mb-3">
        <?php
          if ($q !== '') {
            echo "Search results for \"" . htmlspecialchars($q) . "\"";
          } else {
            if ($trash_view) echo "Trash";
            elseif ($favorite_view) echo "Your Favorite Files";
            else echo "Your Files";
          }
        ?>
      </h3>
      <div class="row g-3">
        <?php
        // Prepare files result set depending on search / favorite / folder / trash
        $files = null;
        if ($favorite_view) {
            if ($q !== '') {
                if ($trash_view) {
                    $stmt = $conn->prepare("SELECT f.* FROM files f JOIN favorites fav ON f.id=fav.file_id WHERE fav.user_id=? AND f.filename LIKE ? AND f.deleted_at IS NOT NULL ORDER BY fav.created_at DESC");
                } else {
                    $stmt = $conn->prepare("SELECT f.* FROM files f JOIN favorites fav ON f.id=fav.file_id WHERE fav.user_id=? AND f.filename LIKE ? AND f.deleted_at IS NULL ORDER BY fav.created_at DESC");
                }
                $like = "%$q%";
                $stmt->bind_param("is", $user_id, $like);
                $stmt->execute();
                $files = $stmt->get_result();
            } else {
                if ($trash_view) {
                    $files = $conn->query("SELECT f.* FROM files f JOIN favorites fav ON f.id=fav.file_id WHERE fav.user_id=$user_id AND f.deleted_at IS NOT NULL ORDER BY fav.created_at DESC");
                } else {
                    $files = $conn->query("SELECT f.* FROM files f JOIN favorites fav ON f.id=fav.file_id WHERE fav.user_id=$user_id AND f.deleted_at IS NULL ORDER BY fav.created_at DESC");
                }
            }
        } elseif ($q !== '') {
            $like = "%$q%";
            if ($current_folder) {
                if ($trash_view) {
                    $stmt = $conn->prepare("SELECT * FROM files WHERE user_id=? AND folder_id=? AND filename LIKE ? AND deleted_at IS NOT NULL ORDER BY uploaded_at DESC");
                } else {
                    $stmt = $conn->prepare("SELECT * FROM files WHERE user_id=? AND folder_id=? AND filename LIKE ? AND deleted_at IS NULL ORDER BY uploaded_at DESC");
                }
                $stmt->bind_param("iis", $user_id, $current_folder, $like);
                $stmt->execute();
                $files = $stmt->get_result();
            } else {
                if ($trash_view) {
                    $stmt = $conn->prepare("SELECT * FROM files WHERE user_id=? AND filename LIKE ? AND deleted_at IS NOT NULL ORDER BY uploaded_at DESC");
                    $stmt->bind_param("is", $user_id, $like);
                } else {
                    $stmt = $conn->prepare("SELECT * FROM files WHERE user_id=? AND folder_id IS NULL AND filename LIKE ? AND deleted_at IS NULL ORDER BY uploaded_at DESC");
                    $stmt->bind_param("is", $user_id, $like);
                }
                $stmt->execute();
                $files = $stmt->get_result();
            }
        } else {
            if ($trash_view) {
                $file_query = "SELECT * FROM files WHERE user_id=$user_id AND deleted_at IS NOT NULL ORDER BY deleted_at DESC";
            } else {
                $file_query = $current_folder
                    ? "SELECT * FROM files WHERE user_id=$user_id AND folder_id=$current_folder AND deleted_at IS NULL ORDER BY uploaded_at DESC"
                    : "SELECT * FROM files WHERE user_id=$user_id AND folder_id IS NULL AND deleted_at IS NULL ORDER BY uploaded_at DESC";
            }
            $files = $conn->query($file_query);
        }

        if ($files && $files->num_rows > 0) {
            while ($file = $files->fetch_assoc()) {
              $is_image = strpos($file['file_type'], 'image/') === 0;
              $file_icon = getFileIcon($file['file_type']);
              $is_fav = isset($fav_files[(int)$file['id']]);
              $cardId = 'file-card-' . (int)$file['id'];
              echo "<div class='col-12 col-sm-6 col-md-4 col-lg-3' id='$cardId'>";
              echo "<div class='card h-100'>";
              echo "<div class='card-body text-center'>";
              if ($is_image) {
                  echo "<img src='".htmlspecialchars($file['filepath'])."' alt='".htmlspecialchars($file['filename'])."' class='preview-image mb-2'>";
              } else {
                  echo "<i class='fas $file_icon fa-3x text-secondary mb-2'></i>";
              }
              echo "<div class='item-name fw-semibold'>". htmlspecialchars($file['filename']) . "</div>";
              // Favorite button (hide in trash view)
              if (!$trash_view) {
                $btnClass = $is_fav ? 'btn-warning' : 'btn-outline-warning';
                $iconStyle = $is_fav ? 'fas' : 'far';
                echo "<div class='mt-2'><button class='btn btn-sm {$btnClass} favorite-btn' data-file='".(int)$file['id']."' onclick='toggleFavorite(".(int)$file['id'].", this)'><i class='{$iconStyle} fa-star'></i></button></div>";
              }
              echo "</div>";
              echo "<div class='card-footer d-flex justify-content-around bg-light'>";
              if ($trash_view) {
                // Restore (POST) and Permanently Delete (force)
                $folder_id_val = isset($file['folder_id']) && $file['folder_id'] !== null ? (int)$file['folder_id'] : 0;
                echo "<form method='POST' action='restore.php' style='display:inline;margin:0;padding:0;'>";
                echo "<input type='hidden' name='id' value='".(int)$file['id']."'>";
                echo "<input type='hidden' name='folder_id' value='".$folder_id_val."'>";
                echo "<button class='btn btn-sm btn-outline-success' type='submit' title='Restore'><i class='fas fa-undo'></i></button>";
                echo "</form>";
                echo "<a href='delete.php?id=".(int)$file['id']."&force=1' class='btn btn-sm btn-outline-danger' title='Delete permanently' onclick='return confirm(\"Delete permanently?\")'><i class='fas fa-trash'></i></a>";
              } else {
                echo "<a href='".htmlspecialchars($file['filepath'])."' download class='btn btn-sm btn-outline-primary' title='Download'><i class='fas fa-download'></i></a>";
                echo "<button class='btn btn-sm btn-outline-secondary' title='Rename' onclick='openRenameFileModal(".(int)$file['id'].", \"".addslashes($file['filename'])."\")'><i class='fas fa-edit'></i></button>";
                echo "<a href='delete.php?id=".(int)$file['id']."' class='btn btn-sm btn-outline-danger' title='Delete'><i class='fas fa-trash'></i></a>";
                echo "<button class='btn btn-sm btn-outline-success' title='Share' onclick='openShareModal(".(int)$file['id'].")'><i class='fas fa-share-alt'></i></button>";
              }
              echo "</div></div></div>";
            }
        } else {
            echo "<div class='col-12'><p class='text-center text-white'>No files found</p></div>";
        }

        function getFileIcon($file_type) {
          $icons = [
            'image/' => 'fa-file-image',
            'audio/' => 'fa-file-audio',
            'video/' => 'fa-file-video',
            'application/pdf' => 'fa-file-pdf',
            'application/msword' => 'fa-file-word',
            'application/vnd.ms-excel' => 'fa-file-excel',
            'application/vnd.ms-powerpoint' => 'fa-file-powerpoint',
            'text/' => 'fa-file-alt',
            'application/zip' => 'fa-file-archive',
            'default' => 'fa-file'
          ];
          foreach ($icons as $prefix => $icon) {
            if (strpos($file_type, $prefix) === 0) return $icon;
          }
          return $icons['default'];
        }
        ?>
      </div>
    </div>
  </div>

  <div class="modal fade" id="folderModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Create New Folder</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form action="create_folder.php" method="POST"><div class="modal-body">
      <input type="text" name="folder_name" class="form-control mb-3" placeholder="Folder Name" required>
      <input type="hidden" name="parent_id" value="<?php echo $current_folder ? (int)$current_folder : ''; ?>">
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create</button></div>
    </form>
  </div></div></div>

  <div class="modal fade" id="uploadModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Upload File</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form action="upload.php" method="POST" enctype="multipart/form-data"><div class="modal-body">
      <input type="text" name="filename" class="form-control mb-3" placeholder="File Name" required>
      <select name="folder_id" class="form-select mb-3"><option value="">-- Select Folder (optional) --</option>
        <?php
        $folders = $conn->query("SELECT * FROM folders WHERE user_id=$user_id");
        while ($f = $folders->fetch_assoc()) {
          $selected = $f['id'] == $current_folder ? 'selected' : '';
          echo "<option value='".(int)$f['id']."' $selected>".htmlspecialchars($f['folder_name'])."</option>";
        }
        ?>
      </select>
      <input type="file" name="file" class="form-control" required>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Upload</button></div>
    </form>
  </div></div></div>

  <div class="modal fade" id="renameFolderModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Rename Folder</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form action="rename.php" method="POST"><div class="modal-body">
      <input type="hidden" name="type" value="folder"><input type="hidden" name="id" id="renameFolderId">
      <input type="text" name="new_name" id="renameFolderName" class="form-control" required>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Rename</button></div></form>
  </div></div></div>

  <div class="modal fade" id="renameFileModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Rename File</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form action="rename.php" method="POST"><div class="modal-body">
      <input type="hidden" name="type" value="file"><input type="hidden" name="id" id="renameFileId">
      <input type="text" name="new_name" id="renameFileName" class="form-control" required>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Rename</button></div></form>
  </div></div></div>

  <div class="modal fade" id="shareModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Share File</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <label class="form-label">Shareable Link:</label>
      <div class="input-group mb-3"><input type="text" id="shareLink" class="form-control" readonly><button class="btn btn-outline-primary" type="button" onclick="copyShareLink()">Copy</button></div>
      <small class="text-muted">Copy this link and share with others.</small>
    </div>
  </div></div></div>

  <script>
    function openRenameFolderModal(id, name) {
      document.getElementById('renameFolderId').value = id;
      document.getElementById('renameFolderName').value = name;
      new bootstrap.Modal(document.getElementById('renameFolderModal')).show();
    }
    function openRenameFileModal(id, name) {
      document.getElementById('renameFileId').value = id;
      document.getElementById('renameFileName').value = name;
      new bootstrap.Modal(document.getElementById('renameFileModal')).show();
    }
    function openShareModal(fileId) {
      document.getElementById('shareLink').value = window.location.origin + '/file-repo-lab03-full/share.php?id=' + fileId;
      new bootstrap.Modal(document.getElementById('shareModal')).show();
    }
    function copyShareLink() {
      var copyText = document.getElementById("shareLink");
      copyText.select();
      copyText.setSelectionRange(0, 99999);
      document.execCommand("copy");
    }

    // Highlight file card if ?highlight=ID present
    (function(){
      const params = new URLSearchParams(window.location.search);
      const highlight = params.get('highlight');
      if (highlight) {
        const el = document.getElementById('file-card-' + highlight);
        if (el) {
          el.scrollIntoView({behavior:'smooth', block:'center'});
          el.classList.add('highlighted');
          setTimeout(()=> el.classList.remove('highlighted'), 3500);
        } else {
          alert('File found but not visible in this view.');
        }
      }
    })();

    // Favorite toggle
    function toggleFavorite(fileId, btn) {
      fetch('favorite.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'file_id=' + encodeURIComponent(fileId)
      })
      .then(res => res.json())
      .then(data => {
        if (!data) { alert('No response'); return; }
        if (data.status === 'error') { alert(data.message || 'Error'); return; }
        var icon = btn.querySelector('i');
        if (data.status === 'added') {
          btn.classList.remove('btn-outline-warning'); btn.classList.add('btn-warning');
          icon.classList.remove('far'); icon.classList.add('fas');
        } else if (data.status === 'removed') {
          btn.classList.remove('btn-warning'); btn.classList.add('btn-outline-warning');
          icon.classList.remove('fas'); icon.classList.add('far');
        }
      }).catch(()=> alert('Request failed'));
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>