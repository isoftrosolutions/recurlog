<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

  case 'GET':
    $notifs = $pdo->query('SELECT * FROM notifications ORDER BY created_at DESC')->fetchAll();
    $unread = 0;
    foreach ($notifs as &$n) {
      $n['isRead'] = (bool)$n['is_read'];
      if (!$n['is_read']) $unread++;
    }
    jsonResponse(['notifications' => $notifs, 'unreadCount' => $unread]);
    break;

  // PUT /api/notifications.php?action=markAllRead
  case 'PUT':
    $action = $_GET['action'] ?? null;
    if ($action === 'markAllRead') {
      $pdo->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
      jsonResponse(['success' => true]);
    }
    errorResponse('Unknown action', 400);
    break;

  default:
    errorResponse('Method not allowed', 405);
}
