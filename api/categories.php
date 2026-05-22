<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

  case 'GET':
    $id = $_GET['id'] ?? null;
    if ($id) {
      $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
      $stmt->execute([$id]);
      $cat = $stmt->fetch();
      if (!$cat) errorResponse('Category not found', 404);
      jsonResponse($cat);
    }
    jsonResponse($pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll());
    break;

  case 'POST':
    $body = getJsonBody();
    if (empty($body['name'])) errorResponse('Category name is required');
    $stmt = $pdo->prepare('INSERT INTO categories (name, color) VALUES (?, ?)');
    $stmt->execute([$body['name'], $body['color'] ?? '#1DB954']);
    $id = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse($stmt->fetch(), 201);
    break;

  case 'PUT':
    $body = getJsonBody();
    if (empty($body['id'])) errorResponse('Category ID is required');
    $stmt = $pdo->prepare('UPDATE categories SET name=?, color=? WHERE id=?');
    $stmt->execute([$body['name'], $body['color'] ?? '#1DB954', $body['id']]);
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$body['id']]);
    jsonResponse($stmt->fetch());
    break;

  case 'DELETE':
    $id = $_GET['id'] ?? null;
    if (!$id) errorResponse('Category ID is required');
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
    break;

  default:
    errorResponse('Method not allowed', 405);
}
