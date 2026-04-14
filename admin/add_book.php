<?php
include '../db_connect.php';

// Fetch departments from database for dropdown
$departmentsQuery = "SELECT * FROM departments ORDER BY department_name";
$departmentsResult = $conn->query($departmentsQuery);
$departments = [];
if ($departmentsResult && $departmentsResult->num_rows > 0) {
    while ($row = $departmentsResult->fetch_assoc()) {
        $departments[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize error array
    $errors = [];

    // Validate and sanitize required fields
    $title = isset($_POST['title']) ? mysqli_real_escape_string($conn, trim($_POST['title'])) : '';
    $author = isset($_POST['author']) ? mysqli_real_escape_string($conn, trim($_POST['author'])) : '';
    $place_of_publication = isset($_POST['place_of_publication']) ? mysqli_real_escape_string($conn, trim($_POST['place_of_publication'])) : null;
    $publisher = isset($_POST['publisher']) ? mysqli_real_escape_string($conn, trim($_POST['publisher'])) : '';
    $date_of_publication = isset($_POST['date_of_publication']) ? $_POST['date_of_publication'] : '';
    $edition = isset($_POST['edition']) ? mysqli_real_escape_string($conn, trim($_POST['edition'])) : null;
    $type_of_material = isset($_POST['type_of_material']) ? mysqli_real_escape_string($conn, trim($_POST['type_of_material'])) : '';
    $classification_number = isset($_POST['classification_number']) ? mysqli_real_escape_string($conn, trim($_POST['classification_number'])) : '';
    $call_number = isset($_POST['call_number']) ? mysqli_real_escape_string($conn, trim($_POST['call_number'])) : '';
    $accession_number = isset($_POST['accession_number']) ? mysqli_real_escape_string($conn, trim($_POST['accession_number'])) : '';
    $copies = isset($_POST['copies']) ? (int)$_POST['copies'] : 1;

    // New fields
    $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $location = isset($_POST['location']) ? mysqli_real_escape_string($conn, trim($_POST['location'])) : '';

    // Determine location if not manually selected (auto-detect)
    if (empty($location) && !empty($date_of_publication)) {
        $current_year = date('Y');
        $publication_year = (int)$date_of_publication;
        $age = $current_year - $publication_year;

        if ($age <= 5) {
            $location = 'New Site';
        } else if ($age <= 15) {
            $location = 'Mid Site';
        } else {
            $location = 'Old Site';
        }
    }

    // Validate required fields - MODIFIED: only title, date_of_publication, and accession_number are required
    if (empty($title)) $errors[] = "Title is required";
    if (empty($date_of_publication)) $errors[] = "Date of publication is required";
    if (empty($accession_number)) $errors[] = "Accession number is required";

    // If no errors, proceed with insertion
    if (empty($errors)) {
        $sql = "INSERT INTO books (
                    title, author, place_of_publication, publisher, 
                    date_of_publication, edition,  type_of_material, 
                    classification_number, call_number, accession_number, copies,
                    department_id, location
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,  ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssssssiss",
            $title,
            $author,
            $place_of_publication,
            $publisher,
            $date_of_publication,
            $edition,
            $type_of_material,
            $classification_number,
            $call_number,
            $accession_number,
            $copies,
            $department_id,
            $location
        );

// Replace the existing PHP code in the if ($stmt->execute()) block with this:
    if ($stmt->execute()) {
        // Store success message in session
        $_SESSION['alert_message'] = 'Book added successfully!';
        $_SESSION['alert_type'] = 'success';
        
        // Redirect to the view books page
        header("Location: add_book.php");
        exit();
    } else {
        // For error handling, add this in the page
        echo "<script>
                console.log('Error: " . $stmt->error . "');
                document.addEventListener('DOMContentLoaded', function() {
                    showAlert('Database Error: " . $stmt->error . "', 'error');
                });
              </script>";
        $errors[] = "Database Error: " . $stmt->error;
    }
    }
}
// MODIFIED: Only title, date_of_publication, and accession_number are required
const requiredFields = [
    'title', 'date_of_publication', 'accession_number'
];
// Check if there's an alert message in the session
if (isset($_SESSION['alert_message'])) {
    $alertMessage = $_SESSION['alert_message'];
    $alertType = $_SESSION['alert_type'] ?? 'success';
    
    // Clear the session variables
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
    
    // Output the JavaScript to show the alert
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showAlert('" . addslashes($alertMessage) . "', '" . addslashes($alertType) . "');
            });
          </script>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book</title>
</head>

<body>

<div class="main-content">
<div id="customAlert" class="custom-alert" style="display: none;"></div>
    <!-- Left Side: Paste Area -->
    <div class="subcontent">
        <div class="paste-guide">
            <h4>Paste Format Guide:</h4>
            <p>Please paste your book data in the following format (one per line):</p>
            <pre>Title
Author
Place of Publication
Publisher
Year of Publication
Edition (e.g., 1st)
ISBN/ISSN
Classification Number
Call Number
Accession Number
Number of Copies</pre>
        </div>
        <textarea id="pasteArea" style="width: 100%; height: 250px; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" placeholder="Paste your book data here..."></textarea>
        <button type="button" onclick="fillForm()" class="submit-btn">Fill Form</button>
    </div>
    
    <!-- Right Side: Form -->
    <div class="subcontent">
        <h2>Add New Book</h2>
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author">
                </div>
                <div class="form-group">
                    <label>Place of Publication</label>
                    <input type="text" name="place_of_publication">
                </div>
                <div class="form-group">
                    <label>Publisher</label>
                    <input type="text" name="publisher">
                </div>
                <div class="form-group">
                    <label>Date of Publication</label>
                    <input type="number" name="date_of_publication" required placeholder="Year only">
                </div>
                <div class="form-group">
                    <label>Edition</label>
                    <input type="text" name="edition" placeholder="e.g., 1st, 2nd, 3rd">
                </div>

                <div class="form-group">
                    <label>Classification Number</label>
                    <input type="text" name="classification_number">
                </div>
                <div class="form-group">
                    <label>Call Number</label>
                    <input type="text" name="call_number">
                </div>
                <div class="form-group">
                    <label>Accession Number</label>
                    <input type="text" name="accession_number" required>
                </div>
                <div class="form-group">
                    <label>Copies</label>
                    <input type="number" name="copies" min="1" value="1">
                </div>
                <div class="form-group">
                    <label>Type of Material</label>
                    <select name="type_of_material">
                        <option value="">Select Type</option>
                        <option value="Book">Book</option>
                        <option value="Journal">Journal</option>
                        <option value="Magazine">Magazine</option>
                        <option value="Thesis">Thesis</option>
                        <option value="Reference">Reference</option>
                    </select>
                </div>
            </div>

            <h4>Book Location</h4>
            <div class="location-options">
                <div class="location-option" onclick="selectLocation(this)" data-location="New Site">New Site</div>
                <div class="location-option" onclick="selectLocation(this)" data-location="Mid Site">Mid Site</div>
                <div class="location-option" onclick="selectLocation(this)" data-location="Old Site">Old Site</div>
            </div>
            <br>
            <div class="form-group">
                <h4>Department</h4>
                <select name="department_id" >
                    <option value="">Select Department</option>
                    <option value=""></option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="location" id="selectedLocation">
            <button type="submit" class="submit-btn">Add Book</button>
        </form>
    </div>
</div>

        <!-- Custom Alert Container -->


        <script>
             // Make sure the showAlert function is defined before it's used
    function showAlert(message, type = 'success') {
        const alertElement = document.getElementById('customAlert');
        if (!alertElement) {
            console.error('Alert element not found');
            return;
        }
        
        alertElement.textContent = message;
        alertElement.className = `custom-alert ${type}`;
        alertElement.style.display = 'block';
        
        // Reset any existing animations
        alertElement.style.animation = '';
        
        // Trigger reflow to ensure animation restarts
        void alertElement.offsetWidth;
        
        // Apply slide in animation
        alertElement.style.animation = 'slideIn 0.3s ease-out forwards';
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            alertElement.style.animation = 'fadeOut 0.3s ease-out forwards';
            setTimeout(() => {
                alertElement.style.display = 'none';
            }, 300);
        }, 3000);
    }
    function fillForm() {
    // Get the pasted text
    const pasteArea = document.getElementById('pasteArea');
    let text = pasteArea.value.trim();

    // Handle both tab-separated and line-separated formats
    let lines;
    if (text.includes('\t')) {
        // Tab-separated format
        lines = text.split('\t').map(line => line.trim());
    } else {
        // Line-separated format
        lines = text.split('\n').map(line => line.trim()).filter(line => line);
    }

    // Validate minimum required fields
    if (lines.length < 10) {
        showAlert('Please paste complete book data with all required fields', 'error');
        return;
    }

    // Map the lines to form fields
    const formFields = {
        'title': lines[0] || '',
        'author': lines[1] || '',
        'place_of_publication': lines[2] || '',
        'publisher': lines[3] || '',
        'date_of_publication': lines[4] || '',
        'edition': lines[5] || '',
        'classification_number': lines[6] || '',
        'call_number': lines[7] || '',
        'accession_number': lines[8] || '',
        'copies': lines[9] || ''
    };

    // Validate and clean the date
    formFields.date_of_publication = formFields.date_of_publication.replace(/[^0-9]/g, '');

    // Clean the edition (remove any unnecessary characters)
    formFields.edition = formFields.edition.replace(/[^0-9a-zA-Z]/g, '') +
        (formFields.edition.match(/[0-9]+/) ? 'th' : '');

    // Fill the form fields
    Object.keys(formFields).forEach(field => {
        const element = document.querySelector(`[name="${field}"]`);
        if (element) {
            element.value = formFields[field];
        }
    });

    // Set default type of material to 'Book'
    document.querySelector('[name="type_of_material"]').value = 'Book';

    // Set call number same as classification number ONLY if call number is empty
    const callNumberField = document.querySelector('[name="call_number"]');
    if (callNumberField && callNumberField.value === '' && formFields.classification_number) {
        callNumberField.value = formFields.classification_number;
    }

    // Suggest location based on publication year
    suggestLocation();

    // Clear the paste area
    pasteArea.value = '';

    // Highlight filled fields for visual feedback
    Object.keys(formFields).forEach(field => {
        const element = document.querySelector(`[name="${field}"]`);
        if (element && element.value) {
            element.style.backgroundColor = '#e8f0fe';
            setTimeout(() => {
                element.style.backgroundColor = '';
            }, 1500);
        }
    });

    showAlert('Form filled successfully!', 'success');
}

function suggestLocation() {
    const yearInput = document.querySelector('[name="date_of_publication"]');
    if (!yearInput) return;
    
    if (!yearInput.value) {
        return;
    }

    const publicationYear = parseInt(yearInput.value);
    const currentYear = new Date().getFullYear();
    const age = currentYear - publicationYear;

    let suggestedLocation;

    if (age <= 5) {
        suggestedLocation = 'New Site';
    } else if (age <= 15) {
        suggestedLocation = 'Mid Site';
    } else {
        suggestedLocation = 'Old Site';
    }

    // Select the appropriate location button
    const locationButtons = document.querySelectorAll('.location-option');
    locationButtons.forEach(btn => {
        if (btn.dataset.location === suggestedLocation) {
            selectLocation(btn);
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

// Additional helper functions for form validation
document.querySelector('form').addEventListener('submit', function(e) {
    // MODIFIED: Only title, date_of_publication, and accession_number are required
    const requiredFields = [
        'title', 'date_of_publication', 'accession_number'
    ];

    let hasErrors = false;
    requiredFields.forEach(field => {
        const element = document.querySelector(`[name="${field}"]`);
        if (element && !element.value.trim()) {
            hasErrors = true;
            element.style.borderColor = '#dc3545';
        } else if (element) {
            element.style.borderColor = '#ddd';
        }
    });



    if (hasErrors) {
        e.preventDefault();
        showAlert('Please fill in all required fields', 'error');
    }
});

// Reset field styling on input
document.querySelectorAll('input, select').forEach(element => {
    element.addEventListener('input', function() {
        this.style.borderColor = '#ddd';
    });
});
        </script>
</body>

</html>

<style>
        .main-content {
        display: flex;
        gap: 20px;
    }
    .subcontent {
        flex: 1;
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .paste-guide pre {
        background: #e9ecef;
        padding: 10px;
        border-radius: 5px;
    }
    .submit-btn {
        background-color: #004d40;
        color: white;
        padding: 6px 12px;
        font-size: 14px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .submit-btn:hover {
        background-color: #00352e;
    }
    /* Main Content Layout */


/* Form Container */
.form-container {
    width: 100%;
    max-width: 800px;
    padding: 25px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
}

/* Heading */
h2 {
    margin-bottom: 15px;
    font-size: 22px;
    font-weight: 600;
    color: #004d40;
    text-align: center;
}

/* Grid Layout */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
}

/* Form Group */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-weight: 500;
    font-size: 14px;
    color: #004d40;
    margin-bottom: 5px;
}

/* Input and Select */
.form-group input,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #bbb;
    border-radius: 5px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #004d40;
    outline: none;
    box-shadow: 0 0 5px rgba(0, 77, 64, 0.3);
}





    /* Add these styles to your page */
    .custom-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        font-weight: 500;
        font-size: 16px;
        max-width: 350px;
        transform: translateX(100%);
        opacity: 0;
    }

    .custom-alert.success {
        background-color: #4CAF50;
        color: white;
        border-left: 5px solid #2E7D32;
    }

    .custom-alert.error {
        background-color: #F44336;
        color: white;
        border-left: 5px solid #B71C1C;
    }

    .custom-alert.warning {
        background-color: #FF9800;
        color: white;
        border-left: 5px solid #E65100;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
    }
/* Responsive Design */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    .submit-btn {
        grid-column: span 1;
        width: 100%;
    }
    h2 {
        font-size: 20px;
    }
}

/* Location Selector */
.location-selector {
    margin-bottom: 15px;
    padding: 12px;
    background-color: #f8f9fa;
    border-radius: 5px;
    border: 1px solid #bbb;
}

.location-options {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}

.location-option {
    padding: 6px 12px;
    border-radius: 3px;
    background-color: #fff;
    border: 1px solid #bbb;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}

.location-option:hover {
    background-color: #e0f2f1;
}

.location-option.selected {
    background-color: #004d40;
    color: white;
    border-color: #004d40;
}

.location-info {
    margin-top: 8px;
    font-size: 0.9em;
    color: #6c757d;
}

</style>
<!-- Add this in the head section of view_books.php -->
<style>
    .custom-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        font-weight: 500;
        font-size: 16px;
        max-width: 350px;
        transform: translateX(100%);
        opacity: 0;
    }

    .custom-alert.success {
        background-color: #4CAF50;
        color: white;
        border-left: 5px solid #2E7D32;
    }

    .custom-alert.error {
        background-color: #F44336;
        color: white;
        border-left: 5px solid #B71C1C;
    }

    .custom-alert.warning {
        background-color: #FF9800;
        color: white;
        border-left: 5px solid #E65100;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
    }
</style>

<!-- Add this right after the opening body tag in view_books.php -->
<div id="customAlert" class="custom-alert" style="display: none;"></div>

<!-- Add this showAlert function in the JavaScript section of view_books.php -->
<script>
function showAlert(message, type = 'success') {
    const alertElement = document.getElementById('customAlert');
    if (!alertElement) {
        console.error('Alert element not found');
        return;
    }
    
    alertElement.textContent = message;
    alertElement.className = `custom-alert ${type}`;
    alertElement.style.display = 'block';
    
    // Reset any existing animations
    alertElement.style.animation = '';
    
    // Trigger reflow to ensure animation restarts
    void alertElement.offsetWidth;
    
    // Apply slide in animation
    alertElement.style.animation = 'slideIn 0.3s ease-out forwards';
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        alertElement.style.animation = 'fadeOut 0.3s ease-out forwards';
        setTimeout(() => {
            alertElement.style.display = 'none';
        }, 300);
    }, 3000);
}
</script>