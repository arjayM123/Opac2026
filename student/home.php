<?php
session_start();
include '../db_connect.php';
include '_layout.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}



// Initialize variables
$search = "";
$author = "";
$publisher = "";
$year_from = "";
$year_to = "";
$material_type = "";
$department_id = "";
$location = "";
$sort_by = "title";
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
if (isset($_GET['author'])) {
    $author = trim($_GET['author']);
}
if (isset($_GET['publisher'])) {
    $publisher = trim($_GET['publisher']);
}
if (isset($_GET['year_from'])) {
    $year_from = trim($_GET['year_from']);
}
if (isset($_GET['year_to'])) {
    $year_to = trim($_GET['year_to']);
}
if (isset($_GET['material_type'])) {
    $material_type = trim($_GET['material_type']);
}
if (isset($_GET['department_id'])) {
    $department_id = trim($_GET['department_id']);
}
if (isset($_GET['location'])) {
    $location = trim($_GET['location']);
}
if (isset($_GET['sort_by'])) {
    $sort_by = trim($_GET['sort_by']);
}
if (isset($_GET['sort_order'])) {
    $sort_order = trim($_GET['sort_order']);
}

// Validate sort parameters
$valid_sort_fields = ['title', 'author', 'publisher', 'date_of_publication', 'copies'];
if (!in_array($sort_by, $valid_sort_fields)) {
    $sort_by = 'title';
}
if ($sort_order != 'DESC') {
    $sort_order = 'ASC';
}

// Build SQL query base for both total count and actual results
$sql_base = "FROM books WHERE 1=1";
$params = [];
$types = "";

// Add search conditions
if (!empty($search)) {
    $sql_base .= " AND (title LIKE ? OR author LIKE ? OR publisher LIKE ? OR isbn_issn LIKE ?)";
    $search_pattern = "%$search%";
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $types .= "ssss";
}

if (!empty($author)) {
    $sql_base .= " AND author LIKE ?";
    $params[] = "%$author%";
    $types .= "s";
}

if (!empty($publisher)) {
    $sql_base .= " AND publisher LIKE ?";
    $params[] = "%$publisher%";
    $types .= "s";
}

if (!empty($year_from)) {
    $sql_base .= " AND date_of_publication >= ?";
    $params[] = $year_from;
    $types .= "i";
}

if (!empty($year_to)) {
    $sql_base .= " AND date_of_publication <= ?";
    $params[] = $year_to;
    $types .= "i";
}

if (!empty($material_type)) {
    $sql_base .= " AND type_of_material = ?";
    $params[] = $material_type;
    $types .= "s";
}

if (!empty($department_id)) {
    $sql_base .= " AND department_id = ?";
    $params[] = $department_id;
    $types .= "i";
}

if (!empty($location)) {
    $sql_base .= " AND location = ?";
    $params[] = $location;
    $types .= "s";
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

// Fetch dropdown options for filters
$material_types_query = "SELECT DISTINCT type_of_material FROM books ORDER BY type_of_material";
$material_types_result = $conn->query($material_types_query);

$departments_query = "SELECT id, department_name FROM departments ORDER BY department_name";
$departments_result = $conn->query($departments_query);

$locations_query = "SELECT DISTINCT location FROM books WHERE location IS NOT NULL ORDER BY location";
$locations_result = $conn->query($locations_query);

// Function to generate pagination links with current search parameters
function generatePageLink($page_num, $current_params)
{
    $params = $current_params;
    $params['page'] = $page_num;
    return 'home.php?' . http_build_query($params);
}

// Get current search parameters for pagination links
$current_params = [];
if (!empty($search)) $current_params['search'] = $search;
if (!empty($author)) $current_params['author'] = $author;
if (!empty($publisher)) $current_params['publisher'] = $publisher;
if (!empty($year_from)) $current_params['year_from'] = $year_from;
if (!empty($year_to)) $current_params['year_to'] = $year_to;
if (!empty($material_type)) $current_params['material_type'] = $material_type;
if (!empty($department_id)) $current_params['department_id'] = $department_id;
if (!empty($location)) $current_params['location'] = $location;
if ($sort_by != 'title') $current_params['sort_by'] = $sort_by;
if ($sort_order != 'ASC') $current_params['sort_order'] = $sort_order;
if ($records_per_page != 10) $current_params['records_per_page'] = $records_per_page;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

</head>

<body>
    <div class="container">

        <div class="main-container">
            <!-- Sidebar with advanced search options -->
            <div class="sidebar" id="sidebar">
                <h3>
                    Advanced Search
                    <button type="button" class="reset-button" id="resetButton">
                        <i class="fas fa-undo"></i>
                    </button>
                </h3>
                <form method="GET" action="home.php" id="searchForm">
                    <div class="form-group">
                        <label for="search">Title:</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="form-group">
                        <label for="author">Author:</label>
                        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($author); ?>">
                    </div>

                    <div class="form-group">
                        <label for="publisher">Publisher:</label>
                        <input type="text" id="publisher" name="publisher" value="<?php echo htmlspecialchars($publisher); ?>">
                    </div>

                    <div class="form-group">
                        <label for="year_from">Year (From):</label>
                        <input type="number" id="year_from" name="year_from" value="<?php echo htmlspecialchars($year_from); ?>">
                    </div>

                    <div class="form-group">
                        <label for="year_to">Year (To):</label>
                        <input type="number" id="year_to" name="year_to" value="<?php echo htmlspecialchars($year_to); ?>">
                    </div>

                    <div class="form-group">
                        <label for="material_type">Material Type:</label>
                        <select id="material_type" name="material_type">
                            <option value="">All Types</option>
                            <?php
                            // Reset the result pointer
                            $material_types_result->data_seek(0);
                            while ($type = $material_types_result->fetch_assoc()):
                            ?>
                                <option value="<?php echo htmlspecialchars($type['type_of_material']); ?>" <?php echo ($material_type == $type['type_of_material']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['type_of_material']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="department_id">Department:</label>
                        <select id="department_id" name="department_id">
                            <option value="">All Departments</option>
                            <?php
                            // Reset the result pointer
                            $departments_result->data_seek(0);
                            while ($dept = $departments_result->fetch_assoc()):
                            ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo ($department_id == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="location">Location:</label>
                        <select id="location" name="location">
                            <option value="">All Locations</option>
                            <?php
                            // Reset the result pointer
                            $locations_result->data_seek(0);
                            while ($loc = $locations_result->fetch_assoc()):
                            ?>
                                <option value="<?php echo htmlspecialchars($loc['location']); ?>" <?php echo ($location == $loc['location']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($loc['location']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sort_by">Sort By:</label>
                        <select id="sort_by" name="sort_by">
                            <option value="title" <?php echo ($sort_by == 'title') ? 'selected' : ''; ?>>Title</option>
                            <option value="author" <?php echo ($sort_by == 'author') ? 'selected' : ''; ?>>Author</option>
                            <option value="publisher" <?php echo ($sort_by == 'publisher') ? 'selected' : ''; ?>>Publisher</option>
                            <option value="date_of_publication" <?php echo ($sort_by == 'date_of_publication') ? 'selected' : ''; ?>>Publication Year</option>
                            <option value="copies" <?php echo ($sort_by == 'copies') ? 'selected' : ''; ?>>Copies Available</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Sort Order:</label>
                        <select id="sort_order" name="sort_order">
                            <option value="ASC" <?php echo ($sort_order == 'ASC') ? 'selected' : ''; ?>>Ascending</option>
                            <option value="DESC" <?php echo ($sort_order == 'DESC') ? 'selected' : ''; ?>>Descending</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="records_per_page">Results Per Page:</label>
                        <select id="records_per_page" name="records_per_page">
                            <option value="5" <?php echo ($records_per_page == 5) ? 'selected' : ''; ?>>5</option>
                            <option value="10" <?php echo ($records_per_page == 10) ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo ($records_per_page == 25) ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo ($records_per_page == 50) ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo ($records_per_page == 100) ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>

                    <div class="button-container">
                        <button type="submit" class="button button-primary">Apply Filters</button>

                    </div>
                </form>
            </div>


            <!-- Main content area -->
            <div class="main-content">
                <div class="results-container">
                    <div class="results-header">
                        <h2>Library Catalog</h2>
                        <div class="search-container">
                        <a href="home.php" class="view-all" id="viewAllButton"><i class="fas fa-list"></i> View All</a>
                        <div class="view-all" id="advancedSearchToggle">
                                <i class="fas fa-search"></i> Advanced Search
                            </div>
                            <form method="GET" action="home.php" id="searchForm">
                                <div class="search-bar">
                                    <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                                    <i class="fas fa-search search-icon" onclick="document.getElementById('searchForm').submit();"></i>
                                </div>
                            </form>

                        </div>
                    </div>

                    <?php if ($result->num_rows > 0 || true): // Always show the table structure ?>
    <div class="responsive-table-container">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Acc No.</th>
                    <th>Class No.</th>
                    <th>Materials</th>
                    <th>Copies</th>
                </tr>
            </thead>
            <?php if ($result->num_rows > 0): // Only show tbody if there are results ?>
                <tbody>
                    <?php
                    $start_number = ($page - 1) * $records_per_page + 1;
                    $counter = $start_number;
                    while ($row = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo $counter; ?></td>
                            <td><?php echo htmlspecialchars($row['accession_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['classification_number']); ?></td>
                            <td>
                                <a href="book_details.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a>
                                <div><i><?php echo htmlspecialchars($row['author']); ?></i></div>
                                <div><i><?php echo htmlspecialchars($row['place_of_publication']) ?> <?php echo htmlspecialchars($row['publisher']); ?>, c<?php echo htmlspecialchars($row['date_of_publication']); ?>.</i></div>
                            </td>
                            <td><?php echo htmlspecialchars($row['copies']); ?></td>
                        </tr>
                    <?php
                        $counter++;
                    endwhile;
                    ?>
                </tbody>
            <?php endif; ?>
        </table>
    </div>

                        <!-- Pagination controls -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo generatePageLink(1, $current_params); ?>">&laquo; First</a>
                                    <a href="<?php echo generatePageLink($page - 1, $current_params); ?>">&lsaquo; Prev</a>
                                <?php else: ?>
                                    <span class="disabled">&laquo; First</span>
                                    <span class="disabled">&lsaquo; Prev</span>
                                <?php endif; ?>

                                <?php
                                // Determine range of page numbers to display
                                $range = 2; // How many pages to show before and after the current page
                                $start = max(1, $page - $range);
                                $end = min($total_pages, $page + $range);

                                // Always show first page
                                if ($start > 1) {
                                    echo '<a href="' . generatePageLink(1, $current_params) . '">1</a>';
                                    if ($start > 2) {
                                        echo '<span class="disabled">...</span>';
                                    }
                                }

                                // Display page numbers
                                for ($i = $start; $i <= $end; $i++) {
                                    if ($i == $page) {
                                        echo '<span class="active">' . $i . '</span>';
                                    } else {
                                        echo '<a href="' . generatePageLink($i, $current_params) . '">' . $i . '</a>';
                                    }
                                }

                                // Always show last page
                                if ($end < $total_pages) {
                                    if ($end < $total_pages - 1) {
                                        echo '<span class="disabled">...</span>';
                                    }
                                    echo '<a href="' . generatePageLink($total_pages, $current_params) . '">' . $total_pages . '</a>';
                                }
                                ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="<?php echo generatePageLink($page + 1, $current_params); ?>">Next &rsaquo;</a>
                                    <a href="<?php echo generatePageLink($total_pages, $current_params); ?>">Last &raquo;</a>
                                <?php else: ?>
                                    <span class="disabled">Next &rsaquo;</span>
                                    <span class="disabled">Last &raquo;</span>
                                <?php endif; ?>
                            </div>
                            <div class="page-info">
                                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($result->num_rows == 0): ?>
                            <p class="no-results">No books found matching your search criteria.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Your existing code for sidebar functionality
    const sidebar = document.getElementById('sidebar');
    const advancedSearchToggle = document.getElementById('advancedSearchToggle');
    const resetButton = document.getElementById('resetButton');
    const viewAllButton = document.getElementById('viewAllButton');

    // Advanced search toggle
    if (advancedSearchToggle && sidebar) {
        advancedSearchToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-open');
        });
    }

    // Reset button functionality
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            window.location.href = 'home.php';
        });
    }

    // View all button functionality
    if (viewAllButton) {
        viewAllButton.addEventListener('click', function() {
            window.location.href = 'home.php';
        });
    }

    // Close sidebar when clicking outside
    if (sidebar && advancedSearchToggle) {
        document.addEventListener('click', function(event) {
            if (!sidebar.contains(event.target) && 
                !advancedSearchToggle.contains(event.target) && 
                sidebar.classList.contains('sidebar-open')) {
                sidebar.classList.remove('sidebar-open');
            }
        });
    }

    // New functionality for real-time search that preserves table headers
    const searchInput = document.querySelector('.search-bar input[name="search"]');
    const table = document.querySelector('.responsive-table');
    
    // Only add search functionality if table exists
    if (table && searchInput) {
        const tableHead = table.querySelector('thead');
        const tableBody = table.querySelector('tbody');
        
        // Debounce function to prevent excessive processing
        function debounce(func, delay) {
            let timeoutId;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    func.apply(context, args);
                }, delay);
            };
        }

        // Function to perform real-time search
        function performRealTimeSearch() {
            const searchTerm = searchInput.value.trim().toLowerCase();
            const rows = tableBody.querySelectorAll('tr');
            let visibleRowCount = 0;

            // Hide/show rows based on search term
            rows.forEach((row) => {
                const cells = row.querySelectorAll('td');
                const matchFound = Array.from(cells).some(cell => 
                    cell.textContent.toLowerCase().includes(searchTerm)
                );

                if (matchFound || searchTerm === '') {
                    row.style.display = '';
                    visibleRowCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle no results state
            if (visibleRowCount === 0) {
                // Keep table and headers visible
                table.style.display = '';
                tableHead.style.display = '';
                
                // Only hide the body content
                tableBody.style.display = 'none';
                
                // Create or show "no results" message
                let noResultsMsg = document.querySelector('.client-side-no-results');
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('p');
                    noResultsMsg.className = 'client-side-no-results no-results';
                    noResultsMsg.textContent = 'No books found matching your search criteria.';
                    table.after(noResultsMsg);
                } else {
                    noResultsMsg.style.display = 'block';
                }
            } else {
                // Show table body when results exist
                tableBody.style.display = '';
                
                // Hide any client-side "no results" message
                const noResultsMsg = document.querySelector('.client-side-no-results');
                if (noResultsMsg) {
                    noResultsMsg.style.display = 'none';
                }
            }

            // Renumber visible rows
            let newItemNumber = 1;
            rows.forEach((row) => {
                if (row.style.display !== 'none') {
                    const itemNumberCell = row.querySelector('td:first-child');
                    if (itemNumberCell) {
                        itemNumberCell.textContent = newItemNumber++;
                    }
                }
            });
        }

        // Add event listener for real-time search
        searchInput.addEventListener('input', debounce(performRealTimeSearch, 300));
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-bar input[name="search"]');
    const table = document.querySelector('.responsive-table');
    
    // Only proceed if the table exists (it might not if there are no results initially)
    if (!table) return;
    
    const tableBody = table.querySelector('tbody');
    const resultCountElement = document.querySelector('.result-count');
    const paginationContainer = document.querySelector('.pagination');
    const pageInfoElement = document.querySelector('.page-info');
    const noResultsElement = document.querySelector('.no-results');
    const resultsContainer = document.querySelector('.results-container');

    // Debounce function to prevent excessive processing
    function debounce(func, delay) {
        let timeoutId;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                func.apply(context, args);
            }, delay);
        };
    }

    // Function to perform real-time search
    function performRealTimeSearch() {
        const searchTerm = searchInput.value.trim().toLowerCase();
        const rows = tableBody.querySelectorAll('tr');
        let visibleRowCount = 0;

        // Hide/show rows based on search term
        rows.forEach((row) => {
            const cells = row.querySelectorAll('td');
            const matchFound = Array.from(cells).some(cell => 
                cell.textContent.toLowerCase().includes(searchTerm)
            );

            if (matchFound || searchTerm === '') {
                row.style.display = '';
                visibleRowCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Create or update "no results" message if needed
        if (visibleRowCount === 0) {
            // Keep the table visible but hide the tbody
            tableBody.style.display = 'none';
            
            // Hide pagination
            if (paginationContainer) paginationContainer.style.display = 'none';
            if (pageInfoElement) pageInfoElement.style.display = 'none';
            
            // Create "no results" message if it doesn't exist
            let messageElement = document.querySelector('.client-side-no-results');
            if (!messageElement) {
                messageElement = document.createElement('p');
                messageElement.className = 'client-side-no-results no-results';
                messageElement.textContent = 'No books found matching your search criteria.';
                table.after(messageElement);
            } else {
                messageElement.style.display = 'block';
            }
        } else {
            // Show the table content and hide any client-side "no results" message
            tableBody.style.display = '';
            
            // Show pagination if it exists
            if (paginationContainer) paginationContainer.style.display = '';
            if (pageInfoElement) pageInfoElement.style.display = '';
            
            // Hide any client-side "no results" message
            const messageElement = document.querySelector('.client-side-no-results');
            if (messageElement) {
                messageElement.style.display = 'none';
            }
            
            // Update result count if element exists
            if (resultCountElement) {
                resultCountElement.innerHTML = `${visibleRowCount} books found`;
            }
        }

        // Renumber visible rows
        let newItemNumber = 1;
        rows.forEach((row) => {
            if (row.style.display !== 'none') {
                const itemNumberCell = row.querySelector('td:first-child');
                if (itemNumberCell) {
                    itemNumberCell.textContent = newItemNumber++;
                }
            }
        });
    }

    // Add event listener with debounce
    if (searchInput) {
        searchInput.addEventListener('input', debounce(performRealTimeSearch, 300));
    }
});
</script>
</body>

</html>
<style>
    /* Base Styles */
    html {
        scrollbar-width: none;
        scroll-behavior: smooth;
        -ms-overflow-style: none;
    }

    .search-container {
        display: flex;
        flex-direction: row;
        gap: 40px;
    }

    .search-bar {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        padding: 10px;
        border-radius: 30px;
        width: 100%;
        max-width: 400px;
        margin: auto;
        position: relative;
    }

    .search-bar input {
        width: 100%;
        padding: 10px 40px 10px 15px;
        border: none;
        border-radius: 30px;
        font-size: 1rem;
        outline: none;
        background: #f1f3f5;
    }

    .search-bar .search-icon {
        position: absolute;
        right: 20px;
        color: #004d40;
        cursor: pointer;
    }

    .view-all {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #004d40;
        text-decoration: none;
        font-size: 1rem;
        justify-content: center;
        cursor: pointer;
    }

    .view-all:hover {
        color: rgb(63, 179, 0);
    }

    /* Main layout */
    .main-container {
        display: flex;
        flex-direction: row;
        gap: 20px;
        margin: 20px 0;
    }

    /* Sidebar styles */
    .sidebar {
        position: fixed;
        padding: 20px;
        top: 0;
        left: -500px; /* Hide by default */
        width: 500px;
        height: 75%;
        background-color: #f8f9fa;
        transition: left 0.3s ease;
        z-index: 1000;
        overflow-y: auto;
        margin-top: 75px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .sidebar.sidebar-open {
        left: 0; /* Show when open */
    }

    /* Style the heading with reset icon */
    .sidebar h3 {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }

    .sidebar h3 .reset-container {
        display: flex;
        align-items: center;
    }

    .sidebar #resetButton {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        margin-left: 10px;
        transition: all 0.3s ease;
        border-radius: 50%;
    }

    .sidebar #resetButton i {
        color: #6c757d;
        font-size: 1rem;
    }

    .sidebar #resetButton:hover {
        background-color: rgba(108, 117, 125, 0.1);
    }

    .sidebar #resetButton:hover i {
        color: #004d40;
        transform: rotate(180deg);
    }

    /* Use grid layout for two-column structure */
    .sidebar form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 5px;
    }

    /* Ensure each form-group takes the full width in its column */
    .sidebar .form-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 15px;
    }

    /* Style the labels */
    .sidebar label {
        font-weight: 400;
        font-size: .9rem;
        margin-bottom: 2px;
    }

    /* Style input fields and dropdowns */
    .sidebar input,
    .sidebar select {
        width: 80%;
        padding: 5px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }

    /* Make the button container span full width */
    .sidebar .button-container {
        grid-column: span 2;
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        width: 48%;
    }

    /* Style buttons */
    .sidebar .button {
        flex: 1;
        padding: 5px;
        cursor: pointer;
        border: none;
        border-radius: 4px;
        text-align: center;
    }

    .sidebar .button-primary {
        background-color: #004d40;
        color: white;
    }

    .sidebar .button-secondary {
        background-color: #6c757d;
        color: white;
    }

    /* Results Container Styles */
    .results-container {
        background: white;
        border-radius: 8px;
        padding: rem;
        overflow: hidden;
    }

    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2px;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .results-header h2 {
        margin: 0;
        font-size: 1.2rem;
    }

    .result-count {
        font-weight: bold;
    }

    .responsive-table-container {
        position: relative;
        max-height: 550px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 5px;
        scroll-behavior: smooth;
        width: 100%;
        table-layout: fixed;
        /* Ensure full width */
    }

    /* Custom Scrollbar Styling */
    .responsive-table-container::-webkit-scrollbar {
        width: 12px;
    }

    .responsive-table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .responsive-table-container::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
        transition: background 0.3s ease;
    }

    .responsive-table-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .responsive-table {
        width: 100%;
        table-layout: fixed;
        /* Critical for maintaining fixed column widths */
        border-collapse: collapse;
    }

    .responsive-table thead {
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .responsive-table thead tr {
        background-color: #004d40;
    }

    .responsive-table th {
        color: white;
        padding: 12px;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;

    }

    /* Custom Column Styling with Fixed Widths */
    .responsive-table th:nth-child(1) {
        /* No. */
        width: 10%;
    }

    .responsive-table th:nth-child(2) {
        /* Accession No. */
        width: 10%;
    }

    .responsive-table th:nth-child(3) {
        /* Classification No. */
        width: 10%;
    }

    .responsive-table th:nth-child(4) {
        /* Materials */
        width: 70%;
    }

    .responsive-table th:nth-child(5) {
        /* Copies */
        width: 10%;
    }

    .responsive-table td {
        padding: 8px;
        border-bottom: 1px solid #ddd;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        /* Prevent text wrapping */
    }

    .responsive-table td:nth-child(1) {
        background-color: rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .responsive-table td:nth-child(2) {
        background-color: rgba(255, 165, 0, 0.1);
        text-align: center;
    }

    .responsive-table td:nth-child(3) {
        background-color: rgba(135, 206, 235, 0.1);
        text-align: center;
    }

    .responsive-table td:nth-child(4) {
        background-color: rgba(255, 255, 0, 0.05);
        text-align: left;

        /* Multiline text handling for materials column */
        display: flex;
        flex-direction: column;
    }

    .responsive-table td:nth-child(5) {
        background-color: rgba(238, 130, 238, 0.1);
        text-align: center;
    }

    .responsive-table tbody tr:hover {
        background-color: #f5f5f5;
    }

    /* Materials column specific styling */
    .responsive-table td:nth-child(4) a {
        color: #004d40;
        text-decoration: none;
        font-weight: bold;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .responsive-table td:nth-child(4) a:hover {
        text-decoration: underline;
    }

    .responsive-table td:nth-child(4) div {
        color: #666;
        font-size: 0.9em;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .no-results {
        text-align: center;
        color: #666;
        margin: 2rem 0;
        font-size: 1.2rem;
        padding: 1rem;
    }

    /* Pagination Styles */
    .pagination {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 5px;
        margin: 1.5rem 0;
    }

    .pagination button,
    .pagination a,
    .pagination span {
        padding: 10px 15px;
        border: 1px solid #006600;
        background: white;
        color: #006600;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        text-decoration: none;
        margin: 0 4px;
    }

    .pagination button.active,
    .pagination a.active,
    .pagination .active {
        background: #006600;
        color: white;
        border: 1px solid #006600;
    }

    .pagination a:hover {
        background-color: #f5f5f5;
    }

    .pagination .disabled {
        color: #aaa;
        cursor: not-allowed;
    }

    .page-info {
        text-align: center;
        margin-top: 10px;
        font-size: 0.9em;
        color: #666;
    }

    /* Sidebar toggle for mobile */
    .sidebar-toggle {
        display: none;
        margin-bottom: 15px;
        padding: 10px;
        background-color: #f0f0f0;
        border: 1px solid #ddd;
        text-align: center;
        cursor: pointer;
        border-radius: 4px;
    }

    /* Mobile Specific Styles */
    @media (max-width: 768px) {
        .container {
            width: 100%;
            padding: 0.5rem;
        }

        .header h1 {
            font-size: 1.5rem;
        }

        .search-container,
        .results-container {
            padding: 1rem;
            border-radius: 5px;
        }

        .simple-search {
            flex-direction: column;
            gap: 8px;
        }

        .simple-search input {
            width: 100%;
        }

        .button-container {
            display: flex;
            gap: 8px;
        }

        .button {
            flex: 1;
            padding: 10px;
            font-size: 0.9rem;
        }

        .results-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .results-header h2 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .main-container {
            flex-direction: column;
        }

        .sidebar {
            flex: none;
            width: auto;
        }

        .sidebar.hidden {
            display: none;
        }

        .sidebar-toggle {
            display: block;
        }

        .pagination a,
        .pagination span,
        .pagination button {
            padding: 8px 12px;
            margin: 0 2px;
        }

        table th,
        table td {
            padding: 8px;
            font-size: 0.9rem;
        }

        .responsive-table {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            min-width: 800px;
            /* Maintains horizontal scrolling */
        }
    }

    @media screen and (min-width: 769px) {
        .sidebar-toggle {
            display: none;
        }

        .sidebar.hidden {
            display: block;
        }
    }

    /* Even smaller screens */
    @media (max-width: 480px) {

        .pagination a,
        .pagination span,
        .pagination button {
            padding: 5px 10px;
            margin: 2px;
        }

        /* Mobile card view for results */
        .mobile-card-view table,
        .mobile-card-view thead,
        .mobile-card-view tbody,
        .mobile-card-view th,
        .mobile-card-view td,
        .mobile-card-view tr {
            display: block;
        }

        .mobile-card-view thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }

        .mobile-card-view tr {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .mobile-card-view td {
            border: none;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 50%;
            text-align: left;
        }

        .mobile-card-view td:before {
            position: absolute;
            top: 6px;
            left: 6px;
            width: 45%;
            padding-right: 10px;
            white-space: nowrap;
            font-weight: bold;
            content: attr(data-label);
        }

        .mobile-card-view td:last-child {
            border-bottom: 0;
        }
    }

    @media (max-width: 380px) {
        .header h1 {
            font-size: 1.3rem;
        }

        .form-group label,
        .form-group input,
        .form-group select {
            font-size: 0.9rem;
        }

        .toggle-advanced {
            font-size: 0.85rem;
        }
    }
</style>