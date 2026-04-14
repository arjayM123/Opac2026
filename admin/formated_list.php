<?php include '_layout.php';
include '../db_connect.php'; ?>
<style>
    .category-header {
        padding: 15px;
        margin-top: 30px;
        margin-bottom: 10px;
        border-radius: 5px;
        font-weight: bold;
        font-size: 18px;
    }

    .category-description {
        font-size: 14px;
        color: #666;
        margin-bottom: 10px;
    }

    .books-table {
        margin-bottom: 30px;
    }

    .content-wrapper {
        margin-left: 0 !important;
        /* Override any existing margin */
        width: 100% !important;
        background: white;
    }


    .header-section {
        text-align: center;
        margin-bottom: 30px;
        padding-top: 20px;
    }

    .header-section img {
        width: 100px;
        margin-bottom: 10px;
    }

    .university-header {
        margin: 0;
        font-size: 18px;
        font-weight: bold;
        line-height: 1.5;
    }

    .address {
        margin: 5px 0;
        line-height: 1.2;
    }

    .department {
        font-size: 16px;
        font-weight: bold;
        margin: 15px 0;
        line-height: 1.5;
    }

    .books-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        table-layout: fixed;
    }

    .books-table th,
    .books-table td {
        border: 1px solid black;
        padding: 8px;
        text-align: left;
    }

    .books-table th {
        background-color: #f5f5f5;
        text-align: center;
        font-weight: bold;
    }

    /* Column widths */
    .books-table th:nth-child(1) {
        width: 15%;
    }

    /* CALL NO. */
    .books-table th:nth-child(2) {
        width: 12%;
    }

    /* ACCESSION NO. */
    .books-table th:nth-child(3) {
        width: 55%;
    }

    /* AUTHOR/TITLE */
    .books-table th:nth-child(4) {
        width: 9%;
    }

    /* TITLE */
    .books-table th:nth-child(5) {
        width: 9%;
    }

    /* VOLUME */

    .print-button {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #007bff;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .call-number {
        white-space: pre-line;
    }

    /* Print-specific styles */
    @media print {


        .content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
            background: white;
        }

        /* Header styles */
        .header-section {
            text-align: center;
            margin-bottom: 30px;
            padding-top: 20px;
        }

        .header-section img {
            width: 100px;
            height: auto;
            margin-bottom: 10px;
        }

        .university-header {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            line-height: 1.5;
        }

        .address {
            margin: 5px 0;
            line-height: 1.2;
        }

        .department {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            line-height: 1.5;
        }

        /* Table styles */
        .books-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            page-break-inside: auto;
        }

        .books-table th,
        .books-table td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .books-table th {
            background-color: #f5f5f5;
            text-align: center;
            font-weight: bold;
        }

        .books-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        /* Column widths */
        .books-table th:nth-child(1),
        .books-table td:nth-child(1) {
            width: 15%;
        }

        .books-table th:nth-child(2),
        .books-table td:nth-child(2) {
            width: 12%;
        }

        .books-table th:nth-child(3),
        .books-table td:nth-child(3) {
            width: 55%;
        }

        .books-table th:nth-child(4),
        .books-table td:nth-child(4) {
            width: 9%;
        }

        .books-table th:nth-child(5),
        .books-table td:nth-child(5) {
            width: 9%;
        }

        .call-number {
            white-space: pre-line;
        }

        /* Print button */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Print-specific styles */
        @media print {

            /* Reset page margins and size */
            @page {
                size: A4;
                margin: 1cm;
            }

            /* Basic print reset */
            html,
            body {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                background: white !important;
            }

            /* Table print optimization */
            .books-table {
                font-size: 9pt;
                width: 100%;
                table-layout: fixed;
            }

            .books-table th,
            .books-table td {
                padding: 4px;
            }

            /* Ensure proper text wrapping */
            .books-table td {
                overflow-wrap: break-word;
                word-wrap: break-word;
                -ms-word-break: break-all;
                word-break: break-word;
            }

            /* Hide unnecessary elements */
            .print-button,
            .sidebar,
            nav,
            footer,
            .main-sidebar,
            .navbar,
            .layout-fixed,
            .layout-navbar-fixed,
            .layout-footer-fixed {
                display: none !important;
            }

            /* Container adjustments */
            .container {
                width: 100% !important;
                max-width: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Header adjustments for print */
            .header-section {
                margin-bottom: 20px;
            }

            .header-section img {
                width: 80px;
            }

            /* Force background colors and images */
            * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    background-color: transparent !important;
}
            /* Remove any potential watermarks */
            body::before,
            body::after,
            .content-wrapper::before,
            .content-wrapper::after {
                display: none !important;
                content: none !important;
            }
        }
    }

    .filter-section {
        margin-bottom: 20px;
        background-color: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
    }

    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 200px;
    }

    .filter-group label {
        margin-bottom: 5px;
        font-weight: bold;
    }

    .filter-group select {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .filter-button,
    .print-button {
        padding: 8px 15px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .print-button {
        background-color: #2196F3;
    }

    .filter-info {
        margin: 5px 0;
        font-weight: bold;
        font-style: italic;
    }

    .total-count {
        margin: 10px 0;
        font-weight: bold;
        font-size: 16px;
        text-align: right;
    }

    .no-books {
        padding: 20px;
        text-align: center;
        font-size: 18px;
        color: #666;
        background-color: #f9f9f9;
        border-radius: 5px;
        margin-top: 20px;
    }

    .category-header {
        font-weight: bold;
        margin-top: 15px;
    }

    @media print {
        .filter-section {
            display: none;
        }
        .toggle-sidebar {
            display: none;
        }
    }
    .export-buttons {
        display: flex;
        gap: 10px;
    }
    
    .excel-button {
        background-color: #217346; /* Excel green color */
        color: white;
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .word-button {
        background-color: #2b579a; /* Word blue color */
        color: white;
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    @media print {
        .excel-button, .word-button {
            display: none;
        }
    }
</style>

<div class="main-content">
    <div class="form-container">
        <!-- Filter Form -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label for="department">Department:</label>
                    <select name="department" id="department">
                        <option value="">All Departments</option>
                        <?php
                        // Fetch departments
                        $dept_query = "SELECT id, department_name FROM departments ORDER BY department_name";
                        $dept_result = $conn->query($dept_query);
                        while ($dept = $dept_result->fetch_assoc()) {
                            $selected = (isset($_GET['department']) && $_GET['department'] == $dept['id']) ? 'selected' : '';
                            echo "<option value='{$dept['id']}' {$selected}>{$dept['department_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="location">Location:</label>
                    <select name="location" id="location">
                        <option value="">All Locations</option>
                        <?php
                        // Fetch unique locations
                        $loc_query = "SELECT DISTINCT location FROM books WHERE location IS NOT NULL ORDER BY location";
                        $loc_result = $conn->query($loc_query);
                        while ($loc = $loc_result->fetch_assoc()) {
                            $selected = (isset($_GET['location']) && $_GET['location'] == $loc['location']) ? 'selected' : '';
                            echo "<option value='{$loc['location']}' {$selected}>{$loc['location']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="filter-button">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
                
                <div class="export-buttons">
                    <button type="button" onclick="window.print()" class="print-button">
                        <i class="fas fa-print"></i> Print List
                    </button>
                    
                    <button type="button" onclick="exportToExcel()" class="excel-button">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                    
                    <button type="button" onclick="exportToWord()" class="word-button">
                        <i class="fas fa-file-word"></i> Export to Word
                    </button>
                </div>
            </form>
        </div>
        <div class="container">
            <div class="header-section">
                <img src="assets/img/images-removebg-preview.png" alt="ISU Logo">
                <p class="university-header">Republic of the Philippines</p>
                <p class="university-header">ISABELA STATE UNIVERSITY</p>
                <h2>LIST OF GENERAL COLLECTION</h2>
                <?php
                // Display filter information
                if (isset($_GET['department']) && $_GET['department'] !== '') {
                    $dept_name_query = "SELECT department_name FROM departments WHERE id = " . intval($_GET['department']);
                    $dept_name_result = $conn->query($dept_name_query);
                    if ($dept_name_result && $dept_name_result->num_rows > 0) {
                        $dept_name = $dept_name_result->fetch_assoc()['department_name'];
                        echo "<p class='filter-info'>Department: {$dept_name}</p>";
                    }
                }

                if (isset($_GET['location']) && $_GET['location'] !== '') {
                    echo "<p class='filter-info'>( {$_GET['location']} )</p>";
                }
                ?>
            </div>

            <?php
            // Define categories with DDC ranges
            $categories = array(
                '000-099' => 'Computers, Information, & General Reference',
                '100-199' => 'Philosophy and Psychology',
                '200-299' => 'Religion',
                '300-399' => 'Social Sciences',
                '400-499' => 'Language',
                '500-599' => 'Science',
                '600-699' => 'Applied Science Technology',
                '700-799' => 'Arts and Recreation',
                '800-899' => 'Literature',
                '900-999' => 'History and Geography'
            );

            // Function to determine category based on classification number
            function getDDCCategory($callNo)
            {
                // Extract the first number group from call number
                if (preg_match('/^(\d+)/', $callNo, $matches)) {
                    $classNum = intval($matches[1]);

                    // Determine category range
                    $baseRange = floor($classNum / 100) * 100;
                    $categoryKey = sprintf('%03d-%03d', $baseRange, $baseRange + 99);

                    return $categoryKey;
                }
                return '000-099'; // Default category if no match
            }

            // Build WHERE clause based on filters
            $whereClause = "";
            $filterParams = array();

            if (isset($_GET['department']) && $_GET['department'] !== '') {
                $dept_id = intval($_GET['department']);
                $whereClause .= "department_id = $dept_id";
                $filterParams[] = "department=$dept_id";
            }

            if (isset($_GET['location']) && $_GET['location'] !== '') {
                $location = $conn->real_escape_string($_GET['location']);
                if ($whereClause !== "") {
                    $whereClause .= " AND ";
                }
                $whereClause .= "location = '$location'";
                $filterParams[] = "location=" . urlencode($_GET['location']);
            }

            // Add WHERE clause to query if filters are set
            $query = "SELECT 
            classification_number,
            call_number,
            date_of_publication,
            accession_number,
            CONCAT(author, title, '.' ,' ', edition, place_of_publication, publisher,'.', ' c', date_of_publication) as author_title,
            '1' as title_count,
            copies as volume
        FROM books ";

            if ($whereClause !== "") {
                $query .= "WHERE $whereClause ";
            }

            $query .= "ORDER BY classification_number ASC, call_number ASC";

            $result = $conn->query($query);

            // Create an array to store books by category
            $categorized_books = array();
            $total_books = 0;

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $category = getDDCCategory($row['classification_number']);
                    if (!isset($categorized_books[$category])) {
                        $categorized_books[$category] = array();
                    }
                    $categorized_books[$category][] = $row;
                    $total_books++;
                }
            }

            // Display total count
            echo "<p class='total-count'> $total_books</p>";

            // Display books by category
            foreach ($categories as $range => $categoryName):
                if (isset($categorized_books[$range]) && !empty($categorized_books[$range])):
            ?>
                    <div class="category-header">
                        <?php echo $categoryName; ?> (<?php echo $range; ?>)
                    </div>

                    <table class="books-table">
                        <thead>
                            <tr>
                                <th>CALL NO.</th>
                                <th>ACCESSION NO.</th>
                                <th>AUTHOR/TITLE OF BOOK</th>
                                <th>TITLE</th>
                                <th>VOLUME</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorized_books[$range] as $row):
                                $formatted_call = $row['classification_number'] . "\n" .
                                    $row['call_number'] . "\n" .
                                    $row['date_of_publication'];
                            ?>
                                <tr>
                                    <td class="call-number"><?php echo $formatted_call; ?></td>
                                    <td><?php echo $row['accession_number']; ?></td>
                                    <td><?php echo $row['author_title']; ?></td>
                                    <td><?php echo $row['title_count']; ?></td>
                                    <td><?php echo $row['volume']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php
                endif;
            endforeach;

            // If no books found
            if ($total_books == 0):
                ?>
                <div class="no-books">
                    <p>No books found matching your filter criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script>
    function exportToExcel() {
    // Get current URL parameters for filters
    let params = new URLSearchParams(window.location.search);
    
    // Create URL for Excel export with current filters
    let exportUrl = 'export_excel.php';
    
    if(params.toString()) {
        exportUrl += '?' + params.toString();
    }
    
    // Redirect to the export script
    window.location.href = exportUrl;
}

function exportToWord() {
    // Get current URL parameters for filters
    let params = new URLSearchParams(window.location.search);
    
    // Create URL for Word export with current filters
    let exportUrl = 'export_word.php';
    
    if(params.toString()) {
        exportUrl += '?' + params.toString();
    }
    
    // Redirect to the export script
    window.location.href = exportUrl;
}
</script>