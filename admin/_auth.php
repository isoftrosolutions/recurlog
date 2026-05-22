<?php
/**
 * Auth check — include at the top of every admin page.
 */
session_start();

// Redirect to login if not authenticated
if (empty($_SESSION['admin_logged_in'])) {
  header('Location: login.php');
  exit;
}

$adminUser = $_SESSION['admin_user'] ?? 'Admin';
