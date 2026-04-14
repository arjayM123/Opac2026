<?php
session_start();
include '../db_connect.php';


// Check if user is logged in
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
    // Validate old password
    $current_password = $_POST['current_password'];
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    
    if (!password_verify($current_password, $user_data['password'])) {
        $error_message = "Current password is incorrect!";
    } else {
        // Process new password if provided
        if (!empty($_POST['new_password'])) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Validate password strength
            if (strlen($new_password) < 8) {
                $error_message = "Password must be at least 8 characters long!";
            } elseif (!preg_match("#[0-9]+#", $new_password)) {
                $error_message = "Password must include at least one number!";
            } elseif (!preg_match("#[A-Z]+#", $new_password)) {
                $error_message = "Password must include at least one uppercase letter!";
            } elseif (!preg_match("#[a-z]+#", $new_password)) {
                $error_message = "Password must include at least one lowercase letter!";
            } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
                $error_message = "Password must include at least one special character!";
            } elseif ($new_password === $current_password) {
                $error_message = "New password cannot be the same as current password!";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "Passwords do not match!";
            } else {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Prepare the SQL query to update the password
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);

                if ($stmt->execute()) {
                    $success_message = "Password updated successfully!";
                    
                    // Log the password change
                    $log_stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity, ip_address) VALUES (?, ?, ?)");
                    $activity = "Password changed";
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $log_stmt->bind_param("iss", $user_id, $activity, $ip_address);
                    $log_stmt->execute();
                    
                    // Send email notification
                    $to = $user['email'];
                    $subject = "Password Change Notification";
                    $message = "Your password was changed on " . date("Y-m-d H:i:s") . ". If you did not make this change, please contact support immediately.";
                    $headers = "From: noreply@yoursite.com";
                    
                    mail($to, $subject, $message, $headers);
                } else {
                    $error_message = "Error updating password. Please try again.";
                }
            }
        } else {
            $error_message = "New password cannot be empty!";
        }
    }
}
include 'we.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <h2>Change Password</h2>
                <hr>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <nav class="nav-links" id="navLinks"></nav>
                    <form id="edit-profile-form" method="post" action="" autocomplete="off">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <i class="fas fa-eye toggle-password"></i>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="new_password">New Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <i class="fas fa-eye toggle-password"></i>
                            </div>
                            <div class="password-requirements">
                                <p>Password must:</p>
                                <ul>
                                    <li id="length" class="invalid">Be at least 8 characters</li>
                                    <li id="uppercase" class="invalid">Include uppercase letter</li>
                                    <li id="lowercase" class="invalid">Include lowercase letter</li>
                                    <li id="number" class="invalid">Include number</li>
                                    <li id="special" class="invalid">Include special character</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <i class="fas fa-eye toggle-password"></i>
                            </div>
                            <div id="password-match-error" class="text-danger" style="display: none;">Passwords do not match!</div>
                        </div>
                        
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary" id="submit-btn" disabled>Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password visibility toggle
        const toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const passwordField = button.parentElement.querySelector('input');
                if (passwordField.type === "password") {
                    passwordField.type = "text";
                    button.classList.remove("fa-eye");
                    button.classList.add("fa-eye-slash");
                } else {
                    passwordField.type = "password";
                    button.classList.remove("fa-eye-slash");
                    button.classList.add("fa-eye");
                }
            });
        });

        const form = document.getElementById('edit-profile-form');
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const currentPassword = document.getElementById('current_password');
        const passwordMatchError = document.getElementById('password-match-error');
        const submitBtn = document.getElementById('submit-btn');

        // Password validation criteria
        const lengthCriteria = document.getElementById('length');
        const uppercaseCriteria = document.getElementById('uppercase');
        const lowercaseCriteria = document.getElementById('lowercase');
        const numberCriteria = document.getElementById('number');
        const specialCriteria = document.getElementById('special');

        // Real-time password validation
        newPassword.addEventListener('input', function() {
            const password = newPassword.value;
            
            // Check length
            if (password.length >= 8) {
                lengthCriteria.classList.remove('invalid');
                lengthCriteria.classList.add('valid');
            } else {
                lengthCriteria.classList.remove('valid');
                lengthCriteria.classList.add('invalid');
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                uppercaseCriteria.classList.remove('invalid');
                uppercaseCriteria.classList.add('valid');
            } else {
                uppercaseCriteria.classList.remove('valid');
                uppercaseCriteria.classList.add('invalid');
            }
            
            // Check lowercase
            if (/[a-z]/.test(password)) {
                lowercaseCriteria.classList.remove('invalid');
                lowercaseCriteria.classList.add('valid');
            } else {
                lowercaseCriteria.classList.remove('valid');
                lowercaseCriteria.classList.add('invalid');
            }
            
            // Check number
            if (/[0-9]/.test(password)) {
                numberCriteria.classList.remove('invalid');
                numberCriteria.classList.add('valid');
            } else {
                numberCriteria.classList.remove('valid');
                numberCriteria.classList.add('invalid');
            }
            
            // Check special character
            if (/[^A-Za-z0-9]/.test(password)) {
                specialCriteria.classList.remove('invalid');
                specialCriteria.classList.add('valid');
            } else {
                specialCriteria.classList.remove('valid');
                specialCriteria.classList.add('invalid');
            }
            
            validateForm();
        });

        // Password matching validation
        function validatePasswords() {
            if (newPassword.value !== confirmPassword.value) {
                passwordMatchError.style.display = 'block';
                return false;
            } else {
                passwordMatchError.style.display = 'none';
                return true;
            }
        }

        // Check passwords match on input
        confirmPassword.addEventListener('input', function() {
            validatePasswords();
            validateForm();
        });

        // Enable/disable submit button based on form validity
        function validateForm() {
            const isPasswordValid = newPassword.value.length >= 8 && 
                                  /[A-Z]/.test(newPassword.value) && 
                                  /[a-z]/.test(newPassword.value) && 
                                  /[0-9]/.test(newPassword.value) && 
                                  /[^A-Za-z0-9]/.test(newPassword.value);
            
            const doPasswordsMatch = newPassword.value === confirmPassword.value;
            const isCurrentPasswordFilled = currentPassword.value.length > 0;
            
            submitBtn.disabled = !(isPasswordValid && doPasswordsMatch && isCurrentPasswordFilled);
        }

        // Check current password field
        currentPassword.addEventListener('input', validateForm);

        // Form submission validation
        form.addEventListener('submit', function(event) {
            if (newPassword.value !== confirmPassword.value) {
                event.preventDefault();
                passwordMatchError.style.display = 'block';
            }
        });

        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 1s';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 1000);
            }, 5000);
        });
    });
</script>

<style>
    /* General container setup */
    .container {
        padding: 0;

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
    
    /* Password container */
    .password-container {
        position: relative;
        width: 100%;
    }
    
    /* Password strength indicators */
    .password-requirements {
        margin-top: 10px;
        font-size: 0.9rem;
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
    }
    
    .password-requirements p {
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .password-requirements ul {
        list-style-type: none;
        padding-left: 10px;
        margin-bottom: 0;
    }
    
    .password-requirements li {
        margin-bottom: 3px;
        position: relative;
        padding-left: 20px;
    }
    
    .password-requirements li::before {
        content: "×";
        position: absolute;
        left: 0;
        color: #dc3545;
        font-weight: bold;
    }
    
    .password-requirements li.valid::before {
        content: "✓";
        color: #28a745;
    }
    
    .invalid {
        color: #dc3545;
    }
    
    .valid {
        color: #28a745;
    }

    /* Toggle password visibility */
    .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
    }

    /* Form Group Styles */
    .form-group {
        margin-bottom: 25px;
        position: relative;
    }

    .form-group label {
        font-weight: 600;
        font-size: 1.1rem;
        color: #333;
        display: block;
        margin-bottom: 8px;
    }

    /* Input Fields */
    .form-control {
        padding: 15px;
        font-size: 1rem;
        border-radius: 8px;
        border: 1px solid #ccc;
        width: 100%;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
        color: #333;
    }

    .form-control:focus {
        border-color: #004d40;
        background-color: #fff;
        outline: none;
        box-shadow: 0 0 10px rgba(0, 77, 64, 0.2);
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
        background-color: #004d40;
        color: white;
    }

    .btn-primary:hover:not([disabled]) {
        background-color: #003d33;
        transform: translateY(-2px);
    }
    
    .btn-primary:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
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