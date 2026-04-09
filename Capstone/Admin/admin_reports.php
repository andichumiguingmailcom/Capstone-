<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports – CoopIMS</title>
  <link rel="stylesheet" href="../css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="js/theme-init.js"></script>
</head>
<body>
<?php
require_once '../includes/config.php';
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

<?php include '../includes/admin_sidebar.php'; ?>

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
        <div class="card" style="margin-bottom: 20px;">
          <div class="card-header">
            <span class="card-title">Loan Applications Analytics (<?= $from ?> to <?= $to ?>)</span>
          </div>
          <div class="card-body">
            <div style="height: 400px;">
              <canvas id="loansChart"></canvas>
            </div>
          </div>
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
        <div class="card" style="margin-bottom: 20px;">
          <div class="card-header">
            <span class="card-title">Sales Analytics (<?= $from ?> to <?= $to ?>)</span>
          </div>
          <div class="card-body">
            <div style="height: 400px;">
              <canvas id="salesChart"></canvas>
            </div>
          </div>
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
        <div class="card" style="margin-bottom: 20px;">
          <div class="card-header">
            <span class="card-title">Payment Analytics (<?= $from ?> to <?= $to ?>)</span>
          </div>
          <div class="card-body">
            <div style="height: 400px;">
              <canvas id="paymentsChart"></canvas>
            </div>
          </div>
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

<script src="../js/app.js"></script>
<script>
// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Loans Chart
    const loansCtx = document.getElementById('loansChart').getContext('2d');
    new Chart(loansCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Approved/Disbursed', 'Rejected'],
            datasets: [{
                data: [<?= $loanStats['pending'] ?>, <?= $loanStats['approved'] ?>, <?= $loanStats['rejected'] ?>],
                backgroundColor: ['#f59e0b', '#3b82f6', '#ef4444'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: ['Cash Sales', 'Credit Sales'],
            datasets: [{
                label: 'Sales Amount (₱)',
                data: [<?= $salesStats['cash_sales'] ?? 0 ?>, <?= $salesStats['credit_sales'] ?? 0 ?>],
                backgroundColor: ['#10b981', '#f59e0b'],
                borderColor: ['#059669', '#d97706'],
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Payments Chart
    const paymentsCtx = document.getElementById('paymentsChart').getContext('2d');
    const paymentCount = <?= $db->query("SELECT COUNT(*) as c FROM loan_payments WHERE DATE(paid_at) BETWEEN '$from' AND '$to'")->fetch_assoc()['c'] ?>;
    new Chart(paymentsCtx, {
        type: 'pie',
        data: {
            labels: ['Total Amount Collected', 'Number of Transactions'],
            datasets: [{
                data: [<?= $totalPayments ?>, paymentCount],
                backgroundColor: ['#8b5cf6', '#06b6d4'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.dataIndex === 0) {
                                return '₱' + context.parsed.toLocaleString();
                            }
                            return context.parsed + ' transactions';
                        }
                    }
                }
            }
        }
    });
});
</script>
</body>
</html>
