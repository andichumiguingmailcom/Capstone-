<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('book_keeper');
$activePage = 'sales';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean($_POST['action'] ?? '');
    $uid = $_SESSION['user_id'];

    if ($action === 'add_sale') {
        $memberId   = (int)($_POST['member_id'] ?? 0) ?: null;
        $payType    = clean($_POST['payment_type']);
        $saleDate   = clean($_POST['sale_date']);
        $items      = $_POST['items'] ?? [];
        $qtys       = $_POST['qtys'] ?? [];
        $prices     = $_POST['prices'] ?? [];

        $total = 0.0;
        foreach ($items as $i => $pid) {
            $total += ((int)($qtys[$i] ?? 0)) * ((float)($prices[$i] ?? 0));
        }

        // Calculate interest for credit payments
        $creditMonths = 0;
        $interestRate = 0;
        if ($payType === 'credit') {
            $creditMonths = (int)($_POST['credit_months'] ?? 1);
            // Interest rates based on months (progressive rates)
            $interestRates = [1 => 5, 2 => 8, 3 => 12, 6 => 20, 12 => 30];
            $interestRate = $interestRates[$creditMonths] ?? 5;
            $interestAmount = $total * ($interestRate / 100);
            $total += $interestAmount;
        }

        $stmt = $db->prepare("INSERT INTO sales (member_id, sale_date, total, payment_type, recorded_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('isdsi', $memberId, $saleDate, $total, $payType, $uid);
        $stmt->execute();
        $saleId = $db->insert_id;

        $saleItemStmt = $db->prepare("INSERT INTO sale_items (sale_id, product_id, qty, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $updateStockStmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

        foreach ($items as $i => $pid) {
            $pid = (int)$pid; $qty = (int)($qtys[$i] ?? 0);
            $price = (float)($prices[$i] ?? 0); $sub = $qty * $price;
            if ($pid && $qty > 0) {
                $saleItemStmt->bind_param('iiidd', $saleId, $pid, $qty, $price, $sub);
                $saleItemStmt->execute();
                $updateStockStmt->bind_param('ii', $qty, $pid);
                $updateStockStmt->execute();
            }
        }
        header('Location: admin_sales.php?msg=Sale+recorded.'); exit;
    }
}

$msg = clean($_GET['msg'] ?? '');
$sales = $db->query("SELECT s.*, CONCAT_WS(' ', m.first_name, m.last_name) as full_name, m.member_id as mc 
    FROM sales s LEFT JOIN members m ON s.member_id=m.id ORDER BY s.sale_date DESC, s.id DESC LIMIT 50");
$products = $db->query("SELECT * FROM products WHERE is_active=1 AND stock>0 ORDER BY category, name");
$members = $db->query("SELECT id, member_id, CONCAT_WS(' ', first_name, last_name) as full_name 
    FROM members WHERE status='active' ORDER BY last_name, first_name");

$todayTotal = $db->query("SELECT SUM(total) as s FROM sales WHERE sale_date=CURDATE()")->fetch_assoc()['s'] ?? 0;
$monthTotal = $db->query("SELECT SUM(total) as s FROM sales WHERE MONTH(sale_date)=MONTH(NOW())")->fetch_assoc()['s'] ?? 0;
?>

<?php include 'includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Sales Recording</div>
    <div class="topbar-actions">
      <button class="btn btn-primary btn-large" onclick="openModal('modal-add-sale')" style="font-size: 1.1rem; padding: 12px 24px; font-weight: 600;">
        🛒 Record New Sale
      </button>
    </div>
  </div>

  <div class="page-body">
    <?php if ($msg): ?>
      <div style="background:#d4f0dc;color:#1a6b3a;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:3px solid #2e9e58;">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="stats-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:24px;">
      <div class="stat-card green"><span class="stat-icon">🛒</span><div class="stat-value">₱<?= number_format($todayTotal,0) ?></div><div class="stat-label">Today's Sales</div></div>
      <div class="stat-card blue"><span class="stat-icon">📅</span><div class="stat-value">₱<?= number_format($monthTotal,0) ?></div><div class="stat-label">This Month's Sales</div></div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">Sales Transactions</span></div>
      <div class="card-body">
        <div class="search-bar">
          <input type="text" id="salesSearch" class="search-input" placeholder="Search..." oninput="filterTable('salesSearch','salesTable')">
        </div>
        <div class="table-wrap">
          <table id="salesTable">
            <thead><tr><th>ID</th><th>Member</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
            <tbody>
              <?php while ($s = $sales->fetch_assoc()): ?>
              <tr>
                <td class="text-muted">#<?= $s['id'] ?></td>
                <td><?= $s['full_name'] ? '<span class="fw-600">'.htmlspecialchars($s['full_name']).'</span><div class="text-muted text-sm">'.$s['mc'].'</div>' : '<span class="text-muted">Walk-in</span>' ?></td>
                <td><?= date('M j, Y', strtotime($s['sale_date'])) ?></td>
                <td class="fw-600">₱<?= number_format($s['total'], 2) ?></td>
                <td><span class="badge <?= $s['payment_type']==='cash'?'badge-green':'badge-blue' ?>"><?= ucfirst($s['payment_type']) ?></span></td>
                <td><span class="badge <?= $s['status']==='completed'?'badge-green':'badge-red' ?>"><?= ucfirst($s['status']) ?></span></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ADD SALE MODAL -->
<div class="modal-overlay" id="modal-add-sale">
  <div class="modal" style="max-width:700px;">
    <button class="modal-close" onclick="closeModal('modal-add-sale')">✕</button>
    <div class="modal-title">🛒 Record New Sale</div>
    <form method="POST">
      <input type="hidden" name="action" value="add_sale">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Member (optional)</label>
          <select name="member_id" class="form-control">
            <option value="">Walk-in / Non-member</option>
            <?php while ($m = $members->fetch_assoc()): ?>
              <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?> (<?= $m['member_id'] ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Payment Type</label>
          <select name="payment_type" class="form-control" id="paymentType" required onchange="toggleCreditFields()">
            <option value="cash">Cash</option><option value="credit">Credit</option>
          </select>
        </div>
      </div>
      <div class="form-group" id="creditFields" style="display:none;">
        <label class="form-label">Credit Terms</label>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" style="font-size:0.78rem;">Months to Pay</label>
            <select name="credit_months" class="form-control" id="creditMonths" onchange="calcTotal()">
              <option value="1">1 month (5% interest)</option>
              <option value="2">2 months (8% interest)</option>
              <option value="3">3 months (12% interest)</option>
              <option value="6">6 months (20% interest)</option>
              <option value="12">12 months (30% interest)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" style="font-size:0.78rem;">Monthly Payment</label>
            <input type="text" id="monthlyPayment" class="form-control" readonly>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Sale Date</label>
        <input type="date" name="sale_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div style="border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <span class="fw-600">Items</span>
          <button type="button" class="btn btn-sm btn-outline" onclick="addItem()">+ Add Item</button>
        </div>
        <div id="itemsContainer">
          <div class="sale-item" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:end;">
            <div>
              <label class="form-label" style="font-size:0.78rem;">Product</label>
              <input type="text" list="productList" class="form-control product-search" placeholder="Type to search..." oninput="fillPrice(this)" required>
              <input type="hidden" name="items[]" class="product-id">
            </div>
            <div><label class="form-label" style="font-size:0.78rem;">Qty</label><input type="number" name="qtys[]" class="form-control" min="1" value="1" required oninput="calcTotal()"></div>
            <div><label class="form-label" style="font-size:0.78rem;">Price</label><input type="number" name="prices[]" class="form-control" step="0.01" required oninput="calcTotal()"></div>
            <div style="padding-top:20px;"><button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">✕</button></div>
          </div>
        </div>
        <div style="text-align:right;margin-top:8px;">
          <span class="text-muted">Total: </span>
          <span id="grandTotal" style="font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:800;color:var(--primary);">₱0.00</span>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add-sale')">Cancel</button>
        <button type="submit" class="btn btn-primary btn-large" style="font-size: 1.1rem; padding: 12px 24px; font-weight: 600;">
          ✅ Record Sale
        </button>
      </div>
    </form>
  </div>
</div>

<datalist id="productList">
  <?php $products->data_seek(0); while ($p = $products->fetch_assoc()): ?>
    <option value="<?= htmlspecialchars($p['name']) ?>">
  <?php endwhile; ?>
</datalist>

<script src="js/app.js"></script>
<script>
const productMap = {
<?php $products->data_seek(0); while ($p = $products->fetch_assoc()): ?>
  "<?= addslashes($p['name']) ?>": {id: <?= $p['id'] ?>, price: <?= $p['price'] ?>},
<?php endwhile; ?>
};

function fillPrice(input) {
  const name = input.value;
  const row = input.closest('.sale-item');
  const idInput = row.querySelector('.product-id');
  const priceInput = row.querySelector('input[name="prices[]"]');
  
  if (productMap[name]) {
    idInput.value = productMap[name].id;
    priceInput.value = productMap[name].price;
  } else {
    idInput.value = '';
  }
  calcTotal();
}

function calcTotal() {
  const qtys = document.querySelectorAll('input[name="qtys[]"]');
  const prices = document.querySelectorAll('input[name="prices[]"]');
  let subtotal = 0;
  qtys.forEach((q, i) => { subtotal += (parseFloat(q.value)||0) * (parseFloat(prices[i]?.value)||0); });

  const paymentType = document.getElementById('paymentType').value;
  let total = subtotal;
  let monthlyPayment = 0;

  if (paymentType === 'credit') {
    const creditMonths = parseInt(document.getElementById('creditMonths').value) || 1;
    const interestRates = {1: 5, 2: 8, 3: 12, 6: 20, 12: 30};
    const interestRate = interestRates[creditMonths] || 5;
    const interestAmount = subtotal * (interestRate / 100);
    total = subtotal + interestAmount;
    monthlyPayment = total / creditMonths;
    document.getElementById('monthlyPayment').value = '₱' + monthlyPayment.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  } else {
    document.getElementById('monthlyPayment').value = '';
  }

  document.getElementById('grandTotal').textContent = '₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function toggleCreditFields() {
  const paymentType = document.getElementById('paymentType').value;
  const creditFields = document.getElementById('creditFields');
  if (paymentType === 'credit') {
    creditFields.style.display = 'block';
    calcTotal(); // Recalculate when showing credit fields
  } else {
    creditFields.style.display = 'none';
    calcTotal(); // Recalculate when hiding credit fields
  }
}

function addItem() {
  const tmpl = document.querySelector('.sale-item').cloneNode(true);
  tmpl.querySelectorAll('input').forEach(el => { el.value = ''; if(el.name==='qtys[]') el.value='1'; });
  document.getElementById('itemsContainer').appendChild(tmpl);
}

function removeItem(btn) {
  if (document.querySelectorAll('.sale-item').length > 1) { btn.closest('.sale-item').remove(); calcTotal(); }
}
</script>
</body>
</html>
