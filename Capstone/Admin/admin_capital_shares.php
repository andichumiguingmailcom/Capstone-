<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Capital Shares – CoopIMS</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php
require_once '../includes/config.php';
requireLogin(['general_manager','book_keeper']);
$activePage = 'capital_shares';
$db = getDB();

$msg = '';
$user = getCurrentUser();
$canEdit = $user['role'] === 'general_manager';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    if (isset($_POST['member_id']) && isset($_POST['amount'])) {
        $memberId = (int)$_POST['member_id'];
        $amount = (float)$_POST['amount'];

        if ($amount < 0) {
            $msg = 'Capital share amount cannot be negative.';
        } else {
            // Check if capital share record exists
            $existing = $db->query("SELECT id FROM capital_shares WHERE member_id=$memberId")->fetch_assoc();

            if ($existing) {
                $db->query("UPDATE capital_shares SET amount=$amount, updated_by={$user['id']} WHERE member_id=$memberId");
            } else {
                $db->query("INSERT INTO capital_shares (member_id, amount, updated_by) VALUES ($memberId, $amount, {$user['id']})");
            }
            $msg = 'Capital share updated successfully.';
        }
    }
}

// Get all members with their capital shares
$query = "SELECT m.*, cs.amount as capital_share, cs.updated_at, cs.updated_by,
          CONCAT_WS(' ', u.first_name, u.last_name) as updated_by_name
          FROM members m
          LEFT JOIN capital_shares cs ON m.id = cs.member_id
          LEFT JOIN users u ON cs.updated_by = u.id
          ORDER BY m.last_name, m.first_name";

$members = $db->query($query);
?>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Capital Shares Management</div>
    <div class="topbar-actions">
      <span class="text-muted text-sm">📅 <?= date('F j, Y') ?></span>
    </div>
  </div>

  <div class="page-body">
    <?php if ($msg): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <span class="card-title">Member Capital Shares</span>
      </div>
      <div class="card-body">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Member ID</th>
                <th>Name</th>
                <th>Capital Share</th>
                <th>Last Updated</th>
                <th>Updated By</th>
                <?php if ($canEdit): ?>
                  <th>Actions</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php while ($member = $members->fetch_assoc()): ?>
              <tr>
                <td class="fw-600"><?= htmlspecialchars($member['member_id']) ?></td>
                <td>
                  <?= htmlspecialchars($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name']) ?>
                  <?php if ($member['status'] !== 'active'): ?>
                    <span class="badge badge-warning"><?= ucfirst($member['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="fw-600 text-success">₱<?= number_format($member['capital_share'] ?? 0, 2) ?></td>
                <td class="text-muted text-sm">
                  <?= $member['updated_at'] ? date('M j, Y H:i', strtotime($member['updated_at'])) : 'Never' ?>
                </td>
                <td class="text-muted text-sm">
                  <?= htmlspecialchars($member['updated_by_name'] ?? 'System') ?>
                </td>
                <?php if ($canEdit): ?>
                  <td>
                    <button class="btn btn-sm btn-outline" onclick="editShare(<?= $member['id'] ?>, '<?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>', <?= $member['capital_share'] ?? 0 ?>)">
                      Edit
                    </button>
                  </td>
                <?php endif; ?>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay">
  <div class="modal" style="max-width:500px;">
    <button type="button" class="modal-close" onclick="closeModal('editModal')">✕</button>
    <div class="modal-title" id="modalTitle">Edit Capital Share</div>
    <form method="POST">
      <input type="hidden" name="member_id" id="edit_member_id">
      
      <div id="editFields">
        <div class="form-group">
          <label class="form-label">Member</label>
          <input type="text" class="form-control" id="edit_member_name" readonly>
        </div>
        <div class="form-group">
          <label class="form-label">Capital Share Amount (₱)</label>
          <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" min="0" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="showConfirmation()">Update Share</button>
        </div>
      </div>

      <div id="confirmFields" style="display:none;">
        <p id="confirmText" style="margin-bottom:24px; line-height:1.6; color:var(--text);"></p>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" onclick="cancelConfirmation()">Go Back</button>
          <button type="submit" class="btn btn-primary">✅ Yes, Confirm Update</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function editShare(memberId, memberName, currentAmount) {
  document.getElementById('edit_member_id').value = memberId;
  document.getElementById('edit_member_name').value = memberName;
  document.getElementById('edit_amount').value = currentAmount;
  cancelConfirmation(); // Ensure we start on the edit view
  openModal('editModal');
}

function showConfirmation() {
  const name = document.getElementById('edit_member_name').value;
  const amount = parseFloat(document.getElementById('edit_amount').value).toLocaleString('en-PH', {minimumFractionDigits: 2});
  
  document.getElementById('confirmText').innerHTML = `Are you sure you want to update the capital share for <strong>${name}</strong> to <strong>₱${amount}</strong>?`;
  document.getElementById('editFields').style.display = 'none';
  document.getElementById('confirmFields').style.display = 'block';
  document.getElementById('modalTitle').textContent = '⚠️ Confirm Update';
}

function cancelConfirmation() {
  document.getElementById('editFields').style.display = 'block';
  document.getElementById('confirmFields').style.display = 'none';
  document.getElementById('modalTitle').textContent = 'Edit Capital Share';
}
</script>

<script src="../js/app.js"></script>
</body>
</html>
