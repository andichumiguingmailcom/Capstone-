<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pre-Applications – CoopIMS</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php
require_once '../includes/config.php';

// PHPMailer Classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
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

requireLogin(['general_manager','loan_officer']);
$activePage = 'pre_apps';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id']; 
    $action = clean($_POST['action'] ?? ''); 
    $notes = clean($_POST['notes'] ?? '');

    if (in_array($action, ['approved','rejected'])) {
        // Fetch applicant details for notification before updating
        $stmt = $db->prepare("SELECT * FROM pre_applications WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $appData = $stmt->get_result()->fetch_assoc();

        if ($action === 'approved' && $appData) {
            $memberId = generateMemberID($db);
            $dateJoined = date('Y-m-d');
            $status = 'active';
            $stmtMem = $db->prepare("INSERT INTO members (member_id, first_name, middle_name, last_name, email, phone, street, barangay, city, province, date_joined, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmtMem->bind_param('ssssssssssss', $memberId, $appData['first_name'], $appData['middle_name'], $appData['last_name'], $appData['email'], $appData['phone'], $appData['street'], $appData['barangay'], $appData['city'], $appData['province'], $dateJoined, $status);
            if ($stmtMem->execute()) {
                $newMemberId = $db->insert_id;
                // Create initial capital share record
                $stmtCap = $db->prepare("INSERT INTO capital_shares (member_id, amount, updated_by) VALUES (?, ?, ?)");
                $stmtCap->bind_param('idi', $newMemberId, $appData['initial_capital'], $_SESSION['user_id']);
                $stmtCap->execute();
            }
        }

        // Update status in pre_applications table
        $memberIdForUpdate = ($action === 'approved' && isset($newMemberId)) ? $newMemberId : null;
        $stmtUpdate = $db->prepare("UPDATE pre_applications SET status=?, admin_notes=?, verified_at=NOW(), member_id=? WHERE id=?");
        $stmtUpdate->bind_param('ssii', $action, $notes, $memberIdForUpdate, $id);
        $stmtUpdate->execute();

        // Send Email Notification via PHPMailer
        $emailError = '';
        if ($appData && !empty($appData['email'])) {
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
                $mail->setFrom(SMTP_USERNAME, MAIL_FROM_NAME);
                $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);
                $mail->SMTPDebug  = 0; // Set to 2 for debugging if needed
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer debug level {$level}: {$str}");
                };

                $mail->addAddress($appData['email'], $appData['first_name']);
                $mail->isHTML(true);
                $mail->Subject = 'Membership Application Status Update';
                
                $statusLabel = ucfirst($action);
                $mail->Body = "Hello " . htmlspecialchars($appData['first_name']) . ",<br><br>Your membership application at CoopIMS has been <b>$statusLabel</b>.<br>Admin Remarks: " . (!empty($notes) ? htmlspecialchars($notes) : "None") . "<br><br>Thank you,<br>CoopIMS Team";
                $mail->send();
            } catch (Exception $e) {
                $emailError = "Mailer Error: " . $e->getMessage();
                error_log("Pre-application email failed for ID {$id}: {$emailError}");
            }
        }

        $redirectMsg = "Application " . ($action === 'approved' ? 'approved and member record created' : 'rejected') . ".";
        if (!empty($emailError)) {
            $redirectMsg .= " (Email notification failed)";
        }
        header('Location: admin_pre_applications.php?msg=' . urlencode($redirectMsg)); exit;
    }
}

/**
 * Helper to generate unique Member IDs (MEM-001, MEM-002, etc.)
 */
function generateMemberID($db) {
    $res = $db->query("SELECT member_id FROM members ORDER BY id DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $last = $res->fetch_assoc()['member_id'];
        $num = (int)str_replace('MEM-', '', $last);
        return 'MEM-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
    return 'MEM-001';
}

$msg = clean($_GET['msg'] ?? '');
$apps = $db->query("SELECT *, 
    CONCAT_WS(' ', first_name, middle_name, last_name) as full_name,
    CONCAT_WS(', ', street, barangay, city, province) as address FROM pre_applications WHERE status='pending' ORDER BY submitted_at DESC");
?>

<?php include '../includes/admin_sidebar.php'; ?>

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
            <thead><tr><th>#</th><th>Full Name</th><th>Contact</th><th>Address</th><th>Submitted</th><th>Status</th><th>Attachments</th></tr></thead>
            <tbody>
              <?php while ($a = $apps->fetch_assoc()):
                $b = ['pending'=>'badge-gold','approved'=>'badge-green','rejected'=>'badge-red'][$a['status']] ?? 'badge-gray';
              ?>
              <tr onclick="reviewApp(this)" 
                  style="cursor:pointer;" 
                  title="Click to review or view details"
                  data-id="<?= $a['id'] ?>"
                  data-name="<?= htmlspecialchars($a['full_name']) ?>"
                  data-status="<?= $a['status'] ?>"
                  data-notes="<?= htmlspecialchars($a['admin_notes'] ?? '') ?>"
                  data-details='<?= htmlspecialchars(json_encode([
                    'dob' => $a['dob'],
                    'sex' => $a['sex'],
                    'civil_status' => $a['civil_status'],
                    'occupation' => $a['occupation'],
                    'res_cert' => $a['res_cert'],
                    'residence_types' => json_decode($a['residence_types'] ?? '[]', true),
                    'spouse' => ['name' => $a['spouse_name'], 'dob' => $a['spouse_dob'], 'job' => $a['spouse_job']],
                    'business' => ['name' => $a['business_name'], 'facebook' => $a['business_facebook']],
                    'beneficiary' => ['name' => $a['beneficiary_name'], 'dob' => $a['beneficiary_dob'], 'sex' => $a['beneficiary_sex'], 'relationship' => $a['beneficiary_relationship']],
                    'income' => ['gross' => $a['gross_income'], 'expenses' => $a['expenses'], 'net' => $a['net_income']],
                    'outstanding' => ['creditor' => $a['outstanding_creditor'], 'address' => $a['outstanding_address'], 'amount' => $a['outstanding_amount'], 'due_date' => $a['outstanding_due_date']],
                    'dependents' => [
                      'names' => json_decode($a['dependents_name'] ?? '[]', true),
                      'dobs' => json_decode($a['dependents_dob'] ?? '[]', true),
                      'ages' => json_decode($a['dependents_age'] ?? '[]', true),
                      'relationships' => json_decode($a['dependents_relationship'] ?? '[]', true)
                    ],
                    'declaration' => $a['declaration']
                  ]), ENT_QUOTES, 'UTF-8') ?>'>
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
                      <a href="<?= htmlspecialchars($doc['filepath']) ?>" target="_blank" class="badge badge-blue" style="font-size:0.75rem;" onclick="event.stopPropagation();">📎 <?= htmlspecialchars($doc['doc_type']) ?></a>
                    <?php endwhile; ?>
                    </div>
                  <?php else: ?>
                    <span class="text-muted text-sm">No attachments</span>
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
  <div class="modal" style="max-width: 800px;">
    <button class="modal-close" onclick="closeModal('modal-review')">✕</button>
    <div class="modal-title" id="reviewTitle">Review Application</div>
    <p id="reviewName" style="color:var(--text-muted);margin-bottom:16px;"></p>
    
    <div id="applicationFullDetails" style="margin-bottom: 20px; max-height: 400px; overflow-y: auto; background: #f9fafb; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb;">
      <!-- Data injected via JavaScript -->
    </div>

    <form method="POST" id="approvalForm">
      <input type="hidden" name="id" id="reviewId">
      <input type="hidden" name="action" id="actionInput" value="">
      <div class="form-group">
        <label class="form-label">Admin Notes</label>
        <textarea name="notes" id="reviewNotes" class="form-control" rows="3" placeholder="Reason or notes (optional)..."></textarea>
      </div>
      <div class="modal-footer" id="reviewFooter">
        <!-- Buttons will be injected by JS -->
      </div>
    </form>
  </div>
</div>

<script src="../js/app.js"></script>
<script>
function reviewApp(row) {
  const id = row.dataset.id;
  const name = row.dataset.name;
  const status = row.dataset.status;
  const notes = row.dataset.notes;
  const detailsJson = row.dataset.details;

  document.getElementById('reviewId').value = id;
  document.getElementById('reviewName').textContent = 'Applicant: ' + name;

  let details = {};
  try {
    details = JSON.parse(detailsJson);
  } catch (e) {
    console.error("Error parsing application details:", e);
  }
  const detailsDiv = document.getElementById('applicationFullDetails');
  
  let html = `<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 0.85rem;">`;
  html += `<div><strong>Date of Birth:</strong> ${details.dob || '—'}</div>`;
  html += `<div><strong>Age:</strong> ${details.age || '—'}</div>`;
  html += `<div><strong>Sex:</strong> ${details.sex || '—'}</div>`;
  html += `<div><strong>Civil Status:</strong> ${details.civil_status || '—'}</div>`;
  html += `<div><strong>Occupation:</strong> ${details.occupation || '—'}</div>`;
  html += `<div><strong>Residence Certificate:</strong> ${details.res_cert || '—'}</div>`;
  html += `<div><strong>Residence Types:</strong> ${(details.residence_types || []).join(', ') || '—'}</div>`;
  html += `<div><strong>Business Name:</strong> ${details.business?.name || '—'}</div>`;
  html += `<div><strong>Facebook:</strong> ${details.business?.facebook || '—'}</div>`;
  html += `</div>`;

  if (details.spouse && details.spouse.name) {
    html += `<div style="margin-top:15px; padding-top:10px; border-top:1px solid #eee;">`;
    html += `<strong>Spouse:</strong> ${details.spouse.name} (Date of Birth: ${details.spouse.dob || '—'}, Job: ${details.spouse.job || '—'})`;
    html += `</div>`;
  }

  if (details.beneficiary && details.beneficiary.name) {
    html += `<div style="margin-top:15px; padding-top:10px; border-top:1px solid #eee;">`;
    html += `<strong>Beneficiary:</strong> ${details.beneficiary.name} (Date of Birth: ${details.beneficiary.dob || '—'}, Sex: ${details.beneficiary.sex || '—'}, Relationship: ${details.beneficiary.relationship || '—'})`;
    html += `</div>`;
  }

  if (details.income) {
    html += `<div style="margin-top:10px; color:#1a6b3a;"><strong>Income:</strong> Gross: ₱${parseFloat(details.income.gross || 0).toLocaleString()}, Expenses: ₱${parseFloat(details.income.expenses || 0).toLocaleString()}, Net: ₱${parseFloat(details.income.net || 0).toLocaleString()}</div>`;
  }

  if (details.outstanding && details.outstanding.creditor) {
    html += `<div style="margin-top:15px;"><strong>Outstanding Loan:</strong><ul style="margin:5px 0 0 20px; padding:0;">`;
    html += `<li>Creditor: ${details.outstanding.creditor}, Address: ${details.outstanding.address || '—'}, Amount: ₱${parseFloat(details.outstanding.amount || 0).toLocaleString()}, Due Date: ${details.outstanding.due_date || '—'}</li>`;
    html += `</ul></div>`;
  }

  if (details.dependents && details.dependents.names && details.dependents.names.length > 0) {
    html += `<div style="margin-top:15px;"><strong>Dependents:</strong><ul style="margin:5px 0 0 20px; padding:0;">`;
    details.dependents.names.forEach((name, i) => {
      html += `<li>${name} (Date of Birth: ${details.dependents.dobs[i] || '—'}, Age: ${details.dependents.ages[i] || '—'}, Relationship: ${details.dependents.relationships[i] || '—'})</li>`;
    });
    html += `</ul></div>`;
  }

  if (details.declaration) {
    html += `<div style="margin-top:15px; font-style:italic; color:#555;"><strong>Declaration:</strong> ${details.declaration}</div>`;
  }

  detailsDiv.innerHTML = html;
  
  const notesField = document.getElementById('reviewNotes');
  const footer = document.getElementById('reviewFooter');
  const form = document.getElementById('approvalForm');
  
  if (status === 'pending') {
    document.getElementById('reviewTitle').textContent = '📝 Review Application';
    notesField.value = '';
    notesField.readOnly = false;
    footer.innerHTML = `
      <button type="button" class="btn btn-ghost" onclick="closeModal('modal-review')">Cancel</button>
      <button type="button" class="btn btn-danger" onclick="submitApproval('rejected')">Reject</button>
      <button type="button" class="btn btn-primary" onclick="submitApproval('approved')">Approve</button>
    `;
  } else {
    document.getElementById('reviewTitle').textContent = '📄 Application Reviewed';
    notesField.value = notes;
    notesField.readOnly = true;
    footer.innerHTML = `<button type="button" class="btn btn-primary" onclick="closeModal('modal-review')">Close</button>`;
  }
  openModal('modal-review');
}

function submitApproval(action) {
  document.getElementById('actionInput').value = action;
  document.getElementById('approvalForm').submit();
}
</script>
</body>
</html>
