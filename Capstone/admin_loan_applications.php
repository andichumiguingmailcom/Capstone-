<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Loan Applications – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin(['general_manager','collector','loan_officer']);
$activePage = 'loan_apps';
$db = getDB();

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $action = clean($_POST['action'] ?? '');
    $remarks = clean($_POST['remarks'] ?? '');

    if ($id && in_array($action, ['approved','rejected'])) {
        $uid = $_SESSION['user_id'];
        $stmt = $db->prepare("UPDATE loan_applications SET status=?, approved_by=?, approved_at=NOW(), remarks=? WHERE id=?");
        $stmt->bind_param('sisi', $action, $uid, $remarks, $id);
        $stmt->execute();

        // If approved, create loan record
        if ($action === 'approved') {
            $app = $db->query("SELECT la.*, lt.type_name, lt.interest FROM loan_applications la 
                               JOIN loan_types lt ON la.loan_type_id = lt.id WHERE la.id=$id")->fetch_assoc();
            if ($app) {
                // Get member's capital share to determine actual interest rate
                $capitalShare = $db->query("SELECT COALESCE(amount, 0) as amount FROM capital_shares WHERE member_id={$app['member_id']}")->fetch_assoc()['amount'] ?? 0;
                
                // Calculate actual interest rate based on loan type and capital share
                $interestRate = $app['interest'];
                if ($app['type_name'] === 'Regular Loan') {
                    $interestRate = 3.0;
                } elseif ($app['type_name'] === 'Special Loan') {
                    $interestRate = $capitalShare >= 75001 ? 1.5 : 2.0;
                } elseif ($app['type_name'] === 'Spring Board Loan') {
                    $interestRate = $capitalShare >= 75001 ? 1.5 : 2.5;
                }
                
                $total_interest_factor = ($interestRate / 100) * $app['term_months'];
                $monthly = round($app['amount'] * (1 + $total_interest_factor) / $app['term_months'], 2);
                $due = date('Y-m-d', strtotime('+1 month'));
                $db->query("INSERT INTO loans (application_id, member_id, principal, balance, monthly_due, due_date) 
                    VALUES ($id, {$app['member_id']}, {$app['amount']}, {$app['amount']}, $monthly, '$due')");
                $db->query("UPDATE loan_applications SET status='disbursed' WHERE id=$id");
            }
        }
        header('Location: admin_loan_applications.php?msg=' . urlencode("Application {$action} successfully."));
        exit;
    }
}

$msg = clean($_GET['msg'] ?? '');
$filterStatus = clean($_GET['status'] ?? '');

$where = $filterStatus ? "WHERE la.status='$filterStatus'" : '';
$applications = $db->query("SELECT la.*, CONCAT_WS(' ', m.first_name, m.last_name) as full_name, m.member_id AS mem_code, lt.type_name, lt.interest,
    (SELECT id FROM loans WHERE application_id = la.id LIMIT 1) AS loan_id
    FROM loan_applications la 
    JOIN members m ON la.member_id = m.id 
    JOIN loan_types lt ON la.loan_type_id = lt.id
    $where
    ORDER BY la.applied_at DESC");
$loanTypes = $db->query("SELECT * FROM loan_types ORDER BY type_name");
$members   = $db->query("SELECT id, member_id, CONCAT_WS(' ', first_name, last_name) as full_name 
    FROM members WHERE status='active' ORDER BY last_name, first_name");
?>

<?php include 'includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Loan Applications</div>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="openModal('modal-add-loan')">+ New Application</button>
    </div>
  </div>

  <div class="page-body">
    <?php if ($msg): ?>
      <div style="background:#d4f0dc;color:#1a6b3a;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:3px solid #2e9e58;">
        ✅ <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <span class="card-title">All Applications</span>
        <div class="flex gap-2">
          <?php foreach(['','pending','approved','rejected','disbursed'] as $s): ?>
            <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filterStatus===$s ? 'btn-primary' : 'btn-ghost' ?>">
              <?= $s ? ucfirst($s) : 'All' ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card-body">
        <div class="search-bar">
          <input type="text" id="loanSearch" class="search-input" placeholder="Search by member or type..." oninput="filterTable('loanSearch','loanTable')">
        </div>
        <div class="table-wrap">
          <table id="loanTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Member</th>
                <th>Loan Type</th>
                <th>Amount</th>
                <th>Term</th>
                <th>Date Applied</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $applications->fetch_assoc()): ?>
              <tr>
                <td class="text-muted">#<?= $row['id'] ?></td>
                <td>
                  <div class="fw-600"><?= htmlspecialchars($row['full_name']) ?></div>
                  <div class="text-muted text-sm"><?= $row['mem_code'] ?></div>
                </td>
                <td><?= htmlspecialchars($row['type_name']) ?></td>
                <td class="fw-600">₱<?= number_format($row['amount'], 2) ?></td>
                <td><?= $row['term_months'] ?> mos.</td>
                <td><?= date('M j, Y', strtotime($row['applied_at'])) ?></td>
                <td>
                  <?php $badge = ['pending'=>'badge-gold','approved'=>'badge-green','rejected'=>'badge-red','disbursed'=>'badge-blue'];
                  $b = $badge[$row['status']] ?? 'badge-gray'; ?>
                  <span class="badge <?= $b ?>"><?= ucfirst($row['status']) ?></span>
                </td>
                <td>
                  <?php if ($row['status'] === 'pending'): ?>
                  <button class="btn btn-sm btn-primary" 
                    onclick="approveApp(<?= $row['id'] ?>, 'approved')">✅ Approve</button>
                  <button class="btn btn-sm btn-danger" 
                    onclick="approveApp(<?= $row['id'] ?>, 'rejected')">❌ Reject</button>
                  <?php endif; ?>
                  <button class="btn btn-sm btn-ghost" 
                    onclick="viewApplication(this)"
                    data-app-id="<?= $row['id'] ?>"
                    data-loan-id="<?= $row['loan_id'] ?: '' ?>"
                    data-member-name="<?= htmlspecialchars($row['full_name'], ENT_QUOTES) ?>"
                    data-member-id="<?= htmlspecialchars($row['mem_code'], ENT_QUOTES) ?>"
                    data-loan-type="<?= htmlspecialchars($row['type_name'], ENT_QUOTES) ?>"
                    data-date-applied="<?= htmlspecialchars(date('Y-m-d', strtotime($row['applied_at'])), ENT_QUOTES) ?>"
                    data-date-decision="<?= $row['approved_at'] ? htmlspecialchars(date('Y-m-d', strtotime($row['approved_at'])), ENT_QUOTES) : '' ?>"
                    data-status="<?= htmlspecialchars($row['status'], ENT_QUOTES) ?>"
                    data-remarks="<?= htmlspecialchars($row['remarks'] ?? 'No remarks', ENT_QUOTES) ?>"
                  >
                    📄 View
                  </button>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ADD LOAN APPLICATION MODAL -->
<div class="modal-overlay" id="modal-add-loan">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-add-loan')">✕</button>
    <div class="modal-title">📝 New Loan Application</div>
    <form method="POST" action="php/loan_application_submit.php">
      <div class="form-group">
        <label class="form-label">Member</label>
        <select name="member_id" class="form-control" required>
          <option value="">— Select Member —</option>
          <?php while ($m = $members->fetch_assoc()): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?> (<?= $m['member_id'] ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Loan Type</label>
        <select name="loan_type_id" class="form-control" required>
          <option value="">— Select Loan Type —</option>
          <?php $loanTypes->data_seek(0); while ($lt = $loanTypes->fetch_assoc()): ?>
            <option value="<?= $lt['id'] ?>">
              <?= htmlspecialchars($lt['type_name']) ?> (<?= $lt['interest'] ?>%/mo, max ₱<?= number_format($lt['max_amount'], 0) ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Loan Amount (₱)</label>
          <input type="number" name="amount" class="form-control" min="100" step="0.01" required placeholder="0.00">
        </div>
        <div class="form-group">
          <label class="form-label">Term (months)</label>
          <input type="number" name="term_months" class="form-control" min="1" max="60" required placeholder="6">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Purpose</label>
        <textarea name="purpose" class="form-control" rows="3" placeholder="State purpose of loan..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add-loan')">Cancel</button>
        <button type="submit" class="btn btn-primary">Submit Application</button>
      </div>
    </form>
  </div>
</div>

<!-- APPROVE/REJECT MODAL -->
<div class="modal-overlay" id="modal-action">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-action')">✕</button>
    <div class="modal-title" id="actionTitle">Approve Application</div>
    <form method="POST">
      <input type="hidden" name="id" id="actionId">
      <input type="hidden" name="action" id="actionType">
      <div class="form-group">
        <label class="form-label">Remarks / Notes</label>
        <textarea name="remarks" class="form-control" rows="3" placeholder="Add remarks (optional)..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-action')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="actionBtn">Confirm</button>
      </div>
    </form>
  </div>
</div>

<!-- APPLICATION VIEW MODAL -->
<div class="modal-overlay" id="modal-view-application">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-view-application')">✕</button>
    <div class="modal-title">Loan Application Details</div>
    <div class="details-grid" style="gap:12px; margin-top:12px;">
      <div><strong>Application ID:</strong> <span id="detailAppId"></span></div>
      <div><strong>Loan ID:</strong> <span id="detailLoanId"></span></div>
      <div><strong>Member Name:</strong> <span id="detailMemberName"></span></div>
      <div><strong>Member ID:</strong> <span id="detailMemberId"></span></div>
      <div><strong>Loan Type:</strong> <span id="detailLoanType"></span></div>
      <div><strong>Date Applied:</strong> <span id="detailDateApplied"></span></div>
      <div><strong>Date Decision:</strong> <span id="detailDateDecision"></span></div>
      <div><strong>Status:</strong> <span id="detailStatus"></span></div>
      <div style="grid-column:1/-1;"><strong>Remarks:</strong> <span id="detailRemarks"></span></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary" onclick="closeModal('modal-view-application')">Close</button>
    </div>
  </div>
</div>

<script src="js/app.js"></script>
<script>
function approveApp(id, action) {
  document.getElementById('actionId').value = id;
  document.getElementById('actionType').value = action;
  document.getElementById('actionTitle').textContent = action === 'approved' ? '✅ Approve Application' : '❌ Reject Application';
  document.getElementById('actionBtn').className = 'btn ' + (action === 'approved' ? 'btn-primary' : 'btn-danger');
  document.getElementById('actionBtn').textContent = action === 'approved' ? 'Approve' : 'Reject';
  openModal('modal-action');
}
function viewApplication(button) {
  const appId = button.dataset.appId || 'N/A';
  const loanId = button.dataset.loanId || 'N/A';
  const memberName = button.dataset.memberName || 'N/A';
  const memberId = button.dataset.memberId || 'N/A';
  const loanType = button.dataset.loanType || 'N/A';
  const dateApplied = button.dataset.dateApplied || 'N/A';
  const dateDecision = button.dataset.dateDecision || 'Pending';
  const status = button.dataset.status || 'N/A';
  const remarks = button.dataset.remarks || 'N/A';

  document.getElementById('detailAppId').textContent = appId;
  document.getElementById('detailLoanId').textContent = loanId === '' ? 'N/A' : loanId;
  document.getElementById('detailMemberName').textContent = memberName;
  document.getElementById('detailMemberId').textContent = memberId;
  document.getElementById('detailLoanType').textContent = loanType;
  document.getElementById('detailDateApplied').textContent = dateApplied;
  document.getElementById('detailDateDecision').textContent = dateDecision === '' ? 'Pending' : dateDecision;
  document.getElementById('detailStatus').textContent = status;
  document.getElementById('detailRemarks').textContent = remarks;

  openModal('modal-view-application');
}
</script>
</body>
</html>
