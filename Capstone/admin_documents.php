<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document Management – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('general_manager');
$activePage = 'documents';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $memberId = (int)$_POST['member_id'];
    $docType  = clean($_POST['doc_type']);
    $uid      = $_SESSION['user_id'];
    $file     = $_FILES['document'];

    $allowed = ['pdf','jpg','jpeg','png'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (in_array($ext, $allowed) && $file['size'] < 5*1024*1024) {
        $uploadDir = 'uploads/docs/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $fname = 'doc_' . time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $uploadDir . $fname);
        $filepath = $uploadDir . $fname;
        $stmt = $db->prepare("INSERT INTO documents (member_id, doc_type, filename, filepath, uploaded_by) VALUES (?,?,?,?,?)");
        $stmt->bind_param('isssi', $memberId, $docType, $fname, $filepath, $uid);
        $stmt->execute();
        header('Location: admin_documents.php?msg=Document+uploaded.'); exit;
    }
}

$msg = clean($_GET['msg'] ?? '');
$filterMember = (int)($_GET['member'] ?? 0);
$where = $filterMember ? "WHERE d.member_id=$filterMember" : '';

$docs = $db->query("SELECT d.*, CONCAT_WS(' ', m.first_name, m.last_name) as full_name, m.member_id as mc, CONCAT_WS(' ', u.first_name, u.last_name) as uploader
    FROM documents d JOIN members m ON d.member_id=m.id LEFT JOIN users u ON d.uploaded_by=u.id $where ORDER BY d.uploaded_at DESC");
$members = $db->query("SELECT id, member_id, CONCAT_WS(' ', first_name, middle_name, last_name) as full_name 
    FROM members WHERE status='active' ORDER BY last_name, first_name");
?>

<?php include 'includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Document Management</div>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="openModal('modal-upload')">+ Upload Document</button>
    </div>
  </div>

  <div class="page-body">
    <?php if ($msg): ?>
      <div style="background:#d4f0dc;color:#1a6b3a;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:3px solid #2e9e58;">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <span class="card-title">Member Documents</span>
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
          <select name="member" class="filter-select" onchange="this.form.submit()">
            <option value="">All Members</option>
            <?php while ($m = $members->fetch_assoc()): ?>
              <option value="<?= $m['id'] ?>" <?= $filterMember===$m['id']?'selected':'' ?>>
                <?= htmlspecialchars($m['full_name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </form>
      </div>
      <div class="card-body">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Member</th><th>Document Type</th><th>File</th><th>Uploaded By</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
              <?php while ($d = $docs->fetch_assoc()): ?>
              <tr>
                <td><div class="fw-600"><?= htmlspecialchars($d['full_name']) ?></div><div class="text-muted text-sm"><?= $d['mc'] ?></div></td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($d['doc_type']) ?></span></td>
                <td class="text-muted text-sm"><?= htmlspecialchars($d['filename']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($d['uploader'] ?? '—') ?></td>
                <td><?= date('M j, Y', strtotime($d['uploaded_at'])) ?></td>
                <td>
                  <a href="<?= htmlspecialchars($d['filepath']) ?>" target="_blank" class="btn btn-sm btn-outline">📄 View</a>
                </td>
              </tr>
              <?php endwhile; ?>
              <?php if ($docs->num_rows === 0): ?>
                <tr><td colspan="6" class="text-muted" style="text-align:center;padding:30px;">No documents found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-upload">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-upload')">✕</button>
    <div class="modal-title">📄 Upload Member Document</div>
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label class="form-label">Member</label>
        <select name="member_id" class="form-control" required>
          <option value="">— Select Member —</option>
          <?php $members->data_seek(0); while ($m = $members->fetch_assoc()): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?> (<?= $m['member_id'] ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Document Type</label>
        <select name="doc_type" class="form-control" required>
          <option value="">— Select Type —</option>
          <option value="Valid ID">Valid ID</option>
          <option value="Membership Form">Membership Form</option>
          <option value="Loan Agreement">Loan Agreement</option>
          <option value="Proof of Income">Proof of Income</option>
          <option value="Birth Certificate">Birth Certificate</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">File (PDF, JPG, PNG · max 5MB)</label>
        <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-upload')">Cancel</button>
        <button type="submit" class="btn btn-primary">Upload</button>
      </div>
    </form>
  </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
