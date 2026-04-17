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
    $fname = clean($_POST['first_name'] ?? '');
    $mname = clean($_POST['middle_name'] ?? '');
    $lname = clean($_POST['last_name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $street = clean($_POST['street'] ?? '');
    $brgy = clean($_POST['barangay'] ?? '');
    $city = clean($_POST['city'] ?? '');
    $prov = clean($_POST['province'] ?? '');
    $initialCapital = (float)($_POST['initial_capital'] ?? 5000);

    // Capture all the new detailed fields
    $dob = clean($_POST['dob'] ?? '');
    $sex = clean($_POST['sex'] ?? '');
    $civilStatus = clean($_POST['civil_status'] ?? '');
    $occupation = clean($_POST['occupation'] ?? '');
    $resCert = clean($_POST['res_cert'] ?? '');
    $residenceTypes = json_encode(isset($_POST['residence']) ? [$_POST['residence']] : []);
    
    $spouseName = clean($_POST['spouse'] ?? '');
    $spouseDob = clean($_POST['spouse_dob'] ?? '');
    $spouseJob = clean($_POST['spouse_job'] ?? '');
    
    $businessName = clean($_POST['business'] ?? '');
    $businessFacebook = clean($_POST['facebook'] ?? '');
    
    $beneficiaryName = clean($_POST['beneficiary'] ?? '');
    $beneficiaryDob = clean($_POST['ben_dob'] ?? '');
    $beneficiarySex = clean($_POST['ben_sex'] ?? '');
    $beneficiaryRelationship = clean($_POST['relationship'] ?? '');
    
    $grossIncome = !empty($_POST['gross']) ? (float)$_POST['gross'] : null;
    $expenses = !empty($_POST['expenses']) ? (float)$_POST['expenses'] : null;
    $netIncome = !empty($_POST['net']) ? (float)$_POST['net'] : null;
    
    $outstandingCreditor = clean($_POST['out_creditor'] ?? '');
    $outstandingAddress = clean($_POST['out_address'] ?? '');
    $outstandingAmount = !empty($_POST['out_amount']) ? (float)$_POST['out_amount'] : null;
    $outstandingDueDate = clean($_POST['out_due_date'] ?? '');
    
    $declaration = clean($_POST['declaration'] ?? '');

    // Process Dependents into separate arrays
    $dependentNames = [];
    $dependentDobs = [];
    $dependentAges = [];
    $dependentRelationships = [];
    if (!empty($_POST['dep_name'])) {
        foreach ($_POST['dep_name'] as $i => $dn) {
            if (empty($dn)) continue;
            $dependentNames[] = clean($dn);
            $dependentDobs[] = clean($_POST['dep_dob'][$i] ?? '');
            $dependentAges[] = clean($_POST['dep_age'][$i] ?? '');
            $dependentRelationships[] = clean($_POST['dep_rel'][$i] ?? '');
        }
    }
    $dependentNamesJson = json_encode($dependentNames);
    $dependentDobsJson = json_encode($dependentDobs);
    $dependentAgesJson = json_encode($dependentAges);
    $dependentRelationshipsJson = json_encode($dependentRelationships);

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
        $stmt = $db->prepare("INSERT INTO pre_applications (first_name, middle_name, last_name, email, phone, street, barangay, city, province, initial_capital, dob, sex, civil_status, occupation, res_cert, residence_types, spouse_name, spouse_dob, spouse_job, business_name, business_facebook, beneficiary_name, beneficiary_dob, beneficiary_sex, beneficiary_relationship, gross_income, expenses, net_income, outstanding_creditor, outstanding_address, outstanding_amount, outstanding_due_date, declaration, dependents_name, dependents_dob, dependents_age, dependents_relationship) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssssdssssssssssssssdddsdssssss', $fname, $mname, $lname, $email, $phone, $street, $brgy, $city, $prov, $initialCapital, $dob, $sex, $civilStatus, $occupation, $resCert, $residenceTypes, $spouseName, $spouseDob, $spouseJob, $businessName, $businessFacebook, $beneficiaryName, $beneficiaryDob, $beneficiarySex, $beneficiaryRelationship, $grossIncome, $expenses, $netIncome, $outstandingCreditor, $outstandingAddress, $outstandingAmount, $outstandingDueDate, $declaration, $dependentNamesJson, $dependentDobsJson, $dependentAgesJson, $dependentRelationshipsJson);
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
          <div class="form-row three">
            <div class="form-group"><label class="form-label">First Name <span style="color:var(--danger);">*</span></label><input type="text" name="first_name" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Middle Name</label><input type="text" name="middle_name" class="form-control"></div>
            <div class="form-group"><label class="form-label">Last Name <span style="color:var(--danger);">*</span></label><input type="text" name="last_name" class="form-control" required></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" name="dob" class="form-control"></div>
            <div class="form-group"><label class="form-label">Sex</label>
              <select name="sex" class="form-control">
                <option value="">Select...</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="form-group"><label class="form-label">Civil Status</label>
              <select name="civil_status" class="form-control">
                <option value="">Select...</option>
                <option value="Single">Single</option>
                <option value="Married">Married</option>
                <option value="Separated">Separated</option>
                <option value="Divorced">Divorced</option>
                <option value="Widowed">Widowed</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Email <span style="color:var(--danger);">*</span></label><input type="email" name="email" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Phone <span style="color:var(--danger);">*</span></label><input type="text" name="phone" class="form-control" required></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Occupation</label><input type="text" name="occupation" class="form-control"></div>
            <div class="form-group"><label class="form-label">Residence Cert No</label><input type="text" name="res_cert" class="form-control"></div>
          </div>
          
          <!-- ADDRESS -->
          <div class="section-title">Address Information</div>
          <div class="form-row">
            <div class="form-group" style="flex: 1.5;"><label class="form-label">Street</label><input type="text" name="street" class="form-control"></div>
            <div class="form-group"><label class="form-label">Barangay</label><input type="text" name="barangay" class="form-control"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">City/Municipality</label><input type="text" name="city" class="form-control"></div>
            <div class="form-group"><label class="form-label">Province</label><input type="text" name="province" class="form-control"></div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Residence Type</label>
            <div class="checkbox-group">
              <label class="checkbox-item"><input type="radio" name="residence" value="owned"> Owned</label>
              <label class="checkbox-item"><input type="radio" name="residence" value="mortgage"> Mortgage</label>
              <label class="checkbox-item"><input type="radio" name="residence" value="rented"> Rented</label>
              <label class="checkbox-item"><input type="radio" name="residence" value="free"> Free</label>
              <label class="checkbox-item"><input type="radio" name="residence" value="parents"> With Parents</label>
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
          <div id="dependents-container">
            <div class="dependent-card" style="background:#f9f9f9; border:1px solid #e0e0e0; border-radius:8px; padding:16px; margin-bottom:16px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <strong style="color:#7a1e2c;">Dependent #1</strong>
                <button type="button" class="btn-remove-dependent" onclick="removeDependent(this)" style="display:none; padding:4px 8px; font-size:0.85rem; background:#e63946; color:white; border:none; border-radius:4px; cursor:pointer;">Remove</button>
              </div>
              <div class="form-row">
                <div class="form-group"><label class="form-label">Name</label><input type="text" name="dep_name[]" class="form-control"></div>
                <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" name="dep_dob[]" class="form-control"></div>
              </div>
              <div class="form-row">
                <div class="form-group"><label class="form-label">Age</label><input type="number" name="dep_age[]" class="form-control"></div>
                <div class="form-group"><label class="form-label">Relationship</label><input type="text" name="dep_rel[]" class="form-control"></div>
              </div>
            </div>
          </div>
          <button type="button" class="btn btn-outline" onclick="addDependent()" style="margin-top:8px;">+ Add Another Dependent</button>

          <!-- INCOME -->
          <div class="section-title">Income & Expenses</div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Gross Income</label><input type="number" name="gross" class="form-control"></div>
            <div class="form-group"><label class="form-label">Expenses</label><input type="number" name="expenses" class="form-control"></div>
            <div class="form-group"><label class="form-label">Net Income</label><input type="number" name="net" class="form-control"></div>
          </div>

          <!-- OUTSTANDING LOANS -->
          <div class="section-title">Outstanding Loans (if any)</div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Creditor Name</label><input type="text" name="out_creditor" class="form-control"></div>
            <div class="form-group"><label class="form-label">Address</label><input type="text" name="out_address" class="form-control"></div>
            <div class="form-group"><label class="form-label">Amount (₱)</label><input type="number" name="out_amount" class="form-control" step="0.01"></div>
            <div class="form-group"><label class="form-label">Due Date</label><input type="date" name="out_due_date" class="form-control"></div>
          </div>

          <!-- DECLARATION -->
          <div class="section-title">Declaration</div>
          <div class="form-group">
            <textarea name="declaration" class="form-control" rows="2">I certify that all information is true.</textarea>
          </div>

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

<script>
function addDependent() {
  const container = document.getElementById('dependents-container');
  const dependentCount = container.querySelectorAll('.dependent-card').length + 1;
  
  const card = document.createElement('div');
  card.className = 'dependent-card';
  card.style.cssText = 'background:#f9f9f9; border:1px solid #e0e0e0; border-radius:8px; padding:16px; margin-bottom:16px;';
  card.innerHTML = `
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
      <strong style="color:#7a1e2c;">Dependent #${dependentCount}</strong>
      <button type="button" class="btn-remove-dependent" onclick="removeDependent(this)" style="padding:4px 8px; font-size:0.85rem; background:#e63946; color:white; border:none; border-radius:4px; cursor:pointer;">Remove</button>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Name</label><input type="text" name="dep_name[]" class="form-control"></div>
      <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" name="dep_dob[]" class="form-control"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Age</label><input type="number" name="dep_age[]" class="form-control"></div>
      <div class="form-group"><label class="form-label">Relationship</label><input type="text" name="dep_rel[]" class="form-control"></div>
    </div>
  `;
  container.appendChild(card);
  updateRemoveButtons();
}

function removeDependent(button) {
  const card = button.closest('.dependent-card');
  card.remove();
  updateRemoveButtons();
  updateDependentNumbers();
}

function updateRemoveButtons() {
  const cards = document.querySelectorAll('.dependent-card');
  cards.forEach(card => {
    const btn = card.querySelector('.btn-remove-dependent');
    btn.style.display = cards.length > 1 ? 'block' : 'none';
  });
}

function updateDependentNumbers() {
  const cards = document.querySelectorAll('.dependent-card');
  cards.forEach((card, index) => {
    const title = card.querySelector('strong');
    title.textContent = `Dependent #${index + 1}`;
  });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  updateRemoveButtons();
});
</script>

</body>
</html>
