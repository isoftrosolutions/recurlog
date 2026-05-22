<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
$pdo = getDB();

$editId = $_GET['id'] ?? null;
$presetCustomerId = $_GET['customer_id'] ?? null;
$service = null;
if ($editId) {
  $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
  $stmt->execute([$editId]);
  $service = $stmt->fetch();
  if (!$service) { header('Location: services.php'); exit; }
}

$customers = $pdo->query('SELECT id, name FROM customers ORDER BY name ASC')->fetchAll();
$categories = $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
$staff = $pdo->query('SELECT * FROM staff ORDER BY name ASC')->fetchAll();

$pageTitle = $editId ? 'Edit Service' : 'Add Service';

$equipmentTypes = ['RO', 'TV', 'Refrigerator', 'AC', 'Washing Machine', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $customerId = (int)($_POST['customer_id'] ?? 0);
  $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
  $serviceFor = $_POST['service_for'] ?? '';
  $title = trim($_POST['title'] ?? '');
  $isRecurring = !empty($_POST['is_recurring']);
  $firstDate = $_POST['first_scheduled_date'] ?? date('Y-m-d');
  $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
  $notes = trim($_POST['notes'] ?? '');
  $recValue = $isRecurring && !empty($_POST['rec_value']) ? (int)$_POST['rec_value'] : null;
  $recUnit = $isRecurring && !empty($_POST['rec_unit']) ? $_POST['rec_unit'] : null;
  $recFrom = $isRecurring ? ($_POST['rec_repeat_from'] ?? 'last_service') : 'last_service';

  if ($customerId) {
    if ($editId) {
      $stmt = $pdo->prepare('UPDATE services SET customer_id=?, category_id=?, service_for=?, title=?, is_recurring=?, first_scheduled_date=?, assigned_to=?, notes=?, recurrence_value=?, recurrence_unit=?, recurrence_repeat_from=? WHERE id=?');
      $stmt->execute([$customerId, $categoryId, $serviceFor, $title, $isRecurring ? 1 : 0, $firstDate, $assignedTo, $notes, $recValue, $recUnit, $recFrom, $editId]);
      echo "<script>localStorage.setItem('admin_toast','Service updated');window.location.href='services.php';</script>";
      exit;
    }

    // Create service + first task
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare('INSERT INTO services (customer_id, category_id, service_for, title, is_recurring, first_scheduled_date, assigned_to, notes, recurrence_value, recurrence_unit, recurrence_repeat_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
      $stmt->execute([$customerId, $categoryId, $serviceFor, $title, 1, $firstDate, $assignedTo, $notes, $recValue, $recUnit, $recFrom]);
      $svcId = (int)$pdo->lastInsertId();

      $catStmt = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
      $catStmt->execute([$categoryId ?? 0]);
      $catName = $catStmt->fetchColumn() ?: 'Service';

      $custStmt = $pdo->prepare('SELECT name FROM customers WHERE id = ?');
      $custStmt->execute([$customerId]);
      $custName = $custStmt->fetchColumn();

      $taskTitle = $catName . ' - ' . $custName;

      $tStmt = $pdo->prepare('INSERT INTO tasks (service_id, customer_id, title, status, scheduled_date, assigned_to, notes, category_id) VALUES (?, ?, ?, "pending", ?, ?, ?, ?)');
      $tStmt->execute([$svcId, $customerId, $taskTitle, $firstDate, $assignedTo, $notes, $categoryId]);

      $staffStmt = $pdo->prepare('SELECT name FROM staff WHERE id = ?');
      $staffStmt->execute([$assignedTo ?? 0]);
      $staffName = $staffStmt->fetchColumn() ?: 'Unassigned';

      pushNotification($pdo, $staffName . ' assigned to ' . $catName . ' for ' . $custName . ' on ' . $firstDate, 'service_added', $svcId);

      $pdo->commit();
      echo "<script>localStorage.setItem('admin_toast','Service created');window.location.href='services.php';</script>";
      exit;
    } catch (Exception $e) {
      $pdo->rollBack();
      $error = 'Error: ' . $e->getMessage();
    }
  } else {
    $error = 'Customer is required';
  }
}

require __DIR__ . '/_header.php'; ?>

<div class="max-w-2xl mx-auto">
  <div class="card">
    <div class="card-header"><h3 class="font-semibold text-navy"><?= $editId ? 'Edit Service' : 'Add Service' ?></h3></div>
    <div class="card-body">
      <?php if (isset($error)): ?>
      <div class="bg-red-50 text-red-700 text-sm rounded-lg px-4 py-3 mb-4"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="form-label">Customer *</label>
            <select name="customer_id" class="form-select" required>
              <option value="">Select customer...</option>
              <?php foreach ($customers as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($editId && $service['customer_id'] == $c['id']) || ($presetCustomerId == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
              <option value="">Select...</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $editId && $service['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="form-label">Service For</label>
            <select name="service_for" class="form-select">
              <option value="">Select...</option>
              <?php foreach ($equipmentTypes as $et): ?>
              <option value="<?= $et ?>" <?= $editId && $service['service_for'] === $et ? 'selected' : '' ?>><?= $et ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-input" value="<?= htmlspecialchars($service['title'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label">Service Type</label>
          <div class="flex gap-3">
            <label class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 cursor-pointer has-checked:border-brand has-checked:bg-brand/5">
              <input type="radio" name="is_recurring" value="1" <?= $editId && $service['is_recurring'] ? 'checked' : (!$editId ? 'checked' : '') ?> class="accent-brand" onchange="toggleRecurrence()">
              <span class="text-sm font-medium">Recurring</span>
            </label>
            <label class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 cursor-pointer has-checked:border-brand has-checked:bg-brand/5">
              <input type="radio" name="is_recurring" value="0" <?= $editId && !$service['is_recurring'] ? 'checked' : '' ?> class="accent-brand" onchange="toggleRecurrence()">
              <span class="text-sm font-medium">One-Time</span>
            </label>
          </div>
        </div>

        <div id="recurrence-section" class="p-4 bg-gray-50 rounded-xl mb-4 border border-gray-200" style="<?= $editId && !$service['is_recurring'] ? 'display:none' : '' ?>">
          <h4 class="text-sm font-semibold text-navy mb-3">Recurrence Settings</h4>
          <div class="grid grid-cols-3 gap-3 mb-3">
            <div>
              <label class="form-label">Repeat Every</label>
              <input type="number" name="rec_value" class="form-input" value="<?= htmlspecialchars($service['recurrence_value'] ?? '30') ?>" min="1">
            </div>
            <div>
              <label class="form-label">Unit</label>
              <select name="rec_unit" class="form-select">
                <?php foreach (['days'=>'Days','weeks'=>'Weeks','months'=>'Months','years'=>'Years'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $editId && $service['recurrence_unit'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Repeat From</label>
              <select name="rec_repeat_from" class="form-select">
                <option value="last_service" <?= $editId && ($service['recurrence_repeat_from'] ?? '') === 'last_service' ? 'selected' : '' ?>>Last Done Date</option>
                <option value="fixed_schedule" <?= $editId && ($service['recurrence_repeat_from'] ?? '') === 'fixed_schedule' ? 'selected' : '' ?>>Fixed Schedule</option>
              </select>
            </div>
          </div>
          <p class="text-xs text-gray-400" id="rec-preview">This service will repeat every 30 days from the last done date.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="form-label">First Scheduled Date</label>
            <input type="date" name="first_scheduled_date" class="form-input" value="<?= $service['first_scheduled_date'] ?? date('Y-m-d') ?>">
          </div>
          <div>
            <label class="form-label">Assign To</label>
            <select name="assigned_to" class="form-select">
              <option value="">Unassigned</option>
              <?php foreach ($staff as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $editId && $service['assigned_to'] == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-textarea" rows="3"><?= htmlspecialchars($service['notes'] ?? '') ?></textarea>
        </div>

        <div class="flex gap-3">
          <button type="submit" class="btn btn-primary"><?= $editId ? 'Update Service' : 'Create Service' ?></button>
          <a href="services.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleRecurrence() {
  var section = document.getElementById('recurrence-section');
  var recurring = document.querySelector('input[name="is_recurring"][value="1"]');
  section.style.display = recurring && recurring.checked ? 'block' : 'none';
}
document.querySelectorAll('input[name="rec_value"], select[name="rec_unit"], select[name="rec_repeat_from"]').forEach(function(el) {
  el.addEventListener('change', function() {
    var val = document.querySelector('input[name="rec_value"]').value || '30';
    var unit = document.querySelector('select[name="rec_unit"]').value || 'days';
    var from = document.querySelector('select[name="rec_repeat_from"]').value || 'last_service';
    document.getElementById('rec-preview').textContent = 'This service will repeat every ' + val + ' ' + unit + ' from the ' + (from === 'last_service' ? 'last done date' : 'fixed schedule');
  });
});
</script>

<?php require __DIR__ . '/_footer.php'; ?>
