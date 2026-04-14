<?php
session_start();
include 'db_connect.php';

// Initialize variables
$search = "";
$sort_by = "author_title";  // Changed default sort to match valid fields
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

// Function to generate pagination links with current search parameters
function generatePageLink($page_num, $current_params)
{
    $params = $current_params;
    $params['page'] = $page_num;
    return 'index.php?' . http_build_query($params);
}

// Get current search parameters for pagination links
$current_params = [];
if (!empty($search)) $current_params['search'] = $search;
if ($sort_by != 'author_title') $current_params['sort_by'] = $sort_by;
if ($sort_order != 'ASC') $current_params['sort_order'] = $sort_order;
if ($records_per_page != 10) $current_params['records_per_page'] = $records_per_page;
include 'student/we.php';
?>
            <nav class="nav-links" id="navLinks"></nav>
<div class="container">
    <div class="main-container">
        <div class="system-title">
            <img src="student/assets/img/images-removebg-preview.png" alt="Library Logo">
            <div class="title-text">
                <h1>OPAC-ISUR</h1>
                <p>Online Public Access Catalog</p>
            </div>
        </div>

        <div class="search-container">

            <form id="searchForm" method="GET" action="index.php">

                <div class="search-bar">
                    <input type="text" name="search" id="searchInput" placeholder="Enter keywords, title, author, or call number..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                    <div class="search-controls">
                        <button type="button" id="clearBtn" class="clear-btn">
                            <i class="fas fa-times"></i>
                        </button>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                            <span>Search</span>
                        </button>
                    </div>
                </div>

                <!-- Hidden fields to maintain sort and pagination -->
                <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
                <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sort_order); ?>">
                <input type="hidden" name="records_per_page" value="<?php echo htmlspecialchars($records_per_page); ?>">
            </form>
            <div class="search-hint">Enter your search terms and press the Search button to find materials</div>
        </div>

        <div class="results-container" id="resultsContainer">
            <div class="results-header">
                <h2>Search Results</h2>
                <button id="newSearchBtn" class="new-btn">
                    <i class="fas fa-redo"></i>
                    <span></span>
                </button>
            </div>

            <!-- This is the modified HTML structure for the main results table -->
            <div class="responsive-table-container">
                <?php if ($result->num_rows > 0): ?>
                    <table class="responsive-table">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th>
                                    <a href="index.php?<?php echo http_build_query(array_merge($current_params, ['sort_by' => 'call_no', 'sort_order' => ($sort_by == 'call_no' && $sort_order == 'ASC') ? 'DESC' : 'ASC'])); ?>">
                                        Call No.
                                        <?php if ($sort_by == 'call_no'): ?>
                                            <i class="fas fa-sort-<?php echo ($sort_order == 'ASC') ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="index.php?<?php echo http_build_query(array_merge($current_params, ['sort_by' => 'accession_no', 'sort_order' => ($sort_by == 'accession_no' && $sort_order == 'ASC') ? 'DESC' : 'ASC'])); ?>">
                                        Acc No.
                                        <?php if ($sort_by == 'accession_no'): ?>
                                            <i class="fas fa-sort-<?php echo ($sort_order == 'ASC') ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="index.php?<?php echo http_build_query(array_merge($current_params, ['sort_by' => 'author_title', 'sort_order' => ($sort_by == 'author_title' && $sort_order == 'ASC') ? 'DESC' : 'ASC'])); ?>">
                                        Author/Title
                                        <?php if ($sort_by == 'author_title'): ?>
                                            <i class="fas fa-sort-<?php echo ($sort_order == 'ASC') ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Title No.</th>
                                <th>
                                    <a href="index.php?<?php echo http_build_query(array_merge($current_params, ['sort_by' => 'volume', 'sort_order' => ($sort_by == 'volume' && $sort_order == 'ASC') ? 'DESC' : 'ASC'])); ?>">
                                        Volume
                                        <?php if ($sort_by == 'volume'): ?>
                                            <i class="fas fa-sort-<?php echo ($sort_order == 'ASC') ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="resultsBody">
                            <?php
                            $item_number = $offset + 1;
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td data-label='No.' class='no'>{$item_number}</td>";
                                echo "<td data-label='Call No.'>" . htmlspecialchars($row['call_no']) . "</td>";
                                echo "<td data-label='Acc No.'>" . htmlspecialchars($row['accession_no']) . "</td>";
                                echo "<td data-label='Author'>" . htmlspecialchars($row['author_title']) . "</td>";
                                echo "<td data-label='Title'>" . htmlspecialchars($row['title']) . "</td>";
                                echo "<td data-label='Volume'>" . htmlspecialchars($row['volume']) . "</td>";
                                echo "</tr>";
                                $item_number++;
                            }
                            ?>
                        </tbody>
                    </table>

                    <!-- Pagination controls -->
                    <!-- Pagination controls -->
                    <div class="pagination" id="pagination" style="margin-top: 30px; clear: both;">
                        <?php if ($total_pages > 1): ?>
                            <?php if ($page > 1): ?>
                                <a href="<?php echo generatePageLink(1, $current_params); ?>" class="pagination-link">First</a>
                                <a href="<?php echo generatePageLink($page - 1, $current_params); ?>" class="pagination-link">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            // Show page numbers with limited range
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                if ($i == $page) {
                                    echo '<span class="pagination-link active">' . $i . '</span>';
                                } else {
                                    echo '<a href="' . generatePageLink($i, $current_params) . '" class="pagination-link">' . $i . '</a>';
                                }
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo generatePageLink($page + 1, $current_params); ?>" class="pagination-link">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <a href="<?php echo generatePageLink($total_pages, $current_params); ?>" class="pagination-link">Last</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="page-info" id="pageInfo">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> records
                    </div>

                <?php else: ?>
                    <div id="noResults" class="no-results">
                        <i class="fas fa-search"></i>
                        <p>No materials found matching your search criteria.</p>
                        <p>Try using different keywords or broaden your search.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'student/footer.php'; ?>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements - with null checks
    const clearBtn = document.getElementById('clearBtn');
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');
    const newSearchBtn = document.getElementById('newSearchBtn');
    const resultsContainer = document.getElementById('resultsContainer');
    const systemTitle = document.querySelector('.system-title');
    const searchContainer = document.querySelector('.search-container');
    const searchHint = document.querySelector('.search-hint');
    const pagination = document.getElementById('pagination');
    const pageInfo = document.getElementById('pageInfo');
    const noResults = document.getElementById('noResults');

    // Add data-label attributes to table cells for mobile view
    const tableHeaders = document.querySelectorAll('.responsive-table th');
    const tableRows = document.querySelectorAll('.responsive-table tbody tr');

    // Add data attributes for mobile responsive display
    if (tableHeaders && tableHeaders.length > 0 && tableRows && tableRows.length > 0) {
        const headerTexts = Array.from(tableHeaders).map(header => {
            // Extract text from header (even if it contains a link)
            if (header.querySelector('a')) {
                return header.querySelector('a').textContent.trim();
            }
            return header.textContent.trim();
        });

        tableRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (index < headerTexts.length) {
                    cell.setAttribute('data-label', headerTexts[index]);
                }
            });
        });
    }

    // Show results container if there's a search query
    if (searchInput && searchInput.value.trim() !== '') {
        // Move UI to search position
        if (systemTitle) systemTitle.classList.add('search-active');
        if (searchContainer) searchContainer.classList.add('search-active');
        if (searchHint) searchHint.classList.add('hidden');

        // Show results with animation
        if (resultsContainer) {
            setTimeout(() => {
                resultsContainer.classList.add('visible');

                // Show pagination and page info with slight delay
                setTimeout(() => {
                    if (pagination) pagination.classList.add('visible');
                    if (pageInfo) pageInfo.classList.add('visible');
                    if (noResults) noResults.classList.add('visible');
                }, 300);
            }, 100);
        }
    }

    // Clear search button functionality
    if (clearBtn && searchInput) {
        // Show clear button when input has text
        searchInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                clearBtn.classList.add('visible');
            } else {
                clearBtn.classList.remove('visible');
            }
        });

        // Initialize clear button state
        if (searchInput.value.trim() !== '') {
            clearBtn.classList.add('visible');
        }

        // Clear button click handler
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.focus();
            clearBtn.classList.remove('visible');
        });
    }

    // New search button functionality
    if (newSearchBtn) {
        newSearchBtn.addEventListener('click', function() {
            // Animate out the results
            if (resultsContainer) resultsContainer.classList.remove('visible');
            if (pagination) pagination.classList.remove('visible');
            if (pageInfo) pageInfo.classList.remove('visible');

            // Restore the search UI to center position
            setTimeout(() => {
                if (systemTitle) systemTitle.classList.remove('search-active');
                if (searchContainer) searchContainer.classList.remove('search-active');
                if (searchHint) searchHint.classList.remove('hidden');

                // Reset form and redirect
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 400);
            }, 300);
        });
    }

    // Handle search form submission with animations
    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', function(e) {
            const searchValue = searchInput.value.trim();
            if (searchValue === '') {
                e.preventDefault();
                searchInput.focus();
                return;
            }

            // If we're not already showing results, animate the transition
            if (resultsContainer && !resultsContainer.classList.contains('visible')) {
                e.preventDefault();

                // Move search elements to top position
                if (systemTitle) systemTitle.classList.add('search-active');
                if (searchContainer) searchContainer.classList.add('search-active');
                if (searchHint) searchHint.classList.add('hidden');

                // Submit form after animation completes
                setTimeout(() => {
                    searchForm.submit();
                }, 400);
            }
        });
    }

    // Handle window resize to adjust table layout
    function checkMobileView() {
        const isMobile = window.innerWidth <= 768;
        const table = document.querySelector('.responsive-table');

        if (table) {
            if (isMobile) {
                table.classList.add('responsive-table-card-view');
                // Make sure container has enough space for pagination
                if (resultsContainer) {
                    resultsContainer.style.paddingBottom = '70px';
                }
            } else {
                table.classList.remove('responsive-table-card-view');
                if (resultsContainer) {
                    resultsContainer.style.paddingBottom = '15px';
                }
            }
        }
    }
    
    // Call checkMobileView initially and add window resize event
    checkMobileView();
    window.addEventListener('resize', checkMobileView);
});

// Add this to your existing JavaScript code
document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let currentPage = <?php echo $page; ?>;
    const totalPages = <?php echo $total_pages; ?>;
    let isLoading = false;
    const resultsBody = document.getElementById('resultsBody');
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'loading-indicator';
    loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading more results...';
    
    // Function to check if we need to load more content
    function checkScroll() {
        if (isLoading || currentPage >= totalPages) return;
        
        const scrollPosition = window.innerHeight + window.scrollY;
        const bodyHeight = document.body.offsetHeight;
        
        // Load more content when user scrolls to 80% of the page
        if (scrollPosition >= bodyHeight * 0.8) {
            loadMoreContent();
        }
    }
    
    // Function to load more content via AJAX
    function loadMoreContent() {
        isLoading = true;
        currentPage++;
        
        // Add loading indicator
        document.querySelector('.responsive-table').after(loadingIndicator);
        
        // Build query parameters for the AJAX request
        const params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('records_per_page', <?php echo $records_per_page; ?>);
        params.append('sort_by', '<?php echo $sort_by; ?>');
        params.append('sort_order', '<?php echo $sort_order; ?>');
        params.append('search', '<?php echo htmlspecialchars($search); ?>');
        params.append('ajax', 'true');
        
        // Make AJAX request
        fetch('get_records.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                // Remove loading indicator
                loadingIndicator.remove();
                
                // Append new records to the table
                if (data.records && data.records.length > 0) {
                    let startNumber = (currentPage - 1) * <?php echo $records_per_page; ?> + 1;
                    
                    data.records.forEach(record => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td data-label="No." class="no">${startNumber}</td>
                            <td data-label="Call No.">${escapeHTML(record.call_no)}</td>
                            <td data-label="Acc No.">${escapeHTML(record.accession_no)}</td>
                            <td data-label="Author">${escapeHTML(record.author_title)}</td>
                            <td data-label="Title">${escapeHTML(record.title)}</td>
                            <td data-label="Volume">${escapeHTML(record.volume)}</td>
                        `;
                        resultsBody.appendChild(row);
                        startNumber++;
                    });
                    
                    // Update page info text
                    const pageInfo = document.getElementById('pageInfo');
                    const totalRecords = data.total_records;
                    const showing = Math.min((currentPage) * <?php echo $records_per_page; ?>, totalRecords);
                    pageInfo.textContent = `Showing 1 to ${showing} of ${totalRecords} records`;
                }
                
                isLoading = false;
                
                // If we've loaded all pages, remove the scroll listener
                if (currentPage >= totalPages) {
                    window.removeEventListener('scroll', checkScroll);
                    const endMessage = document.createElement('div');
                    endMessage.className = 'end-message';
                    endMessage.textContent = 'End of results';
                    document.querySelector('.responsive-table-container').appendChild(endMessage);
                }
            })
            .catch(error => {
                console.error('Error loading more records:', error);
                loadingIndicator.remove();
                isLoading = false;
            });
    }
    
    // Helper function to escape HTML
    function escapeHTML(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Add scroll event listener
    if (totalPages > 1) {
        window.addEventListener('scroll', checkScroll);
    }
});
</script>
<style>
    /* Main Container Styles */
    .main-container {
        min-height: 65vh;
        transition: all 0.5s ease-in-out;
        width: 100%;
        padding: 0 15px;
        box-sizing: border-box;
    }

    /* System Title & Logo */
    .system-title {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 100px;
        transition: all 0.5s ease-in-out;
        text-align: center;
    }

    .system-title.search-active {
        margin-top: 15px;
    }

    .system-title img {
        height: 40px;
        margin-right: 10px;
        transition: all 0.5s ease-in-out;
    }

    .title-text {
        transition: all 0.5s ease-in-out;
    }

    .title-text h1 {
        font-size: 24px;
        color: #004d40;
        margin-bottom: 5px;
        transition: all 0.4s ease;
    }

    .title-text p {
        color: #666;
        font-size: 14px;
        transition: all 0.4s ease;
    }

    /* Search Container */
    .search-container {
        max-width: 90%;
        width: 600px;
        margin: 30px auto;
        transition: all 0.5s ease-in-out;
        padding: 0 15px;
    }

    .search-container.search-active {
        margin: 10px auto;
    }

    .search-bar {
        display: flex;
        background: white;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        border: 1px solid #e0e0e0;
        transition: all 0.3s ease;
    }

    .search-bar:focus-within {
        box-shadow: 0 4px 20px rgba(0, 77, 64, 0.15);
        border-color: #004d40;
    }

    .search-bar input {
        flex: 1;
        border: none;
        margin-left: 15px;
        font-size: 16px;
        outline: none;
        padding: 15px 0;
        min-width: 0;
    }

    .search-controls {
        display: flex;
        align-items: center;
    }

    .clear-btn {
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 0 10px;
        font-size: 18px;
        transition: color 0.2s;
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .clear-btn.visible {
        visibility: visible;
        opacity: 1;
    }

    .search-btn{
        background-color: #00695c;
        color: white;
        border:2px solid #00695c;
        padding: 15px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .search-btn i {
        margin-right: 8px;
    }

    .search-btn:hover{
        background-color: #004d40;
    }


    .new-btn {
        background-color:rgb(255, 255, 255);
        color: #00695c;
        margin-left: 10px;
        font-size: 20px;
        border:none ;
    }

    /* Results Container */
    .results-container {
        background: white;
        border-radius: 8px;
        padding: 15px;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.5s ease-in-out;
        max-height: 0;
        overflow: hidden;
        box-sizing: border-box;
    }

    .results-container.visible {
        opacity: 1;
        transform: translateY(0);
        max-height: none;
        /* Changed from 2000px to allow any height */
        overflow: visible;
        /* Allow content to flow naturally */
    }

    /* Results Header */
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
    }

    .results-header h2 {
        font-size: 20px;
        font-weight: 600;
        color: #004d40;
        margin: 0;
    }

    /* Responsive Table Container */
    .responsive-table-container {
        overflow-x: auto;
        margin: 15px 0;
        -webkit-overflow-scrolling: touch;
        border-radius: 6px;
        transition: all 0.4s ease;
    }

    /* Improved Table Styles */
    .responsive-table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Enhanced Table Header */
    .responsive-table thead {
        background: #004d40;
    }

    .responsive-table th {
        position: sticky;
        top: 0;
        padding: 15px;
        text-align: left;
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 14px;
        letter-spacing: 0.5px;
        transition: all 0.3s;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }

    .responsive-table th:last-child {
        border-right: none;
    }

    .responsive-table th a {
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
    }

    .responsive-table th a i {
        margin-left: 5px;
    }

    /* Table Cells */
    .responsive-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
        transition: all 0.3s;
    }

    /* Row Styling */
    .responsive-table tbody tr {
        transition: all 0.3s ease;
        background-color: #fff;
    }

    .responsive-table tbody tr:nth-child(even) {
        background-color: #f8f8f8;
    }

    .responsive-table tbody tr:hover {
        background-color: #e0f2f1;
    }

    /* Pagination Styles */
    .pagination {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 5px;
        margin: 20px 0 10px;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.4s ease;
    }

    .pagination.visible {
        opacity: 1;
        transform: translateY(0);
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border-radius: 4px;
        background-color: #f5f5f5;
        color: #004d40;
        text-decoration: none;
        transition: all 0.3s;
        min-width: 25px;
        text-align: center;
    }

    .pagination a:hover {
        background-color: #e0e0e0;
    }

    .pagination span.active {
        background-color: #004d40;
        color: white;
    }

    .page-info {
        text-align: center;
        margin: 10px 0;
        color: #666;
        font-size: 14px;
        opacity: 0;
        transition: all 0.4s ease;
    }

    .page-info.visible {
        opacity: 1;
    }

    /* No Results Message */
    .no-results {
        text-align: center;
        padding: 30px 0;
        color: #666;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.4s ease;
    }

    .no-results.visible {
        opacity: 1;
        transform: translateY(0);
    }

    .no-results i {
        font-size: 48px;
        color: #ccc;
        margin-bottom: 20px;
    }

    /* Search Hint */
    .search-hint {
        text-align: center;
        margin-top: 15px;
        color: #666;
        font-style: italic;
        transition: opacity 0.3s ease;
        font-size: 14px;
    }

    .search-hint.hidden {
        opacity: 0;
    }

    /* Mobile & Responsive Styles */
    @media screen and (max-width: 768px) {
        .system-title {
            margin-top: 200px;
        }

        .system-title.search-active {
            margin-top: 10px;
            flex-direction: row;
        }

        /* Remove or modify this rule to keep the title visible */
        .system-title.search-active .title-text {
            /* Change these values to keep the title text visible */
            opacity: 1;
            /* Was 0, now 1 to make it visible */
            width: auto;
            /* Was 0, now auto to allow natural width */
            overflow: visible;
            /* Was hidden, now visible */
            font-size: 14px;
            /* Add smaller font size for better mobile fit */
            margin-left: 10px;
            /* Add some spacing */
        }

        .system-title.search-active img {
            margin-right: 0;
            height: 30px;
            /* Slightly smaller image for better balance */
        }

        .search-container {
            max-width: 95%;
            padding: 0 5px;
        }

        .search-bar input {
            font-size: 14px;
            padding: 12px 0;
        }

        .search-btn {
            border: 2px solid #00695c;
        }

        .search-btn span {
            display: none;
        }

        .results-header {
            flex-direction: row;
            align-items: flex-start;
        }

        .results-header h2 {
            margin-bottom: 10px;
        }

        .pagination a,
        .pagination span {
            padding: 6px 10px;
            font-size: 13px;
        }

        .responsive-table tr:last-child {
            margin-bottom: 30px;
            /* Add space after last card */
        }

        .pagination,
        .page-info {
            position: relative;
            z-index: 5;
            /* Ensure pagination stays on top */
        }

        /* Card view for tables on mobile */
        .responsive-table thead {
            display: none;
        }

        .responsive-table,
        .responsive-table tbody,
        .responsive-table tr {
            display: block;
            width: 100%;
        }

        .responsive-table tr {
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .responsive-table td {
            display: flex;
            padding: 10px 15px;
            text-align: right;
            border-bottom: 1px solid #f0f0f0;
            border-right: none;
            justify-content: space-between;
            font-size: 14px;
            color: #555;
        }

        .responsive-table td:last-child {
            border-bottom: none;
        }

        .responsive-table td:before {
            content: attr(data-label);
            font-weight: 600;
            margin-right: auto;
            color: #004d40;
        }
        .no{
            background-color:rgba(184, 184, 184, 0.42);

        }
    }
/* Add this to your CSS file */
.loading-indicator {
    text-align: center;
    padding: 20px;
    color: #666;
    font-size: 14px;
}

.loading-indicator i {
    margin-right: 10px;
}

.end-message {
    text-align: center;
    padding: 20px;
    color: #666;
    font-style: italic;
    border-top: 1px solid #eee;
    margin-top: 20px;
}

/* Smooth loading animation */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

tr {
    animation: fadeIn 0.3s ease-in-out;
}

</style>