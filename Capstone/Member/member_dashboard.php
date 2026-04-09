<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Member Dashboard – CoopIMS</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php
require_once '../includes/config.php';
requireLogin('member');
$activePage = 'dashboard';
$db = getDB();

$memberId = $_SESSION['member_id'] ?? 0;

// Member info
$member = $db->query("SELECT *, CONCAT_WS(' ', first_name, middle_name, last_name) as full_name 
    FROM members WHERE id=$memberId")->fetch_assoc();

// Active loans
$activeLoans = $db->query("SELECT l.*, lt.type_name FROM loans l 
    JOIN loan_applications la ON l.application_id=la.id 
    JOIN loan_types lt ON la.loan_type_id=lt.id
    WHERE l.member_id=$memberId AND l.status='active' ORDER BY l.due_date");

$totalBalance  = $db->query("SELECT SUM(balance) as s FROM loans WHERE member_id=$memberId AND status='active'")->fetch_assoc()['s'] ?? 0;
$totalPurchases= $db->query("SELECT COUNT(*) as c FROM sales WHERE member_id=$memberId")->fetch_assoc()['c'];
$pendingApps   = $db->query("SELECT COUNT(*) as c FROM loan_applications WHERE member_id=$memberId AND status='pending'")->fetch_assoc()['c'];
// Capital shares
$capitalShare = $db->query("SELECT amount FROM capital_shares WHERE member_id=$memberId")->fetch_assoc()['amount'] ?? 0;
// Recent purchases
$recentPurchases = $db->query("SELECT s.*, GROUP_CONCAT(p.name SEPARATOR ', ') as items 
    FROM sales s LEFT JOIN sale_items si ON s.id=si.sale_id LEFT JOIN products p ON si.product_id=p.id
    WHERE s.member_id=$memberId GROUP BY s.id ORDER BY s.sale_date DESC LIMIT 5");

// Recent payments
$recentPayments = $db->query("SELECT lp.* FROM loan_payments lp 
    JOIN loans l ON lp.loan_id=l.id WHERE l.member_id=$memberId ORDER BY lp.paid_at DESC LIMIT 5");
?>

<?php include '../includes/member_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">My Dashboard</div>
    <div class="topbar-actions">
      <span class="text-muted text-sm">📅 <?= date('F j, Y') ?></span>
    </div>
  </div>

  <div class="page-body">
    <!-- WELCOME BANNER -->
    <div style="background:linear-gradient(135deg,var(--primary-dark),var(--primary));border-radius:var(--radius);padding:28px 32px;margin-bottom:24px;color:#fff;position:relative;overflow:hidden;">
      <div style="position:absolute;right:-20px;top:-20px;font-size:6rem;opacity:0.08;">🌾</div>
      <div style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;">
        Welcome back, <?= htmlspecialchars($member['full_name'] ?? 'Member') ?>!
      </div>
      <div style="opacity:0.75;margin-top:4px;font-size:0.9rem;">
        Member ID: <strong><?= $member['member_id'] ?? '—' ?></strong> &nbsp;·&nbsp;
        Member since <?= $member['date_joined'] ? date('F Y', strtotime($member['date_joined'])) : '—' ?>
      </div>
      <div style="margin-top:16px;">
        <a href="member_loan_apply.php" class="btn btn-accent">Apply for Loan</a>
        <a href="member_loan_payment.php" class="btn" style="background:rgba(255,255,255,0.15);color:#fff;margin-left:8px;">Make Payment</a>
      </div>
    </div>

    <!-- STATS -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
      <div class="stat-card blue">
        <span class="stat-icon">💳</span>
        <div class="stat-value">₱<?= number_format($totalBalance, 0) ?></div>
        <div class="stat-label">Outstanding Loan Balance</div>
      </div>
      <div class="stat-card gold">
        <span class="stat-icon">📝</span>
        <div class="stat-value"><?= $pendingApps ?></div>
        <div class="stat-label">Pending Applications</div>
        <div class="stat-change"><a href="member_loans.php" style="color:inherit;">View status →</a></div>
      </div>
      <div class="stat-card green">
        <span class="stat-icon">📈</span>
        <div class="stat-value">₱<?= number_format($capitalShare, 0) ?></div>
        <div class="stat-label">Capital Shares</div>
        <div class="stat-change"><a href="member_capital_shares.php" style="color:inherit;">View details →</a></div>
      </div>
      <div class="stat-card purple">
        <span class="stat-icon">🛒</span>
        <div class="stat-value"><?= $totalPurchases ?></div>
        <div class="stat-label">Total Purchases</div>
        <div class="stat-change"><a href="member_purchases.php" style="color:inherit;">View history →</a></div>
      </div>
    </div>

    <div class="grid-2">
      <!-- ACTIVE LOANS -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">My Active Loans</span>
          <a href="member_loans.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
          <?php $loanCount = 0; while ($loan = $activeLoans->fetch_assoc()): $loanCount++; ?>
            <div style="border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:12px;">
              <div style="display:flex;justify-content:space-between;align-items:start;">
                <div>
                  <div class="fw-600"><?= htmlspecialchars($loan['type_name']) ?></div>
                  <div class="text-muted text-sm">Due: <?= date('M j, Y', strtotime($loan['due_date'])) ?></div>
                </div>
                <div style="text-align:right;">
                  <div class="fw-600" style="color:var(--danger);">₱<?= number_format($loan['balance'], 2) ?></div>
                  <div class="text-muted text-sm">Monthly: ₱<?= number_format($loan['monthly_due'], 2) ?></div>
                </div>
              </div>
              <?php
              $progress = $loan['principal'] > 0 ? (1 - $loan['balance']/$loan['principal'])*100 : 0;
              ?>
              <div style="margin-top:10px;">
                <div style="display:flex;justify-content:space-between;font-size:0.78rem;color:var(--text-muted);margin-bottom:4px;">
                  <span>Progress</span><span><?= round($progress) ?>%</span>
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width:<?= $progress ?>%"></div></div>
              </div>
              <div style="margin-top:10px;text-align:right;">
                <a href="member_loan_payment.php?loan=<?= $loan['id'] ?>" class="btn btn-sm btn-primary">Pay Now</a>
              </div>
            </div>
          <?php endwhile; ?>
          <?php if ($loanCount === 0): ?>
            <p class="text-muted" style="text-align:center;padding:20px;">No active loans.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- RECENT ACTIVITY -->
      <div>
        <div class="card">
          <div class="card-header">
            <span class="card-title">Recent Purchases</span>
            <a href="member_purchases.php" class="btn btn-sm btn-outline">View All</a>
          </div>
          <div class="card-body">
            <div class="table-wrap">
              <table>
                <thead><tr><th>Date</th><th>Items</th><th>Total</th><th>Type</th></tr></thead>
                <tbody>
                  <?php while ($pur = $recentPurchases->fetch_assoc()): ?>
                  <tr>
                    <td><?= date('M j', strtotime($pur['sale_date'])) ?></td>
                    <td class="text-muted text-sm" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($pur['items'] ?? '—') ?></td>
                    <td class="fw-600">₱<?= number_format($pur['total'], 2) ?></td>
                    <td><span class="badge <?= $pur['payment_type']==='cash'?'badge-green':'badge-blue' ?>"><?= ucfirst($pur['payment_type']) ?></span></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><span class="card-title">Recent Payments</span></div>
          <div class="card-body">
            <div class="table-wrap">
              <table>
                <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
                <tbody>
                  <?php while ($py = $recentPayments->fetch_assoc()): ?>
                  <tr>
                    <td><?= date('M j', strtotime($py['paid_at'])) ?></td>
                    <td class="fw-600">₱<?= number_format($py['amount'], 2) ?></td>
                    <td><span class="badge badge-green"><?= ucfirst($py['payment_method']) ?></span></td>
                    <td class="text-muted text-sm"><?= $py['reference_no'] ?? '—' ?></td>
                  </tr>
                  <?php endwhile; ?>
                  <?php if ($recentPayments->num_rows === 0): ?>
                    <tr><td colspan="4" class="text-muted" style="text-align:center;">No payments yet.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../js/app.js"></script>
</body>
</html>
