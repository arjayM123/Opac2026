<?php
// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>
<header class="header">
        <div class="container">
            <div class="header-content">
                <nav class="nav-links">
                    <a href="./index.php" class="nav-item">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                <!-- <div class="dropdown">
                <a href="#" class="nav-item" onclick="toggleDropdown(event)">
                    <i class="fas fa-user"></i>
                    <span>Account</span>
                    <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
                </a>
                <div class="dropdown-content" id="dropdownContent">
                    <a href="student/profile.php" class="dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile Info</span>
                    </a>
                    <a href="student/change_password.php" class="dropdown-item">
                        <i class="fas fa-lock"></i>
                        <span>Change Password</span>
                    </a>
                    
                    <a href="login.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div> -->
                </nav>
            </div>
        </div>
    </header>

    <script>
        // Toggle mobile menu
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('show');
        }

        // Toggle dropdown
        function toggleDropdown(event) {
            event.preventDefault();
            const dropdownContent = document.getElementById('dropdownContent');
            dropdownContent.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdownContent = document.getElementById('dropdownContent');
            const dropdownButton = document.querySelector('.dropdown .nav-item');

            if (!event.target.closest('.dropdown') && dropdownContent.classList.contains('show')) {
                dropdownContent.classList.remove('show');
            }

            // Close mobile menu when clicking outside
            const navLinks = document.getElementById('navLinks');
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            
            if (!event.target.closest('.nav-links') && 
                !event.target.closest('.mobile-menu-btn') && 
                navLinks.classList.contains('show')) {
                navLinks.classList.remove('show');
            }
        });
    </script>
</body>
</html>
<style>
    *{
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Times New Roman', Times, serif;
    }
    /* Dropdown styles */
    .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(107, 106, 106, 0.1);
            border-radius: 4px;
            z-index: 1000;
            margin-top: 5px;

        }

        .dropdown-content.show {
            display: block;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            text-decoration: none;
            color: #333;
            transition: background-color 0.3s;
        }

        .dropdown-item:hover {
            background-color: #f5f5f5;
        }

        .dropdown-divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 5px 0;
        }

/* Header styles */
.header {
    padding: 15px 0;
    margin: 0 auto;
    background-color: #004d40;

}

.header-content {
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

.nav-links {
    display: flex;
    align-items: center;
}

.nav-item {
    color:rgb(234, 234, 234);
    text-decoration: none;
    padding: 8px 16px;
    margin-left: 10px;
    border-radius: 4px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.nav-item i {
    margin-right: 8px;
    color:rgb(255, 255, 255);
}

.nav-item:hover, .nav-item.active {
    color: rgb(124, 216, 74);
}
</style>