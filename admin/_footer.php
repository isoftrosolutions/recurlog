  </main>
</div>

<!-- Bottom Nav (mobile only) -->
<nav class="bottom-nav md:hidden">
  <a href="dashboard.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : 'text-gray-500' ?>">
    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium truncate w-full text-center">Dashboard</span>
  </a>
  <a href="customers.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= in_array(basename($_SERVER['PHP_SELF']), ['customers.php','customer-add.php','customer-detail.php']) ? 'active' : 'text-gray-500' ?>">
    <i data-lucide="users" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium truncate w-full text-center">Customers</span>
  </a>
  <a href="services.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= in_array(basename($_SERVER['PHP_SELF']), ['services.php','service-add.php']) ? 'active' : 'text-gray-500' ?>">
    <i data-lucide="wrench" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium truncate w-full text-center">Services</span>
  </a>
  <a href="tasks.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : 'text-gray-500' ?>">
    <i data-lucide="clipboard-list" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium truncate w-full text-center">Tasks</span>
  </a>
  <a href="staff.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= basename($_SERVER['PHP_SELF']) === 'staff.php' ? 'active' : 'text-gray-500' ?>">
    <i data-lucide="briefcase" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium truncate w-full text-center">Staff</span>
  </a>
</nav>

<script src="../admin-assets/admin.js"></script>
<script>lucide.createIcons()</script>
</body>
</html>
