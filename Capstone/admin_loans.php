<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Active Loans – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin();
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
            $db->query("UPDATE loans SET balance=0, status='settled' WHERE id=$loanId");
            $msg = 'Loan marked as settled.';
        } else {
            $msg = 'Loan not found or already settled.';
        }
    }
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

<?php include 'includes/admin_sidebar.php'; ?>

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
      <div style="background:#d4f0dc;color:#1a6b3a;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:3px solid #2e9e58;">
        ✅ <?= htmlspecialchars($msg) ?>
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
                  <form method="POST" style="margin:0;">
                    <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                    <input type="hidden" name="action" value="settle">
                    <button class="btn btn-sm btn-primary" type="submit">Mark settled</button>
                  </form>
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

<script src="js/app.js"></script>
</body>
</html>