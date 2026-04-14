<?php
include '../db_connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch departments from database for dropdown
$departmentsQuery = "SELECT * FROM departments ORDER BY department_name";
$departmentsResult = $conn->query($departmentsQuery);
$departments = [];
if ($departmentsResult && $departmentsResult->num_rows > 0) {
    while($row = $departmentsResult->fetch_assoc()) {
        $departments[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $place_of_publication = mysqli_real_escape_string($conn, $_POST['place_of_publication']);
    $publisher = mysqli_real_escape_string($conn, $_POST['publisher']);
    $date_of_publication = mysqli_real_escape_string($conn, $_POST['date_of_publication']);
    $edition = mysqli_real_escape_string($conn, $_POST['edition']);
    $isbn_issn = mysqli_real_escape_string($conn, $_POST['isbn_issn']);
    $type_of_material = mysqli_real_escape_string($conn, $_POST['type_of_material']);
    $classification_number = mysqli_real_escape_string($conn, $_POST['classification_number']);
    $call_number = mysqli_real_escape_string($conn, $_POST['call_number']);
    $accession_number = mysqli_real_escape_string($conn, $_POST['accession_number']);
    $copies = (int)$_POST['copies'];
    
    // New fields
    $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    $sql = "UPDATE books SET 
            title = '$title',
            author = '$author',
            place_of_publication = '$place_of_publication',
            publisher = '$publisher',
            date_of_publication = '$date_of_publication',
            edition = '$edition',
            isbn_issn = '$isbn_issn',
            type_of_material = '$type_of_material',
            classification_number = '$classification_number',
            call_number = '$call_number',
            accession_number = '$accession_number',
            copies = $copies,
            department_id = " . ($department_id ? $department_id : "NULL") . ",
            location = '$location'
            WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Book updated successfully!'); window.location.href='view_books.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

$sql = "SELECT * FROM books WHERE id = $id";
$result = $conn->query($sql);
$book = $result->fetch_assoc();

if (!$book) {
    echo "<script>alert('Book not found!'); window.location.href='view_books.php';</script>";
    exit;
}
?>

<?php include '_layout.php'; ?>


<div class="main-content">
    <div class="form-container">
        <h2 class="form-title">Edit Book</h2>
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label class="required">Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="required">Author</label>
                    <input type="text" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Place of Publication</label>
                    <input type="text" name="place_of_publication" value="<?php echo htmlspecialchars($book['place_of_publication']); ?>">
                </div>
                <div class="form-group">
                    <label class="required">Publisher</label>
                    <input type="text" name="publisher" value="<?php echo htmlspecialchars($book['publisher']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="required">Date of Publication</label>
                    <input type="number" name="date_of_publication" id="publication_year" value="<?php echo htmlspecialchars($book['date_of_publication']); ?>" required onchange="suggestLocation()">
                </div>
                <div class="form-group">
                    <label>Edition</label>
                    <input type="text" name="edition" value="<?php echo htmlspecialchars($book['edition']); ?>" placeholder="e.g., 1st, 2nd, 3rd">
                </div>
                <div class="form-group">
                    <label>ISBN/ISSN</label>
                    <input type="text" name="isbn_issn" value="<?php echo htmlspecialchars($book['isbn_issn']); ?>" placeholder="Enter ISBN or ISSN">
                </div>
                <div class="form-group">
                    <label class="required">Type of Material</label>
                    <select name="type_of_material" required>
                        <option value="">Select Type</option>
                        <option value="Book" <?php echo $book['type_of_material'] == 'Book' ? 'selected' : ''; ?>>Book</option>
                        <option value="Journal" <?php echo $book['type_of_material'] == 'Journal' ? 'selected' : ''; ?>>Journal</option>
                        <option value="Magazine" <?php echo $book['type_of_material'] == 'Magazine' ? 'selected' : ''; ?>>Magazine</option>
                        <option value="Thesis" <?php echo $book['type_of_material'] == 'Thesis' ? 'selected' : ''; ?>>Thesis</option>
                        <option value="Reference" <?php echo $book['type_of_material'] == 'Reference' ? 'selected' : ''; ?>>Reference</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="required">Classification Number</label>
                    <input type="text" name="classification_number" value="<?php echo htmlspecialchars($book['classification_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="required">Call Number</label>
                    <input type="text" name="call_number" value="<?php echo htmlspecialchars($book['call_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="required">Accession Number</label>
                    <input type="text" name="accession_number" value="<?php echo htmlspecialchars($book['accession_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="required">Copies</label>
                    <input type="number" name="copies" value="<?php echo htmlspecialchars($book['copies']); ?>" required min="1">
                </div>
                <div class="form-group">
                    <label class="required">Department</label>
                    <select name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo ($book['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Location Selection - spans both columns -->
                <div class="location-selector">
                    <h4>Book Location</h4>
                    
                    <div class="location-options">
                        <div class="location-option <?php echo ($book['location'] == 'New Site Library') ? 'selected' : ''; ?>" data-location="New Site Library" onclick="selectLocation(this)">New Site Library</div>
                        <div class="location-option <?php echo ($book['location'] == 'Mid Site Library') ? 'selected' : ''; ?>" data-location="Mid Site Library" onclick="selectLocation(this)">Mid Site Library</div>
                        <div class="location-option <?php echo ($book['location'] == 'Old Site Library') ? 'selected' : ''; ?>" data-location="Old Site Library" onclick="selectLocation(this)">Old Site Library</div>
                    </div>
                    
                    
                    <input type="hidden" name="location" id="selectedLocation" value="<?php echo htmlspecialchars($book['location']); ?>">
                </div>
            </div>
            <button type="submit" class="submit-btn">Update Book</button>
        </form>
    </div>
</div>

<script>
    function suggestLocation() {
        const yearInput = document.getElementById('publication_year');
        const locationInfo = document.getElementById('locationInfo');
        
        if (!yearInput.value) {
            locationInfo.textContent = 'Enter publication year to get a suggested location.';
            return;
        }
        
        const publicationYear = parseInt(yearInput.value);
        const currentYear = new Date().getFullYear();
        const age = currentYear - publicationYear;
        
        let suggestedLocation;
        let explanation;
        
        if (age <= 5) {
            suggestedLocation = 'New Site';
            explanation = `This is a newer book (${age} years old), suggested for New Site.`;
        } else if (age <= 15) {
            suggestedLocation = 'Mid Site';
            explanation = `This book is ${age} years old, suggested for Mid Site.`;
        } else {
            suggestedLocation = 'Old Site';
            explanation = `This is an older book (${age} years old), suggested for Old Site.`;
        }
        
        // Update the location info text
        locationInfo.textContent = explanation;
        
        // Select the appropriate location button
        const locationButtons = document.querySelectorAll('.location-option');
        locationButtons.forEach(btn => {
            btn.classList.remove('selected');
            if (btn.dataset.location === suggestedLocation) {
                btn.classList.add('selected');
                document.getElementById('selectedLocation').value = suggestedLocation;
            }
        });
    }

    function selectLocation(element) {
        // Remove selected class from all options
        document.querySelectorAll('.location-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        
        // Add selected class to clicked option
        element.classList.add('selected');
        
        // Update hidden input
        document.getElementById('selectedLocation').value = element.dataset.location;
    }
</script>