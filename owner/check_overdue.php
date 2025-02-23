<?php
// owner/check_overdue.php

require_once '../db/db.php';

// Fetch rentals that are past end_date and not in 'completed', 'returned', 'cancelled', or 'overdue' statuses
$sql = "SELECT id FROM rentals WHERE end_date < CURDATE() AND status NOT IN ('completed', 'returned', 'cancelled', 'overdue')";
$stmt = $conn->prepare($sql);
$stmt->execute();
$overdueRentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($overdueRentals as $rental) {
    $rentalId = $rental['id'];
    $updateStatusSql = "UPDATE rentals SET status = 'overdue', status = 'overdue' WHERE id = :rentalId";
    $updateStatusStmt = $conn->prepare($updateStatusSql);
    $updateStatusStmt->bindParam(':rentalId', $rentalId, PDO::PARAM_INT);
    $updateStatusStmt->execute();
}


?>