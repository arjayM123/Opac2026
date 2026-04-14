<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Check if this is an AJAX request
if (!isset($_GET['ajax']) || $_GET['ajax'] !== 'true') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

// Initialize variables
$search = "";
$sort_by = "author_title";
$sort_order = "ASC";

// Pagination settings
$records_per_page = isset($_GET['records_per_page']) ? intval($_GET['records_per_page']) : 10;
if ($records_per_page < 1) $records_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

// Get search parameters
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}
if (isset($_GET['sort_by'])) {
    $sort_by = trim($_GET['sort_by']);
}
if (isset($_GET['sort_order'])) {
    $sort_order = trim($_GET['sort_order']);
}

// Validate sort parameters
$valid_sort_fields = ['author_title', 'call_no', 'accession_no', 'volume'];
if (!in_array($sort_by, $valid_sort_fields)) {
    $sort_by = 'author_title';
}
if ($sort_order != 'DESC') {
    $sort_order = 'ASC';
}

// Build SQL query base for both total count and actual results
$sql_base = "FROM import WHERE 1=1";
$params = [];
$types = "";

// Add search conditions
if (!empty($search)) {
    $sql_base .= " AND (call_no LIKE ? OR accession_no LIKE ? OR author_title LIKE ?)";
    $search_pattern = "%$search%";
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $params[] = $search_pattern;

    $types .= "sss";
}

// Count total records for pagination
$count_sql = "SELECT COUNT(*) as total " . $sql_base;
$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get actual records with pagination
$sql = "SELECT * " . $sql_base . " ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

// Add pagination parameters
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Prepare records array
$records = [];
while ($row = $result->fetch_assoc()) {
    $records[] = [
        'call_no' => $row['call_no'],
        'accession_no' => $row['accession_no'],
        'author_title' => $row['author_title'],
        'title' => $row['title'],
        'volume' => $row['volume']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'page' => $page,
    'total_pages' => $total_pages,
    'total_records' => $total_records,
    'records' => $records
]);