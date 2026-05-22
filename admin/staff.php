<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
$pdo = getDB();
$pageTitle = 'Staff';

$staffList = getStaffWithStats($pdo);

require __DIR__ . '/_header.php'; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
  <?php foreach ($staffList as $s): ?>
  <div class="card p-4">
    <div class="flex items-start gap-4">
      <img src="<?= htmlspecialchars($s['avatar']) ?>" alt="" class="w-12 h-12 rounded-full" onerror="this.style.display='none'">
      <div class="flex-1 min-w-0">
        <h3 class="font-semibold text-navy"><?= htmlspecialchars($s['name']) ?></h3>
        <p class="text-xs text-gray-500"><?= htmlspecialchars($s['phone'] ?? '') ?></p>
        <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
          <span><strong class="text-navy"><?= $s['total'] ?></strong> Tasks</span>
          <span><strong class="text-green-600"><?= $s['completed'] ?></strong> Done</span>
          <span><strong class="text-red-500"><?= $s['missed'] ?></strong> Missed</span>
        </div>
        <div class="mt-2 h-2 bg-gray-100 rounded-full overflow-hidden">
          <div class="h-full rounded-full transition-all" style="width:<?= $s['completionRate'] ?>%;background:<?= $s['completionRate'] >= 80 ? '#1DB954' : ($s['completionRate'] >= 50 ? '#F59E0B' : '#EF4444') ?>"></div>
        </div>
        <div class="flex items-center justify-between mt-1">
          <span class="text-xs font-medium" style="color:<?= $s['completionRate'] >= 80 ? '#1DB954' : ($s['completionRate'] >= 50 ? '#F59E0B' : '#EF4444') ?>"><?= $s['completionRate'] ?>%</span>
          <a href="staff-detail.php?id=<?= $s['id'] ?>" class="text-brand text-xs hover:underline">View Profile</a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
