<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

  // GET /api/services.php                 — all
  // GET /api/services.php?id=1            — one
  // GET /api/services.php?customerId=1    — filter by customer
  case 'GET':
    $id = $_GET['id'] ?? null;
    $customerId = $_GET['customerId'] ?? null;

    if ($id) {
      $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
      $stmt->execute([$id]);
      $service = $stmt->fetch();
      if (!$service) errorResponse('Service not found', 404);
      $service['isRecurring'] = (bool)$service['is_recurring'];
      jsonResponse($service);
    }

    if ($customerId) {
      $stmt = $pdo->prepare('SELECT * FROM services WHERE customer_id = ? ORDER BY created_at DESC');
      $stmt->execute([$customerId]);
    } else {
      $stmt = $pdo->query('SELECT * FROM services ORDER BY created_at DESC');
    }
    $services = $stmt->fetchAll();
    foreach ($services as &$s) {
      $s['isRecurring'] = (bool)$s['is_recurring'];
    }
    jsonResponse($services);
    break;

  // POST /api/services.php — create
  case 'POST':
    $body = getJsonBody();
    if (empty($body['customer_id'])) errorResponse('Customer ID is required');

    $isRecurring = !empty($body['is_recurring']);
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare('INSERT INTO services (customer_id, category_id, service_for, title, is_recurring, first_scheduled_date, assigned_to, notes, recurrence_value, recurrence_unit, recurrence_repeat_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
      $stmt->execute([
        $body['customer_id'],
        $body['category_id'] ?? null,
        $body['service_for'] ?? '',
        $body['title'] ?? '',
        $isRecurring ? 1 : 0,
        $body['first_scheduled_date'] ?? todayISO(),
        $body['assigned_to'] ?? null,
        $body['notes'] ?? '',
        $body['recurrence']['value'] ?? null,
        $body['recurrence']['unit'] ?? null,
        $body['recurrence']['repeat_from'] ?? 'last_service',
      ]);
      $serviceId = (int)$pdo->lastInsertId();

      // Create the first pending task
      $taskDate = $body['first_scheduled_date'] ?? todayISO();
      $catStmt = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
      $catStmt->execute([$body['category_id'] ?? 0]);
      $catName = $catStmt->fetchColumn() ?: 'Service';

      $custStmt = $pdo->prepare('SELECT name FROM customers WHERE id = ?');
      $custStmt->execute([$body['customer_id']]);
      $custName = $custStmt->fetchColumn() ?: 'Customer';

      $taskTitle = $catName . ' - ' . $custName;
      if (!empty($body['title'])) $taskTitle = $body['title'];

      $taskStmt = $pdo->prepare('INSERT INTO tasks (service_id, customer_id, title, status, scheduled_date, assigned_to, notes, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
      $taskStmt->execute([
        $serviceId,
        $body['customer_id'],
        $taskTitle,
        'pending',
        $taskDate,
        $body['assigned_to'] ?? null,
        $body['notes'] ?? '',
        $body['category_id'] ?? null,
      ]);
      $taskId = (int)$pdo->lastInsertId();

      $staffStmt = $pdo->prepare('SELECT name FROM staff WHERE id = ?');
      $staffStmt->execute([$body['assigned_to'] ?? 0]);
      $staffName = $staffStmt->fetchColumn() ?: 'Unassigned';

      pushNotification($pdo, $staffName . ' assigned to ' . $catName . ' for ' . $custName . ' on ' . $taskDate, 'service_added', $serviceId);

      $pdo->commit();

      $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
      $stmt->execute([$serviceId]);
      $service = $stmt->fetch();
      $service['isRecurring'] = (bool)$service['is_recurring'];
      jsonResponse($service, 201);
    } catch (Exception $e) {
      $pdo->rollBack();
      errorResponse('Failed to create service: ' . $e->getMessage(), 500);
    }
    break;

  // PUT /api/services.php — update
  case 'PUT':
    $body = getJsonBody();
    if (empty($body['id'])) errorResponse('Service ID is required');

    $isRecurring = !empty($body['is_recurring']);
    $stmt = $pdo->prepare('UPDATE services SET customer_id=?, category_id=?, service_for=?, title=?, is_recurring=?, first_scheduled_date=?, assigned_to=?, notes=?, recurrence_value=?, recurrence_unit=?, recurrence_repeat_from=? WHERE id=?');
    $stmt->execute([
      $body['customer_id'],
      $body['category_id'] ?? null,
      $body['service_for'] ?? '',
      $body['title'] ?? '',
      $isRecurring ? 1 : 0,
      $body['first_scheduled_date'] ?? null,
      $body['assigned_to'] ?? null,
      $body['notes'] ?? '',
      $body['recurrence']['value'] ?? null,
      $body['recurrence']['unit'] ?? null,
      $body['recurrence']['repeat_from'] ?? 'last_service',
      $body['id'],
    ]);

    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$body['id']]);
    $service = $stmt->fetch();
    $service['isRecurring'] = (bool)$service['is_recurring'];
    jsonResponse($service);
    break;

  // DELETE /api/services.php?id=1
  case 'DELETE':
    $id = $_GET['id'] ?? null;
    if (!$id) errorResponse('Service ID is required');
    $stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
    break;

  default:
    errorResponse('Method not allowed', 405);
}
