<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apply for Membership – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
$msg = ''; $submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = clean($_POST['first_name'] ?? '');
    $mname = clean($_POST['middle_name'] ?? '');
    $lname = clean($_POST['last_name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $street = clean($_POST['street'] ?? '');
    $brgy   = clean($_POST['barangay'] ?? '');
    $city   = clean($_POST['city'] ?? '');
    $prov   = clean($_POST['province'] ?? '');

    // Required ID upload
    $idDoc = $_FILES['id_document'] ?? null;

    if (!$fname || !$lname || !$email || !$phone) {
        $msg = 'Please fill in all required fields.';
    } elseif (!$idDoc || $idDoc['error'] !== UPLOAD_ERR_OK) {
        $msg = 'Please upload a valid ID document.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO pre_applications (first_name, middle_name, last_name, email, phone, street, barangay, city, province) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssss', $fname, $mname, $lname, $email, $phone, $street, $brgy, $city, $prov);
        $stmt->execute();
        $appId = $db->insert_id;

        $uploadDir = 'uploads/pre_applications/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        function storePreAppDoc($file, $type, $appId, $db, $uploadDir) {
            $allowed = ['pdf','jpg','jpeg','png'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed) || $file['size'] > 5 * 1024 * 1024) return false;
            $fname = 'preapp_' . time() . '_' . uniqid() . '.' . $ext;
            $target = $uploadDir . $fname;
            if (!move_uploaded_file($file['tmp_name'], $target)) return false;
            $stmt = $db->prepare("INSERT INTO pre_application_documents (pre_application_id, doc_type, filename, filepath) VALUES (?,?,?,?)");
            if (!$stmt) return false;
            $stmt->bind_param('isss', $appId, $type, $fname, $target);
            return $stmt->execute();
        }

        if (!storePreAppDoc($idDoc, 'Valid ID', $appId, $db, $uploadDir)) {
            $msg = 'Failed to save ID document. Please try again.';
        } else {
            if (!empty($_FILES['other_documents']) && is_array($_FILES['other_documents']['name'])) {
                foreach ($_FILES['other_documents']['name'] as $idx => $nameFile) {
                    if (empty($nameFile)) continue;
                    $file = [
                        'name' => $_FILES['other_documents']['name'][$idx],
                        'type' => $_FILES['other_documents']['type'][$idx],
                        'tmp_name' => $_FILES['other_documents']['tmp_name'][$idx],
                        'error' => $_FILES['other_documents']['error'][$idx],
                        'size' => $_FILES['other_documents']['size'][$idx],
                    ];
                    storePreAppDoc($file, 'Additional Document', $appId, $db, $uploadDir);
                }
            }

            $submitted = true;
        }
        $db->close();
    }
}
?>

<div class="login-page">
  <div class="login-card" style="width:100%; max-width:800px;">
    <?php if ($submitted): ?>
      <div style="text-align:center;padding:20px 0;">
        <div style="font-size:4rem;margin-bottom:16px;">🎉</div>
        <h2 class="login-title">Application Submitted!</h2>
        <p class="login-sub" style="margin-bottom:32px;">
          Thank you for applying to join <strong>CoopIMS</strong>. Your pre-application has been received and is currently under review by our team.
        </p>
        <div class="alert alert-success" style="text-align:left;display:flex;gap:12px;align-items:flex-start;">
          <div style="font-size:1.5rem;">📧</div>
          <div>
            <div class="fw-600" style="margin-bottom:4px;">What happens next?</div>
            <div style="font-size:0.9rem;opacity:0.9;">
              We will contact you at your provided email or phone number within 3-5 business days regarding the status of your application.
            </div>
          </div>
        </div>
        <a href="index.php" class="btn btn-primary" style="justify-content:center;">Return to Home</a>
      </div>
    <?php else: ?>
      <div>
        <div class="login-logo">
          <div class="logo-text">🌾 CoopIMS</div>
          <div class="logo-sub">Membership Application</div>
        </div>
        <h2 class="login-title">Join Our Cooperative</h2>
        <p class="login-sub">
          Become a member of our cooperative today. Please fill out the form below.
        </p>

        <?php if ($msg): ?>
          <div class="alert alert-error">
            ⚠️ <?= htmlspecialchars($msg) ?>
          </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
          <div style="margin-bottom:20px;">
            <div class="text-muted text-sm fw-600" style="text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:8px;">Personal Information</div>
            <div class="form-row three">
              <div class="form-group"><label class="form-label">First Name <span style="color:var(--danger);">*</span></label><input type="text" name="first_name" class="form-control" required></div>
              <div class="form-group"><label class="form-label">Middle Name</label><input type="text" name="middle_name" class="form-control"></div>
              <div class="form-group"><label class="form-label">Last Name <span style="color:var(--danger);">*</span></label><input type="text" name="last_name" class="form-control" required></div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Email Address <span style="color:var(--danger);">*</span></label>
                <input type="email" name="email" class="form-control" required placeholder="juan@email.com">
              </div>
              <div class="form-group">
                <label class="form-label">Phone Number <span style="color:var(--danger);">*</span></label>
                <input type="tel" name="phone" class="form-control" required placeholder="09XX-XXX-XXXX">
              </div>
            </div>
          </div>

          <div style="margin-bottom:20px;">
            <div class="text-muted text-sm fw-600" style="text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:8px;">Address</div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">Street/House No.</label><input type="text" name="street" class="form-control"></div>
              <div class="form-group"><label class="form-label">Barangay</label><input type="text" name="barangay" class="form-control"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label class="form-label">City/Municipality</label><input type="text" name="city" class="form-control"></div>
              <div class="form-group"><label class="form-label">Province</label><input type="text" name="province" class="form-control"></div>
            </div>
          </div>

          <div style="margin-bottom:24px;">
            <div class="text-muted text-sm fw-600" style="text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:8px;">Required Documents</div>
            
            <div style="background:var(--bg-card);padding:20px;border-radius:var(--radius-sm);border:1px dashed var(--border-strong);">
              <div class="form-group">
                <label class="form-label">Valid ID (PDF/JPG/PNG, max 5MB) <span style="color:var(--danger);">*</span></label>
                <input type="file" name="id_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">Accepted: Gov't ID, Driver's License, Passport</div>
              </div>

              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Additional Documents (optional)</label>
                <input type="file" name="other_documents[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" multiple>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">Proof of income, Barangay clearance, etc.</div>
              </div>
            </div>
          </div>

          <div class="alert alert-success" style="font-size:0.85rem;">
            <strong>Privacy Note:</strong> By submitting this form, you agree to our terms and conditions. Your information will be kept confidential and used solely for membership evaluation.
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;font-size:1rem;">
            Submit Application
          </button>
        </form>

        <div class="login-footer">
          <a href="index.php">
            ← Back to Login
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
