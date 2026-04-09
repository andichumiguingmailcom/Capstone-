<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Loan Payment – CoopIMS</title>
  <link rel="stylesheet" href="../css/style.css">
  <script src="js/theme-init.js"></script>
</head>
<body>
<?php
require_once '../includes/config.php';
requireLogin('member');
$activePage = 'payment';
$db = getDB();

$memberId = $_SESSION['member_id'] ?? 0;
$selectedLoan = (int)($_GET['loan'] ?? 0);
$msg = ''; $msgType = 'green';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loanId = (int)$_POST['loan_id'];
    $amount = (float)$_POST['amount'];
    $method = clean($_POST['payment_method']);
    $ref    = clean($_POST['reference_no'] ?? '');
    $uid    = $_SESSION['user_id'] ?? 0;

    // Verify loan belongs to member
    $stmt = $db->prepare("SELECT id, balance FROM loans WHERE id=? AND member_id=? AND status='active'");
    $stmt->bind_param('ii', $loanId, $memberId);
    $stmt->execute();
    $loanCheck = $stmt->get_result()->fetch_assoc();

    if ($loanCheck && $amount > 0) {
        $stmt = $db->prepare("INSERT INTO loan_payments (loan_id, amount, payment_method, reference_no, recorded_by) VALUES (?,?,?,?,?)");
        $stmt->bind_param('idssi', $loanId, $amount, $method, $ref, $uid);
        $stmt->execute();

        $newBalance = max(0, $loanCheck['balance'] - $amount);
        $status = ($newBalance == 0) ? 'settled' : 'active';
        $updateStmt = $db->prepare("UPDATE loans SET balance=?, status=? WHERE id=?");
        $updateStmt->bind_param('dsi', $newBalance, $status, $loanId);
        $updateStmt->execute();

        $msg = 'Payment of ₱' . number_format($amount, 2) . ' recorded successfully!';
    } else {
        $msg = 'Invalid loan or amount.'; $msgType = 'red';
    }
}

$stmt = $db->prepare("SELECT l.*, lt.type_name FROM loans l 
    JOIN loan_applications la ON l.application_id=la.id 
    JOIN loan_types lt ON la.loan_type_id=lt.id
    WHERE l.member_id=? AND l.status='active' ORDER BY l.due_date");
$stmt->bind_param('i', $memberId);
$stmt->execute();
$myLoans = $stmt->get_result();

// Build loan array for JS
$loans = [];
$myLoans->data_seek(0);
while ($r = $myLoans->fetch_assoc()) $loans[] = $r;
?>

<?php include '../includes/member_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Make a Loan Payment</div>
  </div>

  <div class="page-body">
    <?php if ($msg): ?>
      <div style="background:<?= $msgType==='red'?'#fde8ea':'#d4f0dc' ?>;color:<?= $msgType==='red'?'#c0392b':'#1a6b3a' ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:3px solid currentColor;">
        <?= $msgType==='red'?'⚠️':'✅' ?> <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="grid-2">
      <!-- PAYMENT FORM -->
      <div class="card">
        <div class="card-header"><span class="card-title">💰 Payment Form</span></div>
        <div class="card-body">
          <form method="POST">
            <div class="form-group">
              <label class="form-label">Select Loan</label>
              <select name="loan_id" class="form-control" required id="loanSelect" onchange="updateLoanInfo(this.value)">
                <option value="">— Choose a loan —</option>
                <?php $myLoans->data_seek(0); while ($l = $myLoans->fetch_assoc()): ?>
                  <option value="<?= $l['id'] ?>" <?= $selectedLoan===$l['id']?'selected':'' ?>>
                    <?= htmlspecialchars($l['type_name']) ?> · Balance: ₱<?= number_format($l['balance'], 2) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <!-- LOAN INFO BOX -->
            <div id="loanInfoBox" style="display:none;background:var(--bg);border-radius:10px;padding:16px;margin-bottom:16px;">
              <div class="grid-2" style="gap:12px;">
                <div><div class="text-muted text-sm">Outstanding Balance</div><div class="fw-600" style="color:var(--danger);font-size:1.1rem;" id="loanBalance">—</div></div>
                <div><div class="text-muted text-sm">Monthly Due</div><div class="fw-600" id="loanMonthly">—</div></div>
                <div><div class="text-muted text-sm">Accrued Penalty</div><div class="fw-600" style="color:var(--danger);" id="loanPenalty">—</div></div>
                <div><div class="text-muted text-sm">Due Date</div><div class="fw-600" id="loanDue">—</div></div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Payment Amount (₱)</label>
              <input type="number" name="amount" id="payAmount" class="form-control" min="1" step="0.01" required placeholder="0.00">
              <div id="fullPayBtn" style="display:none;margin-top:6px;">
                <button type="button" class="btn btn-sm btn-outline" onclick="setFullPay()">Pay Full Balance</button>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Payment Method</label>
              <div style="display:flex;gap:10px;">
                <label style="flex:1;cursor:pointer;">
                  <input type="radio" name="payment_method" value="gcash" checked onchange="toggleQR('gcash')" style="margin-right:4px;">
                  <span class="badge badge-blue" style="padding:8px 14px;cursor:pointer;">📱 GCash</span>
                </label>
                <label style="flex:1;cursor:pointer;">
                  <input type="radio" name="payment_method" value="cash" onchange="toggleQR('cash')" style="margin-right:4px;">
                  <span class="badge badge-green" style="padding:8px 14px;cursor:pointer;">💵 Cash</span>
                </label>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Reference / Confirmation No.</label>
              <input type="text" name="reference_no" class="form-control" placeholder="e.g. GCash ref number">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
              Confirm Payment →
            </button>
          </form>
        </div>
      </div>

      <!-- GCASH QR -->
      <div>
        <div class="card" id="gcashCard">
          <div class="card-header"><span class="card-title">📱 GCash Payment</span></div>
          <div class="card-body">
            <div class="qr-box">
              <div class="qr-placeholder"></div>
              <div class="fw-600" style="font-size:1rem;">Scan to Pay via GCash</div>
              <div class="text-muted text-sm mt-1">GCash Number: <strong>0917-XXX-XXXX</strong></div>
              <div class="text-muted text-sm">Account Name: <strong>Cooperative Store</strong></div>
            </div>
            <div style="background:#fef3cd;border-radius:8px;padding:12px;margin-top:12px;font-size:0.85rem;color:#8a6000;">
              <strong>⚠️ Important:</strong> After payment, please save your GCash reference number and enter it in the form. Your payment will be verified within 24 hours.
            </div>
          </div>
        </div>

        <!-- PAYMENT HISTORY -->
        <div class="card">
          <div class="card-header"><span class="card-title">Payment History</span></div>
          <div class="card-body">
            <div class="table-wrap">
              <?php
              $stmt = $db->prepare("SELECT lp.*, lt.type_name FROM loan_payments lp 
                  JOIN loans l ON lp.loan_id=l.id 
                  JOIN loan_applications la ON l.application_id=la.id
                  JOIN loan_types lt ON la.loan_type_id=lt.id
                  WHERE l.member_id=? ORDER BY lp.paid_at DESC LIMIT 10");
              $stmt->bind_param('i', $memberId);
              $stmt->execute();
              $history = $stmt->get_result();
              ?>
              <table>
                <thead><tr><th>Loan</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th></tr></thead>
                <tbody>
                  <?php while ($py = $history->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($py['type_name']) ?></td>
                    <td class="fw-600">₱<?= number_format($py['amount'], 2) ?></td>
                    <td><span class="badge badge-green"><?= ucfirst($py['payment_method']) ?></span></td>
                    <td class="text-muted text-sm"><?= $py['reference_no'] ?? '—' ?></td>
                    <td><?= date('M j, Y', strtotime($py['paid_at'])) ?></td>
                  </tr>
                  <?php endwhile; ?>
                  <?php if ($history->num_rows === 0): ?>
                    <tr><td colspan="5" class="text-muted" style="text-align:center;">No payment history.</td></tr>
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

<script src="../js/app.js"></script>
<script>
const loans = <?= json_encode($loans) ?>;

function updateLoanInfo(id) {
  const loan = loans.find(l => l.id == id);
  if (!loan) { document.getElementById('loanInfoBox').style.display='none'; return; }
  document.getElementById('loanBalance').textContent = '₱' + parseFloat(loan.balance).toLocaleString('en-PH', {minimumFractionDigits:2});
  document.getElementById('loanMonthly').textContent = '₱' + parseFloat(loan.monthly_due).toLocaleString('en-PH', {minimumFractionDigits:2});
  document.getElementById('loanPenalty').textContent = '₱' + parseFloat(loan.accrued_penalty || 0).toLocaleString('en-PH', {minimumFractionDigits:2});
  document.getElementById('loanDue').textContent = loan.due_date;
  document.getElementById('loanInfoBox').style.display='block';
  document.getElementById('fullPayBtn').style.display='block';
  document.getElementById('payAmount').value = loan.monthly_due;
}

function setFullPay() {
  const id = document.getElementById('loanSelect').value;
  const loan = loans.find(l => l.id == id);
  if (loan) document.getElementById('payAmount').value = loan.balance;
}

function toggleQR(method) {
  document.getElementById('gcashCard').style.display = method === 'gcash' ? '' : 'none';
}

// Init if preselected
if (document.getElementById('loanSelect').value) {
  updateLoanInfo(document.getElementById('loanSelect').value);
}
</script>
</body>
</html>
