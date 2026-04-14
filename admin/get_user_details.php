<?php
include '../db_connect.php';

header('Content-Type: application/json');

if (isset($_GET['id_number'])) {
    $id_number = $_GET['id_number'];
    
    $query = $conn->query("SELECT * FROM users WHERE id_number = '$id_number'");
    
    if ($query->num_rows > 0) {
        $user = $query->fetch_assoc();
        echo json_encode([
            'success' => true,
            'fullname' => $user['fullname'],
            'user_type' => $user['user_type'],
            'course' => $user['course'] ?? '',
            'year' => $user['year'] ?? '',
            'department' => $user['department'] ?? ''
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing ID number parameter'
    ]);
}

$conn->close();
?>