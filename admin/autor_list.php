<?php include '_layout.php';
include '../db_connect.php';

// Get unique authors with book counts
$query = "SELECT author, COUNT(*) as book_count 
          FROM books 
          GROUP BY author 
          ORDER BY author ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "Query failed: " . mysqli_error($conn);
}
?>

<style>
    .search-container {
        margin-bottom: 20px;
    }

    #searchInput {
        width: 100%;
        padding: 12px;
        font-size: 16px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    .authors-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }

    .author-card {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 15px;
        transition: transform 0.2s;
    }

    .author-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .author-name {
        font-size: 18px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 8px;
    }

    .book-count {
        color: #6c757d;
        font-size: 14px;
    }

    .sort-container {
        margin-bottom: 20px;
    }

    .sort-btn {
        padding: 8px 16px;
        margin-right: 10px;
        border: none;
        border-radius: 4px;
        background-color: #007bff;
        color: white;
        cursor: pointer;
    }

    .sort-btn:hover {
        background-color: #0056b3;
    }

    .no-results {
        text-align: center;
        padding: 20px;
        color: #6c757d;
    }

    .form-container {
        max-width: 1000px;
        margin: auto;
        padding: 30px;
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
</style>
<div class="main-content">
    <div class="form-container">
        <div class="container">
            <h1>Library Authors</h1>

            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Search authors...">
            </div>

            <div class="sort-container">
                <button class="sort-btn" onclick="sortAuthors('name')">Sort by Name</button>
                <button class="sort-btn" onclick="sortAuthors('count')">Sort by Book Count</button>
            </div>

            <div class="authors-grid">
                <?php
                while ($author = mysqli_fetch_assoc($result)): ?>
                    <div class="author-card" data-author="<?php echo htmlspecialchars($author['author']); ?>" data-count="<?php echo $author['book_count']; ?>">
                        <div class="author-name"><?php echo htmlspecialchars($author['author']); ?></div>
                        <div class="book-count"><?php echo $author['book_count']; ?> book<?php echo $author['book_count'] > 1 ? 's' : ''; ?></div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
        <script>
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            const authorCards = document.querySelectorAll('.author-card');

            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                let hasResults = false;

                authorCards.forEach(card => {
                    const authorName = card.getAttribute('data-author').toLowerCase();
                    if (authorName.includes(searchTerm)) {
                        card.style.display = '';
                        hasResults = true;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Show no results message if needed
                const existingNoResults = document.querySelector('.no-results');
                if (!hasResults && !existingNoResults) {
                    const noResults = document.createElement('div');
                    noResults.className = 'no-results';
                    noResults.textContent = 'No authors found';
                    document.querySelector('.authors-grid').appendChild(noResults);
                } else if (hasResults && existingNoResults) {
                    existingNoResults.remove();
                }
            });

            // Sorting functionality
            function sortAuthors(criteria) {
                const grid = document.querySelector('.authors-grid');
                const cards = Array.from(document.querySelectorAll('.author-card'));

                cards.sort((a, b) => {
                    if (criteria === 'name') {
                        return a.getAttribute('data-author').localeCompare(b.getAttribute('data-author'));
                    } else if (criteria === 'count') {
                        return parseInt(b.getAttribute('data-count')) - parseInt(a.getAttribute('data-count'));
                    }
                });

                grid.innerHTML = '';
                cards.forEach(card => grid.appendChild(card));
            }
        </script>