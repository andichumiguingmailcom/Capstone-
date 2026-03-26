<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/theme-init.js"></script>
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('book_keeper');
$activePage = 'reports';
$db = getDB();

$from = clean($_GET['from'] ?? date('Y-m-01'));
$to   = clean($_GET['to']   ?? date('Y-m-d'));

// Loan stats
$loanStats = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='approved' OR status='disbursed' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(amount) as total_amount
    FROM loan_applications WHERE DATE(applied_at) BETWEEN '$from' AND '$to'")->fetch_assoc();

// Sales stats
$salesStats = $db->query("SELECT 
    COUNT(*) as total_txn,
    SUM(total) as gross_sales,
    SUM(CASE WHEN payment_type='cash' THEN total ELSE 0 END) as cash_sales,
    SUM(CASE WHEN payment_type='credit' THEN total ELSE 0 END) as credit_sales
    FROM sales WHERE sale_date BETWEEN '$from' AND '$to'")->fetch_assoc();

// Top products
$topProducts = $db->query("SELECT p.name, p.category, SUM(si.qty) as sold_qty, SUM(si.subtotal) as revenue
    FROM sale_items si JOIN products p ON si.product_id=p.id
    JOIN sales s ON si.sale_id=s.id
    WHERE s.sale_date BETWEEN '$from' AND '$to'
    GROUP BY si.product_id ORDER BY revenue DESC LIMIT 10");

// Payment history
$payments = $db->query("SELECT lp.*, CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name) AS full_name, m.member_id as mem_code 
    FROM loan_payments lp JOIN loans l ON lp.loan_id=l.id JOIN members m ON l.member_id=m.id
    WHERE DATE(lp.paid_at) BETWEEN '$from' AND '$to' ORDER BY lp.paid_at DESC LIMIT 20");

$totalPayments = $db->query("SELECT SUM(amount) as s FROM loan_payments WHERE DATE(paid_at) BETWEEN '$from' AND '$to'")->fetch_assoc()['s'] ?? 0;
?>

<?php include 'includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Reports</div>
    <div class="topbar-actions">
      <button class="btn btn-outline" onclick="window.print()">🖨️ Print</button>
    </div>
  </div>

  <div class="page-body">
    <!-- DATE FILTER -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-body" style="padding:16px 24px;">
        <form method="GET" style="display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;">
          <div>
            <label class="form-label">From</label>
            <input type="date" name="from" class="form-control" value="<?= $from ?>">
          </div>
          <div>
            <label class="form-label">To</label>
            <input type="date" name="to" class="form-control" value="<?= $to ?>">
          </div>
          <button type="submit" class="btn btn-primary">Generate Report</button>
          <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-ghost">This Month</a>
          <a href="?from=<?= date('Y-01-01') ?>&to=<?= date('Y-12-31') ?>" class="btn btn-ghost">This Year</a>
        </form>
      </div>
    </div>

    <div class="tabs-wrapper">
      <div class="tabs">
        <button class="tab-btn active" data-tab="tab-loans">💳 Loans</button>
        <button class="tab-btn" data-tab="tab-sales">🛒 Sales & Inventory</button>
        <button class="tab-btn" data-tab="tab-payments">💰 Payments</button>
      </div>

      <!-- LOANS TAB -->
      <div class="tab-pane active" id="tab-loans">
        <div class="stats-grid">
          <div class="stat-card green"><span class="stat-icon">📝</span><div class="stat-value"><?= $loanStats['total'] ?></div><div class="stat-label">Total Applications</div></div>
          <div class="stat-card gold"><span class="stat-icon">⏳</span><div class="stat-value"><?= $loanStats['pending'] ?></div><div class="stat-label">Pending</div></div>
          <div class="stat-card blue"><span class="stat-icon">✅</span><div class="stat-value"><?= $loanStats['approved'] ?></div><div class="stat-label">Approved / Disbursed</div></div>
          <div class="stat-card red"><span class="stat-icon">❌</span><div class="stat-value"><?= $loanStats['rejected'] ?></div><div class="stat-label">Rejected</div></div>
          <div class="stat-card green"><span class="stat-icon">💵</span><div class="stat-value">₱<?= number_format($loanStats['total_amount'] ?? 0, 0) ?></div><div class="stat-label">Total Amount Applied</div></div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">Consolidated Loan Report (<?= $from ?> to <?= $to ?>)</span></div>
          <div class="card-body">
            <div class="table-wrap">
              <table>
                <thead><tr><th>Member</th><th>Type</th><th>Amount</th><th>Term</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                  <?php
                  $loans = $db->query("SELECT la.*, CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name) AS full_name, m.member_id as mc, lt.type_name 
                    FROM loan_applications la JOIN members m ON la.member_id=m.id 
                    JOIN loan_types lt ON la.loan_type_id=lt.id 
                    WHERE DATE(la.applied_at) BETWEEN '$from' AND '$to' ORDER BY la.applied_at DESC");
                  while ($r = $loans->fetch_assoc()):
                    $b = ['pending'=>'badge-gold','approved'=>'badge-green','rejected'=>'badge-red','disbursed'=>'badge-blue'][$r['status']] ?? 'badge-gray';
                  ?>
                  <tr>
                    <td><div class="fw-600"><?= htmlspecialchars($r['full_name']) ?></div><div class="text-muted text-sm"><?= $r['mc'] ?></div></td>
                    <td><?= htmlspecialchars($r['type_name']) ?></td>
                    <td class="fw-600">₱<?= number_format($r['amount'], 2) ?></td>
                    <td><?= $r['term_months'] ?> mos</td>
                    <td><span class="badge <?= $b ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td><?= date('M j, Y', strtotime($r['applied_at'])) ?></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- SALES TAB -->
      <div class="tab-pane" id="tab-sales">
        <div class="stats-grid">
          <div class="stat-card green"><span class="stat-icon">💰</span><div class="stat-value">₱<?= number_format($salesStats['gross_sales'] ?? 0, 0) ?></div><div class="stat-label">Gross Sales</div></div>
          <div class="stat-card blue"><span class="stat-icon">💵</span><div class="stat-value">₱<?= number_format($salesStats['cash_sales'] ?? 0, 0) ?></div><div class="stat-label">Cash Sales</div></div>
          <div class="stat-card gold"><span class="stat-icon">📋</span><div class="stat-value">₱<?= number_format($salesStats['credit_sales'] ?? 0, 0) ?></div><div class="stat-label">Credit Sales</div></div>
          <div class="stat-card green"><span class="stat-icon">🧾</span><div class="stat-value"><?= $salesStats['total_txn'] ?></div><div class="stat-label">Transactions</div></div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">Top Selling Products</span></div>
          <div class="card-body">
            <div class="table-wrap">
              <table>
                <thead><tr><th>Product</th><th>Category</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
                <tbody>
                  <?php while ($tp = $topProducts->fetch_assoc()): ?>
                  <tr>
                    <td class="fw-600"><?= htmlspecialchars($tp['name']) ?></td>
                    <td><span class="badge <?= $tp['category']==='rice'?'badge-gold':'badge-blue' ?>"><?= ucfirst($tp['category']) ?></span></td>
                    <td><?= $tp['sold_qty'] ?></td>
                    <td class="fw-600">₱<?= number_format($tp['revenue'], 2) ?></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- PAYMENTS TAB -->
      <div class="tab-pane" id="tab-payments">
        <div class="stats-grid">
          <div class="stat-card green"><span class="stat-icon">💰</span><div class="stat-value">₱<?= number_format($totalPayments, 2) ?></div><div class="stat-label">Total Payments Collected</div></div>
          <div class="stat-card blue"><span class="stat-icon">📱</span><div class="stat-value"><?= $db->query("SELECT COUNT(*) as c FROM loan_payments WHERE DATE(paid_at) BETWEEN '$from' AND '$to'")->fetch_assoc()['c'] ?></div><div class="stat-label">Payment Transactions</div></div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">Payment Records</span></div>
          <div class="card-body">
            <div class="table-wrap">
              <table>
                <thead><tr><th>Member</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th></tr></thead>
                <tbody>
                  <?php while ($py = $payments->fetch_assoc()): ?>
                  <tr>
                    <td><div class="fw-600"><?= htmlspecialchars($py['full_name']) ?></div><div class="text-muted text-sm"><?= $py['mem_code'] ?></div></td>
                    <td class="fw-600">₱<?= number_format($py['amount'], 2) ?></td>
                    <td><span class="badge badge-green"><?= ucfirst($py['payment_method']) ?></span></td>
                    <td class="text-muted text-sm"><?= $py['reference_no'] ?? '—' ?></td>
                    <td><?= date('M j, Y H:i', strtotime($py['paid_at'])) ?></td>
                  </tr>
                  <?php endwhile; ?>
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
