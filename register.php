<?php
include 'db_connect.php';

if (isset($_POST['register'])) {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = $_POST['user_type'];
    $id_number = $_POST['id_number'];

    // Fields based on user type
    $course = isset($_POST['course']) ? $_POST['course'] : '';
    $year = isset($_POST['year']) ? $_POST['year'] : '';
    $department = isset($_POST['department']) ? $_POST['department'] : '';

    // Check if email already exists
    $check_email = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check_email->num_rows > 0) {
        $error_message = "Email already exists!";
    } else {
        // Insert user data
        $sql = "INSERT INTO users (fullname, email, password, user_type, id_number, course, year, department) 
                VALUES ('$fullname', '$email', '$password', '$user_type', '$id_number', '$course', '$year', '$department')";

        if ($conn->query($sql) === TRUE) {
            $success_message = "Registration successful. <a href='login.php'>Login here</a>";
        } else {
            $error_message = "Error: " . $sql . " " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Library System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="form-container">
            <h3 class="text-center">Register</h3>

            <div id="message"></div>
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if(isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <div class="btn-group">
                <button type="button" id="student-toggle" class="btn active">Student</button>
                <button type="button" id="staff-toggle" class="btn">Staff</button>
            </div>

            <form method="POST" id="registration-form">
                <input type="hidden" name="user_type" id="user_type" value="student">

                <div class="form-group">
                    <label id="name-label">Student Name</label>
                    <div class="input-field">
                        <i class="fas fa-user field-icon"></i>
                        <input type="text" name="fullname" required placeholder="Full name...">
                    </div>
                </div>

                <div class="form-group">
                    <label id="id-label">Student ID</label>
                    <div class="input-field">
                        <i class="fas fa-id-card field-icon"></i>
                        <input type="text" name="id_number" required placeholder="Student ID number...">
                    </div>
                </div>

                <div id="student-fields">
                    <div class="form-group">
                        <label>Course</label>
                        <div class="input-field">
                            <i class="fas fa-book field-icon"></i>
                            <input type="text" name="course" placeholder="Course...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Year</label>
                        <div class="input-field">
                            <select name="year" id="year">
                                <option value="">Select Year</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="5th Year">5th Year</option>
                                <option value="Irregular">Irregular</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="staff-fields" class="hidden">
                    <div class="form-group">
                        <label>Department/Office</label>
                        <div class="input-field">
                            <i class="fas fa-building field-icon"></i>
                            <input type="text" name="department" placeholder="Department/Office...">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <div class="input-field">
                        <i class="fas fa-envelope field-icon"></i>
                        <input type="email" name="email" required placeholder="Example@gmail.com">
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-field">
                        <i class="fas fa-lock field-icon"></i>
                        <input type="password" name="password" id="password" required placeholder="Enter a strong password">
                        <span class="toggle-password" onclick="togglePassword('password')">
                            <i id="password-icon" class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div class="password-strength-meter">
                        <div class="strength-bars">
                            <div class="bar" id="bar-1"></div>
                            <div class="bar" id="bar-2"></div>
                            <div class="bar" id="bar-3"></div>
                            <div class="bar" id="bar-4"></div>
                        </div>
                        <small id="password-strength-text">Password strength</small>
                    </div>
                    <div class="password-criteria">
                        <p id="password-hint" class="hint">Your password must have:</p>
                        <ul>
                            <li id="length-check"><i class="fas fa-times-circle"></i> At least 8 characters</li>
                            <li id="uppercase-check"><i class="fas fa-times-circle"></i> At least 1 uppercase letter</li>
                            <li id="lowercase-check"><i class="fas fa-times-circle"></i> At least 1 lowercase letter</li>
                            <li id="number-check"><i class="fas fa-times-circle"></i> At least 1 number</li>
                            <li id="special-check"><i class="fas fa-times-circle"></i> At least 1 special character</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-field">
                        <i class="fas fa-lock field-icon"></i>
                        <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm your password">
                        <span class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i id="confirm_password-icon" class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div id="password-error" class="error hidden">Passwords do not match!</div>
                </div>

                <button type="submit" name="register" class="submit-btn">Create Account</button>
            </form>
            <p class="text-center">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script>
        document.getElementById('student-toggle').addEventListener('click', function() {
            document.getElementById('user_type').value = 'student';
            document.getElementById('student-fields').classList.remove('hidden');
            document.getElementById('staff-fields').classList.add('hidden');
            document.getElementById('id-label').textContent = 'Student ID';
            document.getElementById('name-label').textContent = 'Student Name';
            this.classList.add('active');
            document.getElementById('staff-toggle').classList.remove('active');
        });

        document.getElementById('staff-toggle').addEventListener('click', function() {
            document.getElementById('user_type').value = 'staff';
            document.getElementById('student-fields').classList.add('hidden');
            document.getElementById('staff-fields').classList.remove('hidden');
            document.getElementById('id-label').textContent = 'Staff ID';
            document.getElementById('name-label').textContent = 'Staff Name';
            this.classList.add('active');
            document.getElementById('student-toggle').classList.remove('active');
        });

        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = document.getElementById(`${fieldId}-icon`);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength checker
        document.getElementById('password').addEventListener('input', checkPasswordStrength);

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const lengthCheck = document.getElementById('length-check');
            const uppercaseCheck = document.getElementById('uppercase-check');
            const lowercaseCheck = document.getElementById('lowercase-check');
            const numberCheck = document.getElementById('number-check');
            const specialCheck = document.getElementById('special-check');
            
            // Update individual criteria checks
            updateCriteriaCheck(lengthCheck, password.length >= 8);
            updateCriteriaCheck(uppercaseCheck, /[A-Z]/.test(password));
            updateCriteriaCheck(lowercaseCheck, /[a-z]/.test(password));
            updateCriteriaCheck(numberCheck, /[0-9]/.test(password));
            updateCriteriaCheck(specialCheck, /[^A-Za-z0-9]/.test(password));
            
            // Calculate strength
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update strength meter
            updateStrengthMeter(strength);
        }
        
        function updateCriteriaCheck(element, isValid) {
            const icon = element.querySelector('i');
            if (isValid) {
                icon.className = 'fas fa-check-circle';
                icon.style.color = '#4CAF50';
            } else {
                icon.className = 'fas fa-times-circle';
                icon.style.color = '#F44336';
            }
        }
        
        function updateStrengthMeter(strength) {
            const strengthText = document.getElementById('password-strength-text');
            const bars = [
                document.getElementById('bar-1'),
                document.getElementById('bar-2'),
                document.getElementById('bar-3'),
                document.getElementById('bar-4')
            ];
            
            // Reset all bars
            bars.forEach(bar => {
                bar.className = 'bar';
                bar.style.backgroundColor = '#DDD';
            });
            
            // Set text and colors based on strength
            if (strength === 0) {
                strengthText.textContent = 'Password strength';
                strengthText.style.color = '#666';
            } else if (strength <= 2) {
                for (let i = 0; i < 1; i++) {
                    bars[i].style.backgroundColor = '#F44336'; // Red
                }
                strengthText.textContent = 'Weak';
                strengthText.style.color = '#F44336';
            } else if (strength <= 3) {
                for (let i = 0; i < 2; i++) {
                    bars[i].style.backgroundColor = '#FFA726'; // Orange
                }
                strengthText.textContent = 'Moderate';
                strengthText.style.color = '#FFA726';
            } else if (strength === 4) {
                for (let i = 0; i < 3; i++) {
                    bars[i].style.backgroundColor = '#42A5F5'; // Blue
                }
                strengthText.textContent = 'Good';
                strengthText.style.color = '#42A5F5';
            } else {
                for (let i = 0; i < 4; i++) {
                    bars[i].style.backgroundColor = '#4CAF50'; // Green
                }
                strengthText.textContent = 'Strong';
                strengthText.style.color = '#4CAF50';
            }
        }

        // Check password match
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const passwordError = document.getElementById('password-error');
            
            if (confirmPassword !== '') {
                if (password !== confirmPassword) {
                    passwordError.classList.remove('hidden');
                } else {
                    passwordError.classList.add('hidden');
                }
            }
        });

        // Form validation
        document.getElementById('registration-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Check if password meets criteria
            const isLengthValid = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[^A-Za-z0-9]/.test(password);
            
            if (!(isLengthValid && hasUppercase && hasLowercase && hasNumber && hasSpecial)) {
                e.preventDefault();
                alert('Please ensure your password meets all security requirements');
                return false;
            }
            
            // Check if passwords match
            if (password !== confirmPassword) {
                e.preventDefault();
                document.getElementById('password-error').classList.remove('hidden');
                return false;
            }
        });
    </script>
</body>
</html>
    
    <style>
        .alert {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-danger {
    background-color: #FFEBEE;
    color: #C62828;
    border: 1px solid #EF9A9A;
}

.alert-success {
    background-color: #E8F5E9;
    color: #2E7D32;
    border: 1px solid #A5D6A7;
}
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 500px;
            margin: 30px auto;
            padding: 30px;
            border-radius: 12px;
        }

        .text-center {
            color: #004d40;
            text-align: center;
        }

        .btn-group {
            display: flex;
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            background-color: #f4f4f4;
            color: #666;
            font-size: 15px;
        }

        .btn.active {
            background-color: #004d40;
            color: white;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        .input-field, .password-field {
            position: relative;
            margin-bottom: 20px;
        }

        .field-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #757575;
        }

        input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        input:focus {
            border-color: #004d40;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 77, 64, 0.1);
        }

        .password-field input {
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #757575;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #004d40;
        }

        .password-strength-meter {
            margin-bottom: 15px;
        }

        .strength-bars {
            display: flex;
            gap: 5px;
            margin-bottom: 5px;
        }

        .bar {
            height: 5px;
            flex: 1;
            background-color: #ddd;
            border-radius: 2px;
            transition: background-color 0.3s;
        }

        .password-criteria {
            background-color: #f7f7f7;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .password-criteria .hint {
            margin: 0 0 8px 0;
            font-weight: 500;
        }

        .password-criteria ul {
            margin: 0;
            padding-left: 25px;
        }

        .password-criteria li {
            margin-bottom: 5px;
        }

        .password-criteria i {
            margin-right: 5px;
        }

        #password-strength-text {
            font-size: 12px;
            color: #666;
        }

        .hidden {
            display: none;
        }

        .error {
            color: #F44336;
            font-size: 13px;
            margin: -10px 0 15px;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: #004d40;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .submit-btn:hover {
            background: #00695c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 77, 64, 0.2);
        }


        @media (max-width: 576px) {
            .container {
                width: 90%;
                padding: 20px;
                margin: 15px auto;
            }

            .btn {
                font-size: 14px;
                padding: 10px;
            }

        }
    </style>
</body>
</html>