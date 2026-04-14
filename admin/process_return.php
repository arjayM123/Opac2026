<?php
// process_return.php
include '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_POST['borrower_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Borrower ID is required']);
    exit;
}

$borrower_id = (int)$_POST['borrower_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Update the borrower record
    $update_borrower = $conn->query("
        UPDATE borrowers 
        SET status = 'returned', 
            return_date = NOW() 
        WHERE id = $borrower_id
    ");

    if (!$update_borrower) {
        throw new Exception("Error updating borrower record");
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>