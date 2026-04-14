<?php
include '../db_connect.php';

// Set default filter to current day
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'daily';
$today = date('Y-m-d');
$current_month = date('Y-m');
$current_year = date('Y');
$start_date = null;
$end_date = null;

// Custom date range
$custom_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$custom_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Extract department filters
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';

// Determine date range based on filter
switch($filter_type) {
    case 'daily':
        $title = "Daily Borrowing Report - " . date('F d, Y');
        $start_date = $today . " 00:00:00";
        $end_date = $today . " 23:59:59";
        break;
    case 'weekly':
        $title = "Weekly Borrowing Report";
        // Calculate the start of the week (Monday)
        $start_of_week = date('Y-m-d', strtotime('monday this week'));
        $end_of_week = date('Y-m-d', strtotime('sunday this week'));
        $start_date = $start_of_week . " 00:00:00";
        $end_date = $end_of_week . " 23:59:59";
        break;
    case 'monthly':
        $title = "Monthly Borrowing Report - " . date('F Y');
        $start_date = $current_month . "-01 00:00:00";
        $end_date = date('Y-m-t') . " 23:59:59"; // Last day of month
        break;
    case 'yearly':
        $title = "Yearly Borrowing Report - " . date('Y');
        $start_date = $current_year . "-01-01 00:00:00";
        $end_date = $current_year . "-12-31 23:59:59";
        break;
    case 'custom':
        $title = "Custom Period Borrowing Report";
        if(!empty($custom_start) && !empty($custom_end)) {
            $start_date = $custom_start . " 00:00:00";
            $end_date = $custom_end . " 23:59:59";
        } else {
            // Default to today if dates not provided
            $start_date = $today . " 00:00:00";
            $end_date = $today . " 23:59:59";
        }
        break;
    default:
        $title = "Daily Borrowing Report - " . date('F d, Y');
        $start_date = $today . " 00:00:00";
        $end_date = $today . " 23:59:59";
}

// Build WHERE clause for filters
$where_clause = "WHERE b.borrow_date BETWEEN '$start_date' AND '$end_date'";

if(!empty($department_filter)) {
    $where_clause .= " AND (b.course = '$department_filter' OR b.office = '$department_filter')";
}

// Get classification numbers for dropdown (replacing categories)
$classifications_query = "SELECT DISTINCT classification_number FROM books ORDER BY classification_number";
$classifications_result = $conn->query($classifications_query);

// Get departments/courses for dropdown
$departments_query = "
    SELECT DISTINCT course as department FROM borrowers WHERE course != '' 
    UNION 
    SELECT DISTINCT office as department FROM borrowers WHERE office != ''
    ORDER BY department
";
$departments_result = $conn->query($departments_query);

// Get borrowed books for the selected period
$borrowed_books_query = "
    SELECT b.*, 
           books.title as book_title, 
           books.author, 
           books.classification_number
    FROM borrowers b
    JOIN books ON b.book_id = books.id
    $where_clause
    ORDER BY b.borrow_date DESC
";
$borrowed_books = $conn->query($borrowed_books_query);

// Get statistics by department and borrower type
$stats_query = "
    SELECT 
        borrower_type,
        CASE 
            WHEN borrower_type = 'student' THEN course 
            ELSE office 
        END as department,
        COUNT(*) as total
    FROM borrowers b
    JOIN books ON b.book_id = books.id
    $where_clause
    GROUP BY borrower_type, department
    ORDER BY borrower_type, department
";
$stats_result = $conn->query($stats_query);

// Prepare statistics arrays
$student_stats = [];
$staff_stats = [];
$student_total = 0;
$staff_total = 0;

while($row = $stats_result->fetch_assoc()) {
    if($row['borrower_type'] == 'student') {
        $student_stats[$row['department']] = $row['total'];
        $student_total += $row['total'];
    } else {
        $staff_stats[$row['department']] = $row['total'];
        $staff_total += $row['total'];
    }
}

$grand_total = $student_total + $staff_total;

// Get book classification statistics (replacing categories)
$classification_stats_query = "
    SELECT 
        books.classification_number,
        COUNT(*) as total
    FROM borrowers b
    JOIN books ON b.book_id = books.id
    $where_clause
    GROUP BY books.classification_number
    ORDER BY total DESC
";
$classification_stats_result = $conn->query($classification_stats_query);

// Get overdue books
$overdue_query = "
    SELECT COUNT(*) as overdue_count
    FROM borrowers b
    JOIN books ON b.book_id = books.id
    WHERE b.status = 'borrowed' 
    AND b.due_date < NOW()
    AND b.borrow_date BETWEEN '$start_date' AND '$end_date'
";
$overdue_result = $conn->query($overdue_query);
$overdue_count = $overdue_result->fetch_assoc()['overdue_count'];

// Generate the report date for printing
$report_date = date('F d, Y');

// Function to export to CSV
function exportToCSV($conn, $query, $filename) {
    $result = $conn->query($query);
    
    // Open file
    $f = fopen('php://memory', 'w');
    
    // Get column headers
    $fields = $result->fetch_fields();
    $headers = [];
    foreach ($fields as $field) {
        $headers[] = $field->name;
    }
    
    // Add headers to CSV
    fputcsv($f, $headers);
    
    // Add data
    while ($row = $result->fetch_assoc()) {
        fputcsv($f, $row);
    }
    
    // Reset pointer
    fseek($f, 0);
    
    // Set headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Output
    fpassthru($f);
    exit;
}

// Check if export request
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $export_query = "
        SELECT 
            b.borrower_name, 
            b.borrower_id, 
            b.borrower_type, 
            books.title as book_title, 
            books.classification_number,
            b.accession_number, 
            b.course, 
            b.office,
            b.borrow_date, 
            b.due_date, 
            b.status
        FROM borrowers b
        JOIN books ON b.book_id = books.id
        $where_clause
        ORDER BY b.borrow_date DESC
    ";
    exportToCSV($conn, $export_query, 'borrowed_books_report.csv');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Books Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Add your print styles here */
        @media print {
            .no-print {
                display: none !important;
            }
            .report-header {
                text-align: center;
                margin-bottom: 20px;
            }
            .logo-container {
                text-align: center;
                margin-bottom: 10px;
            }
            .logo-container img {
                max-height: 60px;
            }
            .university-header {
                font-weight: bold;
                margin: 0;
                line-height: 1.2;
            }
            .stats-card {
                border: 1px solid #ddd;
                margin-bottom: 15px;
            }
            .stats-header {
                background-color: #f8f9fa;
                padding: 8px 15px;
                font-weight: bold;
                border-bottom: 1px solid #ddd;
            }
            .stats-body {
                padding: 10px 15px;
            }
            .stats-item {
                display: flex;
                justify-content: space-between;
                padding: 5px 0;
                border-bottom: 1px dotted #eee;
            }
            .stats-total {
                margin-top: 10px;
                border-top: 1px solid #ddd;
                font-weight: bold;
            }
        }
        
        /* Regular styles */
        .dashboard-cards {
            margin: 20px 0;
        }
        .dashboard-card {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
        }
        .dashboard-card .icon {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 2rem;
            opacity: 0.3;
        }
        .dashboard-card .number {
            font-size: 2rem;
            font-weight: bold;
        }
        .dashboard-card .label {
            font-size: 0.9rem;
        }
        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .report-container {
            padding: 20px;
        }
        .chart-container {
            height: 300px;
        }
        .stats-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .stats-header {
            background-color: #f8f9fa;
            padding: 10px 15px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            border-radius: 5px 5px 0 0;
        }
        .stats-body {
            padding: 10px 15px;
        }
        .stats-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #eee;
        }
        .stats-total {
            margin-top: 10px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="main-content">
    <a href="dashboard.php">back</a>
    <div class="report-container">
        <!-- Filter Section -->
        <div class="filter-section no-print">
            <div class="row">
                <div class="col-md-6">
                    <h4>Borrowed Books Report</h4>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <button onclick="window.print()" class="btn btn-sm btn-primary">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-sm btn-success">
                            <i class="bi bi-file-excel"></i> Export CSV
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col">
                    <div class="btn-group w-100" role="group">
                        <a href="?filter=daily" class="btn btn-outline-primary <?php echo $filter_type == 'daily' ? 'active' : ''; ?>">Daily</a>
                        <a href="?filter=weekly" class="btn btn-outline-primary <?php echo $filter_type == 'weekly' ? 'active' : ''; ?>">Weekly</a>
                        <a href="?filter=monthly" class="btn btn-outline-primary <?php echo $filter_type == 'monthly' ? 'active' : ''; ?>">Monthly</a>
                        <a href="?filter=yearly" class="btn btn-outline-primary <?php echo $filter_type == 'yearly' ? 'active' : ''; ?>">Yearly</a>
                        <a href="#" class="btn btn-outline-primary <?php echo $filter_type == 'custom' ? 'active' : ''; ?>" 
                           data-bs-toggle="modal" data-bs-target="#customDateModal">Custom</a>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Filters -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <input type="hidden" name="filter" value="<?php echo $filter_type; ?>">
                        <?php if($filter_type == 'custom'): ?>
                            <input type="hidden" name="start_date" value="<?php echo $custom_start; ?>">
                            <input type="hidden" name="end_date" value="<?php echo $custom_end; ?>">
                        <?php endif; ?>
                        
                        <select name="classification" class="form-select form-select-sm me-2">
                            <option value="">All Classifications</option>
                            <?php while($cls = $classifications_result->fetch_assoc()): ?>
                                <option value="<?php echo $cls['classification_number']; ?>" <?php echo isset($_GET['classification']) && $_GET['classification'] == $cls['classification_number'] ? 'selected' : ''; ?>>
                                    <?php echo $cls['classification_number']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <select name="department" class="form-select form-select-sm me-2">
                            <option value="">All Departments</option>
                            <?php while($dept = $departments_result->fetch_assoc()): ?>
                                <option value="<?php echo $dept['department']; ?>" <?php echo $department_filter == $dept['department'] ? 'selected' : ''; ?>>
                                    <?php echo $dept['department']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-filter"></i> Apply Filters
                        </button>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <div class="input-group input-group-sm">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search in results...">
                        <button class="btn btn-primary" type="button" onclick="searchTable()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Cards -->
        <div class="row dashboard-cards no-print" id="dashboard">
            <div class="col-md-3">
                <div class="card dashboard-card bg-primary text-white">
                    <div class="card-body">
                        <div class="icon"><i class="bi bi-book"></i></div>
                        <div class="number"><?php echo $grand_total; ?></div>
                        <div class="label">Total Borrowed</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card bg-success text-white">
                    <div class="card-body">
                        <div class="icon"><i class="bi bi-mortarboard"></i></div>
                        <div class="number"><?php echo $student_total; ?></div>
                        <div class="label">Student Borrowers</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card bg-info text-white">
                    <div class="card-body">
                        <div class="icon"><i class="bi bi-person-badge"></i></div>
                        <div class="number"><?php echo $staff_total; ?></div>
                        <div class="label">Staff Borrowers</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card bg-danger text-white">
                    <div class="card-body">
                        <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
                        <div class="number"><?php echo $overdue_count; ?></div>
                        <div class="label">Overdue Books</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="row no-print" id="charts" style="display:none;">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        Borrower Type Distribution
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="borrowerTypeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        Top Book Classifications
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="classificationsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Header -->
        <div class="report-header">
            <div class="logo-container">
                <img src="assets/img/images-removebg-preview.png" alt="ISU Logo">
            </div>
            <p class="university-header">Republic of the Philippines</p>
            <p class="university-header">ISABELA STATE UNIVERSITY</p>
            <p class="university-header"><?php echo ucfirst($filter_type); ?> Borrowing Report</p>
            <p class="date-range">
                <?php 
                    if($filter_type == 'daily') {
                        echo "Date: " . date('F d, Y', strtotime($today));
                    } elseif($filter_type == 'weekly') {
                        echo "Period: " . date('F d', strtotime($start_of_week)) . " - " . date('F d, Y', strtotime($end_of_week));
                    } elseif($filter_type == 'monthly') {
                        echo "Month: " . date('F Y', strtotime($current_month));
                    } elseif($filter_type == 'custom') {
                        echo "Period: " . date('F d, Y', strtotime($custom_start)) . " - " . date('F d, Y', strtotime($custom_end));
                    } else {
                        echo "Year: " . $current_year;
                    }
                ?>
            </p>
        </div>

        <!-- Statistics Section -->
        <div class="row">
            <!-- Student Statistics -->
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-header">
                        Student Borrowers
                    </div>
                    <div class="stats-body">
                        <?php if(empty($student_stats)): ?>
                            <p class="text-center">No student borrowers during this period.</p>
                        <?php else: ?>
                            <?php foreach($student_stats as $course => $count): ?>
                                <div class="stats-item">
                                    <span><?php echo empty($course) ? 'No Course' : $course; ?></span>
                                    <span><?php echo $count; ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div class="stats-total">
                                <div class="stats-item">
                                    <span>Total Student Borrowers:</span>
                                    <span><?php echo $student_total; ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Staff Statistics -->
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-header">
                        Staff Borrowers
                    </div>
                    <div class="stats-body">
                        <?php if(empty($staff_stats)): ?>
                            <p class="text-center">No staff borrowers during this period.</p>
                        <?php else: ?>
                            <?php foreach($staff_stats as $office => $count): ?>
                                <div class="stats-item">
                                    <span><?php echo empty($office) ? 'No Office' : $office; ?></span>
                                    <span><?php echo $count; ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div class="stats-total">
                                <div class="stats-item">
                                    <span>Total Staff Borrowers:</span>
                                    <span><?php echo $staff_total; ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Book Classifications Statistics -->
            <div class="col-md-12">
                <div class="stats-card">
                    <div class="stats-header">
                        Book Classifications
                    </div>
                    <div class="stats-body">
                        <div class="row">
                            <?php if($classification_stats_result->num_rows > 0): ?>
                                <?php while($cls_stat = $classification_stats_result->fetch_assoc()): ?>
                                    <div class="col-md-4">
                                        <div class="stats-item">
                                            <span><?php echo empty($cls_stat['classification_number']) ? 'Unclassified' : $cls_stat['classification_number']; ?></span>
                                            <span><?php echo $cls_stat['total']; ?></span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <p class="text-center">No data available for this period.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grand Total -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Grand Total Borrowers:</span>
                            <span><?php echo $grand_total; ?></span>
                        </div>
                        <div class="d-flex justify-content-between text-danger">
                            <span>Overdue Books:</span>
                            <span><?php echo $overdue_count; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       

        <!-- Report Footer -->
        <div class="mt-5">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Prepared by:</strong> ___________________________</p>
                    <p><strong>Position:</strong> ___________________________</p>
                </div>
                <div class="col-md-6 text-end">
                    <p><strong>Date Printed:</strong> <?php echo $report_date; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Date Modal -->
<div class="modal fade" id="customDateModal" tabindex="-1" aria-labelledby="customDateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customDateModalLabel">Custom Date Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="GET" id="customDateForm">
                    <input type="hidden" name="filter" value="custom">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                    
                    <?php if(isset($_GET['classification'])): ?>
                        <input type="hidden" name="classification" value="<?php echo $_GET['classification']; ?>">
                    <?php endif; ?>
                    
                    <?php if(!empty($department_filter)): ?>
                        <input type="hidden" name="department" value="<?php echo $department_filter; ?>">
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('customDateForm').submit()">Apply Date Range</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set custom date values if available
        <?php if(!empty($custom_start) && !empty($custom_end)): ?>
        document.getElementById('start_date').value = '<?php echo $custom_start; ?>';
        document.getElementById('end_date').value = '<?php echo $custom_end; ?>';
        <?php endif; ?>
        
        // Initialize charts if we have data
        <?php if($grand_total > 0): ?>
        initCharts();
        <?php endif; ?>
    });
    // Function to toggle charts visibility
function toggleCharts() {
    const chartsSection = document.getElementById('charts');
    if (chartsSection.style.display === 'none') {
        chartsSection.style.display = 'flex';
    } else {
        chartsSection.style.display = 'none';
    }
}

// Function to search in the table
function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('borrowedBooksTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let found = false;
        const td = tr[i].getElementsByTagName('td');
        
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
}


    </script>
        <style>
        .report-container {
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .filter-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .university-header {
            margin: 0;
            font-weight: bold;
            text-align: center;
        }
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
            text-align: center;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 10px;
        }
        .logo-container img {
            max-height: 80px;
        }
        .stats-card {
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .stats-header {
            background-color: #f8f9fa;
            padding: 10px;
            font-weight: bold;
            border-bottom: 1px solid #dee2e6;
        }
        .stats-body {
            padding: 15px;
        }
        .stats-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .stats-total {
            border-top: 1px solid #dee2e6;
            padding-top: 8px;
            margin-top: 8px;
            font-weight: bold;
        }
        .date-range {
            font-style: italic;
            text-align: center;
            margin-bottom: 15px;
        }
        .dashboard-card {
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .dashboard-card .card-body {
            padding: 1.5rem;
        }
        .dashboard-card .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .dashboard-card .number {
            font-size: 1.8rem;
            font-weight: bold;
        }
        .dashboard-card .label {
            color:rgb(255, 255, 255);
            font-size: 0.9rem;
        }

        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .report-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
            }
            .dashboard-cards, .chart-container {
                display: none;
            }
        }
    </style>