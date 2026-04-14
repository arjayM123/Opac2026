

<?php
session_start();
include 'db_connect.php';

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check user in database
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['fullname'];
            $_SESSION['user_type'] = $row['user_type'];
            $_SESSION['id_number'] = $row['id_number'];

            // Redirect based on user_type
            if ($row['user_type'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error_message = "Invalid password!";
        }
    } else {
        $error_message = "User not found!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Library System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <img src="admin/assets/img/images-removebg-preview.png" alt="Library Logo">
                <h3>OPAC - ISUR</h3>
                <p>Online Public Access Catalog</p>
            </div>
            <div class="card-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <span id="toggle-password" class="toggle-icon"><i class="fa-solid fa-eye"></i></span>
                        </div>
                    </div>
                    <button type="submit" class="btn">Login</button>
                </form>
            </div>
            <div class="card-footer">
            <p class="text-center">Don't have an account? <a href="register.php">Sign up</a></p>
            </div>
        </div>
    </div>
</body>
</html>

<script>
    // Password visibility toggle
    document.getElementById('toggle-password').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>

<style>
    /* General styles */
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
        line-height: 1.6;
    }
    /* Login Container */
    .login-container {
        width: 100%;
        max-width: 400px;
        border-radius: 10px;
        margin:0  auto;
        padding: 10px;
    }
    /* Card Header */
    .card-header {
        background-color: #f4f4f4;
        color: #004d40;
        text-align: center;
        padding:100px 0 10px 0;
        align-items: center;
        justify-items: center;
    }
    .card-header img {
        width: 60px;
        height: 60px;
        
    }
    /* Card Body */
    .card-body {
        padding: 10px 0 0 0;
        background-color: #f4f4f4;
    }
    label {
        color: #004d40;
        font-weight: normal;
    }
    /* Form Inputs */
    .input-group {
        margin-bottom: 15px;
        padding: 10px;
        background-color: #f4f4f4;
    }
    .input-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .input-group input {
        width: 95%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    /* Password Wrapper */
    .password-wrapper input {
        width: 95%;
    }
    .toggle-icon {
        position: relative;
        top:-27px;
        left: 350px;
        cursor: pointer;
    }
    /* Button */
    .btn {
        width: 100%;
        padding: 10px;
        background: #004d40;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
    }
    .btn:hover {
        background: #00332c;
    }
    /* Alert Message */
    .alert {
        background: #ff4d4d;
        color: white;
        padding: 10px;
        margin-bottom: 15px;
        text-align: center;
        border-radius: 5px;
    }
    /* Card Footer */
    .card-footer {
        color: #004d40;
        text-align: center;
        padding: 10px 0 0 0;
        background-color: #f4f4f4;
    }
    /* Text Center */
    .text-center {
        color: #004d40;
        text-align: center;
        background-color: #f4f4f4;

    }
    h3, p{
        margin: 0;
    }
    @media (max-width: 480px) {
        .container {
            width: 90%;
            padding: 15px;
            justify-items: center;
            align-items: center;
        }

        .btn {
            font-size: 0.9em;
        }

        .toggle-password {
            font-size: 1em;
        }
    }
</style>
