<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apply for Membership – CoopIMS</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body {
      background-color: #7a1e2c !important; /* Professional Maroon Background */
      background-image: none !important;
    }
    body::before, .login-page::before {
      display: none !important; /* Disable global gradients */
    }
    .pre-app-card {
      background-color: #f4f4f2 !important; /* Dirty White card */
      color: #333333;
      border: none;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      border-radius: 24px;
    }
    .pre-app-headline, .pre-app-title {
      color: #7a1e2c !important;
    }
    .pre-app-lead, .pre-app-intro, label, .text-muted, .text-sm {
      color: #6b7280 !important;
    }
    .form-control {
      background: #ffffff !important;
      border: 1px solid #d1d5db !important;
      color: #333333 !important;
    }
    .form-control::placeholder {
      color: #9ca3af !important;
    }
    .btn-primary {
      background-color: #7a1e2c !important;
      color: #ffffff !important;
      border: none;
    }
    .pre-app-verify {
      background: #eadddd !important; /* Light maroon tint for verify box */
      color: #7a1e2c !important;
      border: 1px solid rgba(122, 30, 44, 0.2) !important;
    }
  </style>
</head>
<body>
<?php
require_once '../includes/config.php';

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
    $initialCapital = (float)($_POST['initial_capital'] ?? 5000);

    // Required ID upload
    $idDoc = $_FILES['id_document'] ?? null;

    if (!$fname || !$lname || !$email || !$phone) {
        $msg = 'Please fill in all required fields.';
    } elseif ($initialCapital < 5000) {
        $msg = 'Initial capital share must be at least ₱5,000.';
    } elseif (!$idDoc || $idDoc['error'] !== UPLOAD_ERR_OK) {
        $msg = 'Please upload a valid ID document.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO pre_applications (first_name, middle_name, last_name, email, phone, street, barangay, city, province, initial_capital) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssssd', $fname, $mname, $lname, $email, $phone, $street, $brgy, $city, $prov, $initialCapital);
        $stmt->execute();
        $appId = $db->insert_id;

        $uploadDir = '../uploads/pre_applications/';
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

<div class="login-page">
  <div style="width:100%;max-width:600px;padding:20px;">
    <?php if ($submitted): ?>
      <div class="pre-app-card pre-app-card--success" style="padding:48px 40px;text-align:center;">
        <div class="pre-app-emoji">🎉</div>
        <h2 class="pre-app-headline">Application Submitted!</h2>
        <p class="pre-app-lead">
          Thank you for applying for membership at our cooperative. Your pre-application has been received and is under review.
          We will contact you at your provided email or phone number within 3-5 business days.
        </p>
        <div class="pre-app-verify">
          ✅ Your application has been submitted successfully.
          <?php if (empty($emailError)): ?>
            A confirmation email has been sent to <?= htmlspecialchars($email) ?>.
          <?php else: ?>
            (Note: Email notification could not be sent at this time.)
          <?php endif; ?>
        </div>
        <a href="../index.php" class="btn btn-primary">Return to Login</a>
      </div>
    <?php else: ?>
      <div class="pre-app-card" style="padding:44px 40px;">
        <div style="margin-bottom:28px;">
          <div class="pre-app-title">
            🌾 Join Our Cooperative
          </div>
          <p class="pre-app-intro">
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
            <label class="form-label">Initial Capital Share (₱) <span style="color:var(--danger);">*</span></label>
            <input type="number" name="initial_capital" class="form-control" min="5000" step="100" value="5000" required>
            <small class="text-muted">Minimum contribution: ₱5,000</small>
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
          <a href="../index.php" style="font-size:0.83rem;color:var(--text-muted);text-decoration:none;">
            ← Back to Login
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
