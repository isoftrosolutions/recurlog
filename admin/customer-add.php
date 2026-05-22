<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

$editId = $_GET['id'] ?? null;
$customer = null;
if ($editId) {
  $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
  $stmt->execute([$editId]);
  $customer = $stmt->fetch();
  if (!$customer) { header('Location: customers.php'); exit; }
  $customer['services_for'] = json_decode($customer['services_for'] ?? '[]', true);
}

$pageTitle = $editId ? 'Edit Customer' : 'Add Customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $servicesFor = isset($_POST['services_for']) ? json_encode($_POST['services_for']) : '[]';
  $lat = $_POST['lat'] ?? null;
  $lng = $_POST['lng'] ?? null;

  if ($name) {
    if ($editId) {
      $stmt = $pdo->prepare('UPDATE customers SET name=?, address=?, phone=?, services_for=?, location_lat=?, location_lng=? WHERE id=?');
      $stmt->execute([$name, $address, $phone, $servicesFor, $lat, $lng, $editId]);
      $msg = 'Customer updated';
    } else {
      $stmt = $pdo->prepare('INSERT INTO customers (name, address, phone, services_for, location_lat, location_lng) VALUES (?, ?, ?, ?, ?, ?)');
      $stmt->execute([$name, $address, $phone, $servicesFor, $lat, $lng]);
      $newId = (int)$pdo->lastInsertId();
      pushNotification($pdo, 'New customer ' . $name . ' registered', 'customer_added', $newId);
      $msg = 'Customer created';
    }
    echo "<script>localStorage.setItem('admin_toast','" . $msg . "');window.location.href='customers.php';</script>";
    exit;
  }
}

$allServiceTypes = ['RO', 'TV', 'Refrigerator', 'AC', 'Washing Machine', 'Other'];

require __DIR__ . '/_header.php'; ?>

<div class="max-w-2xl mx-auto">
  <div class="card">
    <div class="card-header"><h3 class="font-semibold text-navy"><?= $editId ? 'Edit Customer' : 'Add Customer' ?></h3></div>
    <div class="card-body">
      <form method="post">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($customer['name'] ?? '') ?>" required>
          </div>
          <div>
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-input" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" placeholder="+977-...">
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Address</label>
          <textarea name="address" class="form-textarea" rows="2"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
        </div>
        <div class="mb-4">
          <label class="form-label">Service For</label>
          <div class="flex flex-wrap gap-2">
            <?php $selected = $customer['services_for'] ?? []; ?>
            <?php foreach ($allServiceTypes as $st): ?>
            <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 has-checked:border-brand has-checked:bg-brand/5 transition-colors">
              <input type="checkbox" name="services_for[]" value="<?= $st ?>" <?= in_array($st, $selected) ? 'checked' : '' ?> class="accent-brand">
              <span class="text-sm text-gray-700"><?= $st ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-6">
          <div>
            <label class="form-label">Latitude</label>
            <input type="text" name="lat" class="form-input" value="<?= htmlspecialchars($customer['location_lat'] ?? '') ?>" readonly>
          </div>
          <div>
            <label class="form-label">Longitude</label>
            <input type="text" name="lng" class="form-input" value="<?= htmlspecialchars($customer['location_lng'] ?? '') ?>" readonly>
          </div>
        </div>
        <div class="flex gap-3">
          <button type="submit" class="btn btn-primary"><?= $editId ? 'Update' : 'Save' ?></button>
          <a href="customers.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
