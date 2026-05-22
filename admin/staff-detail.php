<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
$pdo = getDB();

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: staff.php'); exit; }

$staffList = getStaffWithStats($pdo);
$staff = null;
foreach ($staffList as $s) {
  if ($s['id'] == $id) { $staff = $s; break; }
}
if (!$staff) { header('Location: staff.php'); exit; }

$taskStmt = $pdo->prepare('SELECT t.*, c.name AS customer_name FROM tasks t LEFT JOIN customers c ON t.customer_id = c.id WHERE t.assigned_to = ? ORDER BY t.scheduled_date DESC');
$taskStmt->execute([$id]);
$tasks = $taskStmt->fetchAll();

$pageTitle = $staff['name'];

require __DIR__ . '/_header.php'; ?>

<div class="max-w-4xl mx-auto">
  <div class="card mb-6">
    <div class="card-body">
      <div class="flex items-center gap-4">
        <img src="<?= htmlspecialchars($staff['avatar']) ?>" alt="" class="w-16 h-16 rounded-full" onerror="this.style.display='none'">
        <div>
          <h2 class="text-xl font-bold text-navy"><?= htmlspecialchars($staff['name']) ?></h2>
          <p class="text-gray-500 text-sm"><?= htmlspecialchars($staff['phone'] ?? '') ?></p>
        </div>
      </div>
      <div class="grid grid-cols-4 gap-4 mt-6">
        <div class="text-center p-3 bg-gray-50 rounded-xl">
          <div class="text-2xl font-bold text-navy"><?= $staff['total'] ?></div>
          <div class="text-xs text-gray-500">Total Tasks</div>
        </div>
        <div class="text-center p-3 bg-gray-50 rounded-xl">
          <div class="text-2xl font-bold text-green-600"><?= $staff['completed'] ?></div>
          <div class="text-xs text-gray-500">Completed</div>
        </div>
        <div class="text-center p-3 bg-gray-50 rounded-xl">
          <div class="text-2xl font-bold text-red-500"><?= $staff['missed'] ?></div>
          <div class="text-xs text-gray-500">Missed</div>
        </div>
        <div class="text-center p-3 bg-gray-50 rounded-xl">
          <div class="text-2xl font-bold" style="color:<?= $staff['completionRate'] >= 80 ? '#1DB954' : '#F59E0B' ?>"><?= $staff['completionRate'] ?>%</div>
          <div class="text-xs text-gray-500">Rate</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3 class="font-semibold text-navy">Assigned Tasks (<?= count($tasks) ?>)</h3></div>
    <div class="card-body p-0">
      <?php if (count($tasks) > 0): ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Title</th><th>Customer</th><th>Date</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($tasks as $t): ?>
            <tr>
              <td data-label="Title"><?= htmlspecialchars($t['title']) ?></td>
              <td data-label="Customer"><?= htmlspecialchars($t['customer_name'] ?? '—') ?></td>
              <td data-label="Date"><?= date('M j, Y', strtotime($t['scheduled_date'])) ?></td>
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
      <div class="p-6 text-center text-gray-400 text-sm">No tasks assigned</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
