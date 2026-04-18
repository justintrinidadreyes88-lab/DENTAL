<?php
session_start();

// Check if the user is logged in and if the user has admin privileges (role 3)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['3'])) {
    // Redirect to the login page if the user is not logged in or does not have admin privileges
    header("Location: ../signin.php");
    exit();
}

include("../dbcon.php"); // Include database connection

// Check if the database connection is successful
if (!$con) {
    die("Connection failed: " . mysqli_connect_error()); // Terminate if connection fails
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Link to external stylesheet for the page layout -->
    <link rel="stylesheet" href="dental.css">
    <!-- Link to Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Link to Google Fonts for Montserrat font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Dental Assistant Dashboard</title>
</head>

<body>
    <!-- Navigation/Sidebar -->
    <nav>
        <!-- Link to the home page -->
        <a href="../HOME_PAGE/Home_page.php">
            <div class="logo">
                <h1><span>EHM</span> Dental Clinic</h1>
            </div>
        </a>
        <!-- Logout and archives button -->
        <form method="POST" class="s-buttons" action="../logout.php">
            <!-- Link to dental archives -->
            <a href="DENTAL_ASSISTANT_ARCHIVES/archives.php"><i class="fas fa-trash trash"></i></a>
            <!-- Logout button -->
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </nav>

    <div>
        <!-- Sidebar with navigation links -->
        <aside class="sidebar">
            <ul>
                <br>
                <!-- Active link to Dental Assistant Dashboard -->
                <a class="active" href="dental_assistant_dashboard.php">
                    <h3>DENTAL ASSISTANT<br>DASHBOARD</h3>
                </a>
                <br>
                <hr>
                <br>
                <!-- Links to different sections of the dashboard -->
                <li><a href="pending.php">Pending Appointments</a></a></li>
                <li><a href="appointments.php">Approved Appointments</a></li>
                <li><a href="declined.php">Declined Appointment</a></li>
                <li><a href="billing.php">Billing Approval </a></li>
            </ul>
        </aside>
    </div>

    <!-- Main Content/Crud -->
    <div class="top">
        <div class="content-box">
            <?php
            // Include the appointments summary from an external file
            include("appointments_status.php");
            ?>

            <?php
            // Set the number of results to be displayed per page
            $resultsPerPage = 6;

            // Get the current page from the query parameters, default to page 1 if not set
            $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;

            // Calculate the starting row for the SQL query based on the current page
            $startRow = ($currentPage - 1) * $resultsPerPage;

            // SQL query to count the total number of records in the appointments table that match the status conditions
            $countQuery = "SELECT COUNT(*) as total FROM tbl_appointments WHERE status IN ('1', '2', '3', '4')";
            $countResult = mysqli_query($con, $countQuery);
            $totalCount = mysqli_fetch_assoc($countResult)['total'];  // Get the total number of records
            $totalPages = ceil($totalCount / $resultsPerPage); // Calculate the total number of pages for pagination
            
            // SQL query to fetch appointments with a JOIN to include service, patient, and status information
            $query = "SELECT a.*, 
                  s.service_type AS service_name, 
                  p.first_name, p.middle_name, p.last_name,
                  t.status     
                  FROM tbl_appointments a
                  JOIN tbl_service_type s ON a.service_type = s.id
                  JOIN tbl_patient p ON a.name = p.id
                  JOIN tbl_status t ON a.status = t.id
                  WHERE a.status IN ('1', '2', '3', '4')
                  ORDER BY a.date DESC, a.time DESC, a.modified_date DESC, a.modified_time DESC
                  LIMIT $resultsPerPage OFFSET $startRow";  // Limit the number of rows to display per page
            
            // Execute the query to fetch the data
            $result = mysqli_query($con, $query);
            ?>

            <!-- Pagination Controls -->
            <div class="pagination-container">
                <?php if ($currentPage > 1): ?>
                    <!-- Link to the previous page -->
                    <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-btn">
                        < </a>
                        <?php endif; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <!-- Link to the next page -->
                            <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-btn"> > </a>
                        <?php endif; ?>
            </div>
        </div>

        <!-- Table displaying appointment data -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th style="font-size: 15px;">Rescheduled Date</th>
                    <th style="font-size: 15px;">Rescheduled Time</th>
                    <th>Service</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Check if there are any results from the query
                if (mysqli_num_rows($result) > 0) {
                    // Loop through each row of results
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Check if modified_date and modified_time are valid
                        $modified_date = (!empty($row['modified_date']) && $row['modified_date'] !== '0000-00-00') ? $row['modified_date'] : 'N/A';
                        $modified_time = (!empty($row['modified_time']) && $row['modified_time'] !== '00:00:00') ? date("h:i A", strtotime($row['modified_time'])) : 'N/A';

                        // Check if date and time are valid
                        $dateToDisplay = (!empty($row['date']) && $row['date'] !== '0000-00-00') ? $row['date'] : 'N/A';
                        $timeToDisplay = (!empty($row['time']) && $row['time'] !== '00:00:00') ? date("h:i A", strtotime($row['time'])) : 'N/A';

                        // Display the row data in the table
                        echo "<tr>
                        <td style='width: 230px'>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                        <td>{$row['contact']}</td>
                        <td style='width: 110px'>{$dateToDisplay}</td>
                        <td style='width: 110px'>{$timeToDisplay}</td>
                        <td style='width: 110px'>{$modified_date}</td>
                        <td style='width: 110px'>{$modified_time}</td>
                        <td style='font-size: 15px'>{$row['service_name']}</td>
                        <td>{$row['status']}</td>
                    </tr>";
                    }
                } else {
                    // If no records were found, display a message in the table
                    echo "<tr><td colspan='8'>No records found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <br><br>        <script>
            document.addEventListener("DOMContentLoaded", function () {
            // Get the current URL path
            const currentPath = window.location.pathname.split("/").pop();

            // Select all sidebar links
            const sidebarLinks = document.querySelectorAll(".sidebar a");

            // Loop through each link to find a match
            sidebarLinks.forEach(link => {
                if (link.getAttribute("href") === currentPath) {
                    // Remove the active class from all links first
                    sidebarLinks.forEach(l => l.classList.remove("active"));
                    // Add the active class to the matching link
                    link.classList.add("active");

                    // If it's inside a <li>, add a class to <li> as well
                    if (link.parentElement.tagName === "LI") {
                        link.parentElement.classList.add("active");
                    }
                }
            });
        });
        </script>
    </div>
</body>

</html>
