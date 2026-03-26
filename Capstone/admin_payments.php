<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Loan Payments – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('general_manager');
$activePage = 'payments';
$db = getDB();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loan_id']) && isset($_POST['amount'])) {
    $loanId = (int)$_POST['loan_id'];
    $amount = (float)$_POST['amount'];
    $method = clean($_POST['payment_method'] ?? 'cash');
    $ref = clean($_POST['reference_no'] ?? '');
    $uid = $_SESSION['user_id'];

    $stmt = $db->prepare("SELECT balance, status FROM loans WHERE id=?");
    $stmt->bind_param('i', $loanId);
    $stmt->execute();
    $loan = $stmt->get_result()->fetch_assoc();

    if ($loan && $amount > 0 && $loan['status'] === 'active') {
        $db->query("INSERT INTO loan_payments (loan_id, amount, payment_method, reference_no, recorded_by) VALUES ($loanId, $amount, '$method', '{$db->real_escape_string($ref)}', $uid)");
        $newBalance = max(0, $loan['balance'] - $amount);
        $newStatus = $newBalance <= 0 ? 'settled' : 'active';
        $stmt2 = $db->prepare("UPDATE loans SET balance=?, status=? WHERE id=?");
        $stmt2->bind_param('dsi', $newBalance, $newStatus, $loanId);
        $stmt2->execute();
        $msg = 'Payment recorded successfully.';
    } else {
        $msg = 'Invalid payment entry or loan is not active.';
    }
}

$payments = $db->query("SELECT lp.*, CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name) AS full_name, m.member_id AS mem_code, lt.type_name, l.balance as loan_balance
    FROM loan_payments lp
    JOIN loans l ON lp.loan_id=l.id
    JOIN members m ON l.member_id=m.id
    JOIN loan_applications la ON l.application_id=la.id
    JOIN loan_types lt ON la.loan_type_id=lt.id
    ORDER BY lp.paid_at DESC");

$activeLoans = $db->query("SELECT l.id, CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name) AS full_name, lt.type_name, l.balance FROM loans l
    JOIN members m ON l.member_id=m.id
    JOIN loan_applications la ON l.application_id=la.id
    JOIN loan_types lt ON la.loan_type_id=lt.id
    WHERE l.status='active'");
?>

<?php include 'includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Loan Payments</div>
  </div>

  <div class="page-body">
    <?php if ($msg): ?>
      <div style="background:#d4f0dc;color:#1a6b3a;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:3px solid #2e9e58;">
        ✅ <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="grid-2">
      <div class="card">
        <div class="card-header"><span class="card-title">Record Payment</span></div>
        <div class="card-body">
          <form method="POST">
            <div class="form-group">
              <label class="form-label">Select Active Loan</label>
              <select name="loan_id" class="form-control" required>
                <option value="">— Choose a loan —</option>
                <?php while ($l = $activeLoans->fetch_assoc()): ?>
                <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['full_name']) ?> (<?= htmlspecialchars($l['mem_code']) ?>) · <?= htmlspecialchars($l['type_name']) ?> · ₱<?= number_format($l['balance'],2) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Amount (₱)</label>
              <input type="number" name="amount" class="form-control" min="1" step="0.01" required>
            </div>
            <div class="form-group">
              <label class="form-label">Payment Method</label>
              <select name="payment_method" class="form-control">
                <option value="gcash">GCash</option>
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Reference No.</label>
              <input type="text" name="reference_no" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Save Payment</button>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><span class="card-title">Recent Payments</span></div>
        <div class="card-body">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Member</th>
                  <th>Loan Type</th>
                  <th>Amount</th>
                  <th>Method</th>
                  <th>Reference</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($p = $payments->fetch_assoc()): ?>
                <tr>
                  <td><?= date('M j, Y', strtotime($p['paid_at'])) ?></td>
                  <td><?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['mem_code']) ?>)</td>
                  <td><?= htmlspecialchars($p['type_name']) ?></td>
                  <td class="fw-600">₱<?= number_format($p['amount'], 2) ?></td>
                  <td><span class="badge badge-green"><?= ucfirst($p['payment_method']) ?></span></td>
                  <td><?= htmlspecialchars($p['reference_no'] ?: '—') ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($payments->num_rows === 0): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">No payment history yet.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="js/app.js"></script>
</body>
</html>