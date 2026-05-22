/* Admin Panel JavaScript */

// Sidebar toggle
function toggleAdminSidebar() {
  var sidebar = document.getElementById('admin-sidebar');
  var backdrop = document.getElementById('sidebar-backdrop');
  if (window.innerWidth < 768) {
    sidebar.classList.toggle('open');
    backdrop.classList.toggle('hidden');
  } else {
    sidebar.classList.toggle('collapsed');
    var collapsed = sidebar.classList.contains('collapsed');
    try { localStorage.setItem('fscrm_sidebar_collapsed', collapsed ? 'true' : 'false'); } catch(e) {}
  }
}

// Show toast
function showAdminToast(message, type) {
  type = type || 'info';
  var container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = 'position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:360px;width:100%;pointer-events:none';
    document.body.appendChild(container);
  }
  var colors = { success: '#1DB954', error: '#EF4444', info: '#3B82F6' };
  var icons = { success: 'check-circle', error: 'alert-circle', info: 'info' };
  var toast = document.createElement('div');
  toast.style.cssText = 'background:white;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,0.12);border-left:4px solid ' + (colors[type] || '#3B82F6') + ';padding:12px 16px;display:flex;align-items:center;gap:10px;animation:slideIn 0.25s ease-out;pointer-events:auto;font-size:14px';
  toast.innerHTML = '<i data-lucide="' + (icons[type] || 'info') + '" class="w-5 h-5" style="color:' + (colors[type] || '#3B82F6') + ';flex-shrink:0"></i><span style="flex:1">' + message + '</span>';
  container.appendChild(toast);
  try { lucide.createIcons(); } catch(e) {}
  setTimeout(function() {
    toast.style.animation = 'slideOut 0.25s ease-in forwards';
    setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 250);
  }, 2500);
}

// Confirm dialog
function adminConfirm(message, callback) {
  if (confirm(message)) callback();
}

// Init sidebar state
document.addEventListener('DOMContentLoaded', function() {
  var sidebar = document.getElementById('admin-sidebar');
  if (sidebar && window.innerWidth >= 768) {
    var collapsed = localStorage.getItem('fscrm_sidebar_collapsed') === 'true';
    if (collapsed) sidebar.classList.add('collapsed');
  }
  if (typeof lucide !== 'undefined') lucide.createIcons();
});
