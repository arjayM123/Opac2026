<?php
session_start();
include '../db_connect.php';

// Check if user is logged in (assuming you have a session)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $error_message = "User not found!";
} else {
    $user = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $id_number = trim($_POST['id_number']);
    
    // Optional fields based on user_type
    $course = isset($_POST['course']) ? trim($_POST['course']) : null;
    $year = isset($_POST['year']) ? trim($_POST['year']) : null;
    $department = isset($_POST['department']) ? trim($_POST['department']) : null;
    
    // Set up SQL parameters
    $types = "sss";
    $params = array($fullname, $email, $id_number);
    
    // Add course if applicable
    if ($course !== null) {
        $types .= "s";
        $params[] = $course;
    }
    
    // Add year if applicable
    if ($year !== null) {
        $types .= "s";
        $params[] = $year;
    }
    
    // Add department if applicable
    if ($department !== null) {
        $types .= "s";
        $params[] = $department;
    }
    
    // Add user_id at the end for WHERE clause
    $types .= "i";
    $params[] = $user_id;
    
    if (empty($error_message)) {
        // Build the SQL query based on user_type
        $sql = "UPDATE users SET fullname = ?, email = ?, id_number = ?";
        
        if ($user['user_type'] == 'student') {
            $sql .= ", course = ?, year = ?";
        } else if ($user['user_type'] == 'staff' || $user['user_type'] == 'admin') {
            $sql .= ", department = ?";
        }
        
        $sql .= " WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $error_message = "Error updating profile: " . $conn->error;
        }
    }
}
include 'we.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 1s';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 1000);
            }, 5000);
        });
    }
});
</script>
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <h2>Edit profile</h2>
                    <hr>
                    <br>
                    <nav class="nav-links" id="navLinks"></nav>
                    <form id="edit-profile-form" method="post" action="">
                        <div class="form-group mb-3">
                            <label for="fullname">Full Name</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="id_number">ID Number</label>
                            <input type="text" class="form-control" id="id_number" name="id_number" value="<?php echo htmlspecialchars($user['id_number']); ?>" required>
                        </div>
                        
                        <?php if ($user['user_type'] == 'student'): ?>
                            <div class="form-group mb-3">
                                <label for="course">Course</label>
                                <input type="text" class="form-control" id="course" name="course" value="<?php echo htmlspecialchars($user['course']); ?>">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="year">Year</label>
                                <input type="text" class="form-control" id="year" name="year" value="<?php echo htmlspecialchars($user['year']); ?>">
                            </div>
                        <?php elseif ($user['user_type'] == 'staff' || $user['user_type'] == 'admin'): ?>
                            <div class="form-group mb-3">
                                <label for="department">Department</label>
                                <input type="text" class="form-control" id="department" name="department" value="<?php echo htmlspecialchars($user['department']); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>



<style>

.container {
        padding: 0;
        margin: 0 auto;
    }


/* Card styles */
.card {
        width: 100%;
        max-width: 600px;
        border-radius: 15px;
        padding: 20px;
                margin: 0 auto;
    }


.card-body {
    padding: 20px;
    border-radius: 0 0 15px 15px;
}

/* Form Group Styles */
.form-group {
    margin-bottom: 25px;
    display: flex;
    gap: 50px;
}

.form-group label {
    width: 200px;
    font-weight: 600;
    font-size: 1.1rem;
    color: #333;
    display: block;
}

/* Input Fields */
.form-control {
    padding: 15px;
    font-size: 1rem;
    border-radius: 8px;
    border: 1px solid #ccc;
    width: 100%;
    transition: all 0.3s ease;
    background-color: #f0f4f8;
    color: #333;
}

.form-control:focus {
    border-color: #004d40; /* Change to deep teal */
    background-color: #e9f0ff;
    outline: none;
    box-shadow: 0 0 10px rgba(0, 77, 64, 0.3); /* Focus effect */
}

/* Placeholder Text */
.form-control::placeholder {
    color: #b5b5b5;
    font-style: italic;
}

/* Buttons */
.btn {
    padding: 12px 25px;
    font-size: 1.1rem;
    font-weight: 500;
    border-radius: 25px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
}

.btn-primary {
    background-color: #004d40; /* Deep teal */
    color: white;
    
}

.btn-primary:hover {
    background-color: #003d33; /* Darker teal on hover */
    transform: translateY(-2px);
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    list-style: none;
    text-decoration: none;
    
}

/* Alerts */
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 1rem;
    animation: fadeIn 1s ease-in-out;
}

.alert-success {
    background-color: #28a745;
    color: white;
}

.alert-danger {
    background-color: #dc3545;
    color: white;
}

/* Password Matching Error */
#password-match-error {
    font-size: 0.9rem;
    color: #dc3545;
    margin-top: 5px;
}

/* Animations */
@keyframes fadeIn {
    0% {
        opacity: 0;
        transform: translateY(10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Layout */
@media (max-width: 768px) {
    .container {
        margin-top: 20px;
    }

    .card-body {
        padding: 20px;
    }

    .form-group label,
    .form-control {
        font-size: 0.9rem;
    }

    .btn {
        width: 100%;
        margin-top: 10px;
    }
}

</style>