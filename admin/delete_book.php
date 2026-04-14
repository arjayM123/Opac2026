<?php
include '../db_connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $sql = "DELETE FROM books WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Book deleted successfully!'); window.location.href='view_books.php';</script>";
    } else {
        echo "<script>alert('Error deleting book: " . $conn->error . "'); window.location.href='view_books.php';</script>";
    }
} else {
    echo "<script>alert('Invalid book ID!'); window.location.href='view_books.php';</script>";
}
?>