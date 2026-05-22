<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

  // GET /api/tasks.php                             — all
  // GET /api/tasks.php?id=1                        — one
  // GET /api/tasks.php?customerId=1                — by customer
  // GET /api/tasks.php?staffId=1                   — by staff
  // GET /api/tasks.php?status=pending              — by status
  // GET /api/tasks.php?startDate=...&endDate=...   — date range
  case 'GET':
    $id = $_GET['id'] ?? null;
    $customerId = $_GET['customerId'] ?? null;
    $staffId = $_GET['staffId'] ?? null;
    $serviceId = $_GET['serviceId'] ?? null;
    $status = $_GET['status'] ?? null;
    $date = $_GET['date'] ?? null;
    $startDate = $_GET['startDate'] ?? null;
    $endDate = $_GET['endDate'] ?? null;

    if ($id) {
      $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
      $stmt->execute([$id]);
      $task = $stmt->fetch();
      if (!$task) errorResponse('Task not found', 404);
      jsonResponse($task);
    }

    $sql = 'SELECT * FROM tasks WHERE 1=1';
    $params = [];

    if ($customerId) { $sql .= ' AND customer_id = ?'; $params[] = $customerId; }
    if ($staffId)    { $sql .= ' AND assigned_to = ?'; $params[] = $staffId; }
    if ($serviceId)  { $sql .= ' AND service_id = ?';  $params[] = $serviceId; }
    if ($status)     { $sql .= ' AND status = ?';      $params[] = $status; }
    if ($date)       { $sql .= ' AND scheduled_date = ?'; $params[] = $date; }
    if ($startDate)  { $sql .= ' AND scheduled_date >= ?'; $params[] = $startDate; }
    if ($endDate)    { $sql .= ' AND scheduled_date <= ?'; $params[] = $endDate; }

    $sql .= ' ORDER BY scheduled_date DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    jsonResponse($stmt->fetchAll());
    break;

  // POST /api/tasks.php — create
  case 'POST':
    $body = getJsonBody();
    if (empty($body['title'])) errorResponse('Task title is required');

    $stmt = $pdo->prepare('INSERT INTO tasks (service_id, customer_id, title, status, scheduled_date, assigned_to, notes, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
      $body['service_id'] ?? null,
      $body['customer_id'] ?? null,
      $body['title'],
      $body['status'] ?? 'pending',
      $body['scheduled_date'] ?? todayISO(),
      $body['assigned_to'] ?? null,
      $body['notes'] ?? '',
      $body['category_id'] ?? null,
    ]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse($stmt->fetch(), 201);
    break;

  // PUT /api/tasks.php?action=complete — mark task complete (recurrence-aware)
  // PUT /api/tasks.php — update task fields
  case 'PUT':
    $body = getJsonBody();
    if (empty($body['id'])) errorResponse('Task ID is required');

    $action = $_GET['action'] ?? null;

    if ($action === 'complete') {
      // Complete task + recurrency
      $completedDate = $body['completed_date'] ?? todayISO();
      $notes = $body['notes'] ?? '';

      $pdo->beginTransaction();
      try {
        $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
        $stmt->execute([$body['id']]);
        $task = $stmt->fetch();
        if (!$task) errorResponse('Task not found', 404);

        // Update task
        $updateStmt = $pdo->prepare("UPDATE tasks SET status='completed', completed_date=?, notes=CONCAT_WS(' | ', notes, ?) WHERE id=?");
        $updateStmt->execute([$completedDate, $notes, $body['id']]);

        $staffStmt = $pdo->prepare('SELECT name FROM staff WHERE id = ?');
        $staffStmt->execute([$task['assigned_to']]);
        $staffName = $staffStmt->fetchColumn() ?: 'Someone';

        $custStmt = $pdo->prepare('SELECT name FROM customers WHERE id = ?');
        $custStmt->execute([$task['customer_id']]);
        $custName = $custStmt->fetchColumn() ?: 'a customer';

        $nextTask = null;

        // Check if service is recurring
        if ($task['service_id']) {
          $svcStmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
          $svcStmt->execute([$task['service_id']]);
          $service = $svcStmt->fetch();

          if ($service && $service['is_recurring']) {
            $nextDate = getNextDueDate($service, $completedDate, $task['scheduled_date']);
            if ($nextDate) {
              $taskStmt = $pdo->prepare('INSERT INTO tasks (service_id, customer_id, title, status, scheduled_date, assigned_to, category_id) VALUES (?, ?, ?, "pending", ?, ?, ?)');
              $taskStmt->execute([
                $service['id'],
                $service['customer_id'],
                $task['title'],
                $nextDate,
                $service['assigned_to'],
                $service['category_id'],
              ]);
              $nextTaskId = (int)$pdo->lastInsertId();

              $nextTask = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
              $nextTask->execute([$nextTaskId]);
              $nextTask = $nextTask->fetch();

              pushNotification($pdo, $staffName . ' completed ' . $task['title'] . ' for ' . $custName . '. Next service: ' . $nextDate, 'task_completed', $task['id']);
            }
          } else {
            pushNotification($pdo, $staffName . ' completed ' . $task['title'] . ' for ' . $custName, 'task_completed', $task['id']);
          }
        } else {
          pushNotification($pdo, $staffName . ' completed ' . $task['title'] . ' for ' . $custName, 'task_completed', $task['id']);
        }

        $pdo->commit();

        // Return updated task + next task
        $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
        $stmt->execute([$body['id']]);
        $updated = $stmt->fetch();

        jsonResponse(['task' => $updated, 'nextTask' => $nextTask]);
      } catch (Exception $e) {
        $pdo->rollBack();
        errorResponse('Failed to complete task: ' . $e->getMessage(), 500);
      }
      break;
    }

    // General update
    $fields = [];
    $params = [];
    foreach (['title', 'status', 'scheduled_date', 'completed_date', 'assigned_to', 'notes', 'category_id'] as $f) {
      if (isset($body[$f])) {
        $fields[] = "$f=?";
        $params[] = $body[$f];
      }
    }
    if (empty($fields)) errorResponse('No fields to update');
    $params[] = $body['id'];
    $stmt = $pdo->prepare('UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id=?');
    $stmt->execute($params);

    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$body['id']]);
    jsonResponse($stmt->fetch());
    break;

  // DELETE /api/tasks.php?id=1
  case 'DELETE':
    $id = $_GET['id'] ?? null;
    if (!$id) errorResponse('Task ID is required');
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
    break;

  default:
    errorResponse('Method not allowed', 405);
}
