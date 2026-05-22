<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();
$pageTitle = 'Customers';

$search = $_GET['search'] ?? '';
if ($search) {
  $stmt = $pdo->prepare("SELECT * FROM customers WHERE name LIKE ? OR address LIKE ? OR phone LIKE ? ORDER BY name ASC");
  $like = "%$search%";
  $stmt->execute([$like, $like, $like]);
} else {
  $stmt = $pdo->query('SELECT * FROM customers ORDER BY name ASC');
}
$customers = $stmt->fetchAll();

require __DIR__ . '/_header.php'; ?>

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
  <form method="get" class="flex gap-2 w-full sm:w-auto">
    <input type="text" name="search" class="form-input" placeholder="Search customers..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="search" class="w-4 h-4"></i></button>
    <?php if ($search): ?><a href="customers.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
  </form>
  <a href="customer-add.php" class="btn btn-primary btn-sm"><i data-lucide="plus" class="w-4 h-4"></i> Add Customer</a>
</div>

<div class="card">
  <div class="card-body p-0">
    <?php if (count($customers) > 0): ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Name</th><th>Address</th><th>Phone</th><th>Services</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($customers as $c):
            $services = json_decode($c['services_for'] ?? '[]', true);
          ?>
          <tr>
            <td data-label="Name" class="font-medium text-navy"><?= htmlspecialchars($c['name']) ?></td>
            <td data-label="Address"><?= htmlspecialchars($c['address'] ?? '—') ?></td>
            <td data-label="Phone"><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
            <td data-label="Services">
              <?php foreach ($services as $svc): ?>
              <span class="badge badge-info"><?= htmlspecialchars($svc) ?></span>
              <?php endforeach; ?>
            </td>
            <td data-label="Actions">
              <div class="flex gap-1">
                <a href="customer-detail.php?id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm"><i data-lucide="eye" class="w-4 h-4"></i></a>
                <a href="customer-add.php?id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm"><i data-lucide="edit" class="w-4 h-4"></i></a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <i data-lucide="users" class="w-12 h-12 text-gray-300"></i>
      <p class="text-gray-500"><?= $search ? 'No customers match your search' : 'No customers yet' ?></p>
      <?php if (!$search): ?><a href="customer-add.php" class="btn btn-primary btn-sm mt-3">Add First Customer</a><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
