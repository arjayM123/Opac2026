<?php
// Get DDC category based on classification number
function getDDCCategory($callNo)
{
    // Define categories within the function to ensure it's always available
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

    // Extract the first number group from call number
    if (preg_match('/^(\d+)/', $callNo, $matches)) {
        $classNum = intval($matches[1]);

        // Determine category range
        $baseRange = floor($classNum / 100) * 100;
        $categoryKey = sprintf('%03d-%03d', $baseRange, $baseRange + 99);

        return isset($categories[$categoryKey]) ? $categories[$categoryKey] : 'Uncategorized';
    }
    return 'Uncategorized'; // Default category if no match
}

// Include database connection
require_once '../db_connect.php';
include '_layout.php';

// Get book ID from URL
$book_id = intval($_GET['id']);

// Fetch book details with department name
$query = "SELECT b.*, d.department_name as department_name 
          FROM books b 
          LEFT JOIN departments d ON b.department_id = d.id 
          WHERE b.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if book exists
if ($result->num_rows == 0) {
    echo "Book not found.";
    exit;
}

// Fetch book details
$book = $result->fetch_assoc();

// Define categories for use in the DDC display section
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

// Get DDC category
$ddc_category = getDDCCategory($book['classification_number']);



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Details</title>

</head>
<body>
    <div class="container">
        <h4 style="text-align: center; margin-bottom: 20px;">Book Full Details</h4>
        
        <table class="book-details-table">
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
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php 
                    $fields = [
                        'title', 'author', 'place_of_publication', 'publisher', 
                        'date_of_publication', 'edition', 'isbn_issn', 'department_name', 
                        'location', 'type_of_material', 'classification_number', 
                        'call_number', 'accession_number', 'copies'
                    ];

                    foreach ($fields as $field) {
                        $value = $book[$field] ?? 'N/A';
                        echo "<td data-field='" . htmlspecialchars($field) . "'>";
                        echo "<div class='cell-content'>" . htmlspecialchars($value) . "</div>";
                        echo "</td>";
                    }
                    ?>
                </tr>
            </tbody>
        </table>
<?php
        if (!empty($book['classification_number'])): ?>
<div class="ddc-category">
    DDC Category: <?php 
    // Find the matching category range
    $classNum = intval($book['classification_number']);
    $matchedCategory = 'Uncategorized';
    foreach ($categories as $range => $description) {
        list($start, $end) = explode('-', $range);
        if ($classNum >= intval($start) && $classNum <= intval($end)) {
            $matchedCategory = "$range: $description";
            break;
        }
    }
    echo htmlspecialchars($matchedCategory); 
    ?>
</div>
<?php endif; ?>


        <a href="javascript:history.back();" class="back-btn">Back</a>
    </div>

    <!-- Modal for Full Content -->
    <div id="contentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"></h2>
                <span class="modal-close">&times;</span>
            </div>
            <p id="modalText"></p>
        </div>
    </div>
        <?php include 'footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('contentModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalText = document.getElementById('modalText');
            const closeModal = document.querySelector('.modal-close');

            // Add click event to table cells
            document.querySelectorAll('.book-details-table td').forEach(cell => {
                cell.addEventListener('click', () => {
                    const field = cell.getAttribute('data-field');
                    const text = cell.textContent.trim();

                    // Capitalize field name for title
                    const fieldTitle = field.replace(/_/g, ' ')
                        .split(' ')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                        .join(' ');

                    modalTitle.textContent = fieldTitle;
                    modalText.textContent = text;
                    modal.style.display = 'flex';
                });
            });

            // Close modal when clicking 'x'
            closeModal.addEventListener('click', () => {
                modal.style.display = 'none';
            });

            // Close modal when clicking outside
            window.addEventListener('click', (event) => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>

<style>

.container {
    margin: 0 auto;
    background: white;
    padding: 20px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.book-details-table {
    width: 100%;
    border-collapse: collapse;
}

.book-details-table th, 
.book-details-table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
    position: relative;
}

.book-details-table th {
    background-color: #006747;
    color: white;
}

.book-details-table td {
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.book-details-table td:hover {
    background-color: #f0f0f0;
}

.cell-content {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 80%;
    overflow-y: auto;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.modal-close {
    color: #ff0000;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}

.ddc-category {
    background-color: #e0f2f1;
    color: #006747;
    padding: 15px;
    text-align: center;
    margin-top: 20px;
    font-weight: bold;
}

.back-btn {
    display: block;
    width: 100px;
    margin: 20px auto;
    padding: 10px;
    background-color: #006747;
    color: white;
    text-align: center;
    text-decoration: none;
    border-radius: 5px;
}

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


</style>