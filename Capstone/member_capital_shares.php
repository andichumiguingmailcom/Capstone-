<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Capital Shares – CoopIMS</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';
requireLogin('member');
$activePage = 'capital_shares';
$db = getDB();

$memberId = $_SESSION['member_id'] ?? 0;

// Get member's capital share
$capitalShare = $db->query("SELECT cs.*, CONCAT_WS(' ', u.first_name, u.last_name) as updated_by_name
    FROM capital_shares cs
    LEFT JOIN users u ON cs.updated_by = u.id
    WHERE cs.member_id = $memberId")->fetch_assoc();

// Get member info
$member = $db->query("SELECT * FROM members WHERE id = $memberId")->fetch_assoc();
?>

<?php include 'includes/member_sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="topbar-title">My Capital Shares</div>
    <div class="topbar-actions">
      <span class="text-muted text-sm">📅 <?= date('F j, Y') ?></span>
    </div>
  </div>

  <div class="page-body">
    <div class="card">
      <div class="card-header">
        <span class="card-title">Capital Share Information</span>
      </div>
      <div class="card-body">
        <div class="grid-2">
          <div>
            <h4 class="mb-3">Current Share Amount</h4>
            <div style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; padding: 24px; border-radius: var(--radius); text-align: center;">
              <div style="font-size: 2.5rem; font-weight: 800; margin-bottom: 8px;">
                ₱<?= number_format($capitalShare['amount'] ?? 0, 2) ?>
              </div>
              <div style="opacity: 0.9; font-size: 0.9rem;">
                Capital Share Contribution
              </div>
            </div>
          </div>

          <div>
            <h4 class="mb-3">Share Details</h4>
            <div style="display: grid; gap: 16px;">
              <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--border); border-radius: var(--radius);">
                <div>
                  <div class="fw-600">Member ID</div>
                  <div class="text-muted text-sm">Your unique identifier</div>
                </div>
                <div class="fw-600 text-primary"><?= htmlspecialchars($member['member_id']) ?></div>
              </div>

              <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--border); border-radius: var(--radius);">
                <div>
                  <div class="fw-600">Last Updated</div>
                  <div class="text-muted text-sm">When shares were last modified</div>
                </div>
                <div class="text-muted text-sm">
                  <?= $capitalShare['updated_at'] ? date('M j, Y H:i', strtotime($capitalShare['updated_at'])) : 'Not set' ?>
                </div>
              </div>

              <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--border); border-radius: var(--radius);">
                <div>
                  <div class="fw-600">Updated By</div>
                  <div class="text-muted text-sm">Who last modified your shares</div>
                </div>
                <div class="text-muted text-sm">
                  <?= htmlspecialchars($capitalShare['updated_by_name'] ?? 'System') ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div style="margin-top: 32px; padding: 24px; background: var(--bg-light); border-radius: var(--radius);">
          <h4 class="mb-3">About Capital Shares</h4>
          <div style="color: var(--text-muted); line-height: 1.6;">
            <p>Capital shares represent your ownership stake in the cooperative. These shares contribute to the cooperative's capital base and entitle you to certain benefits including:</p>
            <ul style="margin-left: 20px; margin-top: 12px;">
              <li>Voting rights in cooperative decisions</li>
              <li>Share of annual dividends (when declared)</li>
              <li>Priority access to cooperative services</li>
              <li>Collateral for loan applications</li>
            </ul>
            <p style="margin-top: 16px;">Your initial capital share of ₱5,000 was set during your membership application. Additional contributions can be made through the cooperative's administration.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="js/app.js"></script>
</body>
</html>