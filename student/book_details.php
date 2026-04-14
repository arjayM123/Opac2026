<?php
// Include database connection
require_once '../db_connect.php';
include '_layout.php';


$book_id = intval($_GET['id']);

// Fetch book details
$query = "SELECT b.*, d.department_name as department_name 
          FROM books b 
          LEFT JOIN departments d ON b.department_id = d.id 
          WHERE b.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();



$book = $result->fetch_assoc();

$available_query = "SELECT 
                     (SELECT copies FROM books WHERE id = ?) - 
                     (SELECT COUNT(*) FROM borrowers WHERE book_id = ? AND return_date IS NULL) 
                     AS available_copies";

$stmt = $conn->prepare($available_query);
$stmt->bind_param("ii", $book_id, $book_id);
$stmt->execute();
$availability_result = $stmt->get_result();
$availability = $availability_result->fetch_assoc();

// Page title
$page_title = htmlspecialchars($book['title']);

?>

<div class="container">
    <div class="breadcrumb">
        <a href="home.php">Home</a> &raquo;
        Book Details &raquo; <a href="full_details.php?id=<?php echo $book['id']; ?>"> Full details</a>
    </div>

    <div class="book-details">


        <div class="book-info-container">
            <div class="book-info-left">
                                <!-- Include Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<div class="availability-info">
    <?php 

// Ensure copies is not negative and cast to integer
$available_copies = max(0, (int)$availability['available_copies']);

// Check if available copies is more than 0
$is_available = ($available_copies > 0);

// Check if it's the last copy (only 1 remaining)
$is_last_copy = ($available_copies === 1);
?>

<p class="<?php echo $is_available ? 'available' : 'unavailable'; ?>">
    <?php 
    if ($available_copies === 0) {
        echo 'Currently Unavailable';
    } elseif ($is_last_copy) {
        echo 'Reference Only';
    } else {
        echo 'Available for Borrowing';
    }
    ?>
</p>
    
<p>Copies: <?php echo $available_copies; ?></p>

    <!-- Guide Icon with contextual message -->
    <div class="guide-container">
        <i class="fas fa-info-circle guide-icon" onclick="toggleGuideMessage()"></i>
        <p id="guide-message" class="hidden">
            <?php 
            if (!$is_available) {
                echo '📌 We apologize, but the requested book is currently not available.';
            } elseif ($is_last_copy) {
                echo '📌 The last copy of this book is for in-library use only. It cannot be borrowed but may be used within the library premises.';
            } else {
                echo '📌 This book is available for a standard loan period. To borrow this item, please visit our library to complete the checkout process.';
            }
            ?>
        </p>
    </div>
</div>

<!-- JavaScript -->
<script>
    function toggleGuideMessage() {
        let message = document.getElementById("guide-message");
        message.classList.toggle("hidden");
    }
</script>

<!-- CSS for Styling -->
<style>
    .guide-container {
        margin-top: 10px;
        cursor: pointer;
        display: inline-block;
    }

    .guide-icon {
        font-size: 20px;
        color:#004d40; /* Blue color */
    }

    .guide-icon:hover {
        color: #0056b3;
    }

    .hidden {
        display: none;
    }

    #guide-message {
        margin-top: 5px;
        font-size: 14px;
        color:rgb(255, 255, 255); /* Red color */
        font-style: italic;
        background-color:#004d40;
        padding: 1.2rem;
    }
</style>
            </div>
        <div class="book-info-right">
        <div class="book-metadata">
            <h1><?php echo htmlspecialchars($book['title']); ?></h1>

            <table class="metadata-table">
                <tr>
                    <th>Author:</th>
                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                </tr>
                <tr>
                    <th>Edition:</th>
                    <td><?php echo htmlspecialchars($book['edition'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Publication:</th>
                    <td>
                        <?php
                        echo htmlspecialchars($book['place_of_publication'] ?? '');
                        if (!empty($book['place_of_publication']) && !empty($book['publisher'])) echo ': ';
                        echo htmlspecialchars($book['publisher']);
                        echo ', c', htmlspecialchars($book['date_of_publication']);
                        ?>
                    </td>
                </tr>
            </table>

            <table class="metadata-table horizontal">
                <tr>
                    <th>ISBN/ISSN</th>
                    <th>Material Type</th>
                    <th>Classification Number</th>
                    <th>Call Number</th>
                    <th>Accession Number</th>
                    <th>Department</th>
                    <th>Location</th>
                </tr>
                <tr>
                    <td><?php echo htmlspecialchars($book['isbn_issn'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($book['type_of_material'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($book['classification_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($book['call_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($book['accession_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($book['department_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($book['location'] ?? 'N/A'); ?></td>
                </tr>
            </table>

        </div>

        <div class="additional-info">
            <h3>How to Locate This Item</h3>
            <?php
            // Get DDC category based on classification number
            function getDDCCategory($callNo)
            {
                // Extract the first number group from call number
                if (preg_match('/^(\d+)/', $callNo, $matches)) {
                    $classNum = intval($matches[1]);

                    // Determine category range
                    $baseRange = floor($classNum / 100) * 100;
                    $categoryKey = sprintf('%03d-%03d', $baseRange, $baseRange + 99);

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

                    return isset($categories[$categoryKey]) ? $categories[$categoryKey] : 'Uncategorized';
                }
                return 'Uncategorized'; // Default category if no match
            }

            $ddc_category = getDDCCategory($book['classification_number']);
            ?>
<p>This book is located in the <?php echo htmlspecialchars($book['location']); ?> Libary section 
                under category <strong><?php echo $ddc_category; ?></strong>
                (<?php echo substr($book['classification_number'], 0, 3) . '-' . (floor(intval($book['classification_number']) / 100) * 100 + 99); ?>). 
                </p>


            <p>If you need assistance locating this item, please ask a librarian at the information desk.</p>
        </div>
    </div>
</div>

<div class="similar-books">
    <h3>Similar Books</h3>

    <?php
    // Get similar books based on classification number
    $similar_query = "SELECT id, title, author 
                              FROM books 
                              WHERE classification_number = ? 
                              AND id != ? 
                              LIMIT 5";
    $stmt = $conn->prepare($similar_query);
    $stmt->bind_param("si", $book['classification_number'], $book_id);
    $stmt->execute();
    $similar_result = $stmt->get_result();

    if ($similar_result->num_rows > 0):
    ?>
        <div class="similar-books-list">
            <ul>
                <?php while ($similar_book = $similar_result->fetch_assoc()): ?>
                    <li>
                        <a href="book_details.php?id=<?php echo $similar_book['id']; ?>">
                            <?php echo htmlspecialchars($similar_book['title']); ?>
                        </a>
                        <span class="similar-author">by <?php echo htmlspecialchars($similar_book['author']); ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    <?php else: ?>
        <p>No similar books found in the catalog.</p>
    <?php endif; ?>
</div>

<div class="back-button">
    <a href="javascript:history.back();" class="btn btn-secondary">Back to Search Results</a>
</div>
</div>
</div>

<?php
// Include footer
include 'footer.php';
?>

<style>
    .container {
        padding: 20px;
    }

    .metadata-table.horizontal {
        width: 100%;
        border-collapse: collapse;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        margin: 25px 0;
        background: #fff;
    }

    .metadata-table.horizontal th {
        background-color: #004d40;
        color: #ffffff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85em;
        letter-spacing: 0.5px;
        padding: 12px 15px;
        text-align: center;
        border: none;
    }

    .metadata-table.horizontal td {
        padding: 12px 15px;
        text-align: center;
        color: #555;
        border: none;
        border-bottom: 1px solid #eee;
        font-size: 0.95em;
    }

    .metadata-table.horizontal tr:last-child td {
        border-bottom: none;
    }

    .metadata-table.horizontal tr:hover td {
        background-color: #f5f9fc;
        transition: all 0.3s ease;
    }

    /* Zebra striping for better readability */
    .metadata-table.horizontal tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .metadata-table.horizontal {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
            border-radius: 6px;
        }

        .metadata-table.horizontal th,
        .metadata-table.horizontal td {
            padding: 10px;
        }
    }

    /* Book details page specific styles */
    .book-details {
        margin: 20px 0;
        padding: 20px;
        background: #fff;
        border-radius: 8px;

    }

    .book-details h1 {
        font-size: 24px;
        margin-bottom: 20px;
        color: #2c3e50;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    .breadcrumb {
        margin: 15px 0;
        font-size: 14px;
        color: #666;
    }

    .breadcrumb a {
        color: #004d40;
        text-decoration: none;
    }

    .breadcrumb a:hover {
        text-decoration: underline;
    }

    .book-info-container {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 30px;
    }

    .book-info-left {
        flex: 0 0 250px;
        margin-right: 30px;
        margin-bottom: 20px;
    }

    .book-info-right {
        flex: 1;
        min-width: 300px;
    }


    .availability-info {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 5px;
        border-left: 3px solid#004d40;
    }

    .availability-info h3 {
        margin-top: 0;
        font-size: 18px;
        color: #004d40;
    }

    .available {
        color: #27ae60;
        font-weight: bold;
    }

    .unavailable {
        color: #e74c3c;
        font-weight: bold;
    }

    .action-buttons {
        margin-top: 15px;
    }

    .btn {
        display: inline-block;
        padding: 8px 15px;
        border-radius: 4px;
        text-decoration: none;
        text-align: center;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .btn-primary {
        background-color: #004d40;
        color: white;
        border: none;
    }

    .btn-primary:hover {
        background-color: #004d40;
    }

    .btn-secondary {
        background-color: #004d40;
        color: white;
        border: none;
    }

    .btn-secondary:hover {
        background-color: #7f8c8d;
    }

    .book-metadata {
        margin-bottom: 30px;
    }

    .book-metadata h2 {
        font-size: 20px;
        margin-bottom: 15px;
        color: #2c3e50;
    }

    .metadata-table {
        width: 100%;
        border-collapse: collapse;
    }

    .metadata-table th,
    .metadata-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .metadata-table th {
        width: 30%;
        color: #555;
        font-weight: 600;
        vertical-align: top;
    }

    .additional-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .additional-info h3 {
        margin-top: 0;
        font-size: 18px;
        color: #2c3e50;
    }

    .similar-books {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    .similar-books h3 {
        font-size: 18px;
        margin-bottom: 15px;
        color: #2c3e50;
    }

    .similar-books-list ul {
        list-style-type: none;
        padding: 0;
    }

    .similar-books-list li {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px dotted #eee;
    }

    .similar-books-list a {
        color: #3498db;
        text-decoration: none;
        font-weight: 500;
    }

    .similar-books-list a:hover {
        text-decoration: underline;
    }

    .similar-author {
        font-style: italic;
        color: #777;
        font-size: 0.9em;
    }

    .back-button {
        margin-top: 30px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {

        .book-info-left,
        .book-info-right {
            flex: 0 0 100%;
        }

        .book-info-left {
            margin-right: 0;
            margin-bottom: 30px;
        }

        .metadata-table th {
            width: 40%;
        }
    }
</style>