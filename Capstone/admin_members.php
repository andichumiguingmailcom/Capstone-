<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Members – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('book_keeper');
$activePage = 'members';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean($_POST['action'] ?? '');
    if ($action === 'update_status') {
        $id = (int)$_POST['id']; $status = clean($_POST['status']);
        $db->query("UPDATE members SET status='$status' WHERE id=$id");
        header('Location: admin_members.php?msg=Status+updated.'); exit;
    }
}

$msg = clean($_GET['msg'] ?? '');
$members = $db->query("SELECT m.*, CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name) as full_name,
    (SELECT COUNT(*) FROM loans WHERE member_id=m.id AND status='active') as active_loans,
    (SELECT COUNT(*) FROM sales WHERE member_id=m.id) as purchases
    FROM members m ORDER BY m.last_name, m.first_name");
$totalMembers = $db->query("SELECT COUNT(*) as c FROM members")->fetch_assoc()['c'];
$activeMembers = $db->query("SELECT COUNT(*) as c FROM members WHERE status='active'")->fetch_assoc()['c'];
?>

<?php include 'includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Member Management</div>
    <div class="topbar-actions">
    </div>
  </div>

  <div class="page-body">
    <?php if ($msg): ?>
      <div style="background:#d4f0dc;color:#1a6b3a;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:3px solid #2e9e58;">
        ✅ <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
      <div class="stat-card green"><span class="stat-icon">👥</span><div class="stat-value"><?= $totalMembers ?></div><div class="stat-label">Total Members</div></div>
      <div class="stat-card blue"><span class="stat-icon">✅</span><div class="stat-value"><?= $activeMembers ?></div><div class="stat-label">Active</div></div>
      <div class="stat-card gold"><span class="stat-icon">⏸️</span><div class="stat-value"><?= $totalMembers - $activeMembers ?></div><div class="stat-label">Inactive / Suspended</div></div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">All Members</span></div>
      <div class="card-body">
        <div class="search-bar">
          <input type="text" id="memSearch" class="search-input" placeholder="Search members..." oninput="filterTable('memSearch','memTable')">
          <select class="filter-select" onchange="filterByStatus(this.value)">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
          </select>
        </div>
        <div class="table-wrap">
          <table id="memTable">
            <thead>
              <tr><th>Member ID</th><th>Full Name</th><th>Contact</th><th>Date Joined</th><th>Active Loans</th><th>Purchases</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php while ($m = $members->fetch_assoc()): ?>
              <tr>
                <td class="fw-600"><?= $m['member_id'] ?></td>
                <td>
                  <div class="fw-600"><?= htmlspecialchars($m['full_name']) ?></div>
                  <div class="text-muted text-sm"><?= htmlspecialchars($m['email'] ?? '') ?></div>
                </td>
                <td class="text-muted"><?= $m['phone'] ?></td>
                <td><?= $m['date_joined'] ? date('M j, Y', strtotime($m['date_joined'])) : '—' ?></td>
                <td><?= $m['active_loans'] > 0 ? '<span class="badge badge-blue">'.$m['active_loans'].'</span>' : '0' ?></td>
                <td><?= $m['purchases'] ?></td>
                <td>
                  <?php $sb=['active'=>'badge-green','inactive'=>'badge-gray','suspended'=>'badge-red']; ?>
                  <span class="badge <?= $sb[$m['status']] ?>"><?= ucfirst($m['status']) ?></span>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline" onclick="changeStatus(<?= $m['id'] ?>,'<?= $m['status'] ?>')">Edit Status</button>
                  <a href="admin_member_detail.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-ghost">View</a>
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

<!-- STATUS MODAL -->
<div class="modal-overlay" id="modal-status">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-status')">✕</button>
    <div class="modal-title">Update Member Status</div>
    <form method="POST">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="id" id="statusMemberId">
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control" id="statusSelect">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="suspended">Suspended</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-status')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>

<script src="js/app.js"></script>
<script>
function changeStatus(id, currentStatus) {
  document.getElementById('statusMemberId').value = id;
  document.getElementById('statusSelect').value = currentStatus;
  openModal('modal-status');
}
function filterByStatus(val) {
  document.querySelectorAll('#memTable tbody tr').forEach(row => {
    if (!val) { row.style.display=''; return; }
    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
  });
}
</script>
</body>
</html>
