<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
$pdo = getDB();
$pageTitle = 'Tasks';

$tab = $_GET['tab'] ?? 'today';
$today = date('Y-m-d');

switch ($tab) {
  case 'upcoming':
    $stmt = $pdo->prepare("SELECT t.*, c.name AS customer_name, s.name AS staff_name FROM tasks t LEFT JOIN customers c ON t.customer_id = c.id LEFT JOIN staff s ON t.assigned_to = s.id WHERE t.scheduled_date > ? AND t.status = 'pending' ORDER BY t.scheduled_date ASC");
    $stmt->execute([$today]);
    break;
  case 'missed':
    $stmt = $pdo->query("SELECT t.*, c.name AS customer_name, s.name AS staff_name FROM tasks t LEFT JOIN customers c ON t.customer_id = c.id LEFT JOIN staff s ON t.assigned_to = s.id WHERE t.status = 'missed' ORDER BY t.scheduled_date DESC");
    break;
  default: // today
    $stmt = $pdo->prepare("SELECT t.*, c.name AS customer_name, s.name AS staff_name FROM tasks t LEFT JOIN customers c ON t.customer_id = c.id LEFT JOIN staff s ON t.assigned_to = s.id WHERE t.scheduled_date = ? ORDER BY t.status ASC");
    $stmt->execute([$today]);
    break;
}
$tasks = $stmt->fetchAll();

// Handle mark complete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task'])) {
  $taskId = (int)$_POST['task_id'];
  $completedDate = $_POST['completed_date'] ?? $today;
  $notes = trim($_POST['notes'] ?? '');

  $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
  $stmt->execute([$taskId]);
  $task = $stmt->fetch();
  if ($task) {
    $pdo->beginTransaction();
    try {
      $upStmt = $pdo->prepare("UPDATE tasks SET status='completed', completed_date=?, notes=CONCAT_WS(' | ', notes, ?) WHERE id=?");
      $upStmt->execute([$completedDate, $notes, $taskId]);

      $staffStmt = $pdo->prepare('SELECT name FROM staff WHERE id = ?');
      $staffStmt->execute([$task['assigned_to']]);
      $staffName = $staffStmt->fetchColumn() ?: 'Someone';

      $custStmt = $pdo->prepare('SELECT name FROM customers WHERE id = ?');
      $custStmt->execute([$task['customer_id']]);
      $custName = $custStmt->fetchColumn() ?: 'a customer';

      if ($task['service_id']) {
        $svcStmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
        $svcStmt->execute([$task['service_id']]);
        $service = $svcStmt->fetch();

        if ($service && $service['is_recurring']) {
          $nextDate = getNextDueDate($service, $completedDate, $task['scheduled_date']);
          if ($nextDate) {
            $ntStmt = $pdo->prepare('INSERT INTO tasks (service_id, customer_id, title, status, scheduled_date, assigned_to, category_id) VALUES (?, ?, ?, "pending", ?, ?, ?)');
            $ntStmt->execute([$service['id'], $service['customer_id'], $task['title'], $nextDate, $service['assigned_to'], $service['category_id']]);
            pushNotification($pdo, $staffName . ' completed ' . $task['title'] . ' for ' . $custName . '. Next service: ' . $nextDate, 'task_completed', $taskId);
          }
        } else {
          pushNotification($pdo, $staffName . ' completed ' . $task['title'] . ' for ' . $custName, 'task_completed', $taskId);
        }
      } else {
        pushNotification($pdo, $staffName . ' completed ' . $task['title'] . ' for ' . $custName, 'task_completed', $taskId);
      }

      $pdo->commit();
      $msg = 'Task completed';
    } catch (Exception $e) {
      $pdo->rollBack();
      $msg = 'Error: ' . $e->getMessage();
    }
    echo "<script>localStorage.setItem('admin_toast','" . $msg . "');window.location.href='tasks.php?tab=$tab';</script>";
    exit;
  }
}

require __DIR__ . '/_header.php'; ?>

<div class="mb-4">
  <div class="flex gap-1 border-b border-gray-200">
    <a href="?tab=today" class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?= $tab === 'today' ? 'text-brand border-brand' : 'text-gray-500 border-transparent hover:text-gray-700' ?>">Today</a>
    <a href="?tab=upcoming" class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?= $tab === 'upcoming' ? 'text-brand border-brand' : 'text-gray-500 border-transparent hover:text-gray-700' ?>">Upcoming</a>
    <a href="?tab=missed" class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?= $tab === 'missed' ? 'text-brand border-brand' : 'text-gray-500 border-transparent hover:text-gray-700' ?>">Missed</a>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <?php if (count($tasks) > 0): ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Title</th><th>Customer</th><th>Staff</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($tasks as $t): ?>
          <tr>
            <td data-label="Title"><?= htmlspecialchars($t['title']) ?></td>
            <td data-label="Customer"><?= htmlspecialchars($t['customer_name'] ?? '—') ?></td>
            <td data-label="Staff"><?= htmlspecialchars($t['staff_name'] ?? '—') ?></td>
            <td data-label="Date"><?= date('M j, Y', strtotime($t['scheduled_date'])) ?></td>
            <td data-label="Status"><?php
              $badge = $t['status'] === 'completed' ? 'badge-completed' : ($t['status'] === 'missed' ? 'badge-missed' : 'badge-pending');
              echo '<span class="badge ' . $badge . '">' . ucfirst($t['status']) . '</span>';
            ?></td>
            <td data-label="Action">
              <?php if ($t['status'] === 'pending'): ?>
              <button onclick="openCompleteModal(<?= $t['id'] ?>)" class="btn btn-primary btn-sm">Complete</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <i data-lucide="clipboard-list" class="w-12 h-12 text-gray-300"></i>
      <p class="text-gray-500">No <?= $tab ?> tasks</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Complete Modal -->
<div id="complete-modal" class="modal-overlay" style="display:none">
  <div class="modal-content" style="max-width:400px">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-navy">Mark Task Complete</h3>
      <button onclick="closeCompleteModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
    </div>
    <form method="post">
      <input type="hidden" name="task_id" id="complete-task-id">
      <input type="hidden" name="complete_task" value="1">
      <div class="mb-4">
        <label class="form-label">Completion Date</label>
        <input type="date" name="completed_date" class="form-input" value="<?= $today ?>">
      </div>
      <div class="mb-4">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-textarea" rows="3" placeholder="Completion notes..."></textarea>
      </div>
      <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Confirm</button>
        <button type="button" onclick="closeCompleteModal()" class="btn btn-ghost">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openCompleteModal(id) {
  document.getElementById('complete-task-id').value = id;
  document.getElementById('complete-modal').style.display = 'flex';
}
function closeCompleteModal() {
  document.getElementById('complete-modal').style.display = 'none';
}
</script>

<?php require __DIR__ . '/_footer.php'; ?>
