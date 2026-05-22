<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
  errorResponse('Method not allowed', 405);
}

$body = getJsonBody();
$email = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if (!$email || !$password) {
  errorResponse('Email and password are required');
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
  errorResponse('Invalid email or password', 401);
}

jsonResponse([
  'success' => true,
  'user' => [
    'id'    => (int)$user['id'],
    'name'  => $user['name'],
    'email' => $user['email'],
  ]
]);
