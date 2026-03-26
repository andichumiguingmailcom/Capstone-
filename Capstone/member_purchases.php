<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Purchase History – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/theme-init.js"></script>
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('member');
$activePage = 'purchases';
$db = getDB();

$memberId = $_SESSION['member_id'] ?? 0;
$filter = clean($_GET['type'] ?? '');
$search = clean($_GET['q'] ?? '');

$where = "WHERE s.member_id=$memberId";
if ($filter) $where .= " AND s.payment_type='$filter'";

$purchases = $db->query("SELECT s.*, 
    GROUP_CONCAT(p.name SEPARATOR ', ') as item_names,
    GROUP_CONCAT(CONCAT(si.qty, ' ', p.unit) SEPARATOR ', ') as item_qtys
    FROM sales s 
    LEFT JOIN sale_items si ON s.id=si.sale_id 
    LEFT JOIN products p ON si.product_id=p.id
    $where GROUP BY s.id ORDER BY s.sale_date DESC");

$totalSpent = $db->query("SELECT SUM(total) as s FROM sales WHERE member_id=$memberId")->fetch_assoc()['s'] ?? 0;
$cashTotal  = $db->query("SELECT SUM(total) as s FROM sales WHERE member_id=$memberId AND payment_type='cash'")->fetch_assoc()['s'] ?? 0;
$creditTotal= $db->query("SELECT SUM(total) as s FROM sales WHERE member_id=$memberId AND payment_type='credit'")->fetch_assoc()['s'] ?? 0;
?>

<?php include 'includes/member_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Purchase History</div>
  </div>

  <div class="page-body">
    <div class="stats-grid" style="margin-bottom:24px;">
      <div class="stat-card green"><span class="stat-icon">💰</span><div class="stat-value">₱<?= number_format($totalSpent, 0) ?></div><div class="stat-label">Total Spent</div></div>
      <div class="stat-card blue"><span class="stat-icon">💵</span><div class="stat-value">₱<?= number_format($cashTotal, 0) ?></div><div class="stat-label">Cash Purchases</div></div>
      <div class="stat-card gold"><span class="stat-icon">📋</span><div class="stat-value">₱<?= number_format($creditTotal, 0) ?></div><div class="stat-label">Credit Purchases</div></div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">All Purchases</span></div>
      <div class="card-body">
        <div class="search-bar">
          <input type="text" id="purSearch" class="search-input" placeholder="Search purchases..." oninput="filterTable('purSearch','purTable')">
          <a href="?type=" class="btn btn-sm <?= !$filter?'btn-primary':'btn-ghost' ?>">All</a>
          <a href="?type=cash" class="btn btn-sm <?= $filter==='cash'?'btn-primary':'btn-ghost' ?>">Cash</a>
          <a href="?type=credit" class="btn btn-sm <?= $filter==='credit'?'btn-primary':'btn-ghost' ?>">Credit</a>
        </div>
        <div class="table-wrap">
          <table id="purTable">
            <thead>
              <tr><th>Date</th><th>Items</th><th>Quantities</th><th>Total</th><th>Payment</th></tr>
            </thead>
            <tbody>
              <?php while ($p = $purchases->fetch_assoc()): ?>
              <tr>
                <td><?= date('M j, Y', strtotime($p['sale_date'])) ?></td>
                <td class="fw-600"><?= htmlspecialchars($p['item_names'] ?? '—') ?></td>
                <td class="text-muted text-sm"><?= htmlspecialchars($p['item_qtys'] ?? '—') ?></td>
                <td class="fw-600">₱<?= number_format($p['total'], 2) ?></td>
                <td><span class="badge <?= $p['payment_type']==='cash'?'badge-green':'badge-blue' ?>"><?= ucfirst($p['payment_type']) ?></span></td>
              </tr>
              <?php endwhile; ?>
              <?php if ($purchases->num_rows === 0): ?>
                <tr><td colspan="5" class="text-muted" style="text-align:center;padding:30px;">No purchase records found.</td></tr>
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
