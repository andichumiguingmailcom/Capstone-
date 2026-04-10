<?php
$user = getCurrentUser();
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-text">🌾 CoopIMS</div>
    <div class="logo-sub">Member Portal</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">My Account</div>
    <a href="<?= appendContextToUrl('member_dashboard.php') ?>" class="nav-item <?= ($activePage??'') === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">🏠</span> Dashboard
    </a>

    <div class="nav-section-label">Loans</div>
    <a href="<?= appendContextToUrl('member_loan_apply.php') ?>" class="nav-item <?= ($activePage??'') === 'loan_apply' ? 'active' : '' ?>">
      <span class="nav-icon">📝</span> Apply for Loan
    </a>
    <a href="<?= appendContextToUrl('member_loans.php') ?>" class="nav-item <?= ($activePage??'') === 'loans' ? 'active' : '' ?>">
      <span class="nav-icon">💳</span> My Loans
    </a>
    <a href="<?= appendContextToUrl('member_loan_payment.php') ?>" class="nav-item <?= ($activePage??'') === 'payment' ? 'active' : '' ?>">
      <span class="nav-icon">💰</span> Make Payment
    </a>

    <div class="nav-section-label">Shares</div>
    <a href="<?= appendContextToUrl('member_capital_shares.php') ?>" class="nav-item <?= ($activePage??'') === 'capital_shares' ? 'active' : '' ?>">
      <span class="nav-icon">📈</span> Capital Shares
    </a>

    <div class="nav-section-label">Purchases</div>
    <a href="<?= appendContextToUrl('member_purchases.php') ?>" class="nav-item <?= ($activePage??'') === 'purchases' ? 'active' : '' ?>">
      <span class="nav-icon">🛒</span> Purchase History
    </a>
    <a href="<?= appendContextToUrl('member_transactions.php') ?>" class="nav-item <?= ($activePage??'') === 'transactions' ? 'active' : '' ?>">
      <span class="nav-icon">📊</span> Transactions
    </a>

    <div class="nav-section-label">Account</div>
    <a href="<?= appendContextToUrl('../logout.php?context=member') ?>" class="nav-item" style="color:rgba(255,100,100,0.8);">
      <span class="nav-icon">🚪</span> Logout
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="user-role">Member</div>
      </div>
    </div>
  </div>
</aside>
