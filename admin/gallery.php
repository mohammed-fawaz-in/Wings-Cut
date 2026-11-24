<?php
require_once __DIR__ . '/includes/auth.php';

// Create upload dir if not exists
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gallery_image'])) {
    $title    = cl($_POST['img_title']    ?? 'Untitled');
    $category = cl($_POST['img_category'] ?? 'portrait');
    $file     = $_FILES['gallery_image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        adminRedirect('gallery.php', 'Upload error. Try again.', 'error');
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        adminRedirect('gallery.php', 'File too large. Max 5MB.', 'error');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_TYPES)) {
        adminRedirect('gallery.php', 'Only JPG, PNG, WEBP allowed.', 'error');
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('wc_') . '.' . strtolower($ext);
    $dest     = UPLOAD_DIR . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $stmt = $pdo->prepare("INSERT INTO gallery (title, category, image_path, is_active, sort_order, created_at) VALUES (?,?,?,1,0,NOW())");
        $stmt->execute([$title, $category, 'images/gallery/' . $filename]);
        adminRedirect('gallery.php', 'Image uploaded successfully!', 'success');
    } else {
        adminRedirect('gallery.php', 'Could not save file. Check folder permissions.', 'error');
    }
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $img = $pdo->prepare("SELECT image_path FROM gallery WHERE id=?")->execute([(int)$_GET['delete']]);
    $row = $pdo->prepare("SELECT image_path FROM gallery WHERE id=?");
    $row->execute([(int)$_GET['delete']]);
    $imgRow = $row->fetch();
    if ($imgRow) {
        $filePath = __DIR__ . '/../' . $imgRow['image_path'];
        if (file_exists($filePath)) unlink($filePath);
        $pdo->prepare("DELETE FROM gallery WHERE id=?")->execute([(int)$_GET['delete']]);
    }
    adminRedirect('gallery.php', 'Image deleted.', 'success');
}

// Handle Toggle Active
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $pdo->prepare("UPDATE gallery SET is_active = !is_active WHERE id=?")->execute([(int)$_GET['toggle']]);
    adminRedirect('gallery.php', 'Visibility updated.', 'success');
}

// Fetch Gallery
$catFilter = cl($_GET['cat'] ?? '');
$where  = $catFilter ? "WHERE category=?" : "";
$params = $catFilter ? [$catFilter] : [];
$stmt   = $pdo->prepare("SELECT * FROM gallery $where ORDER BY created_at DESC");
$stmt->execute($params);
$images = $stmt->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gallery Manager — Wings Cut Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/admin.css"/>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">
  <?php include 'includes/topbar.php'; ?>
  <div class="admin-content">

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> flash-alert">
      <i class="fas fa-check-circle"></i> <?= htmlspecialchars($flash['text']) ?>
    </div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <h1>Gallery Manager</h1>
        <p><?= count($images) ?> image<?= count($images)!=1?'s':'' ?> in gallery</p>
      </div>
      <button class="btn-primary" onclick="document.getElementById('uploadPanel').classList.toggle('open')">
        <i class="fas fa-plus"></i> Upload Image
      </button>
    </div>

    <!-- Upload Panel (slide down) -->
    <div class="upload-panel" id="uploadPanel">
      <h3><i class="fas fa-cloud-upload-alt"></i> Upload New Image</h3>
      <form id="ajaxUploadForm" enctype="multipart/form-data" class="upload-form">
        <div class="form-row">
          <div class="form-group">
            <label>Image Title</label>
            <input type="text" name="img_title" placeholder="e.g. Wedding Ceremony" required/>
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="img_category" required>
              <option value="wedding">Wedding</option>
              <option value="portrait">Portrait</option>
              <option value="events">Events</option>
              <option value="cinematic">Cinematic</option>
            </select>
          </div>
        </div>
        <div class="form-group full">
          <label>Select Image (JPG, PNG, WEBP — Max 5MB)</label>
          <div class="file-drop" id="fileDrop">
            <i class="fas fa-cloud-upload-alt"></i>
            <p>Drag & drop or <label for="fileInput" class="file-label">browse file</label></p>
            <input type="file" id="fileInput" name="gallery_image" accept="image/jpeg,image/png,image/webp" required/>
            <span id="fileName">No file chosen</span>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
          <button type="submit" id="uploadBtn" class="btn-primary"><i class="fas fa-upload"></i> Upload to Gallery</button>
          <span id="uploadStatus" style="font-size:14px;font-weight:600;"></span>
        </div>
      </form>
    </div>

    <!-- Category Filter -->
    <div class="filter-tabs" style="margin-bottom:24px;">
      <a href="gallery.php" class="ftab <?= !$catFilter?'active':'' ?>">All</a>
      <?php foreach (['wedding','portrait','events','cinematic'] as $c): ?>
      <a href="?cat=<?= $c ?>" class="ftab <?= $catFilter===$c?'active':'' ?>"><?= ucfirst($c) ?></a>
      <?php endforeach; ?>
    </div>

    <!-- Gallery Grid -->
    <div class="gallery-admin-grid" id="galleryGrid">
      <?php foreach ($images as $img): ?>
      <div class="gallery-admin-card <?= $img['is_active'] ? '' : 'inactive' ?>">
        <div class="gac-img">
          <?php
          $imgPath = __DIR__ . '/../' . $img['image_path'];
          if (file_exists($imgPath)):
          ?>
          <img src="<?= '../' . htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($img['title']) ?>"/>
          <?php else: ?>
          <div class="gac-no-img"><i class="fas fa-image"></i></div>
          <?php endif; ?>
          <span class="gac-cat"><?= ucfirst($img['category']) ?></span>
          <?php if (!$img['is_active']): ?><span class="gac-hidden">Hidden</span><?php endif; ?>
        </div>
        <div class="gac-info">
          <strong><?= htmlspecialchars($img['title']) ?></strong>
          <small><?= date('d M Y', strtotime($img['created_at'])) ?></small>
        </div>
        <div class="gac-actions">
          <a href="?toggle=<?= $img['id'] ?>" class="btn-act <?= $img['is_active']?'complete':'reopen' ?>"
             title="<?= $img['is_active']?'Hide':'Show' ?>">
            <i class="fas fa-<?= $img['is_active']?'eye-slash':'eye' ?>"></i>
          </a>
          <a href="?delete=<?= $img['id'] ?>" class="btn-act delete" title="Delete"
             onclick="return confirm('Delete this image permanently?')">
            <i class="fas fa-trash"></i>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($images)): ?>
      <div class="empty-state" style="grid-column:1/-1">
        <i class="fas fa-images"></i>
        <p>No images yet. Upload your first one!</p>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<script src="js/admin.js"></script>
<script>
// File pick: clicking anywhere in drop zone opens picker
const dropZone   = document.getElementById('fileDrop');
const fileInput  = document.getElementById('fileInput');
const fileNameEl = document.getElementById('fileName');

// Click anywhere in the drop zone (except the label itself which already works)
dropZone.addEventListener('click', function(e) {
  if (e.target.tagName !== 'LABEL' && e.target.tagName !== 'INPUT') {
    fileInput.click();
  }
});

fileInput.addEventListener('change', function() {
  fileNameEl.textContent = this.files[0]?.name || 'No file chosen';
  if (this.files[0]) dropZone.classList.add('has-file');
});

// Drag and drop
dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', e => { dropZone.classList.remove('drag-over'); });
dropZone.addEventListener('drop',      e => {
  e.preventDefault();
  dropZone.classList.remove('drag-over');
  const files = e.dataTransfer?.files;
  if (files && files.length > 0) {
    // Assign to file input via DataTransfer
    try {
      const dt = new DataTransfer();
      dt.items.add(files[0]);
      fileInput.files = dt.files;
    } catch(err) { console.warn('DataTransfer assign failed:', err); }
    fileNameEl.textContent = files[0].name;
    dropZone.classList.add('has-file');
  }
});

// AJAX Upload - no page refresh
document.getElementById('ajaxUploadForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const form    = this;
  const btn     = document.getElementById('uploadBtn');
  const status  = document.getElementById('uploadStatus');
  const data    = new FormData(form);

  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
  status.textContent = '';

  fetch('ajax/gallery_upload.php', { method:'POST', body: data })
    .then(r => r.json())
    .then(res => {
      if (res.ok) {
        // Show success
        status.style.color = '#16a34a';
        status.textContent = '✓ ' + res.msg;

        // Inject new card into grid instantly
        const grid = document.getElementById('galleryGrid');
        const card = document.createElement('div');
        card.className = 'gallery-admin-card';
        card.setAttribute('data-id', res.id);
        card.innerHTML = `
          <div class="gac-img">
            <img src="${res.path}" alt="${res.title}" style="width:100%;height:100%;object-fit:cover;"/>
            <span class="gac-cat">${res.category.charAt(0).toUpperCase()+res.category.slice(1)}</span>
          </div>
          <div class="gac-info">
            <strong>${res.title}</strong>
            <small>${res.date}</small>
          </div>
          <div class="gac-actions">
            <a href="?toggle=${res.id}" class="btn-act complete" title="Hide"><i class="fas fa-eye-slash"></i></a>
            <a href="?delete=${res.id}" class="btn-act delete" title="Delete"
               onclick="return confirm('Delete this image?')"><i class="fas fa-trash"></i></a>
          </div>`;

        // Remove empty state if present
        const empty = grid.querySelector('.empty-state');
        if (empty) empty.remove();
        grid.prepend(card);

        // Update count
        const countEl = document.querySelector('.page-header p');
        if (countEl) {
          const n = grid.querySelectorAll('.gallery-admin-card').length;
          countEl.textContent = n + ' image' + (n!==1?'s':'') + ' in gallery';
        }

        // Reset form
        form.reset();
        document.getElementById('fileName').textContent = 'No file chosen';
        document.getElementById('uploadPanel').classList.remove('open');

        // Clear status after 3s
        setTimeout(() => { status.textContent = ''; }, 3000);
      } else {
        status.style.color = '#dc2626';
        status.textContent = '✗ ' + res.msg;
      }
    })
    .catch(() => {
      status.style.color = '#dc2626';
      status.textContent = '✗ Upload failed. Check your connection.';
    })
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-upload"></i> Upload to Gallery';
    });
});
</script>
</body>
</html>
