<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('book_keeper');
$activePage = 'inventory';
$db = getDB();

// Handle stock-in / stock-out / delete / add dsadaasddas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean($_POST['action'] ?? '');
    $uid    = $_SESSION['user_id'];

    if ($action === 'add_product') {
        $sku  = clean($_POST['sku']); $name = clean($_POST['name']);
        $cat  = clean($_POST['category']); $unit = clean($_POST['unit']);
        $price= (float)$_POST['price']; $stock= (int)$_POST['stock'];
        $rop  = (int)$_POST['reorder_pt'];

        // Check if SKU already exists to prevent duplicate entry error
        $check = $db->prepare("SELECT id FROM products WHERE sku = ?");
        $check->bind_param('s', $sku);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            header('Location: admin_inventory.php?msg=Error:+A+product+with+this+SKU+already+exists.'); exit;
        }

        $stmt = $db->prepare("INSERT INTO products (sku,name,category,unit,price,stock,reorder_pt) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param('ssssdii', $sku,$name,$cat,$unit,$price,$stock,$rop);
        $stmt->execute();
        header('Location: admin_inventory.php?msg=Product+added.'); exit;
    }

    if ($action === 'stock_in' || $action === 'stock_out') {
        $pid = (int)$_POST['product_id']; $qty = (int)$_POST['qty'];
        $notes = clean($_POST['notes']); $type = $action;
        $delta = $action === 'stock_in' ? $qty : -$qty;

        $updateStmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $updateStmt->bind_param('ii', $delta, $pid);
        $updateStmt->execute();

        $stmt = $db->prepare("INSERT INTO stock_movements (product_id,type,qty,notes,moved_by) VALUES (?,?,?,?,?)");
        $stmt->bind_param('isisi', $pid, $type, $qty, $notes, $uid);
        $stmt->execute();
        header('Location: admin_inventory.php?msg=Stock+updated.'); exit;
    }

    if ($action === 'delete_product') {
        $pid = (int)$_POST['product_id'];
        $stmt = $db->prepare("UPDATE products SET is_active=0 WHERE id=?");
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        header('Location: admin_inventory.php?msg=Product+removed.'); exit;
    }
}

$msg = clean($_GET['msg'] ?? '');
$cat = clean($_GET['cat'] ?? '');
if ($cat) {
    $stmt = $db->prepare("SELECT * FROM products WHERE is_active=1 AND category=? ORDER BY category, name");
    $stmt->bind_param('s', $cat);
    $stmt->execute();
    $products = $stmt->get_result();
} else {
    $products = $db->query("SELECT * FROM products WHERE is_active=1 ORDER BY category, name");
}
$movements = $db->query("SELECT sm.*, p.name, CONCAT_WS(' ', u.first_name, u.last_name) as moved_by_name 
    FROM stock_movements sm JOIN products p ON sm.product_id=p.id 
    LEFT JOIN users u ON sm.moved_by=u.id ORDER BY sm.moved_at DESC LIMIT 20");

// Stats
$totalProducts = $db->query("SELECT COUNT(*) as c FROM products WHERE is_active=1")->fetch_assoc()['c'];
$lowStockCount = $db->query("SELECT COUNT(*) as c FROM products WHERE stock<=reorder_pt AND is_active=1")->fetch_assoc()['c'];
$riceCount     = $db->query("SELECT COUNT(*) as c FROM products WHERE category='rice' AND is_active=1")->fetch_assoc()['c'];
$groceryCount  = $db->query("SELECT COUNT(*) as c FROM products WHERE category='grocery' AND is_active=1")->fetch_assoc()['c'];
?>

<?php include 'includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">Inventory Management</div>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="openModal('modal-add-product')">+ Add Product</button>
    </div>
  </div>

  <div class="page-body">
    <style>
      .stats-grid .stat-card {
        padding: 30px 24px;
      }
      .stats-grid .stat-value {
        font-size: 2.2rem;
        font-weight: 800;
      }
      .stats-grid .stat-label {
        font-size: 0.95rem;
        font-weight: 600;
      }
      .stats-grid .stat-icon {
        font-size: 1.8rem;
      }
    </style>

    <div class="stats-grid" style="display: flex; gap: 20px; margin-bottom: 32px; width: 100%;">
      <div class="stat-card green" style="flex: 1;"><span class="stat-icon">📦</span><div class="stat-value"><?= $totalProducts ?></div><div class="stat-label">Total Products</div></div>
      <div class="stat-card gold" style="flex: 1;"><span class="stat-icon">🌾</span><div class="stat-value"><?= $riceCount ?></div><div class="stat-label">Rice Products</div></div>
      <div class="stat-card blue" style="flex: 1;"><span class="stat-icon">🛒</span><div class="stat-value"><?= $groceryCount ?></div><div class="stat-label">Grocery Products</div></div>
      <div class="stat-card red" style="flex: 1;"><span class="stat-icon">⚠️</span><div class="stat-value"><?= $lowStockCount ?></div><div class="stat-label">Low Stock</div></div>
    </div>

    <div class="grid-2">
      <!-- PRODUCTS TABLE -->
      <div class="card" style="grid-column:1/-1;">
        <div class="card-header">
          <span class="card-title">Products</span>
          <div class="flex gap-2">
            <?php foreach([''=> 'All','rice'=>'🌾 Rice','grocery'=>'🛒 Grocery','other'=>'Other'] as $k=>$v): ?>
              <a href="?cat=<?= $k ?>" class="btn btn-sm <?= $cat===$k ? 'btn-primary' : 'btn-ghost' ?>"><?= $v ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="card-body">
          <div class="search-bar">
            <input type="text" id="invSearch" class="search-input" placeholder="Search products..." oninput="filterTable('invSearch','invTable')">
          </div>
          <div class="table-wrap">
            <table id="invTable">
              <thead>
                <tr><th>SKU</th><th>Product Name</th><th>Category</th><th>Unit</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php while ($p = $products->fetch_assoc()): ?>
                <tr>
                  <td class="text-muted text-sm"><?= $p['sku'] ?></td>
                  <td class="fw-600"><?= htmlspecialchars($p['name']) ?></td>
                  <td><span class="badge <?= $p['category']==='rice'?'badge-gold':'badge-blue' ?>"><?= ucfirst($p['category']) ?></span></td>
                  <td><?= $p['unit'] ?></td>
                  <td class="fw-600">₱<?= number_format($p['price'], 2) ?></td>
                  <td>
                    <span class="fw-600 <?= $p['stock'] <= $p['reorder_pt'] ? 'badge badge-red' : '' ?>">
                      <?= $p['stock'] ?>
                    </span>
                    <?php if ($p['stock'] <= $p['reorder_pt']): ?>
                      <div class="text-muted text-sm">Min: <?= $p['reorder_pt'] ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php $pct = $p['reorder_pt'] > 0 ? min(100, round($p['stock']/$p['reorder_pt']*100)) : 100; ?>
                    <div class="progress-bar" style="width:80px;">
                      <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $pct<50?'var(--danger)':($pct<80?'var(--accent)':'var(--primary-light)') ?>;"></div>
                    </div>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-primary" onclick="stockAction(<?= $p['id'] ?>,'<?= addslashes($p['name']) ?>','stock_in')">+ In</button>
                    <button class="btn btn-sm btn-outline" onclick="stockAction(<?= $p['id'] ?>,'<?= addslashes($p['name']) ?>','stock_out')">- Out</button>
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="action" value="delete_product">
                      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                    </form>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- STOCK MOVEMENTS LOG -->
      <div class="card" style="grid-column:1/-1;">
        <div class="card-header">
          <span class="card-title">Stock Movement History</span>
        </div>
        <div class="card-body">
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>Product</th><th>Type</th><th>Qty</th><th>Notes</th><th>By</th><th>Date</th></tr>
              </thead>
              <tbody>
                <?php while ($mv = $movements->fetch_assoc()): ?>
                <tr>
                  <td class="fw-600"><?= htmlspecialchars($mv['name']) ?></td>
                  <td><span class="badge <?= $mv['type']==='stock_in'?'badge-green':'badge-red' ?>"><?= str_replace('_',' ', ucfirst($mv['type'])) ?></span></td>
                  <td><?= $mv['qty'] ?></td>
                  <td class="text-muted"><?= htmlspecialchars($mv['notes'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($mv['moved_by_name'] ?? 'System') ?></td>
                  <td><?= date('M j, Y H:i', strtotime($mv['moved_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ADD PRODUCT MODAL -->
<div class="modal-overlay" id="modal-add-product">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-add-product')">✕</button>
    <div class="modal-title">📦 Add New Product</div>
    <form method="POST">
      <input type="hidden" name="action" value="add_product">
      <div class="form-row">
        <div class="form-group"><label class="form-label">SKU</label><input type="text" name="sku" class="form-control" required placeholder="GRC-001"></div>
        <div class="form-group"><label class="form-label">Category</label>
          <select name="category" class="form-control" required>
            <option value="grocery">Grocery</option><option value="rice">Rice</option><option value="other">Other</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Product Name</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Unit</label><input type="text" name="unit" class="form-control" placeholder="kg / bottle / pack" required></div>
        <div class="form-group"><label class="form-label">Price (₱)</label><input type="number" name="price" class="form-control" step="0.01" min="0" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Initial Stock</label><input type="number" name="stock" class="form-control" min="0" value="0" required></div>
        <div class="form-group"><label class="form-label">Reorder Point</label><input type="number" name="reorder_pt" class="form-control" min="0" value="10" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add-product')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Product</button>
      </div>
    </form>
  </div>
</div>

<!-- STOCK IN/OUT MODAL -->
<div class="modal-overlay" id="modal-stock">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-stock')">✕</button>
    <div class="modal-title" id="stockModalTitle">Stock In</div>
    <form method="POST">
      <input type="hidden" name="action" id="stockActionType">
      <input type="hidden" name="product_id" id="stockProductId">
      <p style="color:var(--text-muted);margin-bottom:16px;" id="stockProductName"></p>
      <div class="form-group"><label class="form-label">Quantity</label><input type="number" name="qty" class="form-control" min="1" required id="stockQty"></div>
      <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Reason / supplier / reference..."></textarea></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-stock')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="stockSubmitBtn">Confirm</button>
      </div>
    </form>
  </div>
</div>

<script src="js/app.js"></script>
<script>
function stockAction(id, name, type) {
  document.getElementById('stockProductId').value = id;
  document.getElementById('stockActionType').value = type;
  document.getElementById('stockProductName').textContent = 'Product: ' + name;
  const isIn = type === 'stock_in';
  document.getElementById('stockModalTitle').textContent = isIn ? '📥 Stock In' : '📤 Stock Out';
  document.getElementById('stockSubmitBtn').className = 'btn ' + (isIn ? 'btn-primary' : 'btn-danger');
  openModal('modal-stock');
}
</script>
</body>
</html>
