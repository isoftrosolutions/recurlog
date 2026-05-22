<?php
/**
 * Admin page header — includes sidebar, nav, and opening HTML tags.
 * Assumes _auth.php has already been included.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($page) { global $currentPage; return $currentPage === $page; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'Admin' ?> — Recurlog</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { brand: '#1DB954', navy: '#0B1E3D', amber: '#F59E0B', danger: '#EF4444' }, fontFamily: { sans: ['Poppins', 'sans-serif'] } } } }
  </script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="../assets/css/custom.css">
  <link rel="stylesheet" href="../admin-assets/admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">

<aside id="admin-sidebar" class="sidebar">
  <div class="flex items-center gap-3 px-4 h-16 border-b border-white/10">
    <div class="w-8 h-8 bg-brand rounded-lg flex items-center justify-center flex-shrink-0">
      <i data-lucide="wrench" class="w-4 h-4 text-white"></i>
    </div>
    <span class="sidebar-brand-name text-lg font-bold text-white tracking-tight">Recurlog</span>
  </div>

  <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
    <div class="sidebar-section-title text-xs font-semibold text-white/40 uppercase tracking-wider px-3 mb-2">Main</div>

    <a href="dashboard.php" class="sidebar-link <?= isActive('dashboard.php') ? 'active' : '' ?>">
      <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
      <span>Dashboard</span>
    </a>
    <a href="customers.php" class="sidebar-link <?= isActive('customers.php') || isActive('customer-add.php') || isActive('customer-detail.php') ? 'active' : '' ?>">
      <i data-lucide="users" class="w-5 h-5"></i>
      <span>Customers</span>
    </a>
    <a href="services.php" class="sidebar-link <?= isActive('services.php') || isActive('service-add.php') ? 'active' : '' ?>">
      <i data-lucide="wrench" class="w-5 h-5"></i>
      <span>Services</span>
    </a>
    <a href="tasks.php" class="sidebar-link <?= isActive('tasks.php') ? 'active' : '' ?>">
      <i data-lucide="clipboard-list" class="w-5 h-5"></i>
      <span>Tasks</span>
    </a>
    <a href="staff.php" class="sidebar-link <?= isActive('staff.php') || isActive('staff-detail.php') ? 'active' : '' ?>">
      <i data-lucide="briefcase" class="w-5 h-5"></i>
      <span>Staff</span>
    </a>
    <a href="categories.php" class="sidebar-link <?= isActive('categories.php') ? 'active' : '' ?>">
      <i data-lucide="tag" class="w-5 h-5"></i>
      <span>Categories</span>
    </a>

    <div class="sidebar-section-title text-xs font-semibold text-white/40 uppercase tracking-wider px-3 mt-6 mb-2">System</div>

    <a href="notifications.php" class="sidebar-link <?= isActive('notifications.php') ? 'active' : '' ?>">
      <i data-lucide="bell" class="w-5 h-5"></i>
      <span>Notifications</span>
    </a>
    <a href="logout.php" class="sidebar-link">
      <i data-lucide="log-out" class="w-5 h-5"></i>
      <span>Logout</span>
    </a>
  </nav>
</aside>

<div id="sidebar-backdrop" class="fixed inset-0 bg-black/40 z-30 hidden md:hidden" onclick="toggleAdminSidebar()"></div>

<div class="main-content">
  <header class="bg-white border-b border-gray-200 sticky top-0 z-20" style="height:var(--header-height,56px)">
    <div class="flex items-center justify-between px-4 h-full">
      <div class="flex items-center gap-3">
        <button onclick="toggleAdminSidebar()" class="p-2 rounded-lg hover:bg-gray-100 text-gray-600">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <h1 class="text-lg font-semibold text-navy"><?= $pageTitle ?? 'Admin' ?></h1>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-sm text-gray-500"><?= htmlspecialchars($adminUser) ?></span>
        <div class="w-8 h-8 bg-brand rounded-full flex items-center justify-center text-white text-xs font-bold">
          <?= strtoupper(substr($adminUser, 0, 1)) ?>
        </div>
      </div>
    </div>
  </header>

  <main class="p-4 md:p-6" style="min-height:calc(100vh - 56px)">
    <div id="toast-container"></div>
