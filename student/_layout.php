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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .header {
            background-color: #004d40;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(114, 109, 109, 0.1);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-section img {
            height: 40px;
            width: auto;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .nav-item {
            position: relative;
            display: flex;
            align-items: center;
            gap: 5px;
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-item:hover, .nav-item.active {
            color: rgb(89, 255, 0);
        }

        .nav-item i {
            font-size: 18px;
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

        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        /* Mobile styles */
        @media screen and (max-width: 768px) {

            .mobile-menu-btn {
                display: block;

            }

            .nav-links {
                z-index: 50;
                display: none;
                position: absolute;
                top: 70px;
                left: 0;
                right: 0;
                background-color:rgb(177, 177, 177);
                flex-direction: column;
                padding: 20px;
                gap: 15px;
            }

            .nav-links.show {
                display: flex;
            }

            .dropdown-content {
                position: static;
                box-shadow: none;
                width: 100%;
            }

        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-section">
            <img src="assets/img/images-removebg-preview.png" alt="Library Logo">
            <h1 style="color: white; font-size: 20px;">OPAC-ISUR</h1>
        </div>

        <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>

        <nav class="nav-links" id="navLinks">
            <a href="home.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
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
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile Info</span>
                    </a>
                    <a href="change_password.php" class="dropdown-item">
                        <i class="fas fa-lock"></i>
                        <span>Change Password</span>
                    </a>
                    
                    <a href="../login.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div> -->
        </nav>
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