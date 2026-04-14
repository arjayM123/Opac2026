<?php
session_start();
include '../db_connect.php';
include '_layout.php';


// Get total books
$query = "SELECT COUNT(*) as total_books FROM books";
$result = mysqli_query($conn, $query);
$totalBooks = mysqli_fetch_assoc($result)['total_books'];

// Get total unique authors
$query = "SELECT COUNT(DISTINCT author) as total_authors FROM books";
$result = mysqli_query($conn, $query);
$totalAuthors = mysqli_fetch_assoc($result)['total_authors'];

// Get total borrowed books
$query = "SELECT COUNT(*) as borrowed_books FROM borrowers WHERE status = 'borrowed'";
$result = mysqli_query($conn, $query);
$borrowedBooks = mysqli_fetch_assoc($result)['borrowed_books'];
?>

<style>
    .dashboard {
        max-width: 1200px;
        margin: 0 auto;
    }

    .dashboard-title {
        color: #333;
        margin-bottom: 30px;
        text-align: center;
    }

    .cards-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .card-title {
        color: #666;
        font-size: 1.1em;
        margin-bottom: 10px;
    }

    .card-value {
        color: #2c3e50;
        font-size: 2em;
        font-weight: bold;
    }

    .books-card {
        border-left: 4px solid #3498db;
    }

    .authors-card {
        border-left: 4px solid #2ecc71;
    }

    .borrowed-card {
        border-left: 4px solid #e74c3c;
    }

    .button {
        display: inline-block;
        margin-top: 10px;
        padding: 6px 12px;
        background-color: #3498db;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s;
    }

    .button:hover {
        background-color: #2980b9;
    }
</style>
<div class="main-content">
    <div class="form-container">
        <div class="dashboard">
            <h1 class="dashboard-title">Library Dashboard</h1>

            <div class="cards-container">
                <div class="card books-card">
                    <h2 class="card-title">Total Books</h2>
                    <div class="card-value" id="totalBooks"><?php echo $totalBooks; ?></div>
                    <a href="formated_list.php" class="button">View</a>
                </div>

                <div class="card authors-card">
                    <h2 class="card-title">Total Authors</h2>
                    <div class="card-value" id="totalAuthors"><?php echo $totalAuthors; ?></div>
                    <a href="autor_list.php" class="button">View</a>
                </div>

                <div class="card borrowed-card">
                    <h2 class="card-title">Borrowed Books</h2>
                    <div class="card-value" id="borrowedBooks"><?php echo $borrowedBooks; ?></div>
                    <a href="borrowed_record.php" class="button">View</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Add animations when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Card animations
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
        });

        // Animate cards one by one
        setTimeout(() => {
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        }, 100);
    });
</script>