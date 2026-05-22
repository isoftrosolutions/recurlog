<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

  // GET /api/customers.php          — list all
  // GET /api/customers.php?id=1     — get one
  case 'GET':
    $id = $_GET['id'] ?? null;
    if ($id) {
      $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
      $stmt->execute([$id]);
      $customer = $stmt->fetch();
      if (!$customer) errorResponse('Customer not found', 404);
      if ($customer['services_for']) {
        $customer['services_for'] = json_decode($customer['services_for'], true);
      }
      jsonResponse($customer);
    }
    $customers = $pdo->query('SELECT * FROM customers ORDER BY name ASC')->fetchAll();
    foreach ($customers as &$c) {
      if ($c['services_for']) {
        $c['services_for'] = json_decode($c['services_for'], true);
      }
    }
    jsonResponse($customers);
    break;

  // POST /api/customers.php — create
  case 'POST':
    $body = getJsonBody();
    if (empty($body['name'])) errorResponse('Customer name is required');

    $stmt = $pdo->prepare('INSERT INTO customers (name, address, phone, services_for, location_lat, location_lng) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
      $body['name'],
      $body['address'] ?? '',
      $body['phone'] ?? '',
      isset($body['services_for']) ? json_encode($body['services_for']) : '[]',
      $body['location']['lat'] ?? null,
      $body['location']['lng'] ?? null,
    ]);
    $id = (int)$pdo->lastInsertId();

    pushNotification($pdo, 'New customer ' . $body['name'] . ' registered', 'customer_added', $id);

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    $customer = $stmt->fetch();
    if ($customer['services_for']) {
      $customer['services_for'] = json_decode($customer['services_for'], true);
    }
    jsonResponse($customer, 201);
    break;

  // PUT /api/customers.php — update
  case 'PUT':
    $body = getJsonBody();
    if (empty($body['id'])) errorResponse('Customer ID is required');

    $stmt = $pdo->prepare('UPDATE customers SET name=?, address=?, phone=?, services_for=?, location_lat=?, location_lng=? WHERE id=?');
    $stmt->execute([
      $body['name'],
      $body['address'] ?? '',
      $body['phone'] ?? '',
      isset($body['services_for']) ? json_encode($body['services_for']) : '[]',
      $body['location']['lat'] ?? null,
      $body['location']['lng'] ?? null,
      $body['id'],
    ]);

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$body['id']]);
    $customer = $stmt->fetch();
    if ($customer['services_for']) {
      $customer['services_for'] = json_decode($customer['services_for'], true);
    }
    jsonResponse($customer);
    break;

  // DELETE /api/customers.php?id=1 — delete
  case 'DELETE':
    $id = $_GET['id'] ?? null;
    if (!$id) errorResponse('Customer ID is required');
    $stmt = $pdo->prepare('DELETE FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
    break;

  default:
    errorResponse('Method not allowed', 405);
}
