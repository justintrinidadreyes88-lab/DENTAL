<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['3'])) {
    header("Location: ../signin.php");
    exit();
}

include("../../dbcon.php");

// Check database connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../dental.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=search" />

    <title>Dental Assistant Dashboard</title>
</head>

<body>
    <!-- Navigation/Sidebar -->
    <nav>
        <a href="../HOME_PAGE/Home_page.php">
            <div class="logo">
                <h1><span>EHM</span> Dental Clinic</h1>
            </div>
        </a>
        <form method="POST" class="s-buttons" action="../../logout.php">
            <a href="../dental_assistant_dashboard.php"><i class="fa fa-arrow-left trash"></i></a>
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </nav>
    <div>
        <aside class="sidebar">
            <ul>
                <br>
                <a class="active" href="dental_archives.php">
                    <h3>DENTAL ASSISTANT<br>ARCHIVES</h3>
                </a>
                <br>
                <br>
                <hr>
                <br>
                <li><a href="archives.php">Archives</a></a></li>
                <li><a href="transaction.php">Packages Transaction History</a></a></li>
                <li><a href="bin.php">Appointments Bin</a></li>
            </ul>
        </aside>
    </div>
    <!-- Main Content/Crud -->
    <div class="top">
        <div class="content-box">
            <?php
            // Set the number of results per page
            $resultsPerPage = 6;

            // Get the current page number from query parameters, default to 1
            $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;

            // Calculate the starting row for the SQL query
            $startRow = ($currentPage - 1) * $resultsPerPage;

            // Capture and sanitize filter values from GET parameters
            $filterName = isset($_GET['name']) ? mysqli_real_escape_string($con, $_GET['name']) : '';
            $filterDate = isset($_GET['date']) ? mysqli_real_escape_string($con, $_GET['date']) : '';

            // SQL query to count total records with filtering
            $countQuery = "SELECT COUNT(*) as total FROM tbl_bin a
    JOIN tbl_service_type s ON a.service_type = s.id
    JOIN tbl_patient p ON a.name = p.id
    JOIN tbl_status t ON a.status = t.id
    WHERE a.status IN ('1', '2', '3', '4')";

            // Add name filter if specified
            if ($filterName) {
                $countQuery .= " AND (p.first_name LIKE ? OR p.last_name LIKE ?)";
            }
            // Add date filter if specified
            if ($filterDate) {
                $countQuery .= " AND a.date = ?";
            }

            $stmt = $con->prepare($countQuery);
            if ($filterName && $filterDate) {
                $likeName = "%$filterName%";
                $stmt->bind_param("sss", $likeName, $likeName, $filterDate);
            } elseif ($filterName) {
                $likeName = "%$filterName%";
                $stmt->bind_param("ss", $likeName, $likeName);
            } elseif ($filterDate) {
                $stmt->bind_param("s", $filterDate);
            }
            $stmt->execute();
            $countResult = $stmt->get_result();
            $totalCount = $countResult->fetch_assoc()['total'];
            $totalPages = ceil($totalCount / $resultsPerPage);

            // SQL query to fetch the filtered records with OFFSET
            $query = "SELECT a.*, 
    s.service_type AS service_name, 
    p.first_name, p.middle_name, p.last_name, 
    t.status     
FROM tbl_bin a
JOIN tbl_service_type s ON a.service_type = s.id
JOIN tbl_patient p ON a.name = p.id
JOIN tbl_status t ON a.status = t.id
WHERE a.status IN ('1', '2', '3', '4')";

            // Add name filter if specified
            if ($filterName) {
                $query .= " AND (p.first_name LIKE ? OR p.last_name LIKE ?)";
            }
            // Add date filter if specified
            if ($filterDate) {
                $query .= " AND a.date = ?";
            }

            $query .= " ORDER BY 
              CASE 
                  WHEN a.modified_date IS NOT NULL THEN a.modified_date
                  ELSE a.date
              END DESC
              LIMIT ? OFFSET ?";

            $stmt = $con->prepare($query);
            if ($filterName && $filterDate) {
                $stmt->bind_param("sssii", $likeName, $likeName, $filterDate, $resultsPerPage, $startRow);
            } elseif ($filterName) {
                $stmt->bind_param("ssii", $likeName, $likeName, $resultsPerPage, $startRow);
            } elseif ($filterDate) {
                $stmt->bind_param("sii", $filterDate, $resultsPerPage, $startRow);
            } else {
                $stmt->bind_param("ii", $resultsPerPage, $startRow);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            ?>
            <br><br><br>
            <div class="managehead">
                <!-- Search Form Container -->
                <div class="f-search">
                    <form method="GET" action="" class="search-form">
                        <input type="text" name="name" placeholder="Search by name"
                            value="<?php echo htmlspecialchars($filterName); ?>" />
                        <input type="date" name="date" value="<?php echo htmlspecialchars($filterDate); ?>" />
                        <button class="material-symbols-outlined" type="submit">search</button>
                    </form>
                </div>

                <!-- Pagination Navigation -->
                <div class="pagination-container">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?php echo $currentPage - 1; ?>&name=<?php echo urlencode($filterName); ?>&date=<?php echo urlencode($filterDate); ?>"
                            class="pagination-btn">&lt;</a>
                    <?php endif; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?php echo $currentPage + 1; ?>&name=<?php echo urlencode($filterName); ?>&date=<?php echo urlencode($filterDate); ?>"
                            class="pagination-btn">&gt;</a>
                    <?php endif; ?>
                </div>
            </div>
            <br><br>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th style="font-size: 18px;">Rescheduled Date</th>
                        <th style="font-size: 18px;">Rescheduled Time</th>
                        <th>Service</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Format dates and times
                            $modified_date = (!empty($row['modified_date']) && $row['modified_date'] !== '0000-00-00') ? $row['modified_date'] : 'N/A';
                            $modified_time = (!empty($row['modified_time']) && $row['modified_time'] !== '00:00:00') ? date("h:i A", strtotime($row['modified_time'])) : 'N/A';
                            $dateToDisplay = (!empty($row['date']) && $row['date'] !== '0000-00-00') ? $row['date'] : 'N/A';
                            $timeToDisplay = (!empty($row['time']) && $row['time'] !== '00:00:00') ? date("h:i A", strtotime($row['time'])) : 'N/A';

                            echo "<tr>
                    <td style='width: 230px'>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                    <td>{$row['contact']}</td>
                    <td style='width: 110px'>{$dateToDisplay}</td>
                    <td style='width: 110px'>{$timeToDisplay}</td>
                    <td style='width: 120px'>{$modified_date}</td>
                    <td style='width: 120px'>{$modified_time}</td>
                    <td>{$row['service_name']}</td>
                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>No records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
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
</body>

</html>
