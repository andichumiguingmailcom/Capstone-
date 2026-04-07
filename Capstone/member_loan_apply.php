<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apply for Loan – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/theme-init.js"></script>
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('member');
$activePage = 'loan_apply';
$db = getDB();

$memberId = $_SESSION['member_id'] ?? 0;
$msg = ''; $msgType = 'green';

// Get member's capital share
$member = $db->query("SELECT capital_share FROM members WHERE id=$memberId")->fetch_assoc();
$capitalShare = $member['capital_share'] ?? 0;
$specialLoanRate = $capitalShare <= 75000 ? 2.0 : 1.5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loanTypeId  = (int)$_POST['loan_type_id'];
    $amount      = (float)$_POST['amount'];
    $termMonths  = (int)$_POST['term_months'];
    $purpose     = clean($_POST['purpose'] ?? '');

    // Check for existing pending app
    $existing = $db->query("SELECT id FROM loan_applications WHERE member_id=$memberId AND status='pending'")->num_rows;
    if ($existing) {
        $msg = 'You already have a pending loan application.'; $msgType = 'red';
    } else {
        $stmt = $db->prepare("INSERT INTO loan_applications (member_id,loan_type_id,amount,term_months,purpose) VALUES (?,?,?,?,?)");
        $stmt->bind_param('iidis', $memberId,$loanTypeId,$amount,$termMonths,$purpose);
        $stmt->execute();
        $msg = 'Your loan application has been submitted! We will notify you once reviewed.';
    }
}

$loanTypes = $db->query("SELECT * FROM loan_types ORDER BY type_name");
$myApps = $db->query("SELECT la.*, lt.type_name FROM loan_applications la 
    JOIN loan_types lt ON la.loan_type_id=lt.id 
    WHERE la.member_id=$memberId ORDER BY la.applied_at DESC");
?>

<?php include 'includes/member_sidebar.php'; ?>

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
        <?php 
        $db->query("SELECT 1"); // reset
        $loanTypesInfo = $db->query("SELECT * FROM loan_types ORDER BY type_name");
        while ($lt = $loanTypesInfo->fetch_assoc()): 
          $displayRate = $lt['type_name'] === 'Special Loan' ? $specialLoanRate : $lt['interest'];
          $rateNote = $lt['type_name'] === 'Special Loan' ? ' (Based on capital share)' : '';
        ?>
          <div class="card" style="margin-bottom:12px;cursor:pointer;" onclick="selectLoan(<?= $lt['id'] ?>, '<?= addslashes($lt['type_name']) ?>', <?= $lt['max_amount'] ?>, <?= $lt['max_months'] ?><?= $lt['type_name'] === 'Special Loan' ? ", $specialLoanRate" : '' ?>)">
            <div class="card-body" style="padding:16px 20px;">
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                  <div class="fw-600"><?= htmlspecialchars($lt['type_name']) ?></div>
                  <div class="text-muted text-sm"><?= $displayRate ?>% interest/month · up to <?= $lt['max_months'] ?> months<?= $rateNote ?></div>
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
              <div class="form-group">
                <label class="form-label">Loan Type</label>
                <select name="loan_type_id" class="form-control" required id="loanTypeSelect">
                  <option value="">— Select Loan Type —</option>
                  <?php
                  $db->query("SELECT 1"); // reset
                  $lt2 = $db->query("SELECT * FROM loan_types ORDER BY type_name");
                  while ($lt = $lt2->fetch_assoc()):
                    $displayRate = $lt['type_name'] === 'Special Loan' ? $specialLoanRate : $lt['interest'];
                  ?>
                    <option value="<?= $lt['id'] ?>" data-max="<?= $lt['max_amount'] ?>" data-months="<?= $lt['max_months'] ?>" data-interest="<?= $displayRate ?>" data-type="<?= htmlspecialchars($lt['type_name']) ?>">
                      <?= htmlspecialchars($lt['type_name']) ?>
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
                $b = ['pending'=>'badge-gold','approved'=>'badge-green','rejected'=>'badge-red','disbursed'=>'badge-blue'][$app['status']] ?? 'badge-gray';
              ?>
              <tr>
                <td class="fw-600"><?= htmlspecialchars($app['type_name']) ?></td>
                <td>₱<?= number_format($app['amount'], 2) ?></td>
                <td><?= $app['term_months'] ?> mos.</td>
                <td><span class="badge <?= $b ?>"><?= ucfirst($app['status']) ?></span></td>
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

<script src="js/app.js"></script>
<script>
// Member's capital share data
const capitalShare = <?= $capitalShare ?>;
const specialLoanRate = <?= $specialLoanRate ?>;

function selectLoan(id, name, maxAmt, maxMonths, specialRate) {
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
  let rate = opt ? parseFloat(opt.dataset.interest) / 100 : 0;
  const preview = document.getElementById('loanPreview');
  
  if (amt > 0 && term > 0 && rate > 0) {
    const totalInterestFactor = rate * term;
    const monthly = (amt * (1 + totalInterestFactor)) / term;
    const total = monthly * term;
    let interestNote = `Interest: ${(rate*100).toFixed(1)}%/mo`;
    
    // Add note for Special Loan showing capital share benefit
    if (opt.dataset.type === 'Special Loan') {
      interestNote += ` (Your capital share: ₱${capitalShare.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})})`;
    }
    
    document.getElementById('monthlyEst').textContent = '₱' + monthly.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('totalEst').textContent = 'Total repayment: ₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + ` · ${interestNote}`;
    preview.style.display = 'block';
  } else {
    preview.style.display = 'none';
  }
}
</script>
</body>
</html>
