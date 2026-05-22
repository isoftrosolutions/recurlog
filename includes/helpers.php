<?php
/**
 * Shared helper functions.
 */

/**
 * Read JSON request body.
 */
function getJsonBody() {
  $raw = file_get_contents('php://input');
  return json_decode($raw, true) ?? [];
}

/**
 * Send a JSON response.
 */
function jsonResponse($data, $statusCode = 200) {
  http_response_code($statusCode);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * Send an error response.
 */
function errorResponse($message, $statusCode = 400) {
  jsonResponse(['error' => $message], $statusCode);
}

/**
 * Get today's date as YYYY-MM-DD.
 */
function todayISO() {
  return date('Y-m-d');
}

/**
 * Add date interval.
 */
function addToDate($dateStr, $value, $unit) {
  $d = new DateTime($dateStr);
  switch ($unit) {
    case 'days':   $d->modify("+{$value} days"); break;
    case 'weeks':  $d->modify("+{$value} weeks"); break;
    case 'months': $d->modify("+{$value} months"); break;
    case 'years':  $d->modify("+{$value} years"); break;
  }
  return $d->format('Y-m-d');
}

/**
 * Compute next due date for a recurring service.
 */
function getNextDueDate($service, $lastCompletedDate, $previousScheduledDate) {
  if (!$service['is_recurring'] || !$service['recurrence_value']) return null;
  $baseDate = null;
  if ($service['recurrence_repeat_from'] === 'last_service' && $lastCompletedDate) {
    $baseDate = $lastCompletedDate;
  } elseif ($service['recurrence_repeat_from'] === 'fixed_schedule' && $previousScheduledDate) {
    $baseDate = $previousScheduledDate;
  } else {
    $baseDate = $lastCompletedDate ?: ($previousScheduledDate ?: todayISO());
  }
  return addToDate($baseDate, (int)$service['recurrence_value'], $service['recurrence_unit']);
}

/**
 * Enrich staff with stats.
 */
function getStaffWithStats($pdo) {
  $stmt = $pdo->query('SELECT * FROM staff');
  $staff = $stmt->fetchAll();
  foreach ($staff as &$s) {
    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE assigned_to = ?');
    $totalStmt->execute([$s['id']]);
    $total = (int)$totalStmt->fetchColumn();

    $doneStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'");
    $doneStmt->execute([$s['id']]);
    $completed = (int)$doneStmt->fetchColumn();

    $missedStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'missed'");
    $missedStmt->execute([$s['id']]);
    $missed = (int)$missedStmt->fetchColumn();

    $s['total'] = $total;
    $s['completed'] = $completed;
    $s['missed'] = $missed;
    $s['pending'] = $total - $completed - $missed;
    $s['completionRate'] = $total > 0 ? round(($completed / $total) * 100) : 0;
  }
  return $staff;
}

/**
 * Push a notification.
 */
function pushNotification($pdo, $text, $type = 'info', $relatedId = null) {
  $stmt = $pdo->prepare('INSERT INTO notifications (text, type, related_id, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
  $stmt->execute([$text, $type, $relatedId]);
  return (int)$pdo->lastInsertId();
}
