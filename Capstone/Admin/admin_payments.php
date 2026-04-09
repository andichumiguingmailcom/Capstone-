<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Loan Payments – CoopIMS</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php
require_once '../includes/config.php';
requireLogin(['general_manager','collector']);
$activePage = 'payments';
$db = getDB();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loan_id']) && isset($_POST['amount'])) {
    $loanId = (int)$_POST['loan_id'];
    $amount = (float)$_POST['amount'];
    $method = clean($_POST['payment_method'] ?? 'cash');
    $ref = clean($_POST['reference_no'] ?? '');
    $uid = $_SESSION['user_id'];

    // Auto-generate receipt number for cash payments
    if ($method === 'cash' && empty($ref)) {
        $ref = 'RCP-' . date('YmdHis') . '-' . rand(100, 999);
    }

    $stmt = $db->prepare("SELECT balance, status FROM loans WHERE id=?");
    $stmt->bind_param('i', $loanId);
    $stmt->execute();
    $loan = $stmt->get_result()->fetch_assoc();

    if ($loan && $amount > 0 && $loan['status'] === 'active') {
        $stmt_pay = $db->prepare("INSERT INTO loan_payments (loan_id, amount, payment_method, reference_no, recorded_by) VALUES (?, ?, ?, ?, ?)");
        $stmt_pay->bind_param('idssi', $loanId, $amount, $method, $ref, $uid);
        $stmt_pay->execute();

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

$activeLoans = $db->query("SELECT l.id, CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name) AS full_name, m.member_id AS mem_code, lt.type_name, l.balance FROM loans l
    JOIN members m ON l.member_id=m.id
    JOIN loan_applications la ON l.application_id=la.id
    JOIN loan_types lt ON la.loan_type_id=lt.id
    WHERE l.status='active'");
?>

<?php include '../includes/admin_sidebar.php'; ?>

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
              <select name="payment_method" class="form-control" id="paymentMethod" onchange="updateRefLabel()">
                <option value="gcash">GCash</option>
                <option value="cash">Cash</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" id="refLabel">Reference No.</label>
              <input type="text" name="reference_no" id="referenceInput" class="form-control" placeholder="Leave empty for auto-generated receipt number">
              <small class="text-muted" id="refHelper" style="display:none;">Will auto-generate receipt number if left empty</small>
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
                  <th>Member</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($p = $payments->fetch_assoc()): ?>
                <tr onclick="viewPaymentDetails(this)" 
                    style="cursor:pointer;"
                    data-member="<?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['mem_code']) ?>)"
                    data-date="<?= date('M j, Y H:i', strtotime($p['paid_at'])) ?>"
                    data-loan="<?= htmlspecialchars($p['type_name']) ?>"
                    data-amount="₱<?= number_format($p['amount'], 2) ?>"
                    data-method="<?= ucfirst($p['payment_method']) ?>"
                    data-ref="<?= htmlspecialchars($p['reference_no'] ?: '—') ?>">
                  <td><div class="fw-600"><?= htmlspecialchars($p['full_name']) ?></div><div class="text-muted text-sm"><?= htmlspecialchars($p['mem_code']) ?></div></td>
                  <td><?= date('M j, Y', strtotime($p['paid_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($payments->num_rows === 0): ?>
                <tr><td colspan="2" style="text-align:center;color:var(--text-muted);">No payment history yet.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- PAYMENT DETAILS MODAL -->
<div class="modal-overlay" id="modal-payment-details">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-payment-details')">✕</button>
    <div class="modal-title">Payment Details</div>
    <div class="details-grid" style="gap:12px; margin-top:12px;">
      <div><strong>Member:</strong> <span id="det-member"></span></div>
      <div><strong>Date:</strong> <span id="det-date"></span></div>
      <div><strong>Loan Type:</strong> <span id="det-loan"></span></div>
      <div><strong>Amount:</strong> <span id="det-amount" class="fw-600" style="color:var(--primary);"></span></div>
      <div><strong>Method:</strong> <span><span id="det-method" class="badge badge-green"></span></span></div>
      <div><strong>Reference:</strong> <span id="det-ref"></span></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary" onclick="closeModal('modal-payment-details')">Close</button>
    </div>
  </div>
</div>

<script src="../js/app.js"></script>
<script>
function updateRefLabel() {
  const method = document.getElementById('paymentMethod').value;
  const label = document.getElementById('refLabel');
  const input = document.getElementById('referenceInput');
  const helper = document.getElementById('refHelper');
  
  if (method === 'cash') {
    label.textContent = 'Receipt No.';
    input.placeholder = 'Leave empty for auto-generated receipt number';
    helper.style.display = 'block';
  } else {
    label.textContent = 'Reference No.';
    input.placeholder = 'e.g., GCash Reference Number';
    helper.style.display = 'none';
  }
}

function viewPaymentDetails(row) {
  document.getElementById('det-member').textContent = row.dataset.member;
  document.getElementById('det-date').textContent = row.dataset.date;
  document.getElementById('det-loan').textContent = row.dataset.loan;
  document.getElementById('det-amount').textContent = row.dataset.amount;
  document.getElementById('det-method').textContent = row.dataset.method;
  document.getElementById('det-ref').textContent = row.dataset.ref;
  openModal('modal-payment-details');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', updateRefLabel);
</script>
</body>
</html>
