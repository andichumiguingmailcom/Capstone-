<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pre-Applications – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('general_manager');
$activePage = 'pre_apps';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id']; $action = clean($_POST['action']); $notes = clean($_POST['notes'] ?? '');
    if (in_array($action, ['approved','rejected'])) {
        if ($action === 'approved') {
            $pre = $db->query("SELECT * FROM pre_applications WHERE id=$id")->fetch_assoc();
            if ($pre && $pre['status'] === 'pending') {
                // 1. Auto-generate Member ID based on the last record
                $res = $db->query("SELECT member_id FROM members WHERE member_id LIKE 'MEM-%' ORDER BY id DESC LIMIT 1");
                $last_id = ($res && $res->num_rows > 0) ? $res->fetch_assoc()['member_id'] : 'MEM-000';
                preg_match('/\d+/', $last_id, $matches);
                $num = isset($matches[0]) ? (int)$matches[0] : 0;
                $new_member_id = 'MEM-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);

                // 2. Insert data into the members table
                $stmt = $db->prepare("INSERT INTO members (member_id, first_name, middle_name, last_name, email, phone, street, barangay, city, province, date_joined, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
                $today = date('Y-m-d');
                $status = 'active';
                $stmt->bind_param('ssssssssssss', 
                    $new_member_id, $pre['first_name'], $pre['middle_name'], $pre['last_name'], 
                    $pre['email'], $pre['phone'], $pre['street'], $pre['barangay'], 
                    $pre['city'], $pre['province'], $today, $status
                );
                $stmt->execute();
                $new_member_pk = $db->insert_id;
                $uid = $_SESSION['user_id'] ?? 0;

                // 3. Migrate uploaded documents to the member's official document history
                $db->query("INSERT INTO documents (member_id, doc_type, filename, filepath, uploaded_by) 
                            SELECT $new_member_pk, doc_type, filename, filepath, $uid 
                            FROM pre_application_documents WHERE pre_application_id=$id");

                // 4. Send Approval Email with Credentials
                $to = $pre['email'];
                $subject = "Welcome to CoopIMS - Your Membership is Approved!";
                $pin = substr($pre['phone'], -4);
                $login_url = "http://localhost/Capstone/member_login.php"; // Update this to your actual domain

                $message = "Dear " . htmlspecialchars($pre['first_name']) . ",\n\n";
                $message .= "Congratulations! Your membership application has been approved.\n\n";
                $message .= "You can now log in to the Member Portal using the following credentials:\n";
                $message .= "Member ID: " . $new_member_id . "\n";
                $message .= "PIN: " . $pin . " (The last 4 digits of your phone number)\n\n";
                $message .= "Access the portal here: " . $login_url . "\n\n";
                $message .= "Best regards,\nThe Cooperative Team";

                $headers = "From: no-reply@coopims.com\r\n";
                mail($to, $subject, $message, $headers);
            }
        }
        $stmt = $db->prepare("UPDATE pre_applications SET status=?, admin_notes=?, verified_at=NOW() WHERE id=?");
        $stmt->bind_param('ssi', $action, $notes, $id);
        $stmt->execute();
        header('Location: admin_pre_applications.php?msg=' . urlencode("Application $action. Member record created automatically.")); exit;
    }
}

$msg = clean($_GET['msg'] ?? '');
$apps = $db->query("SELECT *, 
    CONCAT_WS(' ', first_name, middle_name, last_name) as full_name,
    CONCAT_WS(', ', street, barangay, city, province) as address FROM pre_applications ORDER BY submitted_at DESC");
?>

<?php include 'includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar"><div class="topbar-title">Membership Pre-Applications</div></div>

  <div class="page-body">
    <?php if ($msg): ?>
      <div style="background:#d4f0dc;color:#1a6b3a;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:3px solid #2e9e58;">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><span class="card-title">All Pre-Applications</span></div>
      <div class="card-body">
        <div class="search-bar">
          <input type="text" id="appSearch" class="search-input" placeholder="Search applicants..." oninput="filterTable('appSearch','appTable')">
        </div>
        <div class="table-wrap">
          <table id="appTable">
            <thead><tr><th>#</th><th>Full Name</th><th>Contact</th><th>Address</th><th>Submitted</th><th>Status</th><th>Attachments</th><th>Actions</th></tr></thead>
            <tbody>
              <?php while ($a = $apps->fetch_assoc()):
                $b = ['pending'=>'badge-gold','approved'=>'badge-green','rejected'=>'badge-red'][$a['status']] ?? 'badge-gray';
              ?>
              <tr>
                <td class="text-muted">#<?= $a['id'] ?></td>
                <td><div class="fw-600"><?= htmlspecialchars($a['full_name']) ?></div></td> 
                <td><div><?= htmlspecialchars($a['email']) ?></div><div class="text-muted text-sm"><?= $a['phone'] ?></div></td>
                <td class="text-muted text-sm" style="max-width:150px;"><?= htmlspecialchars(trim($a['address'], ', ') ?: '—') ?></td>
                <td><?= date('M j, Y', strtotime($a['submitted_at'])) ?></td>
                <td><span class="badge <?= $b ?>"><?= ucfirst($a['status']) ?></span></td>
                <td>
                  <?php
                    $documents = $db->query("SELECT * FROM pre_application_documents WHERE pre_application_id=" . (int)$a['id'] . " ORDER BY uploaded_at DESC");
                    if ($documents && $documents->num_rows > 0):
                  ?>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <?php while ($doc = $documents->fetch_assoc()): ?>
                      <a href="<?= htmlspecialchars($doc['filepath']) ?>" target="_blank" class="badge badge-blue" style="font-size:0.75rem;">📎 <?= htmlspecialchars($doc['doc_type']) ?></a>
                    <?php endwhile; ?>
                    </div>
                  <?php else: ?>
                    <span class="text-muted text-sm">No attachments</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($a['status']==='pending'): ?>
                    <button class="btn btn-sm btn-primary" onclick="reviewApp(<?= $a['id'] ?>,'approved','<?= addslashes($a['full_name']) ?>')">✅ Approve</button>
                    <button class="btn btn-sm btn-danger" onclick="reviewApp(<?= $a['id'] ?>,'rejected','<?= addslashes($a['full_name']) ?>')">❌ Reject</button>
                  <?php else: ?>
                    <span class="text-muted text-sm"><?= $a['admin_notes'] ? htmlspecialchars(substr($a['admin_notes'],0,30)).'...' : 'Reviewed' ?></span>
                  <?php endif; ?>
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

<div class="modal-overlay" id="modal-review">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-review')">✕</button>
    <div class="modal-title" id="reviewTitle">Review Application</div>
    <p id="reviewName" style="color:var(--text-muted);margin-bottom:16px;"></p>
    <form method="POST">
      <input type="hidden" name="id" id="reviewId">
      <input type="hidden" name="action" id="reviewAction">
      <div class="form-group">
        <label class="form-label">Admin Notes</label>
        <textarea name="notes" class="form-control" rows="3" placeholder="Reason or notes (optional)..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-review')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="reviewBtn">Confirm</button>
      </div>
    </form>
  </div>
</div>

<script src="js/app.js"></script>
<script>
function reviewApp(id, action, name) {
  document.getElementById('reviewId').value = id;
  document.getElementById('reviewAction').value = action;
  document.getElementById('reviewTitle').textContent = (action === 'approved' ? '✅ Approve' : '❌ Reject') + ' Application';
  document.getElementById('reviewName').textContent = 'Applicant: ' + name;
  document.getElementById('reviewBtn').className = 'btn ' + (action === 'approved' ? 'btn-primary' : 'btn-danger');
  document.getElementById('reviewBtn').textContent = action === 'approved' ? 'Approve' : 'Reject';
  openModal('modal-review');
}
</script>
</body>
</html>
