<?php
session_start();                                                                                                                     
require '../db_connect.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

// Handle pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $search_condition = "WHERE (call_no LIKE '%$search_safe%' OR accession_no LIKE '%$search_safe%' OR 
                         author_title LIKE '%$search_safe%' OR title LIKE '%$search_safe%' OR volume LIKE '%$search_safe%')";
}

// Get total records for pagination
$count_query = "SELECT COUNT(*) as total FROM import $search_condition";
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get records for current page
$query = "SELECT * FROM import $search_condition ORDER BY id DESC LIMIT $offset, $records_per_page";
$records = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}

// Handle single record deletion
if (isset($_POST['delete_record'])) {
    $id = intval($_POST['delete_record']);
    $stmt = $conn->prepare("DELETE FROM import WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Record deleted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting record: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
    
    // Redirect to prevent form resubmission
    header("Location: manage-file.php");
    exit;
}

// Handle bulk delete
if (isset($_POST['bulk_delete']) && isset($_POST['selected_records'])) {
    $selected = $_POST['selected_records'];
    $deleted = 0;
    
    foreach ($selected as $id) {
        $id = intval($id);
        $stmt = $conn->prepare("DELETE FROM import WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $deleted++;
        }
    }
    
    if ($deleted > 0) {
        $_SESSION['message'] = "$deleted record(s) deleted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "No records were deleted.";
        $_SESSION['message_type'] = "error";
    }
    
    // Redirect to prevent form resubmission
    header("Location: manage-file.php");
    exit;
}

// Handle individual record update
if (isset($_POST['update_record'])) {
    $id = intval($_POST['record_id']);
    $call_no = trim($_POST['call_no']);
    $accession_no = trim($_POST['accession_no']);
    $author_title = trim($_POST['author_title']);
    $title = trim($_POST['title']);
    $volume = trim($_POST['volume']);
    
    $stmt = $conn->prepare("UPDATE import SET call_no = ?, accession_no = ?, author_title = ?, title = ?, volume = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $call_no, $accession_no, $author_title, $title, $volume, $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Record updated successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating record: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
    
    // Redirect to prevent form resubmission
    header("Location: manage-file.php");
    exit;
}

// Export Function
if (isset($_POST['export'])) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $sheet->setCellValue('A1', 'CALL NO.')
          ->setCellValue('B1', 'ACCESSION NO.')
          ->setCellValue('C1', 'AUTHOR/TITLE OF BOOK')
          ->setCellValue('D1', 'TITLE')
          ->setCellValue('E1', 'VOLUME');
    
    // Modify the export query to include the search if present
    $export_query = "SELECT call_no, accession_no, author_title, title, volume FROM import $search_condition";
    $export_result = $conn->query($export_query);
    
    $rowNum = 2;
    while ($export_result && $row = $export_result->fetch_assoc()) {
        $sheet->setCellValue("A$rowNum", $row['call_no'])
              ->setCellValue("B$rowNum", $row['accession_no'])
              ->setCellValue("C$rowNum", $row['author_title'])
              ->setCellValue("D$rowNum", $row['title'])
              ->setCellValue("E$rowNum", $row['volume']);
        $rowNum++;
    }
    
    $writer = new XlsxWriter($spreadsheet);
    $fileName = "book_data_" . date('Y-m-d') . ".xlsx";
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=$fileName");
    $writer->save("php://output");
    exit;
}

// Export selected records
if (isset($_POST['export_selected']) && isset($_POST['selected_records'])) {
    $selected = $_POST['selected_records'];
    
    if (count($selected) > 0) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'CALL NO.')
              ->setCellValue('B1', 'ACCESSION NO.')
              ->setCellValue('C1', 'AUTHOR/TITLE OF BOOK')
              ->setCellValue('D1', 'TITLE')
              ->setCellValue('E1', 'VOLUME');
        
        // Create a comma-separated list of IDs safely
        $ids = implode(',', array_map('intval', $selected));
        $export_query = "SELECT call_no, accession_no, author_title, title, volume FROM import WHERE id IN ($ids)";
        $export_result = $conn->query($export_query);
        
        $rowNum = 2;
        while ($export_result && $row = $export_result->fetch_assoc()) {
            $sheet->setCellValue("A$rowNum", $row['call_no'])
                  ->setCellValue("B$rowNum", $row['accession_no'])
                  ->setCellValue("C$rowNum", $row['author_title'])
                  ->setCellValue("D$rowNum", $row['title'])
                  ->setCellValue("E$rowNum", $row['volume']);
            $rowNum++;
        }
        
        $writer = new XlsxWriter($spreadsheet);
        $fileName = "selected_books_" . date('Y-m-d') . ".xlsx";
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment; filename=$fileName");
        $writer->save("php://output");
        exit;
    } else {
        $_SESSION['message'] = "No records selected for export.";
        $_SESSION['message_type'] = "error";
        header("Location: manage-file.php");
        exit;
    }
}
require '_layout.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Records</title>

</head>
<body>
<div class="main-content">
    <div class="container">
        <div class="nav-actions">
            <h2>Book Management System - Manage Records</h2>
            <div>
                <a href="import.php" class="btn btn-secondary">Import Data</a>
            </div>
        </div>
        
        <!-- Display messages -->
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert-message <?php echo $_SESSION['message_type']; ?>">
            <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            ?>
        </div>
        <?php endif; ?>

        <!-- Search Form -->
        <div class="card">
            <div class="card-header">
                <h4>Search Records</h4>
            </div>
            <div class="card-body">
                <form action="" method="get" class="search-form">
                    <input type="text" name="search" placeholder="Search by any field..." class="form-control" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if (!empty($search)): ?>
                    <a href="manage-file.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- Database Records -->
        <div class="card">
            <div class="card-header">
                <h4>Record Management</h4>
                <div>
                    <form action="" method="post" style="display: inline;">
                        <button type="submit" name="export" class="btn btn-info">Export All</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="post" id="records-form">
                    <div class="table-header-actions">
                        <div>
                            <button type="button" class="btn btn-primary" onclick="selectAll()">Select All</button>
                            <button type="button" class="btn btn-secondary" onclick="deselectAll()">Deselect All</button>
                        </div>
                        <div>
                            <button type="submit" name="export_selected" class="btn btn-info" onclick="return confirmSelectedAction('export')">Export Selected</button>
                            <button type="submit" name="bulk_delete" class="btn btn-danger" onclick="return confirmSelectedAction('delete')">Delete Selected</button>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <div class="scrollable-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th width="40">#</th>
                                        <th width="50">Select</th>
                                        <th>Call No.</th>
                                        <th>Accession No.</th>
                                        <th>Author/Title</th>
                                        <th>Title</th>
                                        <th>Volume</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($records) > 0): ?>
                                        <?php foreach ($records as $index => $record): ?>
                                            <tr>
                                                <td><?php echo $offset + $index + 1; ?></td>
                                                <td>
                                                    <input type="checkbox" name="selected_records[]" value="<?php echo $record['id']; ?>" class="record-checkbox">
                                                </td>
                                                <td><?php echo htmlspecialchars($record['call_no']); ?></td>
                                                <td><?php echo htmlspecialchars($record['accession_no']); ?></td>
                                                <td><?php echo htmlspecialchars($record['author_title']); ?></td>
                                                <td><?php echo htmlspecialchars($record['title']); ?></td>
                                                <td><?php echo htmlspecialchars($record['volume']); ?></td>
                                                <td class="actions">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            onclick="openEditModal(<?php echo $record['id']; ?>, 
                                                            '<?php echo addslashes($record['call_no']); ?>', 
                                                            '<?php echo addslashes($record['accession_no']); ?>', 
                                                            '<?php echo addslashes($record['author_title']); ?>', 
                                                            '<?php echo addslashes($record['title']); ?>', 
                                                            '<?php echo addslashes($record['volume']); ?>')">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="confirmDelete(<?php echo $record['id']; ?>)">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No records found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-secondary">First</a>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-secondary">Previous</a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-secondary">Next</a>
                        <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-secondary">Last</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>Edit Record</h3>
        <form action="" method="post">
            <input type="hidden" id="edit_record_id" name="record_id">
            <div class="form-group">
                <label for="edit_call_no">Call No.:</label>
                <input type="text" id="edit_call_no" name="call_no" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_accession_no">Accession No.:</label>
                <input type="text" id="edit_accession_no" name="accession_no" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_author_title">Author/Title:</label>
                <input type="text" id="edit_author_title" name="author_title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_title">Title:</label>
                <input type="text" id="edit_title" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_volume">Volume:</label>
                <input type="text" id="edit_volume" name="volume" class="form-control">
            </div>
            <div class="form-actions">
                <button type="submit" name="update_record" class="btn btn-primary">Update Record</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" action="" method="post" style="display: none;">
    <input type="hidden" id="delete_record_id" name="delete_record">
</form>

<script>
// JavaScript functions for modal and actions
function openEditModal(id, call_no, accession_no, author_title, title, volume) {
    document.getElementById('edit_record_id').value = id;
    document.getElementById('edit_call_no').value = call_no;
    document.getElementById('edit_accession_no').value = accession_no;
    document.getElementById('edit_author_title').value = author_title;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_volume').value = volume;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this record?')) {
        document.getElementById('delete_record_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function selectAll() {
    var checkboxes = document.querySelectorAll('.record-checkbox');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = true;
    });
}

function deselectAll() {
    var checkboxes = document.querySelectorAll('.record-checkbox');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = false;
    });
}

function confirmSelectedAction(action) {
    var checkboxes = document.querySelectorAll('.record-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one record.');
        return false;
    }
    
    if (action === 'delete') {
        return confirm('Are you sure you want to delete the selected records?');
    }
    return true;
}

// Close the modal when clicking outside of it
window.onclick = function(event) {
    var modal = document.getElementById('editModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>
</body>
</html>
<style>
        :root {
            --primary-color: #004d40;
            --primary-dark:rgb(2, 51, 43);
            --secondary-color: #e74c3c;
            --secondary-dark: #c0392b;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --light-bg: #ecf0f1;
            --card-bg: #ffffff;
            --border-color: #ddd;
            --text-color: #333;
            --text-muted: #7f8c8d;
        }
        
        .container {
            width: 100%;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        h2 {
            margin-bottom: 20px;
            color: var(--primary-dark);
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h4 {
            margin: 0;
        }
        
        .text-muted {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -10px;
            margin-left: -10px;
        }
        
        .form-row .form-group {
            flex: 0 0 calc(50% - 20px);
            margin: 0 10px 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .search-form {
            display: flex;
            margin-bottom: 20px;
        }
        
        .search-form .form-control {
            flex-grow: 1;
            margin-right: 10px;
        }
        
        /* Button styles */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-align: center;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
        }
        
        .btn-info {
            background-color: #16a085;
            color: white;
        }
        
        .btn-info:hover {
            background-color: #1abc9c;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #d35400;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .btn-danger {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: var(--secondary-dark);
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
        
        /* Table styles */
        .table-container {
            margin-top: 20px;
            position: relative;
        }
        
        .scrollable-table {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            border: 1px solid var(--border-color);
            padding: 10px;
            text-align: left;
            font-size: 0.9rem;
        }
        
        table th {
            background-color: #f2f2f2;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        table tr:hover {
            background-color: #f1f1f1;
        }
        
        .author-title {
            max-width: 400px;
            word-wrap: break-word;
        }
        
        /* Action buttons */
        .action-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .action-buttons .btn {
            margin-right: 10px;
        }
        
        .action-buttons .btn:last-child {
            margin-right: 0;
        }

        /* Alert messages */
        .alert-message {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 5px solid;
        }
        
        .alert-message.success {
            background-color: #e8f5e9;
            border-left-color: var(--success-color);
            color: #1b5e20;
        }
        
        .alert-message.error {
            background-color: #ffebee;
            border-left-color: var(--secondary-color);
            color: #b71c1c;
        }
        
        /* Header table buttons */
        .table-header-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .table-header-actions .btn {
            margin-left: 10px;
        }
        
        /* Row actions */
        .row-actions {
            display: flex;
            justify-content: flex-end;
            gap: 5px;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid var(--border-color);
            color: var(--primary-color);
            border-radius: 4px;
        }
        
        .pagination a:hover {
            background-color: #f1f1f1;
        }
        
        .pagination .active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: var(--card-bg);
            margin: 10% auto;
            padding: 20px;
            border-radius: 4px;
            width: 60%;
            max-width: 600px;
            position: relative;
        }
        
        .close-modal {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }
        
        /* Navigation */
        .nav-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        /* Checkbox styles */
        .select-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        /* Responsive design */
        @media screen and (max-width: 768px) {
            .form-row .form-group {
                flex: 0 0 100%;
                margin: 0 0 15px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .action-buttons div {
                display: flex;
                gap: 10px;
            }
            
            .modal-content {
                width: 90%;
                margin: 20% auto;
            }
        }
    </style>