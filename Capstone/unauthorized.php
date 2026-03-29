<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Unauthorized Access – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body style="display:flex; align-items:center; justify-content:center; height:100vh; background:var(--bg);">

<div class="card" style="max-width:450px; text-align:center; padding:40px;">
  <div style="font-size:4rem; margin-bottom:20px;">🚫</div>
  <h2 style="font-family:'Syne',sans-serif; color:var(--danger); margin-bottom:12px;">Access Denied</h2>
  <p class="text-muted" style="margin-bottom:24px; line-height:1.6;">
    You do not have the necessary permissions to view this page. If you believe this is an error, please contact the System Administrator.
  </p>
  
  <div style="display:flex; gap:10px; justify-content:center;">
    <a href="admin_dashboard.php" class="btn btn-primary">Go to Dashboard</a>
    <?php if (isset($_SERVER['HTTP_REFERER'])): ?>
      <a href="<?= htmlspecialchars($_SERVER['HTTP_REFERER']) ?>" class="btn btn-ghost">Go Back</a>
    <?php endif; ?>
  </div>
</div>

</body>
</html>