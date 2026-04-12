<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard – CoopIMS</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php
require_once '../includes/config.php';
requireLogin(['general_manager','book_keeper','collector','loan_officer','cashier']);
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

<?php include '../includes/admin_sidebar.php'; ?>

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

  <div class="page-body" style="height: calc(100vh - 76px); overflow-y: auto; scroll-snap-type: y mandatory; padding: 0; scroll-behavior: smooth;">
    <style>
      .snap-section {
        scroll-snap-align: start;
        padding: 24px;
        min-height: calc(100vh - 76px);
        display: flex;
        flex-direction: column;
        justify-content: center;
      }
      .stats-grid .stat-card {
        padding: 40px 32px;
      }
      .stats-grid .stat-value {
        font-size: 2.8rem;
        font-weight: 800;
      }
      .stats-grid .stat-label {
        font-size: 1.1rem;
        font-weight: 600;
      }
      .stats-grid .stat-icon {
        font-size: 2.2rem;
      }
      .card .card-header {
        padding: 24px 28px;
      }
      .card .card-title {
        font-size: 1.35rem;
      }
      .table-wrap table th, 
      .table-wrap table td {
        padding: 18px 24px;
        font-size: 1rem;
      }
      .stat-change, .stat-change span {
        font-size: 0.95rem !important;
      }
    </style>

    <!-- PART 1: OVERVIEW CARDS -->
    <div class="snap-section">
      <div class="stats-grid reveal-on-scroll">
        <div class="stat-card green" style="grid-column: span 4;">
          <span class="stat-icon">👥</span>
          <div class="stat-value"><?= number_format($totalMembers) ?></div>
          <div class="stat-label">Active Members</div>
          <div class="stat-change"><span class="text-muted text-xs">Verified accounts</span></div>
        </div>
        <div class="stat-card gold" style="grid-column: span 4;">
          <span class="stat-icon">📝</span>
          <div class="stat-value"><?= $pendingLoans ?></div>
          <div class="stat-label">Pending Loan Applications</div>
          <div class="stat-change"><a href="admin_loan_applications.php" style="color:inherit; text-decoration:none; font-weight:600;">View all →</a></div>
        </div>
        <div class="stat-card blue" style="grid-column: span 4;">
          <span class="stat-icon">💳</span>
          <div class="stat-value"><?= $activeLoans ?></div>
          <div class="stat-label">Active Loans</div>
          <div class="stat-change"><span class="text-sm">₱<?= number_format($totalLoanBalance, 2) ?> outstanding</span></div>
        </div>
        <div class="stat-card green" style="grid-column: span 4;">
          <span class="stat-icon">🛒</span>
          <div class="stat-value">₱<?= number_format($todaySales, 0) ?></div>
          <div class="stat-label">Today's Sales</div>
          <div class="stat-change"><span class="text-muted text-xs">Gross revenue today</span></div>
        </div>
        <div class="stat-card red" style="grid-column: span 4;">
          <span class="stat-icon">📦</span>
          <div class="stat-value"><?= $lowStock ?></div>
          <div class="stat-label">Low Stock Items</div>
          <div class="stat-change"><a href="admin_inventory.php" style="color:inherit; text-decoration:none; font-weight:600;">Manage inventory →</a></div>
        </div>
        <div class="stat-card gold" style="grid-column: span 4;">
          <span class="stat-icon">📋</span>
          <div class="stat-value"><?= $pendingPreApps ?></div>
          <div class="stat-label">Pre-Applications Pending</div>
          <div class="stat-change"><a href="admin_pre_applications.php" style="color:inherit; text-decoration:none; font-weight:600;">Review →</a></div>
        </div>
      </div>
    </div>

    <!-- PART 2: ACTIVITY LOGS & ALERTS -->
    <div class="snap-section" style="justify-content: flex-start;">
      <div class="grid-2" style="align-items: start; gap: 24px;">
        <!-- LEFT COLUMN: RECENT LOAN APPLICATIONS -->
      <div class="card reveal-on-scroll">
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

      <!-- RIGHT COLUMN: ALERTS & SALES STACK -->
      <div style="display: flex; flex-direction: column; gap: 24px;">
        <?php if ($lowStock > 0): ?>
        <div class="card reveal-on-scroll" style="border-left:3px solid var(--danger);">
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

        <div class="card reveal-on-scroll">
          <div class="card-header">
            <span class="card-title">Recent Sales</span>
            <a href="admin_sales.php" class="btn btn-sm btn-outline">View All Sales</a>
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
</div>

<script src="../js/app.js"></script>
</body>
</html>
