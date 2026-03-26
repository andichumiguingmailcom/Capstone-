<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>COOP IMS – Login</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {  
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, CONCAT_WS(' ', first_name, middle_name, last_name) AS full_name, password, role FROM users WHERE username = ? AND is_active = 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && password_verify($password, $result['password'])) {
            $_SESSION['user_id']   = $result['id'];
            $_SESSION['user_name'] = $result['full_name'];
            $_SESSION['role']      = $result['role'];

            // Log login
            $ip = $_SERVER['REMOTE_ADDR'];
            $db->query("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES ({$result['id']}, 'LOGIN', 'User logged in', '$ip')");

            header('Location: admin_dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
        $db->close();
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>

<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-text">🌾 CoopIMS</div>
      <div class="logo-sub">Cooperative Management System</div>
    </div>

    <h2 class="login-title">Welcome back</h2>
    <p class="login-sub">Sign in to access the portal</p>

    <?php if ($error): ?>
      <div style="background:#fde8ea;color:#c0392b;padding:10px 14px;border-radius:8px;font-size:0.87rem;margin-bottom:16px;border-left:3px solid #e63946;">
        ⚠️ <?= $error ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" placeholder="Enter username" required autocomplete="username">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter password" required autocomplete="current-password">
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
          <input type="checkbox" name="remember"> Remember me
        </label>
        <a href="#" style="font-size:0.83rem;color:var(--primary);text-decoration:none;">Forgot password?</a>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">
        Sign In →
      </button>
    </form>

    <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--border);text-align:center;">
      <p class="text-muted text-sm">Not yet a member?</p>
      <a href="member_pre_application.php" class="btn btn-outline" style="margin-top:10px;width:100%;justify-content:center;">
        Apply for Membership
      </a>
    </div>

    <div style="margin-top:20px;text-align:center;">
      <a href="member_login.php" style="font-size:0.83rem;color:var(--primary);text-decoration:none;">
        🙋 Member Portal Login →
      </a>
    </div>
  </div>
</div>

</body>
</html>
