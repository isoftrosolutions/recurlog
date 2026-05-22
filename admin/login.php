<?php session_start(); if (!empty($_SESSION['admin_logged_in'])) { header('Location: dashboard.php'); exit; }

require_once __DIR__ . '/../config/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($email && $password) {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['admin_logged_in'] = true;
      $_SESSION['admin_user'] = $user['name'];
      $_SESSION['admin_email'] = $user['email'];
      header('Location: dashboard.php');
      exit;
    }
    $error = 'Invalid email or password';
  } else {
    $error = 'Please enter email and password';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Recurlog Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { brand: '#1DB954', navy: '#0B1E3D', amber: '#F59E0B', danger: '#EF4444' }, fontFamily: { sans: ['Poppins', 'sans-serif'] } } } }
  </script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../assets/css/custom.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { background: var(--color-navy); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .login-card { animation: fadeIn 0.6s ease-out; }
  </style>
</head>
<body>
  <div class="login-card w-full max-w-md px-4">
    <div class="text-center mb-8">
      <div class="w-16 h-16 bg-brand rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-brand/25">
        <i data-lucide="wrench" class="w-8 h-8 text-white"></i>
      </div>
      <h1 class="text-3xl font-extrabold text-white tracking-tight">Recurlog</h1>
      <p class="text-white/60 text-sm mt-1">Admin Panel</p>
    </div>

    <div class="bg-white rounded-2xl shadow-xl p-6">
      <h2 class="text-lg font-semibold text-navy mb-4">Sign In</h2>

      <?php if ($error): ?>
        <div class="bg-red-50 text-red-700 text-sm rounded-lg px-4 py-3 mb-4 flex items-center gap-2">
          <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-4">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-input" placeholder="admin@demo.com" value="admin@demo.com" required>
        </div>
        <div class="mb-6">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-input" placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn btn-primary w-full">Sign In</button>
      </form>
    </div>

    <p class="text-center text-white/30 text-xs mt-6">&copy; 2026 Recurlog</p>
  </div>
  <script>lucide.createIcons()</script>
</body>
</html>
