<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();
$pageTitle = 'Dashboard';

$today = date('Y-m-d');

$totalCustomers = (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$totalStaff = (int)$pdo->query('SELECT COUNT(*) FROM staff')->fetchColumn();
$totalServices = (int)$pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE scheduled_date = ?");
$stmt->execute([$today]);
$todayTasks = (int)$stmt->fetchColumn();

$missedTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'missed'")->fetchColumn();

$stmt = $pdo->prepare("SELECT t.*, c.name AS customer_name, s.name AS staff_name FROM tasks t LEFT JOIN customers c ON t.customer_id = c.id LEFT JOIN staff s ON t.assigned_to = s.id WHERE t.scheduled_date = ? ORDER BY t.status ASC, t.created_at DESC LIMIT 10");
$stmt->execute([$today]);
$todaysSchedule = $stmt->fetchAll();

$recentNotifs = $pdo->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 8')->fetchAll();

require __DIR__ . '/_header.php'; ?>

<!-- Stat cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="card p-4 flex items-center gap-4">
    <div class="stat-icon" style="background:#D1FAE5;color:#1DB954"><i data-lucide="users" class="w-5 h-5"></i></div>
    <div><div class="text-2xl font-bold text-navy"><?= $totalCustomers ?></div><div class="text-xs text-gray-500">Customers</div></div>
  </div>
  <div class="card p-4 flex items-center gap-4">
    <div class="stat-icon" style="background:#DBEAFE;color:#3B82F6"><i data-lucide="clipboard-list" class="w-5 h-5"></i></div>
    <div><div class="text-2xl font-bold text-navy"><?= $todayTasks ?></div><div class="text-xs text-gray-500">Tasks Today</div></div>
  </div>
  <div class="card p-4 flex items-center gap-4">
    <div class="stat-icon" style="background:#FEE2E2;color:#EF4444"><i data-lucide="alert-circle" class="w-5 h-5"></i></div>
    <div><div class="text-2xl font-bold text-navy"><?= $missedTasks ?></div><div class="text-xs text-gray-500">Missed Tasks</div></div>
  </div>
  <div class="card p-4 flex items-center gap-4">
    <div class="stat-icon" style="background:#FEF3C7;color:#F59E0B"><i data-lucide="briefcase" class="w-5 h-5"></i></div>
    <div><div class="text-2xl font-bold text-navy"><?= $totalStaff ?></div><div class="text-xs text-gray-500">Staff</div></div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  <!-- Today's Schedule -->
  <div class="card">
    <div class="card-header"><h3 class="font-semibold text-navy">Today's Schedule</h3></div>
    <div class="card-body p-0">
      <?php if (count($todaysSchedule) > 0): ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Task</th><th>Customer</th><th>Staff</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($todaysSchedule as $t): ?>
            <tr>
              <td data-label="Task"><?= htmlspecialchars($t['title']) ?></td>
              <td data-label="Customer"><?= htmlspecialchars($t['customer_name'] ?? '—') ?></td>
              <td data-label="Staff"><?= htmlspecialchars($t['staff_name'] ?? '—') ?></td>
              <td data-label="Status"><?php
                $badge = $t['status'] === 'completed' ? 'badge-completed' : ($t['status'] === 'missed' ? 'badge-missed' : 'badge-pending');
                echo '<span class="badge ' . $badge . '">' . ucfirst($t['status']) . '</span>';
              ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="p-6 text-center text-gray-400 text-sm">No tasks scheduled for today</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Notifications -->
  <div class="card">
    <div class="card-header flex items-center justify-between">
      <h3 class="font-semibold text-navy">Recent Activity</h3>
      <a href="notifications.php" class="text-brand text-sm hover:underline">View all</a>
    </div>
    <div class="card-body p-0">
      <?php if (count($recentNotifs) > 0): ?>
        <?php foreach ($recentNotifs as $n): ?>
        <div class="px-4 py-3 border-b border-gray-100 last:border-0 flex items-start gap-3 <?= !$n['is_read'] ? 'bg-brand/5' : '' ?>">
          <i data-lucide="<?= $n['type'] === 'task_completed' ? 'check-circle' : ($n['type'] === 'task_missed' ? 'alert-circle' : ($n['type'] === 'customer_added' ? 'user-plus' : 'info')) ?>" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:<?= $n['type'] === 'task_completed' ? '#1DB954' : ($n['type'] === 'task_missed' ? '#EF4444' : '#3B82F6') ?>"></i>
          <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-700"><?= htmlspecialchars($n['text']) ?></p>
            <p class="text-xs text-gray-400 mt-0.5"><?= date('M j, g:i A', strtotime($n['created_at'])) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
      <div class="p-6 text-center text-gray-400 text-sm">No recent activity</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
