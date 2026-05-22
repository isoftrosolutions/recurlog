<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
  errorResponse('Method not allowed', 405);
}

$today = todayISO();

// Counts
$totalCustomers = (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$totalStaff = (int)$pdo->query('SELECT COUNT(*) FROM staff')->fetchColumn();
$todayTasks = (int)$pdo->prepare("SELECT COUNT(*) FROM tasks WHERE scheduled_date = ?")->execute([$today]) ? (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE scheduled_date = '$today'")->fetchColumn() : 0;
$missedTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'missed'")->fetchColumn();

// Today's tasks detail
$stmt = $pdo->prepare("
  SELECT t.*, c.name AS customer_name, s.name AS staff_name
  FROM tasks t
  LEFT JOIN customers c ON t.customer_id = c.id
  LEFT JOIN staff s ON t.assigned_to = s.id
  WHERE t.scheduled_date = ?
  ORDER BY t.status ASC, t.created_at DESC
");
$stmt->execute([$today]);
$todaysSchedule = $stmt->fetchAll();

// Recent notifications (last 8)
$recentNotifs = $pdo->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 8')->fetchAll();
foreach ($recentNotifs as &$n) {
  $n['isRead'] = (bool)$n['is_read'];
}

jsonResponse([
  'totalCustomers' => $totalCustomers,
  'totalStaff'     => $totalStaff,
  'todayTasks'     => $todayTasks,
  'missedTasks'    => $missedTasks,
  'todaysSchedule' => $todaysSchedule,
  'recentNotifications' => $recentNotifs,
]);
