<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Capital Share – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/theme-init.js"></script>
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('member');
$activePage = 'capital_share';
$db = getDB();
$memberId = $_SESSION['member_id'] ?? 0;

$member = $db->query("SELECT m.*, CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name) as full_name
    FROM members m WHERE m.id=$memberId")->fetch_assoc();

$capitalShare = $member['capital_share'] ?? 0;

// Get total loans and status
$allLoans = $db->query("SELECT l.*, lt.type_name FROM loans l 
    JOIN loan_applications la ON l.application_id=la.id 
    JOIN loan_types lt ON la.loan_type_id=lt.id
    WHERE l.member_id=$memberId ORDER BY l.disbursed_at DESC");

$activeLoanCount = $db->query("SELECT COUNT(*) as c FROM loans WHERE member_id=$memberId AND status='active'")->fetch_assoc()['c'];
$settledLoanCount = $db->query("SELECT COUNT(*) as c FROM loans WHERE member_id=$memberId AND status='settled'")->fetch_assoc()['c'];
?>

<?php include 'includes/member_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Capital Share Dashboard</div>
  </div>

  <div class="page-body">
    <!-- CAPITAL SHARE INFO CARD -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-body" style="padding:32px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:center;">
          <div>
            <div class="text-muted" style="font-size:0.9rem;margin-bottom:8px;">Your Capital Share</div>
            <div style="font-family:'Syne',sans-serif;font-size:3rem;font-weight:800;color:var(--primary);margin-bottom:8px;">₱<?= number_format($capitalShare, 2) ?></div>
            <div class="text-muted text-sm">
              <?php if ($capitalShare <= 75000): ?>
                <span class="badge badge-blue">Standard Rate (2%)</span>
              <?php else: ?>
                <span class="badge badge-green">Premium Rate (1.5%)</span>
              <?php endif; ?>
            </div>
          </div>
          <div style="background:var(--bg);border-radius:12px;padding:20px;text-align:center;">
            <div style="margin-bottom:16px;">
              <div class="text-muted text-sm">Member ID</div>
              <div class="fw-600"><?= htmlspecialchars($member['member_id']) ?></div>
            </div>
            <div style="margin-bottom:16px;">
              <div class="text-muted text-sm">Member Name</div>
              <div class="fw-600"><?= htmlspecialchars($member['full_name']) ?></div>
            </div>
            <div>
              <div class="text-muted text-sm">Member Since</div>
              <div class="fw-600"><?= date('M j, Y', strtotime($member['date_joined'])) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CAPITAL SHARE BENEFIT EXPLANATION -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header"><span class="card-title">💡 Capital Share Benefits</span></div>
      <div class="card-body">
        <div style="display:grid;gap:16px;">
          <div style="background:rgba(46, 158, 88, 0.1);border-left:3px solid #2e9e58;padding:16px;border-radius:6px;">
            <div class="fw-600" style="color:#2e9e58;margin-bottom:4px;">Standard Rate (₱75,000 and below)</div>
            <div class="text-sm">If your capital share is ₱75,000 or less, you qualify for a Special Loan with <strong>2% monthly interest rate</strong>.</div>
          </div>
          <div style="background:rgba(0, 120, 160, 0.1);border-left:3px solid #0078a0;padding:16px;border-radius:6px;">
            <div class="fw-600" style="color:#0078a0;margin-bottom:4px;">Premium Rate (₱75,001 and above)</div>
            <div class="text-sm">If your capital share is ₱75,001 or more, you qualify for a Special Loan with <strong>1.5% monthly interest rate</strong>.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- LOAN OVERVIEW -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header"><span class="card-title">📊 Loan Overview</span></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:16px;">
          <div style="background:var(--bg);border-radius:8px;padding:16px;text-align:center;">
            <div style="font-size:2rem;font-weight:bold;color:var(--primary);margin-bottom:4px;"><?= $activeLoanCount ?></div>
            <div class="text-muted text-sm">Active Loans</div>
          </div>
          <div style="background:var(--bg);border-radius:8px;padding:16px;text-align:center;">
            <div style="font-size:2rem;font-weight:bold;color:#2e9e58;margin-bottom:4px;"><?= $settledLoanCount ?></div>
            <div class="text-muted text-sm">Settled Loans</div>
          </div>
        </div>
      </div>
    </div>

    <!-- LOAN HISTORY -->
    <div class="card">
      <div class="card-header"><span class="card-title">📋 Loan History</span></div>
      <div class="card-body">
        <?php if ($allLoans->num_rows > 0): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Type</th><th>Principal</th><th>Balance</th><th>Monthly Due</th><th>Status</th><th>Disbursed</th></tr>
            </thead>
            <tbody>
              <?php while ($l = $allLoans->fetch_assoc()): ?>
              <tr>
                <td class="fw-600"><?= htmlspecialchars($l['type_name']) ?></td>
                <td>₱<?= number_format($l['principal'], 2) ?></td>
                <td style="color:<?= $l['balance'] > 0 ? 'var(--danger)' : '#2e9e58' ?>;">₱<?= number_format($l['balance'], 2) ?></td>
                <td>₱<?= number_format($l['monthly_due'], 2) ?></td>
                <td><span class="badge <?= $l['status']==='active'?'badge-blue':($l['status']==='settled'?'badge-green':'badge-red') ?>"><?= ucfirst($l['status']) ?></span></td>
                <td><?= date('M j, Y', strtotime($l['disbursed_at'])) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">
          <div style="font-size:2rem;margin-bottom:12px;">💳</div>
          <div>No loans yet. <a href="member_loan_apply.php" style="color:var(--primary);text-decoration:underline;">Apply for a loan</a></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
