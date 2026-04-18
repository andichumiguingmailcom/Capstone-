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
requireLogin(['general_manager','collector','loan_officer']);
$activePage = 'payments';
$db = getDB();
$user = getCurrentUser();
$isLoanOfficer = $user['role'] === 'loan_officer';
$isCollector = $user['role'] === 'collector';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loan_id']) && isset($_POST['amount'])) {
    $loanId = !empty($_POST['loan_id']) ? (int)$_POST['loan_id'] : null;
    $memberId = !empty($_POST['member_id_grocery']) ? (int)$_POST['member_id_grocery'] : null;
    $amount = (float)$_POST['amount'];
    $category = clean($_POST['payment_category'] ?? 'loan');
    $method = clean($_POST['payment_method'] ?? 'cash');
    $ref = clean($_POST['reference_no'] ?? '');
    $uid = $_SESSION['user_id'];
    $status = $isCollector ? 'pending' : 'verified';

    // Auto-generate receipt number for cash payments
    if ($method === 'cash' && empty($ref)) {
        $ref = 'RCP-' . date('YmdHis') . '-' . rand(100, 999);
    }

    if ($category === 'loan' && $loanId) {
        $stmt = $db->prepare("SELECT member_id, balance, status FROM loans WHERE id=?");
        $stmt->bind_param('i', $loanId);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();

        if ($loan && $amount > 0 && $amount <= $loan['balance'] && $loan['status'] === 'active') {
            $stmt_pay = $db->prepare("INSERT INTO loan_payments (loan_id, member_id, amount, category, payment_method, reference_no, recorded_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_pay->bind_param('iidsssis', $loanId, $loan['member_id'], $amount, $category, $method, $ref, $uid, $status);
            $stmt_pay->execute();

            if ($status === 'verified') {
                $newBalance = max(0, $loan['balance'] - $amount);
                $newStatus = $newBalance <= 0 ? 'settled' : 'active';
                $stmt2 = $db->prepare("UPDATE loans SET balance=?, status=? WHERE id=?");
                $stmt2->bind_param('dsi', $newBalance, $newStatus, $loanId);
                $stmt2->execute();
                $msg = 'Loan payment recorded successfully.';
            } else {
                $msg = 'Loan payment recorded. Waiting for Loan Officer verification.';
            }
        } else {
            $msg = 'Error: Invalid amount or loan is not active.';
        }
    } elseif ($category === 'grocery' && $memberId) {
        // Fetch current grocery balance
        $stmt = $db->prepare("SELECT grocery_balance FROM members WHERE id=?");
        $stmt->bind_param('i', $memberId);
        $stmt->execute();
        $mInfo = $stmt->get_result()->fetch_assoc();
        $balance = (float)($mInfo['grocery_balance'] ?? 0);

        if ($mInfo && $amount > 0 && $amount <= $balance) {
            $stmt_pay = $db->prepare("INSERT INTO loan_payments (loan_id, member_id, amount, category, payment_method, reference_no, recorded_by, status) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_pay->bind_param('idsssis', $memberId, $amount, $category, $method, $ref, $uid, $status);
            $stmt_pay->execute();

            if ($status === 'verified') {
                $db->query("UPDATE members SET grocery_balance = grocery_balance - $amount WHERE id = $memberId");
                $msg = 'Grocery payment recorded and applied successfully.';
            } else {
                $msg = 'Grocery payment recorded. Waiting for Loan Officer verification.';
            }
        } else {
            $msg = 'Error: Invalid amount or exceeds grocery balance (₱' . number_format($balance, 2) . ').';
        }
    } else {
        $msg = 'Error: Please select a valid loan or member.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_payment') {
    if (!$isLoanOfficer && $user['role'] !== 'general_manager') {
        die('Unauthorized');
    }
    $paymentId = (int)$_POST['payment_id'];
    $pay = $db->query("SELECT lp.*, l.balance FROM loan_payments lp LEFT JOIN loans l ON lp.loan_id = l.id WHERE lp.id = $paymentId AND lp.status = 'pending'")->fetch_assoc();
    
    if ($pay) {
        if ($pay['category'] === 'loan' && !empty($pay['loan_id'])) {
            $newBalance = max(0, $pay['balance'] - $pay['amount']);
            $newStatus = ($newBalance <= 0) ? 'settled' : 'active';
            $db->query("UPDATE loans SET balance = $newBalance, status = '$newStatus' WHERE id = {$pay['loan_id']}");
        } elseif ($pay['category'] === 'grocery' && !empty($pay['member_id'])) {
            $db->query("UPDATE members SET grocery_balance = grocery_balance - {$pay['amount']} WHERE id = {$pay['member_id']}");
        }
        $db->query("UPDATE loan_payments SET status = 'verified', verified_by = " . (int)$_SESSION['user_id'] . " WHERE id = $paymentId");
        $msg = 'Payment accepted and balance updated.';
    }
}

$payments = $db->query("SELECT lp.*, CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name) AS full_name, m.member_id AS mem_code, lt.type_name, l.balance as loan_balance,
    CONCAT_WS(' ', u1.first_name, u1.last_name) AS recorded_by_name,
    CONCAT_WS(' ', u2.first_name, u2.last_name) AS verified_by_name
    FROM loan_payments lp
    LEFT JOIN loans l ON lp.loan_id=l.id
    LEFT JOIN members m ON lp.member_id=m.id
    LEFT JOIN loan_applications la ON l.application_id=la.id
    LEFT JOIN loan_types lt ON la.loan_type_id=lt.id
    LEFT JOIN users u1 ON lp.recorded_by = u1.id
    LEFT JOIN users u2 ON lp.verified_by = u2.id
    ORDER BY lp.paid_at DESC");

$activeLoans = $db->query("SELECT l.id, CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name) AS full_name, m.member_id AS mem_code, lt.type_name, l.balance FROM loans l
    JOIN members m ON l.member_id=m.id
    JOIN loan_applications la ON l.application_id=la.id
    JOIN loan_types lt ON la.loan_type_id=lt.id
    WHERE l.status='active'");

$allMembers = $db->query("SELECT id, member_id, CONCAT_WS(' ', first_name, last_name) as full_name, grocery_balance FROM members WHERE status='active' ORDER BY last_name, first_name");
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
      <div class="card" <?= $isLoanOfficer ? 'style="display:none;"' : '' ?>>
        <div class="card-header"><span class="card-title">Record Payment</span></div>
        <div class="card-body">
          <form method="POST">
            <div class="form-group">
              <label class="form-label">Payment Category</label>
              <select name="payment_category" id="paymentCategory" class="form-control" onchange="togglePaymentInputs()">
                <option value="loan">Loan Repayment</option>
                <option value="grocery">Grocery Payment</option>
              </select>
            </div>

            <div class="form-group" id="loanInputGroup">
              <label class="form-label">Select Active Loan Record</label>
              <select name="loan_id" id="loanSelect" class="form-control" required onchange="updateMaxAmount()">
                <option value="">— Choose a loan —</option>
                <?php while ($l = $activeLoans->fetch_assoc()): ?>
                <option value="<?= $l['id'] ?>" data-balance="<?= $l['balance'] ?>"><?= htmlspecialchars($l['full_name']) ?> (<?= htmlspecialchars($l['mem_code']) ?>) · <?= htmlspecialchars($l['type_name']) ?> · ₱<?= number_format($l['balance'],2) ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="form-group" id="groceryInputGroup" style="display:none;">
              <label class="form-label">Select Member</label>
              <select name="member_id_grocery" id="memberSelect" class="form-control" onchange="updateMaxAmount()">
                <option value="">— Choose a member —</option>
                <?php while ($m = $allMembers->fetch_assoc()): ?>
                <option value="<?= $m['id'] ?>" data-balance="<?= $m['grocery_balance'] ?>"><?= htmlspecialchars($m['full_name']) ?> (<?= htmlspecialchars($m['member_id']) ?>) · Balance: ₱<?= number_format($m['grocery_balance'] ?? 0, 2) ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Amount (₱)</label>
              <input type="number" id="amountInput" name="amount" class="form-control" min="1" step="0.01" required>
              <small class="text-muted" id="maxAmountHelper" style="display:none;">Maximum amount: ₱<span id="maxAmount">0.00</span></small>
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
        <div class="card-header"><span class="card-title">Payment History</span></div>
        <div class="card-body">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Member</th>
                  <th>Status / Action</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                while ($p = $payments->fetch_assoc()): 
                  $pStatus = $p['status'] ?? 'verified';
                ?>
                <tr onclick="viewPaymentDetails(this)" 
                    style="cursor:pointer;"
                    data-member="<?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['mem_code']) ?>)"
                    data-date="<?= date('M j, Y H:i', strtotime($p['paid_at'])) ?>"
                    data-loan="<?= htmlspecialchars($p['type_name'] ?: 'Grocery Payment') ?>"
                    data-amount="₱<?= number_format($p['amount'], 2) ?>"
                    data-method="<?= ucfirst($p['payment_method']) ?> (<?= ucfirst($pStatus) ?>)"
                    data-ref="<?= htmlspecialchars($p['reference_no'] ?: '—') ?>"
                    data-collector="<?= htmlspecialchars($p['recorded_by_name'] ?: 'Member (Online)') ?>"
                    data-officer="<?= htmlspecialchars($p['verified_by_name'] ?: '—') ?>">
                  <td><div class="fw-600"><?= htmlspecialchars($p['full_name']) ?></div><div class="text-muted text-sm"><?= htmlspecialchars($p['mem_code']) ?></div></td>
                  <td>
                    <?php if ($pStatus === 'pending'): ?>
                      <span class="badge badge-gold">Pending</span>
                      <?php if ($isLoanOfficer): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Accept this payment and reflect it on the loan balance?')">
                          <input type="hidden" name="action" value="verify_payment">
                          <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-primary">Accept</button>
                        </form>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="badge badge-green">Verified</span>
                      <div class="text-muted text-xs"><?= date('M j, Y', strtotime($p['paid_at'])) ?></div>
                    <?php endif; ?>
                  </td>
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
      <div><strong>Recorded By:</strong> <span id="det-collector"></span></div>
      <div><strong>Verified By:</strong> <span id="det-officer"></span></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary" onclick="closeModal('modal-payment-details')">Close</button>
    </div>
  </div>
</div>

<script src="../js/app.js"></script>
<script>
function togglePaymentInputs() {
  const category = document.getElementById('paymentCategory').value;
  const loanGroup = document.getElementById('loanInputGroup');
  const groceryGroup = document.getElementById('groceryInputGroup');
  
  if (category === 'loan') {
    loanGroup.style.display = 'block';
    groceryGroup.style.display = 'none';
    document.getElementById('loanSelect').required = true;
    document.getElementById('memberSelect').required = false;
  } else {
    loanGroup.style.display = 'none';
    groceryGroup.style.display = 'block';
    document.getElementById('loanSelect').required = false;
    document.getElementById('memberSelect').required = true;
    updateMaxAmount(); // Reset max amount
  }
}

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
  document.getElementById('det-collector').textContent = row.dataset.collector;
  document.getElementById('det-officer').textContent = row.dataset.officer;
  openModal('modal-payment-details');
}

function updateMaxAmount() {
  const loanSelect = document.getElementById('loanSelect');
  const amountInput = document.getElementById('amountInput');
  const maxHelper = document.getElementById('maxAmountHelper');
  const maxAmountSpan = document.getElementById('maxAmount');
  const category = document.getElementById('paymentCategory').value;
  
  const selectedOption = loanSelect.options[loanSelect.selectedIndex];
  const balance = selectedOption.dataset.balance;
  
  if (category === 'loan' && balance) {
    amountInput.max = balance;
    maxAmountSpan.textContent = parseFloat(balance).toFixed(2);
    maxHelper.style.display = 'block';
  } else {
    amountInput.removeAttribute('max');
    maxHelper.style.display = 'none';
  }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  updateRefLabel();
  updateMaxAmount();
});
</script>
</body>
</html>
