<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();
$pageTitle = 'Categories';

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
  $name = trim($_POST['name'] ?? '');
  $color = trim($_POST['color'] ?? '#1DB954');
  $editId = $_POST['edit_id'] ?? null;
  if ($name) {
    if ($editId) {
      $stmt = $pdo->prepare('UPDATE categories SET name=?, color=? WHERE id=?');
      $stmt->execute([$name, $color, $editId]);
    } else {
      $stmt = $pdo->prepare('INSERT INTO categories (name, color) VALUES (?, ?)');
      $stmt->execute([$name, $color]);
    }
    echo "<script>localStorage.setItem('admin_toast','Category saved');window.location.href='categories.php';</script>";
    exit;
  }
}

// Handle delete
if (isset($_GET['delete'])) {
  $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
  $stmt->execute([$_GET['delete']]);
  echo "<script>localStorage.setItem('admin_toast','Category deleted');window.location.href='categories.php';</script>";
  exit;
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();

require __DIR__ . '/_header.php'; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- Add/edit form -->
  <div class="card lg:col-span-1">
    <div class="card-header"><h3 class="font-semibold text-navy">Add Category</h3></div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="edit_id" id="edit-id" value="">
        <div class="mb-4">
          <label class="form-label">Name</label>
          <input type="text" name="name" id="cat-name" class="form-input" required>
        </div>
        <div class="mb-4">
          <label class="form-label">Color</label>
          <div class="flex gap-2 items-center">
            <input type="color" name="color" id="cat-color" class="w-10 h-10 rounded border border-gray-200 cursor-pointer" value="#1DB954">
            <span id="color-preview" class="text-sm text-gray-500">#1DB954</span>
          </div>
        </div>
        <div class="flex gap-2">
          <button type="submit" name="save" class="btn btn-primary">Save</button>
          <button type="button" onclick="resetForm()" class="btn btn-ghost">Reset</button>
        </div>
      </form>
    </div>
  </div>

  <!-- List -->
  <div class="card lg:col-span-2">
    <div class="card-header"><h3 class="font-semibold text-navy">All Categories (<?= count($categories) ?>)</h3></div>
    <div class="card-body p-0">
      <?php if (count($categories) > 0): ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Name</th><th>Color</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
              <td data-label="Name">
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-full" style="background:<?= htmlspecialchars($cat['color']) ?>"></span>
                  <?= htmlspecialchars($cat['name']) ?>
                </div>
              </td>
              <td data-label="Color"><code class="text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($cat['color']) ?></code></td>
              <td data-label="Actions">
                <div class="flex gap-1">
                  <button onclick="editCat(<?= $cat['id'] ?>,'<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>','<?= $cat['color'] ?>')" class="btn btn-ghost btn-sm"><i data-lucide="edit" class="w-4 h-4"></i></button>
                  <a href="?delete=<?= $cat['id'] ?>" class="btn btn-ghost btn-sm text-red-500" onclick="return confirm('Delete this category?')"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="p-6 text-center text-gray-400 text-sm">No categories yet</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function editCat(id, name, color) {
  document.getElementById('edit-id').value = id;
  document.getElementById('cat-name').value = name;
  document.getElementById('cat-color').value = color;
  document.getElementById('color-preview').textContent = color;
}
function resetForm() {
  document.getElementById('edit-id').value = '';
  document.getElementById('cat-name').value = '';
  document.getElementById('cat-color').value = '#1DB954';
  document.getElementById('color-preview').textContent = '#1DB954';
}
document.getElementById('cat-color').addEventListener('input', function() {
  document.getElementById('color-preview').textContent = this.value;
});
</script>

<?php require __DIR__ . '/_footer.php'; ?>
