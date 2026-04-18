<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Loan Application – CoopIMS</title>
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
$preAppData = [];
$preApp = $db->query("SELECT * FROM pre_applications WHERE member_id=$memberId AND status='approved' LIMIT 1")->fetch_assoc();
if ($preApp) {
    $preAppData = $preApp;
    // Decode JSON fields
    if (!empty($preApp['residence_types'])) {
        $preAppData['residence_types'] = json_decode($preApp['residence_types'], true) ?: [];
    } else {
        $preAppData['residence_types'] = [];
    }
    // Reconstruct dependents from separate columns
    $depNames = json_decode($preApp['dependents_name'] ?? '[]', true) ?: [];
    $depDobs = json_decode($preApp['dependents_dob'] ?? '[]', true) ?: [];
    $depAges = json_decode($preApp['dependents_age'] ?? '[]', true) ?: [];
    $depRels = json_decode($preApp['dependents_relationship'] ?? '[]', true) ?: [];
    $preAppData['dependents'] = [];
    for ($i = 0; $i < count($depNames); $i++) {
        $preAppData['dependents'][] = [
            'name' => $depNames[$i] ?? '',
            'dob' => $depDobs[$i] ?? '',
            'age' => $depAges[$i] ?? '',
            'rel' => $depRels[$i] ?? ''
        ];
    }
    // Create nested structures for spouse, business, beneficiary, income
    $preAppData['spouse'] = [
        'name' => $preApp['spouse_name'] ?? '',
        'dob' => $preApp['spouse_dob'] ?? '',
        'job' => $preApp['spouse_job'] ?? ''
    ];
    $preAppData['business'] = [
        'name' => $preApp['business_name'] ?? '',
        'facebook' => $preApp['business_facebook'] ?? ''
    ];
    $preAppData['beneficiary'] = [
        'name' => $preApp['beneficiary_name'] ?? '',
        'dob' => $preApp['beneficiary_dob'] ?? '',
        'sex' => $preApp['beneficiary_sex'] ?? '',
        'relationship' => $preApp['beneficiary_relationship'] ?? ''
    ];
    $preAppData['income'] = [
        'gross' => $preApp['gross_income'] ?? '',
        'expenses' => $preApp['expenses'] ?? '',
        'net' => $preApp['net_income'] ?? ''
    ];
    $preAppData['outstanding'] = [
        'creditor' => $preApp['outstanding_creditor'] ?? '',
        'address' => $preApp['outstanding_address'] ?? '',
        'amount' => $preApp['outstanding_amount'] ?? '',
        'due_date' => $preApp['outstanding_due_date'] ?? ''
    ];
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
    // Collect all personal details
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
        'province' => clean($_POST['province'] ?? ''),
        'sex' => clean($_POST['sex'] ?? ''),
        'civil_status' => clean($_POST['civil_status'] ?? ''),
        'occupation' => clean($_POST['occupation'] ?? ''),
        'res_cert' => clean($_POST['res_cert'] ?? ''),
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
        'outstanding' => [
            'creditor' => clean($_POST['out_creditor'] ?? ''),
            'address' => clean($_POST['out_address'] ?? ''),
            'amount' => clean($_POST['out_amount'] ?? ''),
            'due_date' => clean($_POST['out_due_date'] ?? '')
        ],
        'dependents' => []
    ];

    // Process Dependents
    if (!empty($_POST['dep_name'])) {
        foreach ($_POST['dep_name'] as $i => $dn) {
            if (empty($dn)) continue;
            $personalDetails['dependents'][] = [
                'name' => clean($dn),
                'dob'  => clean($_POST['dep_dob'][$i] ?? ''),
                'age'  => clean($_POST['dep_age'][$i] ?? ''),
                'rel'  => clean($_POST['dep_rel'][$i] ?? '')
            ];
        }
    }

    $detailsJson = json_encode($personalDetails);

    // Loan details
    $loanTypeId  = (int)$_POST['loan_type_id'];
    $amount      = (float)$_POST['amount'];
    $termMonths  = (int)$_POST['term_months'];
    $purpose     = clean($_POST['purpose'] ?? '');

    // Check for existing pending app
    $existing = $db->query("SELECT id FROM loan_applications WHERE member_id=$memberId AND status='pending'")->num_rows;
    if ($existing) {
        $msg = 'You already have a pending loan application.'; $msgType = 'red';
    } else {
        $stmt = $db->prepare("INSERT INTO loan_applications (member_id,loan_type_id,amount,term_months,purpose,details_json) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('iidiss', $memberId,$loanTypeId,$amount,$termMonths,$purpose,$detailsJson);
        $stmt->execute();
        $msg = 'Your loan application has been submitted! We will notify you once reviewed.';
    }
}

$loanTypes = $db->query("SELECT * FROM loan_types ORDER BY type_name");
?>

<?php include '../includes/member_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Loan Application</div>
  </div>

  <div class="page-body">
    <?php if ($msg): ?>
      <div style="background:<?= $msgType==='red'?'#fde8ea':'#d4f0dc' ?>;color:<?= $msgType==='red'?'#c0392b':'#1a6b3a' ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:3px solid currentColor;">
        <?= $msgType==='red'?'⚠️':'✅' ?> <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><span class="card-title">📝 Complete Loan Application Form</span></div>
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
            <div class="form-group">
              <label class="form-label">Sex</label>
              <select name="sex" class="form-control" required>
                <option value="">— Select —</option>
                <option value="Male" <?= ($preAppData['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= ($preAppData['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Civil Status</label>
              <select name="civil_status" class="form-control" required>
                <option value="">— Select —</option>
                <option value="Single" <?= ($preAppData['civil_status'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                <option value="Married" <?= ($preAppData['civil_status'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                <option value="Widowed" <?= ($preAppData['civil_status'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                <option value="Divorced" <?= ($preAppData['civil_status'] ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Occupation</label>
              <input type="text" name="occupation" class="form-control" value="<?= htmlspecialchars($preAppData['occupation'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Residence Certificate</label>
              <input type="text" name="res_cert" class="form-control" value="<?= htmlspecialchars($preAppData['res_cert'] ?? '') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Residence Types</label>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
              <?php 
              $resTypesMap = [
                  'owned' => 'Owned',
                  'rented' => 'Rented',
                  'mortgage' => 'Mortgaged',
                  'free' => 'Free',
                  'parents' => 'With Parents'
              ];
              foreach ($resTypesMap as $val => $label): ?>
                <label><input type="checkbox" name="residence[]" value="<?= $val ?>" <?= in_array($val, $preAppData['residence_types'] ?? []) ? 'checked' : '' ?>> <?= $label ?></label>
              <?php endforeach; ?>
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
          <h4 style="margin:24px 0 16px; color:var(--primary);">Spouse Information</h4>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Spouse Name</label>
              <input type="text" name="spouse" class="form-control" value="<?= htmlspecialchars($preAppData['spouse']['name'] ?? '') ?>">
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

          <!-- BUSINESS INFORMATION -->
          <h4 style="margin:24px 0 16px; color:var(--primary);">Business Information</h4>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Business Name</label>
              <input type="text" name="business" class="form-control" value="<?= htmlspecialchars($preAppData['business']['name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Facebook Page</label>
              <input type="text" name="facebook" class="form-control" value="<?= htmlspecialchars($preAppData['business']['facebook'] ?? '') ?>">
            </div>
          </div>

          <!-- BENEFICIARY INFORMATION -->
          <h4 style="margin:24px 0 16px; color:var(--primary);">Beneficiary Information</h4>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Beneficiary Name</label>
              <input type="text" name="beneficiary" class="form-control" value="<?= htmlspecialchars($preAppData['beneficiary']['name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Beneficiary Date of Birth</label>
              <input type="date" name="ben_dob" class="form-control" value="<?= htmlspecialchars($preAppData['beneficiary']['dob'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Beneficiary Sex</label>
              <select name="ben_sex" class="form-control">
                <option value="">— Select —</option>
                <option value="Male" <?= ($preAppData['beneficiary']['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= ($preAppData['beneficiary']['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Relationship</label>
              <input type="text" name="relationship" class="form-control" value="<?= htmlspecialchars($preAppData['beneficiary']['relationship'] ?? '') ?>">
            </div>
          </div>

          <!-- DEPENDENTS -->
          <h4 style="margin:24px 0 16px; color:var(--primary);">Dependents</h4>
          <div class="table-wrap" style="margin-bottom: 16px;">
            <table class="loan-table" id="dependents-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Date of Birth</th>
                  <th>Age</th>
                  <th>Relationship</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="dependents-body">
            <?php $deps = $preAppData['dependents'] ?? []; ?>
            <?php if (empty($deps)): ?>
                  <tr>
                    <td><input type="text" name="dep_name[]" class="form-control"></td>
                    <td><input type="date" name="dep_dob[]" class="form-control"></td>
                    <td><input type="number" name="dep_age[]" class="form-control"></td>
                    <td><input type="text" name="dep_rel[]" class="form-control"></td>
                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeDepRow(this)" style="display:none;">✕</button></td>
                  </tr>
            <?php else: ?>
                  <?php foreach ($deps as $dep): ?>
                    <tr>
                      <td><input type="text" name="dep_name[]" class="form-control" value="<?= htmlspecialchars($dep['name'] ?? '') ?>"></td>
                      <td><input type="date" name="dep_dob[]" class="form-control" value="<?= htmlspecialchars($dep['dob'] ?? '') ?>"></td>
                      <td><input type="number" name="dep_age[]" class="form-control" value="<?= htmlspecialchars($dep['age'] ?? '') ?>"></td>
                      <td><input type="text" name="dep_rel[]" class="form-control" value="<?= htmlspecialchars($dep['rel'] ?? '') ?>"></td>
                      <td><button type="button" class="btn btn-sm btn-danger" onclick="removeDepRow(this)" <?= count($deps) > 1 ? '' : 'style="display:none;"' ?>>✕</button></td>
                    </tr>
                  <?php endforeach; ?>
            <?php endif; ?>
              </tbody>
            </table>
          </div>
          <button type="button" class="btn btn-outline" onclick="addDepRow()">+ Add Dependent</button>

          <!-- INCOME & EXPENSES -->
          <h4 style="margin:24px 0 16px; color:var(--primary);">Income & Expenses</h4>
          <div class="grid-3">
            <div class="form-group">
              <label class="form-label">Gross Income</label>
              <input type="number" name="gross" class="form-control" value="<?= htmlspecialchars($preAppData['income']['gross'] ?? '') ?>" step="0.01">
            </div>
            <div class="form-group">
              <label class="form-label">Expenses</label>
              <input type="number" name="expenses" class="form-control" value="<?= htmlspecialchars($preAppData['income']['expenses'] ?? '') ?>" step="0.01">
            </div>
            <div class="form-group">
              <label class="form-label">Net Income</label>
              <input type="number" name="net" class="form-control" value="<?= htmlspecialchars($preAppData['income']['net'] ?? '') ?>" step="0.01">
            </div>
          </div>

          <!-- OUTSTANDING LOANS -->
          <div>
            <h4 style="margin:24px 0 16px; color:var(--primary);">Outstanding Loans (if any)</h4>
            <div class="grid-4">
              <div class="form-group">
                <label class="form-label">Creditor Name</label>
                <input type="text" name="out_creditor" class="form-control" value="<?= htmlspecialchars($preAppData['outstanding']['creditor'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="out_address" class="form-control" value="<?= htmlspecialchars($preAppData['outstanding']['address'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Amount (₱)</label>
                <input type="number" name="out_amount" class="form-control" value="<?= htmlspecialchars($preAppData['outstanding']['amount'] ?? '') ?>" step="0.01">
              </div>
              <div class="form-group">
                <label class="form-label">Due Date</label>
                <input type="date" name="out_due_date" class="form-control" value="<?= htmlspecialchars($preAppData['outstanding']['due_date'] ?? '') ?>">
              </div>
            </div>
          </div>

          <hr style="margin:24px 0; border:0; border-top:1px solid var(--border);">

          <!-- LOAN DETAILS -->
          <h4 style="margin-bottom:16px; color:var(--primary);">Loan Details</h4>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Loan Type</label>
              <select name="loan_type_id" class="form-control" required id="loanTypeSelect">
                <option value="">— Select Loan Type —</option>
                <?php
                $loanTypes->data_seek(0);
                while ($lt = $loanTypes->fetch_assoc()):
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
            <div class="form-group">
              <label class="form-label">Purpose of Loan</label>
              <textarea name="purpose" class="form-control" rows="3" placeholder="Briefly describe the purpose..." required></textarea>
            </div>
          </div>

          <!-- COMPUTED PREVIEW -->
          <div id="loanPreview" style="background:var(--bg);border-radius:10px;padding:16px;margin-bottom:16px;display:none;">
            <div class="text-muted text-sm fw-600" style="margin-bottom:8px;">Estimated Monthly Payment</div>
            <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--primary);" id="monthlyEst">₱0.00</div>
            <div class="text-muted text-sm" id="totalEst"></div>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Submit Loan Application →</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function addDepRow() {
  const tbody = document.getElementById('dependents-body');
  
  const row = document.createElement('tr');
  row.innerHTML = `
    <td><input type="text" name="dep_name[]" class="form-control"></td>
    <td><input type="date" name="dep_dob[]" class="form-control"></td>
    <td><input type="number" name="dep_age[]" class="form-control"></td>
    <td><input type="text" name="dep_rel[]" class="form-control"></td>
    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeDepRow(this)">✕</button></td>
  `;
  tbody.appendChild(row);
  updateDepActions();
}

function removeDepRow(btn) {
  btn.closest('tr').remove();
  updateDepActions();
}

function updateDepActions() {
  const rows = document.querySelectorAll('#dependents-body tr');
  rows.forEach(row => {
    const btn = row.querySelector('.btn-danger');
    if (btn) btn.style.display = rows.length > 1 ? 'block' : 'none';
  });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', updateDepActions);

function calcMonthly() {
  const amount = parseFloat(document.getElementById('amountInput').value) || 0;
  const term = parseInt(document.getElementById('termInput').value) || 1;
  const select = document.getElementById('loanTypeSelect');
  const option = select.options[select.selectedIndex];
  const interest = parseFloat(option.getAttribute('data-interest')) || 0;

  if (amount > 0 && term > 0 && interest > 0) {
    const monthlyRate = interest / 100 / 12;
    const monthlyPayment = (amount * monthlyRate * Math.pow(1 + monthlyRate, term)) / (Math.pow(1 + monthlyRate, term) - 1);
    const totalPayment = monthlyPayment * term;

    document.getElementById('monthlyEst').textContent = '₱' + monthlyPayment.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('totalEst').textContent = 'Total: ₱' + totalPayment.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('loanPreview').style.display = 'block';
  } else {
    document.getElementById('loanPreview').style.display = 'none';
  }
}
</script>
</body>
</html>