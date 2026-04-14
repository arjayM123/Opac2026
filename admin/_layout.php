
<!DOCTYPE html>
<html>
<head>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            padding: 0;
            overflow-x: hidden;
        }
        
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #1a1d21;
            position: fixed;
            left: 0;
            top: 0;
            color: #9ca3af;
            font-family: Arial, sans-serif;
            padding: 1rem;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: 60px;
            padding: 1rem 0.5rem;
        }

        .sidebar.collapsed .logo-container span, 
        .sidebar.collapsed .nav-item-text, 
        .sidebar.collapsed .arrow {
            display: none;
        }

        .sidebar.collapsed .logo-container {
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .sidebar.collapsed .nav-item {
            justify-content: center;
            padding: 0.75rem 0;
        }

        .sidebar.collapsed .nav-item i:first-child {
            margin-right: 0;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.5rem 0;
            margin-bottom: 2rem;
        }

        .logo-container img {
            width: 40px;
            height: 40px;
        }

        .logo-container span {
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #9ca3af;
            text-decoration: none;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            cursor: pointer;
            white-space: nowrap;
        }

        .nav-item:hover {
            background-color: #2d3238;
            color: #ffffff;
        }

        .nav-item i:first-child {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        .nav-item-text {
            flex: 1;
        }

        .submenu {
            margin-left: 2.5rem;
            display: none;
            margin-top: 0.25rem;
        }

        .submenu.active {
            display: block;
        }

        .sidebar.collapsed .submenu {
            position: absolute;
            left: 60px;
            top: 0;
            width: 180px;
            background-color: #1a1d21;
            border-radius: 0 0.5rem 0.5rem 0;
            padding: 0.5rem;
            margin-left: 0;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
        }

        .submenu a {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: #9ca3af;
            text-decoration: none;
            font-size: 0.875rem;
            border-radius: 0.5rem;
            white-space: nowrap;
        }

        .submenu a i {
            margin-right: 0.5rem;
            width: 16px;
            text-align: center;
        }

        .submenu a:hover {
            background-color: #2d3238;
            color: #ffffff;
        }

        .nav-item .arrow {
            margin-left: 0.5rem;
            transition: transform 0.2s;
        }

        .nav-item.active .arrow {
            transform: rotate(180deg);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
            width: calc(100% - 250px);
        }

        .main-content.expanded {
            margin-left: 60px;
            width: calc(100% - 60px);
        }

        .toggle-sidebar {
            position: fixed;
            top: 5px;
            left: 250px;
            background-color: #f4f6f9;
            color:rgb(0, 0, 0);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            font-size: 1.rem;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 1001;
            transition: all 0.3s ease;
        }

        .toggle-sidebar.moved {
            left: 60px;

        }

        .toggle-sidebar:hover {
            color:rgb(34, 255, 0);
        }

        /* Responsive styles for smaller screens */
        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
                transform: translateX(-100%);
            }
            
            .sidebar.collapsed {
                transform: translateX(0);
                width: 60px;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .main-content.expanded {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
            
            .toggle-sidebar {
                left: 20px;
            }
            
            .toggle-sidebar.moved {
                left: 70px;
            }
        }

        html {
            scrollbar-width: none;
            scroll-behavior: smooth;
            -ms-overflow-style: none;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <button class="toggle-sidebar" id="toggle-btn" title="Toggle Sidebar">
        <i class="fas fa-chevron-left"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="assets/img/images-removebg-preview.png" alt="ISU Logo">
            <span>ISU Library</span>
        </div>
        
        <!-- <a href="dashboard.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span class="nav-item-text">Dashboard</span>
        </a> -->
        
        <!-- Modified Book Management Section -->
        <div class="nav-item" onclick="toggleSubmenu('books')">
            <i class="fas fa-book"></i>
            <span class="nav-item-text">Book Management</span>
            <i class="fas fa-chevron-down arrow"></i>
        </div>
        <div class="submenu" id="books">
            <!-- <a href="add_book.php"><i class="fas fa-plus-circle"></i> <span>Add New Book</span></a> -->
            <a href="import.php"><i class="fas fa-upload"></i> <span>import book</span></a>
            <a href="manage-file.php"><i class="fas fa-list"></i> <span>View Books</span></a>
        </div>
        
        <div class="nav-item" onclick="toggleSubmenu('transactions')" style="display: none;">
            <i class="fas fa-exchange-alt"></i>
            <span class="nav-item-text">Transactions</span>
            <i class="fas fa-chevron-down arrow"></i>
        </div>
        <div class="submenu" id="transactions" >
            <a href="borrowed.php"><i class="fas fa-file-alt"></i> <span>Borrowed form</span></a>
            <a href="borrowed_list.php"><i class="fas fa-list-alt"></i> <span>Borrowed list</span></a>
            <a href="return_list.php"><i class="fas fa-undo"></i> <span>Returned list</span></a>
            <a href="overdue_list.php"><i class="fas fa-exclamation-circle"></i> <span>Overdue list</span></a>
        </div>     
        <a href="../login.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span class="nav-item-text">Logout</span>
        </a>
    </div>

    <div class="main-content" id="main-content"></div>

    <script>
        function toggleSubmenu(id) {
            // Only toggle submenu if sidebar is not collapsed or we're on mobile
            if (!document.getElementById('sidebar').classList.contains('collapsed') || window.innerWidth <= 768) {
                const submenu = document.getElementById(id);
                const navItem = submenu.previousElementSibling;
                
                // Toggle the active class on the submenu
                submenu.classList.toggle('active');
                
                // Toggle the active class on the nav item (for arrow rotation)
                navItem.classList.toggle('active');
                
                // Close other submenus
                const allSubmenus = document.querySelectorAll('.submenu');
                
                allSubmenus.forEach(menu => {
                    if (menu.id !== id && menu.classList.contains('active')) {
                        menu.classList.remove('active');
                        menu.previousElementSibling.classList.remove('active');
                    }
                });
            }
        }

        // Toggle sidebar functionality
        const toggleBtn = document.getElementById('toggle-btn');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');

        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            toggleBtn.classList.toggle('moved');
            
            // Change the toggle button icon
            const icon = toggleBtn.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-right');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-left');
            }
        });

        // Handle hover on nav items when sidebar is collapsed
        const navItems = document.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            // If the item has a submenu
            if (item.nextElementSibling && item.nextElementSibling.classList.contains('submenu')) {
                item.addEventListener('mouseenter', function() {
                    if (sidebar.classList.contains('collapsed')) {
                        const submenu = this.nextElementSibling;
                        submenu.style.top = `${this.offsetTop}px`;
                        submenu.classList.add('active');
                    }
                });
                
                item.addEventListener('mouseleave', function() {
                    if (sidebar.classList.contains('collapsed')) {
                        const submenu = this.nextElementSibling;
                        setTimeout(() => {
                            if (!submenu.matches(':hover')) {
                                submenu.classList.remove('active');
                            }
                        }, 300);
                    }
                });
            }
        });

        // Handle submenu mouseleave when sidebar is collapsed
        const submenus = document.querySelectorAll('.submenu');
        submenus.forEach(submenu => {
            submenu.addEventListener('mouseleave', function() {
                if (sidebar.classList.contains('collapsed')) {
                    setTimeout(() => {
                        if (!this.previousElementSibling.matches(':hover')) {
                            this.classList.remove('active');
                        }
                    }, 300);
                }
            });
        });

        // Add touch support for mobile
        navItems.forEach(item => {
            if (item.nextElementSibling && item.nextElementSibling.classList.contains('submenu')) {
                item.addEventListener('click', function(e) {
                    if (sidebar.classList.contains('collapsed') && window.innerWidth > 768) {
                        e.preventDefault();
                        const submenu = this.nextElementSibling;
                        submenu.style.top = `${this.offsetTop}px`;
                        
                        // Close other submenus first
                        submenus.forEach(menu => {
                            if (menu !== submenu && menu.classList.contains('active')) {
                                menu.classList.remove('active');
                            }
                        });
                        
                        submenu.classList.toggle('active');
                    }
                });
            }
        });

        // Adjust for window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                if (!sidebar.classList.contains('collapsed')) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                    toggleBtn.classList.add('moved');
                    
                    const icon = toggleBtn.querySelector('i');
                    icon.classList.remove('fa-chevron-left');
                    icon.classList.add('fa-chevron-right');
                }
            }
        });

        // Initial check for mobile
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            toggleBtn.classList.add('moved');
            
            const icon = toggleBtn.querySelector('i');
            icon.classList.remove('fa-chevron-left');
            icon.classList.add('fa-chevron-right');
        }
    </script>
</body>
</html>