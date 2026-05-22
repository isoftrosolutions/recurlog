<?php
/**
 * Database Configuration
 *
 * Update these values with your shared hosting credentials.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'ektamultp_recurlog');
define('DB_USER', 'ektamultp_recurlog');
define('DB_PASS', '2.Sm&dwT3n.L.k~v');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection.
 */
function getDB() {
  static $pdo = null;
  if ($pdo === null) {
    try {
      $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
      $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
      ]);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
      exit;
    }
  }
  return $pdo;
}
