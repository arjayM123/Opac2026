<?php include '_layout.php';
include '../db_connect.php'; ?>

<style>
    .form-container {
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    /* General table styling */
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
        background-color: transparent;
    }

    .table th,
    .table td {
        padding: 0.75rem;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
    }

    .table thead th {
        vertical-align: bottom;
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
        color: #495057;
    }

    .table tbody+tbody {
        border-top: 2px solid #dee2e6;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.075);
    }

    /* Specific styling for the borrowed books table */
    .table-borrowed {
        margin-top: 20px;
    }

    .table-borrowed th {
        background-color: #007bff;
        color: white;
    }

    .table-borrowed td {
        background-color: #f8f9fa;
    }

    .table-borrowed tr:hover td {
        background-color: #e9ecef;
    }

    /* Previous CSS styles remain the same */
    .btn-return {
        padding: 5px 10px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .btn-return:hover {
        background-color: #218838;
    }
    .overdue {
        color: #dc3545;
        font-weight: bold;
    }
</style>

<div class="main-content">
    <div class="form-container">
        <h3 class="mt-4">Overdue Books</h3>
        <table class="table table-borrowed table-striped table-hover">
            <thead>
                <tr>
                    <th>Borrower</th>
                    <th>ID</th>
                    <th>Book Title</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $borrowed = $conn->query("
                    SELECT b.*, bk.title 
                    FROM borrowers b 
                    JOIN books bk ON b.book_id = bk.id 
                    WHERE b.status IN ( 'overdue')
                    ORDER BY b.borrow_date DESC
                ");
                while ($row = $borrowed->fetch_assoc()):
                    // Check if book is overdue
                    $due_date = strtotime($row['due_date']);
                    $current_date = strtotime(date('Y-m-d H:i:s'));
                    $is_overdue = $due_date < $current_date;
                    
                    // Update status to overdue if past due date
                    if ($is_overdue && $row['status'] == 'borrowed') {
                        $conn->query("UPDATE borrowers SET status = 'overdue' WHERE id = " . $row['id']);
                        $row['return_status'] = 'overdue';
                    }
                ?>
                    <tr>
                        <td><?php echo $row['borrower_name'] ?> (<?php echo $row['borrower_type'] ?>)</td>
                        <td><?php echo $row['borrower_id'] ?></td>
                        <td><?php echo $row['title'] ?></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($row['borrow_date'])) ?></td>
                        <td class="<?php echo $is_overdue ? 'overdue' : '' ?>">
                            <?php echo date('M d, Y h:i A', strtotime($row['due_date'])) ?>
                        </td>
                        <td class="<?php echo $is_overdue ? 'overdue' : '' ?>">
                            <?php echo ucfirst($row['status']) ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
