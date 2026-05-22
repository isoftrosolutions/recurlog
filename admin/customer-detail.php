<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: customers.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) { header('Location: customers.php'); exit; }
$customer['services_for'] = json_decode($customer['services_for'] ?? '[]', true);

$svcStmt = $pdo->prepare('SELECT s.*, cat.name AS category_name FROM services s LEFT JOIN categories cat ON s.category_id = cat.id WHERE s.customer_id = ? ORDER BY s.created_at DESC');
$svcStmt->execute([$id]);
$services = $svcStmt->fetchAll();

$taskStmt = $pdo->prepare("SELECT t.*, s.name AS staff_name FROM tasks t LEFT JOIN staff s ON t.assigned_to = s.id WHERE t.customer_id = ? ORDER BY t.scheduled_date DESC");
$taskStmt->execute([$id]);
$tasks = $taskStmt->fetchAll();

$pageTitle = $customer['name'];

require __DIR__ . '/_header.php'; ?>

<div class="max-w-4xl mx-auto">
  <!-- Customer info card -->
  <div class="card mb-6">
    <div class="card-body">
      <div class="flex items-start justify-between">
        <div>
          <h2 class="text-xl font-bold text-navy"><?= htmlspecialchars($customer['name']) ?></h2>
          <p class="text-gray-500 text-sm mt-1"><?= htmlspecialchars($customer['phone'] ?? '') ?> &middot; <?= htmlspecialchars($customer['address'] ?? '') ?></p>
          <div class="flex gap-1 mt-2">
            <?php foreach ($customer['services_for'] as $svc): ?>
            <span class="badge badge-info"><?= htmlspecialchars($svc) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <a href="customer-add.php?id=<?= $customer['id'] ?>" class="btn btn-ghost btn-sm"><i data-lucide="edit" class="w-4 h-4"></i> Edit</a>
      </div>
    </div>
  </div>

  <!-- Services -->
  <div class="card mb-6">
    <div class="card-header flex items-center justify-between">
      <h3 class="font-semibold text-navy">Services (<?= count($services) ?>)</h3>
      <a href="service-add.php?customer_id=<?= $customer['id'] ?>" class="btn btn-primary btn-sm"><i data-lucide="plus" class="w-4 h-4"></i> Add Service</a>
    </div>
    <div class="card-body p-0">
      <?php if (count($services) > 0): ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Title</th><th>Category</th><th>Type</th><th>Next Due</th><th>Assigned To</th></tr></thead>
          <tbody>
            <?php foreach ($services as $s): ?>
            <tr>
              <td data-label="Title"><?= htmlspecialchars($s['title'] ?: 'Service #' . $s['id']) ?></td>
              <td data-label="Category"><?= htmlspecialchars($s['category_name'] ?? '—') ?></td>
              <td data-label="Type"><?= $s['is_recurring'] ? '<span class="badge badge-completed">Recurring</span>' : '<span class="badge badge-info">One-Time</span>' ?></td>
              <td data-label="Next Due"><?= $s['first_scheduled_date'] ? date('M j, Y', strtotime($s['first_scheduled_date'])) : '—' ?></td>
              <td data-label="Assigned To"><?php
                $staffStmt = $pdo->prepare('SELECT name FROM staff WHERE id = ?');
                $staffStmt->execute([$s['assigned_to']]);
                echo htmlspecialchars($staffStmt->fetchColumn() ?: '—');
              ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="p-6 text-center text-gray-400 text-sm">No services for this customer</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Tasks -->
  <div class="card">
    <div class="card-header"><h3 class="font-semibold text-navy">Task History (<?= count($tasks) ?>)</h3></div>
    <div class="card-body p-0">
      <?php if (count($tasks) > 0): ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Title</th><th>Date</th><th>Staff</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($tasks as $t): ?>
            <tr>
              <td data-label="Title"><?= htmlspecialchars($t['title']) ?></td>
              <td data-label="Date"><?= date('M j, Y', strtotime($t['scheduled_date'])) ?></td>
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
      <div class="p-6 text-center text-gray-400 text-sm">No tasks for this customer</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
