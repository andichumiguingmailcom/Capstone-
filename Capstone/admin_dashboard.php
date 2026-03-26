<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin();
$activePage = 'dashboard';
$db = getDB();

// Stats
$totalMembers    = $db->query("SELECT COUNT(*) as c FROM members WHERE status='active'")->fetch_assoc()['c'];
$pendingLoans    = $db->query("SELECT COUNT(*) as c FROM loan_applications WHERE status='pending'")->fetch_assoc()['c'];
$activeLoans     = $db->query("SELECT COUNT(*) as c FROM loans WHERE status='active'")->fetch_assoc()['c'];
$totalLoanBalance= $db->query("SELECT SUM(balance) as s FROM loans WHERE status='active'")->fetch_assoc()['s'] ?? 0;
$todaySales      = $db->query("SELECT SUM(total) as s FROM sales WHERE sale_date=CURDATE()")->fetch_assoc()['s'] ?? 0;
$lowStock        = $db->query("SELECT COUNT(*) as c FROM products WHERE stock <= reorder_pt AND is_active=1")->fetch_assoc()['c'];
$pendingPreApps  = $db->query("SELECT COUNT(*) as c FROM pre_applications WHERE status='pending'")->fetch_assoc()['c'];

// Recent Loan Applications
$recentLoans = $db->query("SELECT la.*, CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name) as full_name, m.member_id, lt.type_name 
    FROM loan_applications la 
    JOIN members m ON la.member_id = m.id 
    JOIN loan_types lt ON la.loan_type_id = lt.id 
    ORDER BY la.applied_at DESC LIMIT 6");

// Recent Sales
$recentSales = $db->query("SELECT s.*, CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name) as full_name, m.member_id 
    FROM sales s LEFT JOIN members m ON s.member_id = m.id 
    ORDER BY s.sale_date DESC LIMIT 6");

// Low Stock Products
$lowStockItems = $db->query("SELECT * FROM products WHERE stock <= reorder_pt AND is_active=1 LIMIT 5");
?>

<?php include 'includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Dashboard</div>
    <div class="topbar-actions">
      <span class="text-muted text-sm">📅 <?= date('F j, Y') ?></span>
      <?php if ($pendingLoans > 0): ?>
        <span class="badge badge-gold">⚠️ <?= $pendingLoans ?> pending loans</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="page-body">
    <!-- STATS GRID -->
    <div class="stats-grid">
      <div class="stat-card green">
        <span class="stat-icon">👥</span>
        <div class="stat-value"><?= number_format($totalMembers) ?></div>
        <div class="stat-label">Active Members</div>
      </div>
      <div class="stat-card gold">
        <span class="stat-icon">📝</span>
        <div class="stat-value"><?= $pendingLoans ?></div>
        <div class="stat-label">Pending Loan Applications</div>
        <div class="stat-change"><a href="admin_loan_applications.php" style="color:inherit;">View all →</a></div>
      </div>
      <div class="stat-card blue">
        <span class="stat-icon">💳</span>
        <div class="stat-value"><?= $activeLoans ?></div>
        <div class="stat-label">Active Loans</div>
        <div class="stat-change">₱<?= number_format($totalLoanBalance, 2) ?> outstanding</div>
      </div>
      <div class="stat-card green">
        <span class="stat-icon">🛒</span>
        <div class="stat-value">₱<?= number_format($todaySales, 0) ?></div>
        <div class="stat-label">Today's Sales</div>
      </div>
      <div class="stat-card red">
        <span class="stat-icon">📦</span>
        <div class="stat-value"><?= $lowStock ?></div>
        <div class="stat-label">Low Stock Items</div>
        <div class="stat-change"><a href="admin_inventory.php" style="color:inherit;">View inventory →</a></div>
      </div>
      <div class="stat-card gold">
        <span class="stat-icon">📋</span>
        <div class="stat-value"><?= $pendingPreApps ?></div>
        <div class="stat-label">Pre-Applications Pending</div>
        <div class="stat-change"><a href="admin_pre_applications.php" style="color:inherit;">Review →</a></div>
      </div>
    </div>

    <div class="grid-2">
      <!-- RECENT LOAN APPLICATIONS -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Recent Loan Applications</span>
          <a href="admin_loan_applications.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Member</th>
                  <th>Type</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $recentLoans->fetch_assoc()): ?>
                <tr>
                  <td>
                    <div class="fw-600"><?= htmlspecialchars($row['full_name']) ?></div>
                    <div class="text-muted text-sm"><?= $row['member_id'] ?></div>
                  </td>
                  <td><?= htmlspecialchars($row['type_name']) ?></td>
                  <td class="fw-600">₱<?= number_format($row['amount'], 2) ?></td>
                  <td>
                    <?php
                      $badge = ['pending'=>'badge-gold','approved'=>'badge-green','rejected'=>'badge-red','disbursed'=>'badge-blue'];
                      $b = $badge[$row['status']] ?? 'badge-gray';
                    ?>
                    <span class="badge <?= $b ?>"><?= ucfirst($row['status']) ?></span>
                  </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($recentLoans->num_rows === 0): ?>
                  <tr><td colspan="4" style="text-align:center;color:var(--text-muted);">No applications yet</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- LOW STOCK & RECENT SALES -->
      <div>
        <?php if ($lowStock > 0): ?>
        <div class="card" style="border-left:3px solid var(--danger);">
          <div class="card-header">
            <span class="card-title" style="color:var(--danger);">⚠️ Low Stock Alert</span>
            <a href="admin_inventory.php" class="btn btn-sm btn-outline">Manage</a>
          </div>
          <div class="card-body">
            <?php while ($item = $lowStockItems->fetch_assoc()): ?>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
                <div>
                  <div class="fw-600"><?= htmlspecialchars($item['name']) ?></div>
                  <div class="text-muted text-sm"><?= ucfirst($item['category']) ?> · SKU: <?= $item['sku'] ?></div>
                </div>
                <div style="text-align:right;">
                  <div class="badge badge-red"><?= $item['stock'] ?> <?= $item['unit'] ?></div>
                  <div class="text-muted text-sm">Min: <?= $item['reorder_pt'] ?></div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="card">
          <div class="card-header">
            <span class="card-title">Recent Sales</span>
            <a href="admin_sales.php" class="btn btn-sm btn-outline">View All</a>
          </div>
          <div class="card-body">
            <div class="table-wrap">
              <table>
                <thead>
                  <tr><th>Member</th><th>Date</th><th>Total</th><th>Type</th></tr>
                </thead>
                <tbody>
                  <?php while ($s = $recentSales->fetch_assoc()): ?>
                  <tr>
                    <td><?= $s['full_name'] ? htmlspecialchars($s['full_name']) : '<span class="text-muted">Walk-in</span>' ?></td>
                    <td><?= date('M j', strtotime($s['sale_date'])) ?></td>
                    <td class="fw-600">₱<?= number_format($s['total'], 2) ?></td>
                    <td><span class="badge <?= $s['payment_type']==='cash'?'badge-green':'badge-blue' ?>"><?= ucfirst($s['payment_type']) ?></span></td>
                  </tr>
                  <?php endwhile; ?>
                  <?php if ($recentSales->num_rows === 0): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--text-muted);">No sales today</td></tr>
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

<script src="js/app.js"></script>
</body>
</html>
