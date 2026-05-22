<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();
$pageTitle = 'Notifications';

// Mark all read
if (isset($_GET['markAllRead'])) {
  $pdo->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
  echo "<script>localStorage.setItem('admin_toast','All marked as read');window.location.href='notifications.php';</script>";
  exit;
}

$notifs = $pdo->query('SELECT * FROM notifications ORDER BY created_at DESC')->fetchAll();
$unreadCount = 0;
foreach ($notifs as $n) { if (!$n['is_read']) $unreadCount++; }

require __DIR__ . '/_header.php'; ?>

<div class="flex justify-between items-center mb-4">
  <p class="text-sm text-gray-500"><?= $unreadCount ?> unread <?= $unreadCount === 1 ? 'notification' : 'notifications' ?></p>
  <?php if ($unreadCount > 0): ?>
  <a href="?markAllRead=1" class="btn btn-primary btn-sm"><i data-lucide="check-check" class="w-4 h-4"></i> Mark All Read</a>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-body p-0">
    <?php if (count($notifs) > 0): ?>
      <?php foreach ($notifs as $n): ?>
      <div class="px-4 py-3 border-b border-gray-100 last:border-0 flex items-start gap-3 <?= !$n['is_read'] ? 'bg-brand/5 border-l-4 border-l-brand' : '' ?>">
        <?php
          $icon = $n['type'] === 'task_completed' ? 'check-circle' : ($n['type'] === 'task_missed' ? 'alert-circle' : ($n['type'] === 'customer_added' ? 'user-plus' : ($n['type'] === 'service_added' ? 'wrench' : 'info')));
          $color = $n['type'] === 'task_completed' ? '#1DB954' : ($n['type'] === 'task_missed' ? '#EF4444' : '#3B82F6');
        ?>
        <i data-lucide="<?= $icon ?>" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:<?= $color ?>"></i>
        <div class="flex-1 min-w-0">
          <p class="text-sm text-gray-700"><?= htmlspecialchars($n['text']) ?></p>
          <p class="text-xs text-gray-400 mt-0.5"><?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></p>
        </div>
        <?php if (!$n['is_read']): ?><span class="w-2 h-2 rounded-full bg-brand flex-shrink-0 mt-2"></span><?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
    <div class="empty-state">
      <i data-lucide="bell" class="w-12 h-12 text-gray-300"></i>
      <p class="text-gray-500">No notifications</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
