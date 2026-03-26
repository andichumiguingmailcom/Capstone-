<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Transactions – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/theme-init.js"></script>
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('member');
$activePage = 'transactions';
$db = getDB();
$memberId = $_SESSION['member_id'] ?? 0;
$year = (int)($_GET['year'] ?? date('Y'));

// Combined transactions
$purchases = $db->query("SELECT 'purchase' as type, sale_date as txn_date, total as amount, payment_type, 'Grocery/Rice Purchase' as description
    FROM sales WHERE member_id=$memberId AND YEAR(sale_date)=$year ORDER BY sale_date DESC");
$payments  = $db->query("SELECT 'payment' as type, DATE(paid_at) as txn_date, amount, payment_method as payment_type, 'Loan Payment' as description
    FROM loan_payments lp JOIN loans l ON lp.loan_id=l.id WHERE l.member_id=$memberId AND YEAR(paid_at)=$year ORDER BY paid_at DESC");

$yearlyPurchases = $db->query("SELECT SUM(total) as s FROM sales WHERE member_id=$memberId AND YEAR(sale_date)=$year")->fetch_assoc()['s'] ?? 0;
$yearlyPayments  = $db->query("SELECT SUM(lp.amount) as s FROM loan_payments lp JOIN loans l ON lp.loan_id=l.id WHERE l.member_id=$memberId AND YEAR(lp.paid_at)=$year")->fetch_assoc()['s'] ?? 0;
?>

<?php include 'includes/member_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Transaction History</div>
    <div class="topbar-actions">
      <?php for ($y = date('Y'); $y >= date('Y')-3; $y--): ?>
        <a href="?year=<?= $y ?>" class="btn btn-sm <?= $year==$y?'btn-primary':'btn-ghost' ?>"><?= $y ?></a>
      <?php endfor; ?>
    </div>
  </div>

  <div class="page-body">
    <div style="background:#f0f8f1;border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:24px;">
      <div class="text-muted text-sm fw-600" style="margin-bottom:8px;">Year-end Benefits Tracking (<?= $year ?>)</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
        <div><div class="text-muted text-sm">Total Purchases</div><div class="fw-600" style="font-size:1.1rem;">₱<?= number_format($yearlyPurchases, 2) ?></div></div>
        <div><div class="text-muted text-sm">Loan Payments</div><div class="fw-600" style="font-size:1.1rem;">₱<?= number_format($yearlyPayments, 2) ?></div></div>
        <div><div class="text-muted text-sm">Combined Activity</div><div class="fw-600" style="font-size:1.1rem;color:var(--primary);">₱<?= number_format($yearlyPurchases+$yearlyPayments, 2) ?></div></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">All Transactions — <?= $year ?></span></div>
      <div class="card-body">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Date</th><th>Description</th><th>Amount</th><th>Type</th><th>Payment Method</th></tr></thead>
            <tbody>
              <?php
              // Merge and sort transactions
              $all = [];
              while ($r = $purchases->fetch_assoc()) $all[] = $r;
              while ($r = $payments->fetch_assoc()) $all[] = $r;
              usort($all, fn($a,$b) => strcmp($b['txn_date'], $a['txn_date']));
              foreach ($all as $t):
              ?>
              <tr>
                <td><?= date('M j, Y', strtotime($t['txn_date'])) ?></td>
                <td class="fw-600"><?= htmlspecialchars($t['description']) ?></td>
                <td class="fw-600 <?= $t['type']==='payment'?'':''; ?>">₱<?= number_format($t['amount'], 2) ?></td>
                <td><span class="badge <?= $t['type']==='purchase'?'badge-gold':'badge-blue' ?>"><?= ucfirst($t['type']) ?></span></td>
                <td><span class="badge <?= in_array($t['payment_type'],['cash'])? 'badge-green':'badge-blue' ?>"><?= ucfirst($t['payment_type']) ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($all)): ?>
                <tr><td colspan="5" class="text-muted" style="text-align:center;padding:30px;">No transactions found for <?= $year ?>.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
