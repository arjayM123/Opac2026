<?php
include '../db_connect.php';

if (isset($_GET['accession_number'])) {
    $accession_number = $_GET['accession_number'];
    
    $query = $conn->prepare("SELECT title, copies FROM books WHERE accession_number = ?");
    $query->bind_param("s", $accession_number);
    $query->execute();
    $result = $query->get_result();
    
    if ($book = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'title' => $book['title'],
            'copies' => $book['copies']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Book not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>