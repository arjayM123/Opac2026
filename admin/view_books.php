<?php
include '../db_connect.php';
include '_layout.php';

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = '';
if ($search) {
    $where = "WHERE b.title LIKE '%$search%' 
              OR b.author LIKE '%$search%' 
              OR b.isbn_issn LIKE '%$search%'
              OR b.classification_number LIKE '%$search%'
              OR b.call_number LIKE '%$search%'
              OR b.accession_number LIKE '%$search%'
              OR d.department_name LIKE '%$search%'
              OR b.location LIKE '%$search%'";
}

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM books b 
              LEFT JOIN departments d ON b.department_id = d.id 
              $where";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Get books with pagination
$sql = "SELECT b.*, d.department_name 
        FROM books b 
        LEFT JOIN departments d ON b.department_id = d.id 
        $where 
        ORDER BY b.created_at DESC 
        LIMIT $offset, $items_per_page";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Library Catalog System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div class="main-content">
        <div class="books-container">
            <div class="header">
                <h2>Library Catalog System</h2>
                <a href="add_book.php" class="add-book-btn">Add New Book</a>
            </div>

            <div class="search-container">
                <form method="GET" class="search-form">
                    <input type="text"
                        name="search"
                        placeholder="Search by title, author, ISBN/ISSN, classification, call number, accession, department, location"
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Place of Publication</th>
                            <th>Publisher</th>
                            <th>Date of Publication</th>
                            <th>Edition</th>
                            <th>ISBN/ISSN</th>
                            <th>Department</th>
                            <th>Location</th>
                            <th>Type of Material</th>
                            <th>Classification Number</th>
                            <th>Call Number</th>
                            <th>Accession Number</th>
                            <th>Copies</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['author']); ?></td>
                                    <td><?php echo htmlspecialchars($row['place_of_publication']); ?></td>
                                    <td><?php echo htmlspecialchars($row['publisher']); ?></td>
                                    <td><?php echo htmlspecialchars($row['date_of_publication']); ?></td>
                                    <td><?php echo htmlspecialchars($row['edition'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['isbn_issn'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['department_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['location'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['type_of_material']); ?></td>
                                    <td><?php echo htmlspecialchars($row['classification_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['call_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['accession_number']); ?></td>
                                    <td>
                                        <?php
                                        $copies = (int)$row['copies'];
                                        $badgeClass = $copies > 5 ? 'in-stock' : ($copies > 0 ? 'low-stock' : 'out-of-stock');
                                        $status = $copies > 5 ? 'Available' : ($copies > 0 ? 'Low Stock' : 'Unavailable');
                                        ?>
                                        <span class="copies-badge <?php echo $badgeClass; ?>">
                                            <?php echo $copies; ?> (<?php echo $status; ?>)
                                        </span>
                                    </td>
                                    <td>
    <a href="manage_book.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">
        <i class="fas fa-edit"></i>
    </a>
    <button onclick="deleteBook(<?php echo $row['id']; ?>)" class="action-btn delete-btn">
        <i class="fas fa-trash"></i>
    </button>
</td>

                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="15" style="text-align: center;">No books found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?>">&laquo;</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                            class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">&raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function deleteBook(id) {
            if (confirm('Are you sure you want to delete this book? This action cannot be undone.')) {
                window.location.href = 'delete_book.php?id=' + id;
            }
        }
    </script>
</body>

</html>

<style>
    .books-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .add-book-btn {
        padding: 10px 20px;
        background: #4CAF50;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s;
    }

    .add-book-btn:hover {
        background: #45a049;
    }

    .search-container {
        margin-bottom: 20px;
    }

    .search-form {
        display: flex;
        gap: 10px;
        max-width: 600px;
    }

    .search-container input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .search-btn {
        padding: 10px 20px;
        background: #1a1d21;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .search-btn:hover {
        background: #2c3038;
    }

    .table-responsive {
        overflow-x: auto;
        margin-bottom: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1200px;
    }

    th,
    td {
   
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
        font-size: 14px;
    }

    th {
        background-color: #f5f5f5;
        font-weight: bold;
        position: sticky;
        top: 0;
    }

    tr:hover {
        background-color: #f9f9f9;
    }

    .copies-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.85em;
    }

    .in-stock {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .low-stock {
        background: #fff3e0;
        color: #ef6c00;
    }

    .out-of-stock {
        background: #ffebee;
        color: #c62828;
    }

    .action-btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 5px;
        text-decoration: none;
        display: inline-block;
        transition: opacity 0.3s;
    }

    .action-btn:hover {
        opacity: 0.8;
    }

    .edit-btn {
        background: #4CAF50;
        color: white;
    }

    .delete-btn {
        background: #f44336;
        color: white;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 20px;
    }

    .pagination a {
        padding: 8px 12px;
        text-decoration: none;
        color: #333;
        border: 1px solid #ddd;
        border-radius: 4px;
        transition: all 0.3s;
    }

    .pagination a:hover {
        background: #f5f5f5;
    }

    .pagination .active {
        background: #1a1d21;
        color: white;
        border-color: #1a1d21;
    }

    .secondary-info {
        font-size: 0.85em;
        color: #666;
        margin-top: 4px;
    }

    @media (max-width: 768px) {
        .search-form {
            flex-direction: column;
        }

        .books-container {
            margin: 10px;
            padding: 10px;
        }

        .header {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>