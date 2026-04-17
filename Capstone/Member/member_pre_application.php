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
    h3 {
      border-bottom: 2px solid rgba(122, 30, 44, 0.1);
      padding-bottom: 10px;
      margin-top: 32px;
      margin-bottom: 20px;
      font-size: 1.2rem;
      text-transform: uppercase;
      color: #7a1e2c !important;
    }
    .checkbox-group {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      background: #fff;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #d1d5db;
    }
    .checkbox-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.9rem;
      color: #333;
    }
    .loan-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .loan-table th, .loan-table td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
    .loan-table th { background: #eee; color: #7a1e2c; font-size: 0.85rem; }
    .section-title {
      background: #7a1e2c;
      color: white;
      padding: 10px 15px;
      border-radius: 8px;
      margin-top: 30px;
      margin-bottom: 20px;
      font-size: 1.1rem;
    }
  </style>
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
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $address = clean($_POST['address'] ?? '');
    $initialCapital = (float)($_POST['initial_capital'] ?? 5000);

    // Splitting name for existing database schema compatibility
    $nameParts = explode(' ', $name);
    $fname = $nameParts[0] ?? '';
    $lname = count($nameParts) > 1 ? end($nameParts) : $name;
    $mname = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 1, -1)) : '';

    // Simple address mapping to the street column
    $street = $address;
    $brgy = '';
    $city = '';
    $prov = '';

    // Capture all the new detailed fields into a structured array
    $extraDetails = [
        'dob' => clean($_POST['dob'] ?? ''),
        'age' => clean($_POST['age'] ?? ''),
        'sex' => clean($_POST['sex'] ?? ''),
        'civil_status' => clean($_POST['civil_status'] ?? ''),
        'res_cert' => clean($_POST['res_cert'] ?? ''),
        'occupation' => clean($_POST['occupation'] ?? ''),
        'residence_types' => $_POST['residence'] ?? [],
        'spouse' => [
            'name' => clean($_POST['spouse'] ?? ''),
            'dob' => clean($_POST['spouse_dob'] ?? ''),
            'job' => clean($_POST['spouse_job'] ?? '')
        ],
        'business' => [
            'name' => clean($_POST['business'] ?? ''),
            'facebook' => clean($_POST['facebook'] ?? '')
        ],
        'beneficiary' => [
            'name' => clean($_POST['beneficiary'] ?? ''),
            'dob' => clean($_POST['ben_dob'] ?? ''),
            'sex' => clean($_POST['ben_sex'] ?? ''),
            'relationship' => clean($_POST['relationship'] ?? '')
        ],
        'income' => [
            'gross' => clean($_POST['gross'] ?? ''),
            'expenses' => clean($_POST['expenses'] ?? ''),
            'net' => clean($_POST['net'] ?? '')
        ],
        'dependents' => [],
        'obligations' => [],
        'declaration' => clean($_POST['declaration'] ?? ''),
        'signature' => clean($_POST['signature'] ?? '')
    ];

    // Process Dependents Table
    if (!empty($_POST['dep_name'])) {
        foreach ($_POST['dep_name'] as $i => $dn) {
            if (empty($dn)) continue;
            $extraDetails['dependents'][] = [
                'name' => clean($dn),
                'dob'  => clean($_POST['dep_dob'][$i] ?? ''),
                'age'  => clean($_POST['dep_age'][$i] ?? ''),
                'rel'  => clean($_POST['dep_rel'][$i] ?? '')
            ];
        }
    }

    // Process Outstanding Loans
    if (!empty($_POST['creditor'])) {
        foreach ($_POST['creditor'] as $i => $cred) {
            if (empty($cred)) continue;
            $extraDetails['obligations'][] = [
                'creditor' => clean($cred),
                'address' => clean($_POST['cred_addr'][$i] ?? ''),
                'amount' => clean($_POST['cred_amount'][$i] ?? ''),
                'due_date' => clean($_POST['cred_due'][$i] ?? '')
            ];
        }
    }
    $detailsJson = json_encode($extraDetails);

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
        $stmt = $db->prepare("INSERT INTO pre_applications (first_name, middle_name, last_name, email, phone, street, barangay, city, province, initial_capital, details_json) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssssds', $fname, $mname, $lname, $email, $phone, $street, $brgy, $city, $prov, $initialCapital, $detailsJson);
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
                $mail->Subject = 'Application Received - CoopIMS';

                $mail->Body = "Hello " . htmlspecialchars($name) . ",<br><br>Thank you for submitting your application at CoopIMS.<br><br>Your application has been received and is currently under review. We will contact you within 3-5 business days regarding the status of your application.<br><br>Best regards,<br>CoopIMS Team";

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
          <!-- APPLICANT -->
          <div class="section-title">Applicant Information</div>
          <div class="form-group"><label class="form-label">Full Name <span style="color:var(--danger);">*</span></label><input type="text" name="name" class="form-control" required></div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" name="dob" class="form-control"></div>
            <div class="form-group"><label class="form-label">Age</label><input type="number" name="age" class="form-control"></div>
            <div class="form-group"><label class="form-label">Sex</label>
              <select name="sex" class="form-control">
                <option>Male</option><option>Female</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Civil Status</label>
              <select name="civil_status" class="form-control">
                <option>Single</option><option>Married</option><option>Widow</option>
              </select>
            </div>
            <div class="form-group"><label class="form-label">Residence Cert No</label><input type="text" name="res_cert" class="form-control"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Phone <span style="color:var(--danger);">*</span></label><input type="text" name="phone" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Email <span style="color:var(--danger);">*</span></label><input type="email" name="email" class="form-control" required></div>
          </div>
          <div class="form-group"><label class="form-label">Occupation</label><input type="text" name="occupation" class="form-control"></div>
          <div class="form-group"><label class="form-label">Address</label><input type="text" name="address" class="form-control"></div>
          
          <div class="form-group">
            <label class="form-label">Residence Type</label>
            <div class="checkbox-group">
              <label class="checkbox-item"><input type="checkbox" name="residence[]" value="owned"> Owned</label>
              <label class="checkbox-item"><input type="checkbox" name="residence[]" value="mortgage"> Mortgage</label>
              <label class="checkbox-item"><input type="checkbox" name="residence[]" value="rented"> Rented</label>
              <label class="checkbox-item"><input type="checkbox" name="residence[]" value="free"> Free</label>
              <label class="checkbox-item"><input type="checkbox" name="residence[]" value="parents"> With Parents</label>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Initial Capital Share (₱) <span style="color:var(--danger);">*</span></label>
            <input type="number" name="initial_capital" class="form-control" min="5000" step="100" value="5000" required>
            <small class="text-muted">Minimum contribution: ₱5,000</small>
          </div>

          <!-- SPOUSE -->
          <div class="section-title">Spouse Information</div>
          <div class="form-group"><label class="form-label">Name</label><input type="text" name="spouse" class="form-control"></div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">DOB</label><input type="date" name="spouse_dob" class="form-control"></div>
            <div class="form-group"><label class="form-label">Occupation</label><input type="text" name="spouse_job" class="form-control"></div>
          </div>

          <!-- BUSINESS -->
          <div class="section-title">Business</div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Business Name</label><input type="text" name="business" class="form-control"></div>
            <div class="form-group"><label class="form-label">Facebook</label><input type="text" name="facebook" class="form-control"></div>
          </div>

          <!-- BENEFICIARY -->
          <div class="section-title">Beneficiary</div>
          <div class="form-group"><label class="form-label">Name</label><input type="text" name="beneficiary" class="form-control"></div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">DOB</label><input type="date" name="ben_dob" class="form-control"></div>
            <div class="form-group"><label class="form-label">Sex</label><input type="text" name="ben_sex" class="form-control"></div>
            <div class="form-group"><label class="form-label">Relationship</label><input type="text" name="relationship" class="form-control"></div>
          </div>

          <!-- DEPENDENTS -->
          <div class="section-title">Dependents</div>
          <div class="table-wrap">
            <table class="loan-table">
              <thead><tr><th>Name</th><th>DOB</th><th>Age</th><th>Relationship</th></tr></thead>
              <tbody>
                <tr>
                  <td><input type="text" name="dep_name[]" class="form-control"></td>
                  <td><input type="date" name="dep_dob[]" class="form-control"></td>
                  <td><input type="number" name="dep_age[]" class="form-control"></td>
                  <td><input type="text" name="dep_rel[]" class="form-control"></td>
                </tr>
                <tr>
                  <td><input type="text" name="dep_name[]" class="form-control"></td>
                  <td><input type="date" name="dep_dob[]" class="form-control"></td>
                  <td><input type="number" name="dep_age[]" class="form-control"></td>
                  <td><input type="text" name="dep_rel[]" class="form-control"></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- INCOME -->
          <div class="section-title">Income & Expenses</div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Gross Income</label><input type="number" name="gross" class="form-control"></div>
            <div class="form-group"><label class="form-label">Expenses</label><input type="number" name="expenses" class="form-control"></div>
            <div class="form-group"><label class="form-label">Net Income</label><input type="number" name="net" class="form-control"></div>
          </div>

          <!-- OBLIGATIONS -->
          <div class="section-title">Outstanding Loans</div>
          <div class="table-wrap">
            <table class="loan-table">
              <thead><tr><th>Creditor</th><th>Address</th><th>Amount</th><th>Due Date</th></tr></thead>
              <tbody>
                <tr>
                  <td><input type="text" name="creditor[]" class="form-control"></td>
                  <td><input type="text" name="cred_addr[]" class="form-control"></td>
                  <td><input type="number" name="cred_amount[]" class="form-control"></td>
                  <td><input type="date" name="cred_due[]" class="form-control"></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- DECLARATION -->
          <div class="section-title">Declaration</div>
          <div class="form-group">
            <textarea name="declaration" class="form-control" rows="2">I certify that all information is true.</textarea>
          </div>
          <div class="form-group"><label class="form-label">Signature</label><input type="text" name="signature" class="form-control"></div>

          <div class="section-title">Upload Documents</div>
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
