<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

  // GET /api/staff.php              — list all with stats
  // GET /api/staff.php?id=1         — get one with stats
  case 'GET':
    $id = $_GET['id'] ?? null;
    if ($id) {
      $stmt = $pdo->prepare('SELECT * FROM staff WHERE id = ?');
      $stmt->execute([$id]);
      $staff = $stmt->fetch();
      if (!$staff) errorResponse('Staff not found', 404);

      $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE assigned_to = ?');
      $totalStmt->execute([$id]);
      $staff['total'] = (int)$totalStmt->fetchColumn();

      $doneStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'");
      $doneStmt->execute([$id]);
      $staff['completed'] = (int)$doneStmt->fetchColumn();

      $missedStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'missed'");
      $missedStmt->execute([$id]);
      $staff['missed'] = (int)$missedStmt->fetchColumn();

      $staff['pending'] = $staff['total'] - $staff['completed'] - $staff['missed'];
      $staff['completionRate'] = $staff['total'] > 0 ? round(($staff['completed'] / $staff['total']) * 100) : 0;

      jsonResponse($staff);
    }
    jsonResponse(getStaffWithStats($pdo));
    break;

  // POST /api/staff.php — create
  case 'POST':
    $body = getJsonBody();
    if (empty($body['name'])) errorResponse('Staff name is required');

    $avatar = $body['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($body['name']) . '&background=1DB954&color=fff&size=200';
    $stmt = $pdo->prepare('INSERT INTO staff (name, phone, avatar) VALUES (?, ?, ?)');
    $stmt->execute([$body['name'], $body['phone'] ?? '', $avatar]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT * FROM staff WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse($stmt->fetch(), 201);
    break;

  // PUT /api/staff.php — update
  case 'PUT':
    $body = getJsonBody();
    if (empty($body['id'])) errorResponse('Staff ID is required');

    $stmt = $pdo->prepare('UPDATE staff SET name=?, phone=?, avatar=? WHERE id=?');
    $stmt->execute([
      $body['name'],
      $body['phone'] ?? '',
      $body['avatar'] ?? '',
      $body['id'],
    ]);

    $stmt = $pdo->prepare('SELECT * FROM staff WHERE id = ?');
    $stmt->execute([$body['id']]);
    jsonResponse($stmt->fetch());
    break;

  // DELETE /api/staff.php?id=1
  case 'DELETE':
    $id = $_GET['id'] ?? null;
    if (!$id) errorResponse('Staff ID is required');
    $stmt = $pdo->prepare('DELETE FROM staff WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
    break;

  default:
    errorResponse('Method not allowed', 405);
}
