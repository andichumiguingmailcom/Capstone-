<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Loans – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/theme-init.js"></script>
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('member');
$activePage = 'loans';
$db = getDB();
$memberId = $_SESSION['member_id'] ?? 0;

$loans = $db->query("SELECT l.*, lt.type_name, la.purpose, la.term_months, la.status as app_status
    FROM loans l 
    JOIN loan_applications la ON l.application_id=la.id 
    JOIN loan_types lt ON la.loan_type_id=lt.id
    WHERE l.member_id=$memberId ORDER BY l.disbursed_at DESC");

$applications = $db->query("SELECT la.*, lt.type_name FROM loan_applications la 
    JOIN loan_types lt ON la.loan_type_id=lt.id 
    WHERE la.member_id=$memberId ORDER BY la.applied_at DESC");
?>

<?php include 'includes/member_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">My Loans</div>
    <div class="topbar-actions">
      <a href="member_loan_apply.php" class="btn btn-primary">Apply for Loan</a>
    </div>
  </div>

  <div class="page-body">
    <div class="tabs-wrapper">
      <div class="tabs">
        <button class="tab-btn active" data-tab="tab-active">Active Loans</button>
        <button class="tab-btn" data-tab="tab-applications">All Applications</button>
      </div>

      <div class="tab-pane active" id="tab-active">
        <?php $hasLoans = false; $loans->data_seek(0); while ($l = $loans->fetch_assoc()): $hasLoans = true; ?>
        <div class="card" style="margin-bottom:16px;">
          <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
              <div>
                <h3 style="font-family:'Syne',sans-serif;color:var(--primary-dark);"><?= htmlspecialchars($l['type_name']) ?></h3>
                <div class="text-muted text-sm">Purpose: <?= htmlspecialchars($l['purpose'] ?? '—') ?></div>
              </div>
              <span class="badge <?= $l['status']==='active'?'badge-green':($l['status']==='settled'?'badge-blue':'badge-red') ?>">
                <?= ucfirst($l['status']) ?>
              </span>
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:16px;">
              <div style="background:var(--bg);border-radius:8px;padding:14px;">
                <div class="text-muted text-sm">Principal</div>
                <div class="fw-600">₱<?= number_format($l['principal'], 2) ?></div>
              </div>
              <div style="background:var(--bg);border-radius:8px;padding:14px;">
                <div class="text-muted text-sm">Outstanding Balance</div>
                <div class="fw-600" style="color:var(--danger);">₱<?= number_format($l['balance'], 2) ?></div>
              </div>
              <div style="background:var(--bg);border-radius:8px;padding:14px;">
                <div class="text-muted text-sm">Monthly Due</div>
                <div class="fw-600">₱<?= number_format($l['monthly_due'], 2) ?></div>
              </div>
            </div>

            <div style="margin-bottom:12px;">
              <?php $pct = $l['principal'] > 0 ? min(100, (1 - $l['balance']/$l['principal'])*100) : 100; ?>
              <div style="display:flex;justify-content:space-between;font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">
                <span>Repayment Progress</span><span><?= round($pct) ?>% paid</span>
              </div>
              <div class="progress-bar" style="height:10px;">
                <div class="progress-fill" style="width:<?= $pct ?>%;"></div>
              </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;">
              <div class="text-muted text-sm">Due: <?= date('M j, Y', strtotime($l['due_date'])) ?></div>
              <?php if ($l['status'] === 'active'): ?>
                <a href="member_loan_payment.php?loan=<?= $l['id'] ?>" class="btn btn-primary">💰 Make Payment</a>
              <?php endif; ?>
            </div>

            <!-- Payment History for this loan -->
            <?php
            $pmts = $db->query("SELECT * FROM loan_payments WHERE loan_id={$l['id']} ORDER BY paid_at DESC LIMIT 5");
            if ($pmts->num_rows > 0): ?>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
              <div class="text-muted text-sm fw-600" style="margin-bottom:8px;">📋 Recent Payments</div>
              <table style="width:100%;font-size:0.83rem;">
                <thead><tr style="color:var(--text-muted);"><th style="padding:4px 0;">Date</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
                <tbody>
                  <?php while ($py = $pmts->fetch_assoc()): ?>
                  <tr>
                    <td style="padding:4px 0;"><?= date('M j, Y', strtotime($py['paid_at'])) ?></td>
                    <td class="fw-600">₱<?= number_format($py['amount'],2) ?></td>
                    <td><?= ucfirst($py['payment_method']) ?></td>
                    <td class="text-muted"><?= $py['reference_no'] ?? '—' ?></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endwhile; if (!$hasLoans): ?>
          <div style="text-align:center;padding:60px;color:var(--text-muted);">
            <div style="font-size:3rem;margin-bottom:12px;">💳</div>
            <div>No active loans.</div>
            <a href="member_loan_apply.php" class="btn btn-primary" style="margin-top:16px;">Apply for a Loan</a>
          </div>
        <?php endif; ?>
      </div>

      <div class="tab-pane" id="tab-applications">
        <div class="card">
          <div class="card-body">
            <div class="table-wrap">
              <table>
                <thead><tr><th>Type</th><th>Amount</th><th>Term</th><th>Status</th><th>Remarks</th><th>Date</th></tr></thead>
                <tbody>
                  <?php while ($a = $applications->fetch_assoc()):
                    $b = ['pending'=>'badge-gold','approved'=>'badge-green','rejected'=>'badge-red','disbursed'=>'badge-blue'][$a['status']] ?? 'badge-gray';
                  ?>
                  <tr>
                    <td class="fw-600"><?= htmlspecialchars($a['type_name']) ?></td>
                    <td>₱<?= number_format($a['amount'], 2) ?></td>
                    <td><?= $a['term_months'] ?> mos</td>
                    <td><span class="badge <?= $b ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td class="text-muted text-sm"><?= htmlspecialchars($a['remarks'] ?? '—') ?></td>
                    <td><?= date('M j, Y', strtotime($a['applied_at'])) ?></td>
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
