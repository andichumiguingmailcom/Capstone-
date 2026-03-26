<?php
// Admin sidebar include – pass $activePage variable before including
$user = getCurrentUser();
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-text">🌾 CoopIMS</div>
    <div class="logo-sub">Admin Portal</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Dashboard</div>
    <a href="admin_dashboard.php" class="nav-item <?= ($activePage??'') === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>

    <div class="nav-section-label">Members</div>
    <a href="admin_members.php" class="nav-item <?= ($activePage??'') === 'members' ? 'active' : '' ?>">
      <span class="nav-icon">👥</span> Member List
    </a>
    <a href="admin_pre_applications.php" class="nav-item <?= ($activePage??'') === 'pre_apps' ? 'active' : '' ?>">
      <span class="nav-icon">📋</span> Pre-Applications
    </a>
    <a href="admin_documents.php" class="nav-item <?= ($activePage??'') === 'documents' ? 'active' : '' ?>">
      <span class="nav-icon">🗂️</span> Documents
    </a>

    <div class="nav-section-label">Loans</div>
    <a href="admin_loan_applications.php" class="nav-item <?= ($activePage??'') === 'loan_apps' ? 'active' : '' ?>">
      <span class="nav-icon">📝</span> Loan Applications
    </a>
    <a href="admin_loans.php" class="nav-item <?= ($activePage??'') === 'loans' ? 'active' : '' ?>">
      <span class="nav-icon">💳</span> Active Loans
    </a>
    <a href="admin_payments.php" class="nav-item <?= ($activePage??'') === 'payments' ? 'active' : '' ?>">
      <span class="nav-icon">💰</span> Payments
    </a>

    <div class="nav-section-label">Store</div>
    <a href="admin_inventory.php" class="nav-item <?= ($activePage??'') === 'inventory' ? 'active' : '' ?>">
      <span class="nav-icon">📦</span> Inventory
    </a>
    <a href="admin_sales.php" class="nav-item <?= ($activePage??'') === 'sales' ? 'active' : '' ?>">
      <span class="nav-icon">🛒</span> Sales
    </a>

    <div class="nav-section-label">Reports & Admin</div>
    <a href="admin_reports.php" class="nav-item <?= ($activePage??'') === 'reports' ? 'active' : '' ?>">
      <span class="nav-icon">📈</span> Reports
    </a>
    <?php if ($user['role'] === 'general_manager'): ?>
      <a href="admin_users.php" class="nav-item <?= ($activePage??'') === 'users' ? 'active' : '' ?>">
        <span class="nav-icon">🔧</span> User Management
      </a>
      <a href="admin_audit.php" class="nav-item <?= ($activePage??'') === 'audit' ? 'active' : '' ?>">
        <span class="nav-icon">🔍</span> Audit Logs
      </a>
    <?php endif; ?>
    <a href="logout.php" class="nav-item" style="color:rgba(255,100,100,0.8);">
      <span class="nav-icon">🚪</span> Logout
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="user-role"><?= ucfirst($user['role']) ?></div>
      </div>
    </div>
  </div>
</aside>
