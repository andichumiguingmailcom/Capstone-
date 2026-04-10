<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Member Login – CoopIMS</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body {
      background-color: #7a1e2c !important; /* Professional Maroon Background */
      background-image: none !important;
    }
    body::before, .login-page::before {
      display: none !important; /* Disable global gradients */
    }
    .login-card {
      background-color: #f4f4f2 !important; /* Dirty White login card */
      color: #333333;
      border: none;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }
    .login-title, .login-logo .logo-text {
      color: #7a1e2c !important;
    }
    .login-sub, .login-logo .logo-sub, .login-card label, .login-card .text-muted, .login-card .text-sm {
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
    .login-card .btn-primary {
      background-color: #7a1e2c !important;
      color: #ffffff !important;
    }
  </style>
</head>
<body>
<?php
require_once '../includes/config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberId = clean($_POST['member_id'] ?? '');
    $pin      = $_POST['pin'] ?? '';

    if ($memberId && $pin) {
        $db = getDB();
        // Members use member_id + last 4 digits of phone as PIN
        $stmt = $db->prepare("SELECT id, CONCAT_WS(' ', first_name, middle_name, last_name) AS full_name, phone FROM members WHERE member_id=? AND status='active'");
        $stmt->bind_param('s', $memberId);
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc();

        if ($member && substr($member['phone'], -4) === $pin) {
            $token = bin2hex(random_bytes(16));
            $_SESSION['member_contexts'][$token] = [
                'id'        => $member['id'],
                'member_id' => $member['id'],
                'name'      => $member['full_name'],
                'role'      => 'member',
            ];
            $_SESSION['member_current_ctx'] = $token;
            $_SESSION['user_id']   = $member['id'];
            $_SESSION['member_id'] = $member['id'];
            $_SESSION['user_name'] = $member['full_name'];
            $_SESSION['role']      = 'member';
            header('Location: member_dashboard.php?member_ctx=' . $token);
            exit;
        } else {
            $error = 'Invalid Member ID or PIN.';
        }
        $db->close();
    } else {
        $error = 'Please enter your Member ID and PIN.';
    }
}
?>

<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-text">🌾 CoopIMS</div>
      <div class="logo-sub">Member Portal</div>
    </div>

    <h2 class="login-title">Member Login</h2>
    <p class="login-sub">Enter your Member ID and PIN to access the portal</p>

    <?php if ($error): ?>
      <div style="background:#fde8ea;color:#c0392b;padding:10px 14px;border-radius:8px;font-size:0.87rem;margin-bottom:16px;border-left:3px solid #e63946;">
        ⚠️ <?= $error ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Member ID</label>
        <input type="text" name="member_id" class="form-control" placeholder="e.g. MEM-001" required>
      </div>
      <div class="form-group">
        <label class="form-label">PIN (last 4 digits of phone)</label>
        <div class="password-toggle-wrapper">
          <input type="password" name="pin" id="pin" class="form-control" maxlength="4" placeholder="••••" required>
          <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('pin', this)">
            👁️
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">
        Enter Portal →
      </button>
    </form>

    <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--border);text-align:center;">
      <a href="../index.php" style="font-size:0.83rem;color:var(--text-muted);text-decoration:none;">
        ← Back to Admin Login
      </a>
      &nbsp;·&nbsp;
      <a href="member_pre_application.php" style="font-size:0.83rem;color:var(--primary);text-decoration:none;">
        Apply for Membership
      </a>
    </div>
  </div>
</div>

<script src="../js/app.js"></script>
</body>
</html>
