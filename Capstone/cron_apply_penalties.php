<?php
/**
 * Automated Penalty Logic
 * This script should be set up to run daily via Cron Job.
 */
require_once __DIR__ . '/../includes/config.php';

$db = getDB();
$today = date('Y-m-d');

// 1. Find all active loans that are past their due date
$sql = "SELECT l.id, l.balance, l.monthly_due, l.due_date, lt.penalty_rate 
        FROM loans l
        JOIN loan_applications la ON l.application_id = la.id
        JOIN loan_types lt ON la.loan_type_id = lt.id
        WHERE l.status = 'active' AND l.due_date < ?";

$stmt = $db->prepare($sql);
$stmt->bind_param('s', $today);
$stmt->execute();
$overdueLoans = $stmt->get_result();

while ($loan = $overdueLoans->fetch_assoc()) {
    // 2. Calculate Penalty (Percentage of the monthly due)
    $penaltyAmount = round($loan['monthly_due'] * ($loan['penalty_rate'] / 100), 2);
    
    // 3. Update Loan: Increase balance, record penalty, and set next due date
    $newDueDate = date('Y-m-d', strtotime($loan['due_date'] . ' +1 month'));
    
    $updateSql = "UPDATE loans SET 
                  balance = balance + ?, 
                  accrued_penalty = accrued_penalty + ?, 
                  due_date = ? 
                  WHERE id = ?";
    $upd = $db->prepare($updateSql);
    $upd->bind_param('ddsi', $penaltyAmount, $penaltyAmount, $newDueDate, $loan['id']);
    $upd->execute();
    
    echo "Applied ₱$penaltyAmount penalty to Loan #{$loan['id']}. Next due: $newDueDate\n";
}

$db->close();
?>