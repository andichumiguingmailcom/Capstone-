<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apply for Loan – CoopIMS</title>
  <link rel="stylesheet" href="../css/style.css">
  <script src="js/theme-init.js"></script>
</head>
<body>
<?php
require_once '../includes/config.php';
requireLogin('member');
$activePage = 'loan_apply';
$db = getDB();

$memberId = $_SESSION['member_id'] ?? 0;
$msg = ''; $msgType = 'green';

// Fetch pre-application data for pre-filling
$preAppData = null;
$preApp = $db->query("SELECT * FROM pre_applications WHERE first_name='" . $db->real_escape_string($_SESSION['user_name']) . "' OR email='" . $db->real_escape_string($_SESSION['user_name']) . "' LIMIT 1")->fetch_assoc();
if ($preApp) {
    $preAppData = $preApp;
    if ($preApp['details_json']) {
        $details = json_decode($preApp['details_json'], true);
        $preAppData = array_merge($preAppData, $details);
    }
}

// Get member's capital share
$memberCapitalShare = $db->query("SELECT COALESCE(amount, 0) as amount FROM capital_shares WHERE member_id=$memberId")->fetch_assoc()['amount'] ?? 0;

// Function to calculate interest rate based on loan type and capital share
function getInterestRate($loanTypeName, $capitalShare) {
    if ($loanTypeName === 'Regular Loan') {
        return 3.0;
    } elseif ($loanTypeName === 'Special Loan') {
        return $capitalShare >= 75001 ? 1.5 : 2.0;
    } elseif ($loanTypeName === 'Spring Board Loan') {
        return $capitalShare >= 75001 ? 1.5 : 2.5;
    }
    return 1.5;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loanTypeId  = (int)$_POST['loan_type_id'];
    $amount      = (float)$_POST['amount'];
    $termMonths  = (int)$_POST['term_months'];
    $purpose     = clean($_POST['purpose'] ?? '');

    // Collect personal details
    $personalDetails = [
        'first_name' => clean($_POST['first_name'] ?? ''),
        'middle_name' => clean($_POST['middle_name'] ?? ''),
        'last_name' => clean($_POST['last_name'] ?? ''),
        'email' => clean($_POST['email'] ?? ''),
        'phone' => clean($_POST['phone'] ?? ''),
        'dob' => clean($_POST['dob'] ?? ''),
        'street' => clean($_POST['street'] ?? ''),
        'barangay' => clean($_POST['barangay'] ?? ''),
        'city' => clean($_POST['city'] ?? ''),
        'province' => clean($_POST['province'] ?? '')
    ];
    $detailsJson = json_encode($personalDetails);

    // Check for existing pending app
    $existing = $db->query("SELECT id FROM loan_applications WHERE member_id=$memberId AND status='pending'")->num_rows;
    if ($existing) {
        $msg = 'You already have a pending loan application.'; $msgType = 'red';
    } else {
        $stmt = $db->prepare("INSERT INTO loan_applications (member_id,loan_type_id,amount,term_months,purpose,details_json) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('iidisss', $memberId,$loanTypeId,$amount,$termMonths,$purpose,$detailsJson);
        $stmt->execute();
        $msg = 'Your loan application has been submitted! We will notify you once reviewed.';
    }
}

$loanTypes = $db->query("SELECT * FROM loan_types ORDER BY type_name");
$myApps = $db->query("SELECT la.*, lt.type_name FROM loan_applications la 
    JOIN loan_types lt ON la.loan_type_id=lt.id 
    WHERE la.member_id=$memberId ORDER BY la.applied_at DESC");
?>

<?php include '../includes/member_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Apply for a Loan</div>
  </div>

  <div class="page-body">
    <?php if ($msg): ?>
      <div style="background:<?= $msgType==='red'?'#fde8ea':'#d4f0dc' ?>;color:<?= $msgType==='red'?'#c0392b':'#1a6b3a' ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:3px solid currentColor;">
        <?= $msgType==='red'?'⚠️':'✅' ?> <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="grid-2">
      <!-- LOAN TYPES INFO -->
      <div>
        <h3 style="font-family:'Syne',sans-serif;margin-bottom:16px;color:var(--primary-dark);">Available Loan Types</h3>
        <div style="background: var(--bg-light); padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 3px solid var(--primary);">
          <div class="text-muted text-sm">💡 Your Capital Share: <strong style="color: var(--primary);">₱<?= number_format($memberCapitalShare, 2) ?></strong></div>
        </div>
        <?php $loanTypes->data_seek(0); while ($lt = $loanTypes->fetch_assoc()): 
          $interestRate = getInterestRate($lt['type_name'], $memberCapitalShare);
          $rateNote = '';
          if ($lt['type_name'] === 'Special Loan' || $lt['type_name'] === 'Spring Board Loan') {
            $rateNote = $memberCapitalShare >= 75001 ? ' (Platinum Member Rate)' : ' (Standard Rate)';
          }
        ?>
          <div class="card" style="margin-bottom:12px;cursor:pointer;" onclick="selectLoan(<?= $lt['id'] ?>, '<?= addslashes($lt['type_name']) ?>', <?= $lt['max_amount'] ?>, <?= $lt['max_months'] ?>, <?= $interestRate ?>)">
            <div class="card-body" style="padding:16px 20px;">
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                  <div class="fw-600"><?= htmlspecialchars($lt['type_name']) ?></div>
                  <div class="text-muted text-sm"><?= $interestRate ?>% interest/month · up to <?= $lt['max_months'] ?> months<?= $rateNote ?></div>
                </div>
                <div style="text-align:right;">
                  <div class="fw-600" style="color:var(--primary);">₱<?= number_format($lt['max_amount'], 0) ?></div>
                  <div class="text-muted text-sm">Max amount</div>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

      <!-- APPLICATION FORM -->
      <div>
        <div class="card">
          <div class="card-header"><span class="card-title">📝 Loan Application Form</span></div>
          <div class="card-body">
            <form method="POST" id="loanForm">
              <!-- PERSONAL INFORMATION -->
              <h4 style="margin-bottom:16px; color:var(--primary);">Personal Information</h4>
              <div class="grid-2">
                <div class="form-group">
                  <label class="form-label">First Name</label>
                  <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($preAppData['first_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Middle Name</label>
                  <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($preAppData['middle_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Last Name</label>
                  <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($preAppData['last_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($preAppData['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Phone</label>
                  <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($preAppData['phone'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Date of Birth</label>
                  <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($preAppData['dob'] ?? '') ?>" required>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Address</label>
                <div class="grid-3">
                  <input type="text" name="street" class="form-control" placeholder="Street" value="<?= htmlspecialchars($preAppData['street'] ?? '') ?>" required>
                  <input type="text" name="barangay" class="form-control" placeholder="Barangay" value="<?= htmlspecialchars($preAppData['barangay'] ?? '') ?>" required>
                  <input type="text" name="city" class="form-control" placeholder="City" value="<?= htmlspecialchars($preAppData['city'] ?? '') ?>" required>
                </div>
                <input type="text" name="province" class="form-control" placeholder="Province" value="<?= htmlspecialchars($preAppData['province'] ?? '') ?>" required style="margin-top:8px;">
              </div>

              <!-- SPOUSE INFORMATION -->
              <h4 style="margin:24px 0 16px 0; color:var(--primary);">Spouse Information (Optional)</h4>
              <div class="grid-2">
                <div class="form-group">
                  <label class="form-label">Spouse Name</label>
                  <input type="text" name="spouse_name" class="form-control" value="<?= htmlspecialchars($preAppData['spouse']['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Spouse Date of Birth</label>
                  <input type="date" name="spouse_dob" class="form-control" value="<?= htmlspecialchars($preAppData['spouse']['dob'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Spouse Job</label>
                  <input type="text" name="spouse_job" class="form-control" value="<?= htmlspecialchars($preAppData['spouse']['job'] ?? '') ?>">
                </div>
              </div>

              <!-- BENEFICIARY INFORMATION -->
              <h4 style="margin:24px 0 16px 0; color:var(--primary);">Beneficiary Information (Optional)</h4>
              <div class="grid-2">
                <div class="form-group">
                  <label class="form-label">Beneficiary Name</label>
                  <input type="text" name="beneficiary_name" class="form-control" value="<?= htmlspecialchars($preAppData['beneficiary']['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Beneficiary Date of Birth</label>
                  <input type="date" name="beneficiary_dob" class="form-control" value="<?= htmlspecialchars($preAppData['beneficiary']['dob'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Beneficiary Sex</label>
                  <select name="beneficiary_sex" class="form-control">
                    <option value="">— Select —</option>
                    <option value="Male" <?= ($preAppData['beneficiary']['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($preAppData['beneficiary']['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Relationship</label>
                  <input type="text" name="beneficiary_relationship" class="form-control" value="<?= htmlspecialchars($preAppData['beneficiary']['relationship'] ?? '') ?>">
                </div>
              </div>

              <!-- BUSINESS INFORMATION -->
              <h4 style="margin:24px 0 16px 0; color:var(--primary);">Business Information (Optional)</h4>
              <div class="grid-2">
                <div class="form-group">
                  <label class="form-label">Business Name</label>
                  <input type="text" name="business_name" class="form-control" value="<?= htmlspecialchars($preAppData['business']['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Facebook Page</label>
                  <input type="text" name="business_facebook" class="form-control" value="<?= htmlspecialchars($preAppData['business']['facebook'] ?? '') ?>">
                </div>
              </div>

              <!-- INCOME INFORMATION -->
              <h4 style="margin:24px 0 16px 0; color:var(--primary);">Income Information</h4>
              <div class="grid-2">
                <div class="form-group">
                  <label class="form-label">Gross Monthly Income</label>
                  <input type="number" name="gross_income" class="form-control" step="0.01" value="<?= htmlspecialchars($preAppData['income']['gross'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Monthly Expenses</label>
                  <input type="number" name="monthly_expenses" class="form-control" step="0.01" value="<?= htmlspecialchars($preAppData['income']['expenses'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Net Monthly Income</label>
                  <input type="number" name="net_income" class="form-control" step="0.01" value="<?= htmlspecialchars($preAppData['income']['net'] ?? '') ?>">
                </div>
              </div>

              <!-- DEPENDENTS -->
              <h4 style="margin:24px 0 16px 0; color:var(--primary);">Dependents (Optional)</h4>
              <div id="dependents-container">
                <?php if (!empty($preAppData['dependents'])): ?>
                  <?php foreach ($preAppData['dependents'] as $index => $dep): ?>
                  <div class="dependent-row grid-3" style="margin-bottom:10px;">
                    <div class="form-group">
                      <label class="form-label">Name</label>
                      <input type="text" name="dep_name[]" class="form-control" value="<?= htmlspecialchars($dep['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                      <label class="form-label">Date of Birth</label>
                      <input type="date" name="dep_dob[]" class="form-control" value="<?= htmlspecialchars($dep['dob'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                      <label class="form-label">Relationship</label>
                      <input type="text" name="dep_rel[]" class="form-control" value="<?= htmlspecialchars($dep['rel'] ?? '') ?>">
                    </div>
                  </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <button type="button" class="btn btn-outline" onclick="addDependent()">+ Add Dependent</button>

              <!-- ADDITIONAL INFO -->
              <h4 style="margin:24px 0 16px 0; color:var(--primary);">Additional Information</h4>
              <div class="grid-2">
                <div class="form-group">
                  <label class="form-label">Age</label>
                  <input type="number" name="age" class="form-control" value="<?= htmlspecialchars($preAppData['age'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Sex</label>
                  <select name="sex" class="form-control">
                    <option value="">— Select —</option>
                    <option value="Male" <?= ($preAppData['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($preAppData['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Civil Status</label>
                  <select name="civil_status" class="form-control">
                    <option value="">— Select —</option>
                    <option value="Single" <?= ($preAppData['civil_status'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                    <option value="Married" <?= ($preAppData['civil_status'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                    <option value="Widowed" <?= ($preAppData['civil_status'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                    <option value="Divorced" <?= ($preAppData['civil_status'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Occupation</label>
                  <input type="text" name="occupation" class="form-control" value="<?= htmlspecialchars($preAppData['occupation'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Residence Certificate</label>
                  <input type="text" name="res_cert" class="form-control" value="<?= htmlspecialchars($preAppData['res_cert'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Residence Types</label>
                  <input type="text" name="residence_types" class="form-control" value="<?= htmlspecialchars(is_array($preAppData['residence_types']) ? implode(', ', $preAppData['residence_types']) : '') ?>">
                </div>
              </div>

              <hr style="margin:24px 0; border:0; border-top:1px solid var(--border);">

              <!-- LOAN DETAILS -->
              <h4 style="margin-bottom:16px; color:var(--primary);">Loan Details</h4>
                <select name="loan_type_id" class="form-control" required id="loanTypeSelect">
                  <option value="">— Select Loan Type —</option>
                  <?php
                  $db->query("SELECT 1"); // reset
                  $lt2 = $db->query("SELECT * FROM loan_types ORDER BY type_name");
                  while ($lt = $lt2->fetch_assoc()):
                    $interestRate = getInterestRate($lt['type_name'], $memberCapitalShare);
                  ?>
                    <option value="<?= $lt['id'] ?>" data-max="<?= $lt['max_amount'] ?>" data-months="<?= $lt['max_months'] ?>" data-interest="<?= $interestRate ?>">
                      <?= htmlspecialchars($lt['type_name']) ?> - <?= $interestRate ?>%
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Loan Amount (₱)</label>
                <input type="number" name="amount" id="amountInput" class="form-control" min="100" step="0.01" required placeholder="0.00" oninput="calcMonthly()">
                <div class="text-muted text-sm mt-1" id="amountHint"></div>
              </div>
              <div class="form-group">
                <label class="form-label">Term (months)</label>
                <input type="number" name="term_months" id="termInput" class="form-control" min="1" max="60" required placeholder="6" oninput="calcMonthly()">
              </div>

              <!-- COMPUTED PREVIEW -->
              <div id="loanPreview" style="background:var(--bg);border-radius:10px;padding:16px;margin-bottom:16px;display:none;">
                <div class="text-muted text-sm fw-600" style="margin-bottom:8px;">Estimated Monthly Payment</div>
                <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--primary);" id="monthlyEst">₱0.00</div>
                <div class="text-muted text-sm" id="totalEst"></div>
              </div>

              <div class="form-group">
                <label class="form-label">Purpose of Loan</label>
                <textarea name="purpose" class="form-control" rows="3" placeholder="Briefly describe the purpose..." required></textarea>
              </div>
              <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Submit Application →</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- APPLICATION HISTORY -->
    <div class="card">
      <div class="card-header"><span class="card-title">My Application History</span></div>
      <div class="card-body">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Type</th><th>Amount</th><th>Term</th><th>Status</th><th>Date Applied</th><th>Remarks</th></tr></thead>
            <tbody>
              <?php while ($app = $myApps->fetch_assoc()):
                $statusLabels = [
                    'pending' => 'For Evaluation of Loan Officer',
                    'for_gm_evaluation' => 'For Evaluation of General Manager',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'disbursed' => 'Disbursed'
                ];
                $badge = ['pending'=>'badge-gold','for_gm_evaluation'=>'badge-orange','approved'=>'badge-green','rejected'=>'badge-red','disbursed'=>'badge-blue'];
                $b = $badge[$app['status']] ?? 'badge-gray';
                $label = $statusLabels[$app['status']] ?? ucfirst($app['status']);
              ?>
              <tr>
                <td class="fw-600"><?= htmlspecialchars($app['type_name']) ?></td>
                <td>₱<?= number_format($app['amount'], 2) ?></td>
                <td><?= $app['term_months'] ?> mos.</td>
                <td><span class="badge <?= $b ?>"><?= $label ?></span></td>
                <td><?= date('M j, Y', strtotime($app['applied_at'])) ?></td>
                <td class="text-muted text-sm"><?= htmlspecialchars($app['remarks'] ?? '—') ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../js/app.js"></script>
<script>
function selectLoan(id, name, maxAmt, maxMonths, interest) {
  document.getElementById('loanTypeSelect').value = id;
  const opt = document.querySelector(`#loanTypeSelect option[value="${id}"]`);
  updateLimits(opt);
  calcMonthly();
}

function updateLimits(opt) {
  if (opt.dataset.max) {
    document.getElementById('amountInput').max = opt.dataset.max;
    document.getElementById('amountHint').textContent = 'Max: ₱' + parseFloat(opt.dataset.max).toLocaleString();
    document.getElementById('termInput').max = opt.dataset.months;
  }
}

document.getElementById('loanTypeSelect').addEventListener('change', function() {
  updateLimits(this.options[this.selectedIndex]);
  calcMonthly();
});

function calcMonthly() {
  const amt = parseFloat(document.getElementById('amountInput').value);
  const term = parseInt(document.getElementById('termInput').value);
  const opt = document.getElementById('loanTypeSelect').options[document.getElementById('loanTypeSelect').selectedIndex];
  const rate = opt ? parseFloat(opt.dataset.interest) / 100 : 0;
  const preview = document.getElementById('loanPreview');
  if (amt > 0 && term > 0 && rate > 0) {
    const totalInterestFactor = rate * term;
    const monthly = (amt * (1 + totalInterestFactor)) / term;
    const total = monthly * term;
    document.getElementById('monthlyEst').textContent = '₱' + monthly.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('totalEst').textContent = 'Total repayment: ₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + ` · Interest: ${(rate*100).toFixed(1)}%/mo`;
    preview.style.display = 'block';
  } else {
    preview.style.display = 'none';
  }
}
</script>
</body>
</html>
