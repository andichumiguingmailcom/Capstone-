<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Active Loans – CoopIMS</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php
require_once '../includes/config.php';
requireLogin(['general_manager','book_keeper','collector','loan_officer']);
$activePage = 'loans';
$db = getDB();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loan_id']) && isset($_POST['action'])) {
    $loanId = (int)$_POST['loan_id'];
    $action = clean($_POST['action']);

    if ($action === 'settle') {
        $stmt = $db->prepare("SELECT balance FROM loans WHERE id=? AND status='active'");
        $stmt->bind_param('i', $loanId);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();

        if ($loan) {
            if ($loan['balance'] > 0) {
                $msg = 'Error: Loan cannot be settled. Outstanding balance: ₱' . number_format($loan['balance'], 2);
            } else {
                $db->query("UPDATE loans SET balance=0, status='settled' WHERE id=$loanId");
                $msg = 'Loan marked as settled.';
            }
        } else {
            $msg = 'Loan not found or already settled.';
        }
    }
}

// Handle AJAX request for settle form
if (isset($_GET['action']) && $_GET['action'] === 'get_settle_form' && isset($_GET['loan_id'])) {
    $loanId = (int)$_GET['loan_id'];

    // Get loan details
    $loan = $db->query("SELECT l.*, CONCAT_WS(' ', m.first_name, m.last_name) as full_name, m.member_id as mem_code
        FROM loans l
        JOIN members m ON l.member_id = m.id
        WHERE l.id = $loanId AND l.status = 'active'")->fetch_assoc();

    if (!$loan) {
        echo '<p class="text-center text-muted">Loan not found or already settled.</p>';
        exit;
    }

    // Get payment history
    $payments = $db->query("SELECT lp.*, u.first_name, u.last_name
        FROM loan_payments lp
        LEFT JOIN users u ON lp.recorded_by = u.id
        WHERE lp.loan_id = $loanId
        ORDER BY lp.paid_at DESC");

    ?>
    <div class="mb-4">
      <h4 class="mb-3">Loan Details</h4>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Member</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($loan['full_name']) ?> (<?= htmlspecialchars($loan['mem_code']) ?>)" readonly>
        </div>
        <div class="form-group">
          <label class="form-label">Principal Amount</label>
          <input type="text" class="form-control" value="₱<?= number_format($loan['principal'], 2) ?>" readonly>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Current Balance</label>
          <input type="text" class="form-control" value="₱<?= number_format($loan['balance'], 2) ?>" readonly>
        </div>
        <div class="form-group">
          <label class="form-label">Monthly Due</label>
          <input type="text" class="form-control" value="₱<?= number_format($loan['monthly_due'], 2) ?>" readonly>
        </div>
      </div>
    </div>

    <div class="mb-4">
      <h4 class="mb-3">Payment History</h4>
      <?php if ($payments->num_rows > 0): ?>
      <div class="table-wrap" style="max-height: 300px; overflow-y: auto;">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Reference</th>
              <th>Recorded By</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($payment = $payments->fetch_assoc()): ?>
            <tr>
              <td><?= date('M j, Y H:i', strtotime($payment['paid_at'])) ?></td>
              <td class="fw-600">₱<?= number_format($payment['amount'], 2) ?></td>
              <td><span class="badge badge-blue"><?= ucfirst($payment['payment_method']) ?></span></td>
              <td><?= htmlspecialchars($payment['reference_no'] ?: '—') ?></td>
              <td><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?: 'System' ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <p class="text-center text-muted">No payments recorded yet.</p>
      <?php endif; ?>
    </div>

    <div class="alert alert-warning" style="background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
      <strong>⚠️ Confirmation Required</strong><br>
      Are you sure you want to mark this loan as settled?
      <?php if ($loan['balance'] > 0): ?>
      <br><br><strong>Warning:</strong> This loan still has an outstanding balance of ₱<?= number_format($loan['balance'], 2) ?>. It cannot be settled until fully paid.
      <?php endif; ?>
    </div>

    <form method="POST">
      <input type="hidden" name="loan_id" value="<?= $loanId ?>">
      <input type="hidden" name="action" value="settle">
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-settle-loan')">Cancel</button>
        <button type="submit" class="btn btn-primary" <?= $loan['balance'] > 0 ? 'disabled' : '' ?>>
          <?= $loan['balance'] > 0 ? '❌ Cannot Settle (Outstanding Balance)' : '✅ Confirm Settlement' ?>
        </button>
      </div>
    </form>
    <?php
    exit;
}

$filterStatus = clean($_GET['status'] ?? 'active');
$where = '';
if (in_array($filterStatus, ['active', 'settled', 'defaulted'])) {
    $where = "WHERE l.status='$filterStatus'";
}

$loans = $db->query("SELECT l.*, CONCAT_WS(' ', m.first_name, m.last_name) as full_name, m.member_id AS mem_code, lt.type_name
    FROM loans l
    JOIN members m ON l.member_id=m.id
    JOIN loan_applications la ON l.application_id=la.id
    JOIN loan_types lt ON la.loan_type_id=lt.id
    $where
    ORDER BY l.due_date ASC");
?>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Active Loans</div>
    <div class="topbar-actions">
      <a href="admin_loans.php?status=active" class="btn btn-sm btn-outline">Active</a>
      <a href="admin_loans.php?status=settled" class="btn btn-sm btn-outline">Settled</a>
      <a href="admin_loans.php?status=defaulted" class="btn btn-sm btn-outline">Defaulted</a>
      <a href="admin_loans.php" class="btn btn-sm btn-outline">All</a>
    </div>
  </div>

  <div class="page-body">
    <?php if ($msg): ?>
      <div style="background:<?= strpos($msg, 'Error') === 0 ? '#fee' : '#d4f0dc' ?>;color:<?= strpos($msg, 'Error') === 0 ? '#c33' : '#1a6b3a' ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:3px solid <?= strpos($msg, 'Error') === 0 ? '#e74c3c' : '#2e9e58' ?>;">
        <?= strpos($msg, 'Error') === 0 ? '❌' : '✅' ?> <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><span class="card-title">Loan Records</span></div>
      <div class="card-body">
        <div class="search-bar">
          <input type="text" id="loanSearch" class="search-input" placeholder="Search by member, loan type, or status..." oninput="filterTable('loanSearch','loanTable')">
        </div>
        <div class="table-wrap">
          <table id="loanTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Member</th>
                <th>Loan Type</th>
                <th>Principal</th>
                <th>Balance</th>
                <th>Monthly Due</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($loan = $loans->fetch_assoc()): ?>
              <tr>
                <td class="text-muted">#<?= $loan['id'] ?></td>
                <td>
                  <div class="fw-600"><?= htmlspecialchars($loan['full_name']) ?></div>
                  <div class="text-muted text-sm"><?= htmlspecialchars($loan['mem_code']) ?></div>
                </td>
                <td><?= htmlspecialchars($loan['type_name']) ?></td>
                <td class="fw-600">₱<?= number_format($loan['principal'], 2) ?></td>
                <td class="fw-600">₱<?= number_format($loan['balance'], 2) ?></td>
                <td>₱<?= number_format($loan['monthly_due'], 2) ?></td>
                <td><?= date('M j, Y', strtotime($loan['due_date'])) ?></td>
                <td><span class="badge <?= $loan['status'] === 'active' ? 'badge-blue' : ($loan['status'] === 'settled' ? 'badge-green' : 'badge-red') ?>"><?= ucfirst($loan['status']) ?></span></td>
                <td>
                  <?php if ($loan['status'] === 'active'): ?>
                  <button class="btn btn-sm btn-primary" onclick="openSettleModal(<?= $loan['id'] ?>, '<?= htmlspecialchars($loan['full_name']) ?>', '<?= htmlspecialchars($loan['mem_code']) ?>', <?= $loan['balance'] ?>)">Mark settled</button>
                  <?php else: ?>
                  <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
              <?php if ($loans->num_rows === 0): ?>
              <tr><td colspan="9" style="text-align:center;color:var(--text-muted);">No loans found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- SETTLE LOAN MODAL -->
<div class="modal-overlay" id="modal-settle-loan">
  <div class="modal" style="max-width:800px;">
    <button class="modal-close" onclick="closeModal('modal-settle-loan')">✕</button>
    <div class="modal-title">🔒 Mark Loan as Settled</div>

    <div id="settleLoanContent">
      <!-- Content will be loaded here -->
    </div>
  </div>
</div>

<script src="../js/app.js"></script>
<script>
function openSettleModal(loanId, memberName, memberCode, balance) {
  const modal = document.getElementById('modal-settle-loan');
  const content = document.getElementById('settleLoanContent');

  // Load payment history and confirmation form
  fetch(`admin_loans.php?action=get_settle_form&loan_id=${loanId}`)
    .then(response => response.text())
    .then(html => {
      content.innerHTML = html;
      openModal('modal-settle-loan');
    })
    .catch(error => {
      console.error('Error loading settle form:', error);
      content.innerHTML = '<p class="text-center text-muted">Error loading form. Please try again.</p>';
      openModal('modal-settle-loan');
    });
}
</script>
</body>
</html>
