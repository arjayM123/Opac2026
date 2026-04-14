<?php
include '../db_connect.php';
include '_layout.php';

if (isset($_POST['submit'])) {
    $id_number = $_POST['id_number'];
    $accession_numbers = $_POST['accession_number']; // Now an array
    $due_dates = $_POST['due_date']; // Now an array
    $borrow_date = date('Y-m-d H:i:s');
    $success_count = 0;
    $error_messages = [];
    
    // Get user details from users table
    $user_query = $conn->query("SELECT * FROM users WHERE id_number = '$id_number'");
    
    if ($user_query->num_rows > 0) {
        $user = $user_query->fetch_assoc();
        $borrower_name = $user['fullname'];
        $borrower_type = $user['user_type'];
        $department = $user['department'] ?? '';
        $course = $user['course'] ?? '';
        $year = $user['year'] ?? '';
        
        // Process each book
        for ($i = 0; $i < count($accession_numbers); $i++) {
            if (empty($accession_numbers[$i])) continue; // Skip empty entries
            
            $accession_number = $accession_numbers[$i];
            $due_date = $due_dates[$i];
            
            // Check if book is available
            $check_book = $conn->query("SELECT id, copies FROM books WHERE accession_number = '$accession_number' AND copies > 0");
            
            if ($check_book->num_rows > 0) {
                $book = $check_book->fetch_assoc();
                $book_id = $book['id'];
                
                // Insert borrowing record
                $stmt = $conn->prepare("INSERT INTO borrowers (borrower_name, borrower_id, borrower_type, book_id, accession_number, borrow_date, due_date, department, course, year) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssissssss", $borrower_name, $id_number, $borrower_type, $book_id, $accession_number, $borrow_date, $due_date, $department, $course, $year);
                
                if ($stmt->execute()) {
                    $conn->query("UPDATE books SET copies = copies - 1 WHERE id = $book_id");
                    $success_count++;
                } else {
                    $error_messages[] = "Error borrowing book with accession number: $accession_number";
                }
            } else {
                $error_messages[] = "Book with accession number $accession_number is not available or invalid.";
            }
        }
        
        if ($success_count > 0) {
            echo "<div class='alert alert-success'>Successfully borrowed $success_count book(s)!</div>";
        }
        
        if (!empty($error_messages)) {
            echo "<div class='alert alert-danger'>" . implode("<br>", $error_messages) . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>User ID not found! Please register first.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multiple Book Borrowing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<div class="main-content">
    <div class="form-container">
        <div class="container">
            <h2 class="text-center mb-4">Multiple Book Borrowing System</h2>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Borrower ID Number</label>
                            <input type="text" class="form-control" name="id_number" id="id_number" required>
                            <div id="user-details" class="mt-2"></div>
                        </div>
                        
                        <div id="books-container">
                            <div class="book-entry card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Book Accession Number</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control book-accession" name="accession_number[]" required>
                                                <button type="button" class="btn btn-outline-danger remove-book" disabled><i class="bi bi-trash"></i></button>
                                            </div>
                                            <div class="book-details mt-2"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Return Due Date</label>
                                            <input type="datetime-local" class="form-control" name="due_date[]" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="button" id="add-book" class="btn btn-secondary"><i class="bi bi-plus-circle"></i> Add Another Book</button>
                        </div>
                        
                        <button type="submit" name="submit" class="btn btn-primary" id="borrow-btn" disabled>
                            Borrow Books
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const idInput = document.getElementById('id_number');
        const userDetailsDiv = document.getElementById('user-details');
        const borrowBtn = document.getElementById('borrow-btn');
        const addBookBtn = document.getElementById('add-book');
        const booksContainer = document.getElementById('books-container');
        
        let userValid = false;
        let bookEntries = document.querySelectorAll('.book-entry');
        
        // Check user ID
        idInput.addEventListener('input', async function() {
            if (this.value.length > 0) {
                try {
                    const response = await fetch(`get_user_details.php?id_number=${this.value}`);
                    const data = await response.json();
                    if (data.success) {
                        userDetailsDiv.innerHTML = `
                            <div class="alert alert-info">
                                <strong>Name:</strong> ${data.fullname}<br>
                                <strong>Type:</strong> ${data.user_type}
                                ${data.user_type === 'student' ? 
                                  `<br><strong>Course:</strong> ${data.course}<br><strong>Year:</strong> ${data.year}` : 
                                  `<br><strong>Department:</strong> ${data.department}`}
                            </div>`;
                        userValid = true;
                    } else {
                        userDetailsDiv.innerHTML = '<div class="alert alert-warning">User not found. Please register first.</div>';
                        userValid = false;
                    }
                    updateButtonState();
                } catch (error) {
                    userDetailsDiv.innerHTML = '<div class="alert alert-danger">Error checking user ID</div>';
                    userValid = false;
                    updateButtonState();
                }
            } else {
                userDetailsDiv.innerHTML = '';
                userValid = false;
                updateButtonState();
            }
        });
        
        // Add new book entry
        addBookBtn.addEventListener('click', function() {
            const bookEntry = document.querySelector('.book-entry').cloneNode(true);
            const inputs = bookEntry.querySelectorAll('input');
            inputs.forEach(input => { 
                input.value = '';
                if (input.classList.contains('book-accession')) {
                    input.addEventListener('input', checkBookAccession);
                }
            });
            
            const removeBtn = bookEntry.querySelector('.remove-book');
            removeBtn.disabled = false;
            removeBtn.addEventListener('click', function() {
                bookEntry.remove();
                updateBookEntries();
                updateButtonState();
            });
            
            const bookDetailsDiv = bookEntry.querySelector('.book-details');
            bookDetailsDiv.innerHTML = '';
            
            booksContainer.appendChild(bookEntry);
            updateBookEntries();
        });
        
        // Initial setup for the first book entry
        const firstAccessionInput = document.querySelector('.book-accession');
        firstAccessionInput.addEventListener('input', checkBookAccession);
        
        // Function to check book accession number
        async function checkBookAccession() {
            const bookDetailsDiv = this.closest('.row').querySelector('.book-details');
            if (this.value.length > 0) {
                try {
                    const response = await fetch(`get_book_details.php?accession_number=${this.value}`);
                    const data = await response.json();
                    if (data.success) {
                        bookDetailsDiv.innerHTML = `
                            <div class="alert alert-info">
                                <strong>Title:</strong> ${data.title}<br>
                                <strong>Author:</strong> ${data.author || 'N/A'}<br>
                                <strong>Available Copies:</strong> ${data.copies}
                            </div>`;
                        this.dataset.valid = data.copies > 0 ? 'true' : 'false';
                        if (data.copies <= 0) {
                            bookDetailsDiv.innerHTML += '<div class="alert alert-warning">No copies available!</div>';
                        }
                    } else {
                        bookDetailsDiv.innerHTML = '<div class="alert alert-warning">Book not found</div>';
                        this.dataset.valid = 'false';
                    }
                    updateButtonState();
                } catch (error) {
                    bookDetailsDiv.innerHTML = '<div class="alert alert-danger">Error searching for book</div>';
                    this.dataset.valid = 'false';
                    updateButtonState();
                }
            } else {
                bookDetailsDiv.innerHTML = '';
                this.dataset.valid = 'false';
                updateButtonState();
            }
        }
        
        // Update book entries reference
        function updateBookEntries() {
            bookEntries = document.querySelectorAll('.book-entry');
            // If there's only one book left, disable its remove button
            if (bookEntries.length === 1) {
                bookEntries[0].querySelector('.remove-book').disabled = true;
            } else {
                bookEntries.forEach(entry => {
                    entry.querySelector('.remove-book').disabled = false;
                });
            }
        }
        
        // Enable/disable borrow button based on validation
        function updateButtonState() {
            const accessionInputs = document.querySelectorAll('.book-accession');
            let allBooksValid = true;
            
            // Check if at least one book is valid
            if (accessionInputs.length === 0) {
                allBooksValid = false;
            } else {
                let atleastOneValid = false;
                accessionInputs.forEach(input => {
                    if (input.value && input.dataset.valid === 'true') {
                        atleastOneValid = true;
                    }
                });
                allBooksValid = atleastOneValid;
            }
            
            borrowBtn.disabled = !(userValid && allBooksValid);
        }
        
        // Form validation
        const form = document.querySelector('.needs-validation');
        form.addEventListener('submit', function(event) {
            let formValid = form.checkValidity();
            let atleastOneValidBook = false;
            
            const accessionInputs = document.querySelectorAll('.book-accession');
            accessionInputs.forEach(input => {
                if (input.value && input.dataset.valid === 'true') {
                    atleastOneValidBook = true;
                }
            });
            
            if (!formValid || !userValid || !atleastOneValidBook) {
                event.preventDefault();
                event.stopPropagation();
                
                if (!atleastOneValidBook) {
                    alert('Please add at least one valid book to borrow');
                }
            }
            
            form.classList.add('was-validated');
        }, false);
    });
</script>
</body>
</html>

<style>
            .form-container {
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .borrower-type-toggle {
            margin-bottom: 2rem;
            text-align: center;
        }
        .borrower-type-toggle .btn {
            width: 120px;
            margin: 0 10px;
        }
        .form-card {
            display: none;
        }
        .form-card.active {
            display: block;
        }
        .book-search-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #dee2e6;
        }
        .form-label {
            font-weight: 500;
            color: #555;
        }
        .form-control:focus {
            border-color:rgb(63, 255, 5);
            box-shadow: 0 0 2px rgb(20, 255, 3);
        }

    </style>