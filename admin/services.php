<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();
$pageTitle = 'Services';

$stmt = $pdo->query('SELECT s.*, c.name AS customer_name, cat.name AS category_name, st.name AS staff_name FROM services s LEFT JOIN customers c ON s.customer_id = c.id LEFT JOIN categories cat ON s.category_id = cat.id LEFT JOIN staff st ON s.assigned_to = st.id ORDER BY s.created_at DESC');
$services = $stmt->fetchAll();

require __DIR__ . '/_header.php'; ?>

<div class="flex justify-end mb-4">
  <a href="service-add.php" class="btn btn-primary btn-sm"><i data-lucide="plus" class="w-4 h-4"></i> Add Service</a>
</div>

<div class="card">
  <div class="card-body p-0">
    <?php if (count($services) > 0): ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Title</th><th>Customer</th><th>Category</th><th>Type</th><th>Assigned To</th><th>Next Date</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($services as $s): ?>
          <tr>
            <td data-label="Title"><?= htmlspecialchars($s['title'] ?: 'Service #' . $s['id']) ?></td>
            <td data-label="Customer"><?= htmlspecialchars($s['customer_name'] ?? '—') ?></td>
            <td data-label="Category"><?= htmlspecialchars($s['category_name'] ?? '—') ?></td>
            <td data-label="Type"><?= $s['is_recurring'] ? '<span class="badge badge-completed">Recurring</span>' : '<span class="badge badge-info">One-Time</span>' ?></td>
            <td data-label="Staff"><?= htmlspecialchars($s['staff_name'] ?? '—') ?></td>
            <td data-label="Date"><?= $s['first_scheduled_date'] ? date('M j, Y', strtotime($s['first_scheduled_date'])) : '—' ?></td>
            <td data-label="Actions">
              <a href="service-add.php?id=<?= $s['id'] ?>" class="btn btn-ghost btn-sm"><i data-lucide="edit" class="w-4 h-4"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <i data-lucide="wrench" class="w-12 h-12 text-gray-300"></i>
      <p class="text-gray-500">No services yet</p>
      <a href="service-add.php" class="btn btn-primary btn-sm mt-3">Add First Service</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
