<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management – CoopIMS</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php
require_once '../includes/config.php';
requireLogin('general_manager');
$activePage = 'users';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean($_POST['action'] ?? '');
    if ($action === 'add_user') {
        $allowedRoles = ['loan_officer','cashier','book_keeper','collector','general_manager'];

        $uname = clean($_POST['username']);
        $fname = clean($_POST['first_name']); $mname = clean($_POST['middle_name']); $lname = clean($_POST['last_name']);
        $email = clean($_POST['email']); $role = clean($_POST['role']);

        if (!in_array($role, $allowedRoles, true)) {
            $role = 'staff';
        }

        $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt  = $db->prepare("INSERT INTO users (username,password,first_name,middle_name,last_name,email,role) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssss', $uname,$pass,$fname,$mname,$lname,$email,$role);
        $stmt->execute();
        header('Location: admin_users.php?msg=User+added.'); exit;
    }
    if ($action === 'delete_user') {
        $id = (int)$_POST['id'];
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }
        header('Location: admin_users.php?msg=User+deleted.'); exit;
    }
}

$msg = clean($_GET['msg'] ?? '');
$users = $db->query("SELECT *, CONCAT_WS(' ', first_name, middle_name, last_name) AS full_name FROM users ORDER BY role, last_name, first_name");
?>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">User Management</div>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="openModal('modal-add-user')">+ Add User</button>
    </div>
  </div>

  <div class="page-body">
    <?php if ($msg): ?>
      <div style="background:#d4f0dc;color:#1a6b3a;padding:12px 16px;border-radius:8px;margin-bottom:20px;border-left:3px solid #2e9e58;">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><span class="card-title">Staff & Administrator Accounts</span></div>
      <div class="card-body">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              <?php while ($u = $users->fetch_assoc()): ?>
              <tr>
                <td class="fw-600"><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                <?php
                  $roleLabels = [
                    'loan_officer' => 'Loan Officer',
                    'cashier' => 'Cashier',
                    'book_keeper' => 'Book Keeper',
                    'collector' => 'Collector',
                    'general_manager' => 'General Manager',
                    'staff' => 'Staff'
                  ];
                  $badgeClass = $u['role'] === 'general_manager' ? 'badge-red' : 'badge-blue';
                  $roleLabel = $roleLabels[$u['role']] ?? ucfirst(str_replace('_', ' ', $u['role']));
                ?>
                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($roleLabel) ?></span></td>
                <td><span class="badge <?= $u['is_active']?'badge-green':'badge-gray' ?>"><?= $u['is_active']?'Active':'Inactive' ?></span></td>
                <td>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user account?');">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-add-user">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-add-user')">✕</button>
    <div class="modal-title">🔧 Add New User</div>
    <form method="POST">
      <input type="hidden" name="action" value="add_user">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Role</label>
          <select name="role" class="form-control" required>
            <option value="loan_officer">Loan Officer</option>
            <option value="cashier">Cashier</option>
            <option value="book_keeper">Book Keeper</option>
            <option value="collector">Collector</option>
            <option value="general_manager">General Manager</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" required></div>
      </div>
      <div class="form-group">
        <label class="form-label">Middle Name (Optional)</label>
        <input type="text" name="middle_name" class="form-control">
      </div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add-user')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add User</button>
      </div>
    </form>
  </div>
</div>

<script src="../js/app.js"></script>
</body>
</html>
