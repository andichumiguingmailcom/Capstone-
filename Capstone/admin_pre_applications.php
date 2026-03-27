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

// PHPMailer Classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
}

// Email settings (update values to your SMTP provider/account)
define('MAIL_FROM', 'no-reply@coopims.com');
define('MAIL_FROM_NAME', 'CoopIMS');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'bichamco5@gmail.com');
define('SMTP_PASSWORD', 'wkhrtajdvqckwbzz'); // Gmail App Password without spaces
define('SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS);
define('SMTP_PORT', 587);

requireLogin();
$activePage = 'pre_apps';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id']; $action = clean($_POST['action']); $notes = clean($_POST['notes'] ?? '');
    if (in_array($action, ['approved','rejected'])) {
        // Fetch applicant details for notification before updating
        $stmt = $db->prepare("SELECT first_name, email FROM pre_applications WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $applicant = $stmt->get_result()->fetch_assoc();

        $db->query("UPDATE pre_applications SET status='$action', admin_notes='$notes', verified_at=NOW() WHERE id=$id");

        // Send Email Notification via PHPMailer
        $emailError = '';
        if ($applicant && !empty($applicant['email'])) {
            $mail = new PHPMailer(true);
            try {
                // SMTP Server Settings
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST; // e.g., smtp.gmail.com
                $mail->SMTPAuth   = SMTP_AUTH;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port       = SMTP_PORT;
                $mail->SMTPAutoTLS = true;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
                $mail->CharSet    = 'UTF-8';
                $mail->setFrom(SMTP_USERNAME, MAIL_FROM_NAME); // for Gmail SMTP, from should match authenticated account
                $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);
                $mail->SMTPDebug  = 0; // Set to 2 for debugging if needed
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer debug level {$level}: {$str}");
                };

                $mail->addAddress($applicant['email'], $applicant['first_name']);
                $mail->isHTML(true);
                $mail->Subject = 'Membership Application Status Update';
                
                $statusLabel = ucfirst($action);
                $mail->Body = "Hello " . htmlspecialchars($applicant['first_name']) . ",<br><br>Your membership application at CoopIMS has been <b>$statusLabel</b>.<br>Admin Remarks: " . (!empty($notes) ? htmlspecialchars($notes) : "None") . "<br><br>Thank you,<br>CoopIMS Team";
                $mail->send();
            } catch (Exception $e) {
                $emailError = "Mailer Error: " . $e->getMessage();
                error_log("Pre-application email failed for ID {$id}: {$emailError}");
            }
        }

        $redirectMsg = "Application $action.";
        if (!empty($emailError)) {
            $redirectMsg .= " (Email notification failed)";
        }
        header('Location: admin_pre_applications.php?msg=' . urlencode($redirectMsg)); exit;
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
