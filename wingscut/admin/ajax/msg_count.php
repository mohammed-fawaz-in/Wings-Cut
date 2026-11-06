<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
$count = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
echo json_encode(['count' => (int)$count]);
