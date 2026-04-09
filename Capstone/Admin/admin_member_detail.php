<?php
require_once '../includes/config.php';
// The roles here should ideally match those allowed to view members in admin_members.php
// This ensures the AJAX endpoint is also protected.
requireLogin(['general_manager','book_keeper','collector','loan_officer']);
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$section = clean($_GET['section'] ?? 'default');

if (!$id) {
    // For AJAX requests, return an error message and appropriate HTTP status
    http_response_code(400); // Bad Request
    echo '<div class="text-center text-muted" style="padding:40px;">Invalid member ID.</div>';
    exit; // Stop execution to prevent rendering full page HTML
}

$member = $db->query("SELECT *, CONCAT_WS(' ', first_name, middle_name, last_name) as full_name,
    CONCAT_WS(', ', street, barangay, city, province) as address
    FROM members WHERE id=$id")->fetch_assoc();
if (!$member) {
    // For AJAX requests, return an error message and appropriate HTTP status
    http_response_code(404); // Not Found
    echo '<div class="text-center text-muted" style="padding:40px;">Member not found.</div>';
    exit; // Stop execution
}

// If viewing documents section, display documents only
if ($section === 'documents') {
    // Get regular documents
    $documents = $db->query("SELECT 'member' as source, doc_type, filename, filepath, uploaded_at FROM documents WHERE member_id=$id ORDER BY uploaded_at DESC");
    
    // Get pre-application documents by matching member details to pre_application
    $preAppDocs = $db->query("SELECT 'pre_app' as source, doc_type, filename, filepath, uploaded_at FROM pre_application_documents WHERE pre_application_id IN (
        SELECT id FROM pre_applications WHERE 
        first_name='" . $db->real_escape_string($member['first_name']) . "' AND 
        last_name='" . $db->real_escape_string($member['last_name']) . "' AND 
        email='" . $db->real_escape_string($member['email']) . "'
    ) ORDER BY uploaded_at DESC");
    
    // Combine results
    $allDocs = [];
    if ($documents) {
        while ($doc = $documents->fetch_assoc()) {
            $allDocs[] = $doc;
        }
    }
    if ($preAppDocs) {
        while ($doc = $preAppDocs->fetch_assoc()) {
            $allDocs[] = $doc;
        }
    }
    
    // Sort by upload date descending
    usort($allDocs, function($a, $b) {
        return strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']);
    });
    
    if (count($allDocs) > 0) {
        echo '<div style="padding: 20px;">';
        echo '<div class="table-wrap"><table>';
        echo '<thead><tr><th>Type</th><th>File</th><th>Source</th><th>Uploaded</th><th>Action</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($allDocs as $doc) {
            // Construct correct file path as absolute URL from web root
            if ($doc['source'] === 'pre_app') {
                $fileLink = '/Capstone-/Capstone/uploads/pre_applications/' . htmlspecialchars($doc['filename']);
            } else {
                $fileLink = '/Capstone-/Capstone/uploads/docs/' . htmlspecialchars($doc['filename']);
            }
            $fileName = htmlspecialchars($doc['filename']);
            $docType = htmlspecialchars($doc['doc_type'] ?? 'Document');
            $source = $doc['source'] === 'pre_app' ? 'Pre-Application' : 'Member';
            $uploadDate = date('M j, Y', strtotime($doc['uploaded_at']));
            
            echo "<tr>";
            echo "<td class='fw-600'>$docType</td>";
            echo "<td>$fileName</td>";
            echo "<td><span class='badge " . ($doc['source'] === 'pre_app' ? 'badge-blue' : 'badge-green') . "'>$source</span></td>";
            echo "<td class='text-muted text-sm'>$uploadDate</td>";
            echo "<td><a href='$fileLink' target='_blank' class='btn btn-sm btn-outline'>View</a></td>";
            echo "</tr>";
        }
        
        echo '</tbody></table></div>';
        echo '</div>';
    } else {
        echo '<div class="text-center text-muted" style="padding:40px;">No documents uploaded for this member.</div>';
    }
    exit;
}

// Member stats
$activeLoans = $db->query("SELECT COUNT(*) as c FROM loans WHERE member_id=$id AND status='active'")->fetch_assoc()['c'];
$totalLoans = $db->query("SELECT COUNT(*) as c FROM loan_applications WHERE member_id=$id")->fetch_assoc()['c'];
$totalPurchases = $db->query("SELECT COUNT(*) as c FROM sales WHERE member_id=$id")->fetch_assoc()['c'];
$totalPayments = $db->query("SELECT SUM(lp.amount) as s FROM loan_payments lp JOIN loans l ON lp.loan_id=l.id WHERE l.member_id=$id")->fetch_assoc()['s'] ?? 0;

// Recent loans
$loans = $db->query("SELECT la.*, lt.type_name, l.balance, l.status as loan_status FROM loan_applications la 
    JOIN loan_types lt ON la.loan_type_id=lt.id 
    LEFT JOIN loans l ON la.id=l.application_id 
    WHERE la.member_id=$id ORDER BY la.applied_at DESC LIMIT 10");

// Recent purchases
$purchases = $db->query("SELECT s.*, GROUP_CONCAT(p.name SEPARATOR ', ') as items
    FROM sales s LEFT JOIN sale_items si ON s.id=si.sale_id LEFT JOIN products p ON si.product_id=p.id
    WHERE s.member_id=$id GROUP BY s.id ORDER BY s.sale_date DESC LIMIT 10");

// Recent payments (limit 10 for modal display, can be adjusted if needed)
$payments = $db->query("SELECT lp.*, l.principal FROM loan_payments lp 
    JOIN loans l ON lp.loan_id=l.id WHERE l.member_id=$id ORDER BY lp.paid_at DESC LIMIT 10");
?>
<div class="modal-title"> <?= htmlspecialchars($member['full_name']) ?></div>
<div class="modal-body-content" style="max-height: 70vh; overflow-y: auto; padding-right: 15px;">
    <!-- MEMBER INFO -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header">
        <span class="card-title">👤 <?= htmlspecialchars($member['full_name']) ?> (<?= $member['member_id'] ?>)</span>
        <div>
          <?php $sb=['active'=>'badge-green','inactive'=>'badge-gray','suspended'=>'badge-red']; ?>
          <span class="badge <?= $sb[$member['status']] ?>"><?= ucfirst($member['status']) ?></span>
        </div>
      </div>
      <div class="card-body">
        <div class="grid-2">
          <div>
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <div class="text-lg fw-600"><?= htmlspecialchars($member['full_name']) ?></div>
            </div>
            <div class="form-group">
              <label class="form-label">Member ID</label>
              <div><?= $member['member_id'] ?></div>
            </div>
            <div class="form-group">
              <label class="form-label">Date Joined</label>
              <div><?= $member['date_joined'] ? date('F j, Y', strtotime($member['date_joined'])) : '—' ?></div>
            </div>
          </div>
          <div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <div><?= htmlspecialchars($member['email'] ?? '—') ?></div>
            </div>
            <div class="form-group">
              <label class="form-label">Phone</label>
              <div><?= htmlspecialchars($member['phone'] ?? '—') ?></div>
            </div>
            <div class="form-group">
              <label class="form-label">Address</label>
              <div><?= htmlspecialchars(trim($member['address'], ', ') ?: '—') ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- STATS -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
      <div class="stat-card blue"><span class="stat-icon">💳</span><div class="stat-value"><?= $activeLoans ?></div><div class="stat-label">Active Loans</div></div>
      <div class="stat-card gold"><span class="stat-icon">📋</span><div class="stat-value"><?= $totalLoans ?></div><div class="stat-label">Total Loan Apps</div></div>
      <div class="stat-card green"><span class="stat-icon">🛒</span><div class="stat-value"><?= $totalPurchases ?></div><div class="stat-label">Total Purchases</div></div>
      <div class="stat-card purple"><span class="stat-icon">💰</span><div class="stat-value">₱<?= number_format($totalPayments, 0) ?></div><div class="stat-label">Total Payments</div></div>
    </div>

    <div class="grid-2">
      <!-- LOANS -->
      <div class="card">
        <div class="card-header"><span class="card-title">Loan History</span></div>
        <div class="card-body">
          <?php if ($loans->num_rows > 0): ?>
            <?php while ($loan = $loans->fetch_assoc()): ?>
              <div style="border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;align-items:start;">
                  <div>
                    <div class="fw-600">₱<?= number_format($loan['amount'], 2) ?> - <?= htmlspecialchars($loan['type_name']) ?></div>
                    <div class="text-muted text-sm">Applied: <?= date('M j, Y', strtotime($loan['applied_at'])) ?> | Status: <?= ucfirst($loan['status']) ?></div>
                    <?php if ($loan['loan_status']): ?>
                      <div class="text-muted text-sm">Balance: ₱<?= number_format($loan['balance'], 2) ?> | Loan Status: <?= ucfirst($loan['loan_status']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center text-muted" style="padding:40px;">No loans found.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- PURCHASES -->
      <div class="card">
        <div class="card-header"><span class="card-title">Purchase History</span></div>
        <div class="card-body">
          <?php if ($purchases->num_rows > 0): ?>
            <?php while ($purchase = $purchases->fetch_assoc()): ?>
              <div style="border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;align-items:start;">
                  <div>
                    <div class="fw-600">₱<?= number_format($purchase['total'], 2) ?></div>
                    <div class="text-muted text-sm">Date: <?= date('M j, Y', strtotime($purchase['sale_date'])) ?> | Items: <?= htmlspecialchars($purchase['items'] ?? '—') ?></div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center text-muted" style="padding:40px;">No purchases found.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- PAYMENTS -->
    <div class="card" style="margin-top:24px;">
      <div class="card-header"><span class="card-title">Payment History</span></div>
      <div class="card-body">
        <?php if ($payments->num_rows > 0): ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th></tr>
              </thead>
              <tbody>
                <?php while ($payment = $payments->fetch_assoc()): ?>
                <tr>
                  <td><?= date('M j, Y H:i', strtotime($payment['paid_at'])) ?></td>
                  <td>₱<?= number_format($payment['amount'], 2) ?></td>
                  <td><?= ucfirst($payment['payment_method']) ?></td>
                  <td><?= htmlspecialchars($payment['reference_no'] ?? '—') ?></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center text-muted" style="padding:40px;">No payments found.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<div class="modal-footer">
    <button class="btn btn-ghost" onclick="closeModal('modal-member-details')">Close</button>
</div>
