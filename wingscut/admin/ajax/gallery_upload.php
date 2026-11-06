<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['gallery_image'])) {
    echo json_encode(['ok'=>false,'msg'=>'Invalid request']); exit;
}

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

$title    = cl($_POST['img_title']    ?? 'Untitled');
$category = cl($_POST['img_category'] ?? 'portrait');
$file     = $_FILES['gallery_image'];

if ($file['error'] !== UPLOAD_ERR_OK)      { echo json_encode(['ok'=>false,'msg'=>'Upload error']); exit; }
if ($file['size'] > MAX_FILE_SIZE)          { echo json_encode(['ok'=>false,'msg'=>'File too large (max 5MB)']); exit; }

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, ALLOWED_TYPES)) { echo json_encode(['ok'=>false,'msg'=>'Only JPG, PNG, WEBP allowed']); exit; }

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('wc_') . '.' . strtolower($ext);
$dest     = UPLOAD_DIR . $filename;
$imgPath  = 'images/gallery/' . $filename;

if (move_uploaded_file($file['tmp_name'], $dest)) {
    $stmt = $pdo->prepare("INSERT INTO gallery (title, category, image_path, is_active, sort_order, created_at) VALUES (?,?,?,1,0,NOW())");
    $stmt->execute([$title, $category, $imgPath]);
    $newId = $pdo->lastInsertId();
    echo json_encode([
        'ok'       => true,
        'msg'      => 'Image uploaded successfully!',
        'id'       => $newId,
        'title'    => $title,
        'category' => $category,
        'path'     => '../' . $imgPath,
        'date'     => date('d M Y'),
    ]);
} else {
    echo json_encode(['ok'=>false,'msg'=>'Could not save file. Check folder permissions.']);
}
