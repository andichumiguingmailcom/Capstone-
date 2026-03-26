<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Audit Logs – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('general_manager');
$activePage = 'audit';
$db = getDB();

$logs = $db->query("SELECT al.*, CONCAT_WS(' ', u.first_name, u.last_name) as full_name 
    FROM audit_logs al LEFT JOIN users u ON al.user_id=u.id ORDER BY al.logged_at DESC LIMIT 100");
?>

<?php include 'includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar"><div class="topbar-title">Audit Logs</div></div>
  <div class="page-body">
    <div class="card">
      <div class="card-header"><span class="card-title">System Activity Log (Latest 100)</span></div>
      <div class="card-body">
        <div class="search-bar">
          <input type="text" id="logSearch" class="search-input" placeholder="Search logs..." oninput="filterTable('logSearch','logTable')">
        </div>
        <div class="table-wrap">
          <table id="logTable">
            <thead><tr><th>User</th><th>Action</th><th>Table</th><th>Record ID</th><th>Details</th><th>IP</th><th>Date/Time</th></tr></thead>
            <tbody>
              <?php while ($log = $logs->fetch_assoc()): ?>
              <tr>
                <td class="fw-600"><?= htmlspecialchars($log['full_name'] ?? 'System') ?></td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($log['action']) ?></span></td>
                <td class="text-muted text-sm"><?= $log['table_name'] ?? '—' ?></td>
                <td class="text-muted text-sm"><?= $log['record_id'] ?? '—' ?></td>
                <td class="text-muted text-sm"><?= htmlspecialchars(substr($log['details'] ?? '', 0, 60)) ?></td>
                <td class="text-muted text-sm"><?= $log['ip_address'] ?></td>
                <td><?= date('M j, Y H:i', strtotime($log['logged_at'])) ?></td>
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
</body>
</html>
