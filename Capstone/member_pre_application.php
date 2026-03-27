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

// Email settings
define('MAIL_FROM', 'no-reply@coopims.com');
define('MAIL_FROM_NAME', 'CoopIMS');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'bichamco5@gmail.com');
define('SMTP_PASSWORD', 'wkhrtajdvqckwbzz'); // Gmail App Password without spaces
define('SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS);
define('SMTP_PORT', 587);

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

            // Send confirmation email to applicant
            $emailError = '';
            $mail = new PHPMailer(true);
            try {
                // SMTP Server Settings
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
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
                $mail->SMTPDebug  = 0; // Set to 2 for debugging
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer debug level {$level}: {$str}");
                };

                $mail->addAddress($email, $fname);
                $mail->isHTML(true);
                $mail->Subject = 'Membership Application Received - CoopIMS';

                $mail->Body = "Hello " . htmlspecialchars($fname) . ",<br><br>Thank you for submitting your membership application at CoopIMS.<br><br>Your application has been received and is currently under review. We will contact you within 3-5 business days regarding the status of your application.<br><br><b>Application Details:</b><br>Name: " . htmlspecialchars($fname . ' ' . $mname . ' ' . $lname) . "<br>Email: " . htmlspecialchars($email) . "<br>Phone: " . htmlspecialchars($phone) . "<br><br>If you have any questions, please contact us.<br><br>Best regards,<br>CoopIMS Team";

                $mail->send();
            } catch (Exception $e) {
                $emailError = "Mailer Error: " . $e->getMessage();
                error_log("Pre-application confirmation email failed for {$email}: {$emailError}");
            }
        }
        $db->close();
    }
}
?>

<div class="login-page" style="background:linear-gradient(135deg,#0f4424,#1a6b3a,#2e9e58);">
  <div style="width:100%;max-width:600px;padding:20px;">
    <?php if ($submitted): ?>
      <div style="background:#fff;border-radius:20px;padding:48px 40px;text-align:center;">
        <div style="font-size:4rem;margin-bottom:20px;">🎉</div>
        <h2 style="font-family:'Syne',sans-serif;font-size:1.6rem;color:var(--primary-dark);margin-bottom:12px;">Application Submitted!</h2>
        <p style="color:var(--text-muted);margin-bottom:24px;line-height:1.6;">
          Thank you for applying for membership at our cooperative. Your pre-application has been received and is under review.
          We will contact you at your provided email or phone number within 3-5 business days.
        </p>
        <div style="background:#d4f0dc;border-radius:10px;padding:16px;margin-bottom:24px;color:#1a6b3a;font-size:0.88rem;">
          ✅ Your application has been submitted successfully.
          <?php if (empty($emailError)): ?>
            A confirmation email has been sent to <?= htmlspecialchars($email) ?>.
          <?php else: ?>
            (Note: Email notification could not be sent at this time.)
          <?php endif; ?>
        </div>
        <a href="index.php" class="btn btn-primary">Return to Login</a>
      </div>
    <?php else: ?>
      <div style="background:#fff;border-radius:20px;padding:44px 40px;">
        <div style="margin-bottom:28px;">
          <div style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:var(--primary-dark);">
            🌾 Join Our Cooperative
          </div>
          <p style="color:var(--text-muted);margin-top:6px;font-size:0.9rem;">
            Fill out this pre-application form. Our team will review and contact you shortly.
          </p>
        </div>

        <?php if ($msg): ?>
          <div style="background:#fde8ea;color:#c0392b;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:0.87rem;border-left:3px solid #e63946;">
            ⚠️ <?= htmlspecialchars($msg) ?>
          </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
          <div class="form-row">
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
          <div class="form-row">
            <div class="form-group"><label class="form-label">Street</label><input type="text" name="street" class="form-control"></div>
            <div class="form-group"><label class="form-label">Barangay</label><input type="text" name="barangay" class="form-control"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">City/Municipality</label><input type="text" name="city" class="form-control"></div>
            <div class="form-group"><label class="form-label">Province</label><input type="text" name="province" class="form-control"></div>
          </div>

          <div class="form-group">
            <label class="form-label">Valid ID (PDF/JPG/PNG, max 5MB) <span style="color:var(--danger);">*</span></label>
            <input type="file" name="id_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
          </div>

          <div class="form-group">
            <label class="form-label">Additional Documents (optional, multiple allowed)</label>
            <input type="file" name="other_documents[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" multiple>
          </div>

          <div style="background:#f4f7f2;border-radius:8px;padding:14px;margin-bottom:20px;font-size:0.84rem;color:var(--text-muted);">
            By submitting this form, you agree to our terms and conditions. Your information will be kept confidential.
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">
            Submit Pre-Application →
          </button>
        </form>

        <div style="margin-top:24px;text-align:center;">
          <a href="index.php" style="font-size:0.83rem;color:var(--text-muted);text-decoration:none;">
            ← Back to Login
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
