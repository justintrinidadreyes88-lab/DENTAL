<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['1'])) {
    header("Location: ../signin.php");
    exit();
}

// Database connection
include("../dbcon.php");

// Handle adding and editing users
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the required fields are set
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    $firstName = isset($_POST['firstname']) ? $_POST['firstname'] : '';
    $lastName = isset($_POST['lastname']) ? $_POST['lastname'] : '';
    $middleName = isset($_POST['middlename']) ? $_POST['middlename'] : '';

    // Hash password if it's provided
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Edit existing user
        $id = $_POST['id'];

        // Check if username already exists for another user
        $checkQuery = "SELECT * FROM tbl_users WHERE username = ? AND id != ?";
        $stmt = $con->prepare($checkQuery);
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
        } else {
            // Update the user with the hashed password
            $updateQuery = "UPDATE tbl_users SET username = ?, password = ?, role = ?, firstname = ?, lastname = ?, middlename = ? WHERE id = ?";
            $updateStmt = $con->prepare($updateQuery);
            $updateStmt->bind_param("ssssssi", $username, $hashedPassword, $role, $firstName, $lastName, $middleName, $id);

            if ($updateStmt->execute()) {
            } else {
                echo "<p>Error: " . $updateStmt->error . "</p>";
            }
            $updateStmt->close();
        }
        $stmt->close();
    } else {
        // Add new user
        $checkQuery = "SELECT * FROM tbl_users WHERE username = ?";
        $stmt = $con->prepare($checkQuery);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
        } else {
            // Insert new user with hashed password
            $insertQuery = "INSERT INTO tbl_users (username, password, role, firstname, lastname, middlename) VALUES (?, ?, ?, ?, ?, ?)";
            $insertStmt = $con->prepare($insertQuery);
            $insertStmt->bind_param("ssssss", $username, $hashedPassword, $role, $firstName, $lastName, $middleName);

            if ($insertStmt->execute()) {
            } else {
                echo "<p>Error: " . $insertStmt->error . "</p>";
            }
            $insertStmt->close();
        }
        $stmt->close();
    }
}

// Handle deleting users
if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $deleteQuery = "DELETE FROM tbl_users WHERE id = ?";
    $deleteStmt = $con->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $id);

    if ($deleteStmt->execute()) {
    } else {
        echo "<p>Error: " . $deleteStmt->error . "</p>";
    }
    $deleteStmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="ad.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Admin Dashboard</title>
</head>

<body>
    <!-- Navigation/Sidebar -->
    <nav>
        <a >
            <div class="logo">
                <h1><span>EHM</span> Dental Clinic</h1>
            </div>
        </a>
        <form method="POST" class="s-buttons" action="../logout.php">
            <a href="ADMIN_ARCHIVES/archives.php"><i class="fas fa-trash trash"></i></a>
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </nav>
    <div>
        <aside class="sidebar">
            <ul>
                <br>
                <a class="active" href="admin_dashboard.php">
                    <h3>ADMIN<br>DASHBOARD</h3>
                </a>
                <br>
                <br>
                <hr>
                <br>
                <li><a href="pending.php">Pending Appointments</a></a></li>
                <li><a href="appointments.php">Approved Appointments</a></li>
                <li><a href="declined.php">Decline Appointments</a></a></li>
                <li><a href="billing.php">Billing Approval</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="manage_user.php">Manage Users</a></li>
            </ul>
        </aside>
    </div>
    <!-- Main Content/Crud -->
    <div class="top">
        <div class="content-box">
            <div class="round-box">
                <p>APPOINTMENT TODAY:</p>
                <?php
                include("../dbcon.php");

                // Set the default time zone to Hong Kong
                date_default_timezone_set('Asia/Hong_Kong');

                // Check database connection
                if (!$con) {
                    die("Connection failed: " . mysqli_connect_error());
                }

                // Get current date
                $today = date('Y-m-d');

                // Query to count appointments for today
                $sql_today = "SELECT COUNT(*) as total_appointments_today 
                              FROM tbl_appointments 
                              WHERE (
                                (modified_date IS NOT NULL AND 
                                DATE(modified_date) = CURDATE()) 
                                OR (modified_date IS NULL AND 
                                DATE(date) = CURDATE())
                                ) AND status = '3'";


                $result_today = mysqli_query($con, $sql_today);

                // Check for SQL errors
                if (!$result_today) {
                    die("Query failed: " . mysqli_error($con));
                }

                $row_today = mysqli_fetch_assoc($result_today);
                $appointments_today = $row_today['total_appointments_today'];

                if ($appointments_today) {
                    echo "<span style='color: #FF9F00; font-weight: bold; font-size: 25px;'>$appointments_today</span>";
                } else {
                    echo "<span style='color: red;'>No data available</span>";
                }
                ?>
            </div>
            <div class="round-box">
                <p>PENDING APPOINTMENTS:</p>
                <?php
                // Query to count pending appointments
                $sql_pending = "SELECT COUNT(*) as total_pending_appointments 
                                FROM tbl_appointments 
                                WHERE status = '1'";
                $result_pending = mysqli_query($con, $sql_pending);

                // Check for SQL errors
                if (!$result_pending) {
                    die("Query failed: " . mysqli_error($con));
                }

                $row_pending = mysqli_fetch_assoc($result_pending);
                $pending_appointments = $row_pending['total_pending_appointments'];

                if ($pending_appointments) {
                    echo "<span style='color: #FF9F00; font-weight: bold; font-size: 25px;'>$pending_appointments</span>";
                } else {
                    echo "<span style='color: red;'>No data available</span>";
                }
                ?>
            </div>
            <div class="round-box">
                <p>APPOINTMENT FOR THIS WEEK:</p>
                <?php
                // Get the start and end date of the current week
                $start_of_week = date('Y-m-d', strtotime('monday this week'));
                $end_of_week = date('Y-m-d', strtotime('sunday this week'));

                // Query to count appointments for the current week
                $sql_week = "SELECT COUNT(*) as total_appointments_week 
                 FROM tbl_appointments 
                 WHERE (
                    (modified_date IS NOT NULL AND 
                     WEEK(DATE(modified_date), 1) = WEEK(CURDATE(), 1) AND DATE(modified_date) != CURDATE())
                    OR 
                    (date IS NOT NULL AND 
                     WEEK(DATE(date), 1) = WEEK(CURDATE(), 1) AND DATE(date) > CURDATE())
                        )
                 AND status = '3'";

                $result_week = mysqli_query($con, $sql_week);

                // Check for SQL errors
                if (!$result_week) {
                    die("Query failed: " . mysqli_error($con));
                }

                $row_week = mysqli_fetch_assoc($result_week);
                $appointments_for_week = $row_week['total_appointments_week'];

                if ($appointments_for_week) {
                    echo "<span style='color: #FF9F00; font-weight: bold; font-size: 25px;'>$appointments_for_week</span>";
                } else {
                    echo "<span style='color: red;'>No data available</span>";
                }
                ?>
            </div>
            <div class="round-box">
                <p>APPOINTMENT FOR NEXT WEEK:</p>
                <?php
                // Get the start and end date of the current week
                $start_of_week = date('Y-m-d', strtotime('monday this week'));
                $end_of_week = date('Y-m-d', strtotime('sunday this week'));

                // Query to count appointments for the current week
                $sql_week = "SELECT COUNT(*) as total_appointments_week 
                 FROM tbl_appointments 
                 WHERE (
                    (modified_date IS NOT NULL AND 
                    WEEK(DATE(modified_date), 1) = WEEK(CURDATE(), 1) + 1 AND DATE(modified_date) != CURDATE())
                    OR 
                    (date IS NOT NULL AND 
                    WEEK(DATE(date), 1) = WEEK(CURDATE(), 1) + 1 AND DATE(date) > CURDATE())
                    )
                    AND status = '3'";

                $result_week = mysqli_query($con, $sql_week);

                // Check for SQL errors
                if (!$result_week) {
                    die("Query failed: " . mysqli_error($con));
                }

                $row_week = mysqli_fetch_assoc($result_week);
                $appointments_for_week = $row_week['total_appointments_week'];

                if ($appointments_for_week) {
                    echo "<span style='color: #FF9F00; font-weight: bold; font-size: 25px;'>$appointments_for_week</span>";
                } else {
                    echo "<span style='color: red;'>No data available</span>";
                }
                ?>
            </div>
            <div class="round-box">
                <p>DECLINED APPOINTMENTS:</p>
                <?php
                // Query to count finished appointments
                $sql_finished = "SELECT COUNT(*) as total_finished_appointments FROM tbl_appointments WHERE status = '2'";
                $result_finished = mysqli_query($con, $sql_finished);

                // Check for SQL errors
                if (!$result_finished) {
                    die("Query failed: " . mysqli_error($con));
                }

                $row_finished = mysqli_fetch_assoc($result_finished);
                $finished_appointments = $row_finished['total_finished_appointments'];

                if ($finished_appointments) {
                    echo "<span style='color: #FF9F00; font-weight: bold; font-size: 25px;'>$finished_appointments</span>";
                } else {
                    echo "<span style='color: red;'>No data available</span>";
                }
                ?>
            </div>
            <?php
            // Set the number of results per page
            $resultsPerPage = 4;

            // Get the current page number from query parameters, default to 1
            $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;

            // Calculate the starting row for the SQL query
            $startRow = ($currentPage - 1) * $resultsPerPage;

            // SQL query to count total records
            $countQuery = "SELECT COUNT(*) as total FROM tbl_users";
            $countResult = mysqli_query($con, $countQuery);
            $totalCount = mysqli_fetch_assoc($countResult)['total'];
            $totalPages = ceil($totalCount / $resultsPerPage); // Calculate total pages
            
            // SQL query to fetch the limited number of records with OFFSET
            $query = "SELECT a.*, 
                        s.role AS acc_role 
                        FROM tbl_users a
                        JOIN tbl_role s ON a.role = s.id
            
            LIMIT $resultsPerPage OFFSET $startRow";
            $result = mysqli_query($con, $query);
            ?>

            <div class="managehead">
                <div class="manage">
                    <!-- Users Management Section -->
                    <h2>Manage Users</h2>
                    <button id="openModalBtn" class="add-user-btn">Add New User</button>
                </div>
                <!-- Pagination Navigation -->
                <div class="pagination-container">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-btn">
                            < </a>
                            <?php endif; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-btn">></a>
                            <?php endif; ?>
                </div>

            </div>
            <!-- Modal for Adding and Editing Users -->
            <div id="userModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2 id="modalTitle">Add New User</h2>
                    <form id="userForm" method="POST" action="">
                        <input type="hidden" name="id" id="userId">
                        <label>First Name:</label>
                        <input type="text" name="firstname" id="firstname" required><br>
                        <label>Middle Name:</label>
                        <input type="text" name="middlename" id="middlename"><br>
                        <label>Last Name:</label>
                        <input type="text" name="lastname" id="lastname" required><br>
                        <label>Username:</label>
                        <input type="text" name="username" id="username" required><br>
                        <label>Password:</label>
                        <input type="password" name="password" id="password" required><br>
                        <label for="role">Select Role:</label>
                        <select id="role" name="role">
                            <option value="">--Select Role--</option>
                            <option value="1">Admin</option>
                            <option value="2">Doctor</option>
                            <option value="3">Dental Assistant</option>
                        </select>
                        <button type="submit" id="submitBtn">Add User</button>
                    </form>
                </div>
            </div>
            <div id="notification" class="notification" style="display: none;">
                <p>Successfully Added!</p>
            </div>


            <!-- Display Table -->
            <table class="table table-bordered">
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
                <?php
                // Fetch and display user data
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>
            <td style='width:330px;'>{$row['lastname']}, {$row['firstname']} {$row['middlename']}</td>
            <td>{$row['acc_role']}</td>
            <td style='width:110px'>
            <button type='button' onclick='openModal({$row['id']}, \"{$row['firstname']}\", \"{$row['middlename']}\", \"{$row['lastname']}\", \"{$row['username']}\", \"{$row['role']}\")'
            style='background-color:#083690; color:white; border:none; padding:10px 5px; border-radius:10px; box-shadow: 1px 2px 5px 0px #414141; cursor:pointer;'>Update</button>
            <form method='POST' action='' style='display:inline;'>
                <input type='hidden' name='id' value='{$row['id']}'>
                <input type='submit' name='delete' value='Delete' onclick=\"return confirm('Are you sure you want to delete this record?');\" 
                style='background-color: rgb(196, 0, 0); color:white; border:none; padding:10px 5px; border-radius:10px; box-shadow: 1px 2px 5px 0px #414141; cursor:pointer;'>
            </form>
            </td>
          </tr>";
                }
                ?>
            </table>
            <br><br>

            <script>
                // Get modal elements
                var modal = document.getElementById("userModal");
                var openModalBtn = document.getElementById("openModalBtn");
                var closeModalSpan = document.getElementsByClassName("close")[0];

                // Open modal for adding a new user
                openModalBtn.onclick = function () {
                    document.getElementById("modalTitle").innerText = "Add New User";
                    document.getElementById("userForm").reset();
                    document.getElementById("userId").value = ""; // Reset hidden ID
                    modal.style.display = "block";
                }

                // Open modal for editing a user
                function openModal(id, username, password, role, created_at) {
                    document.getElementById("modalTitle").innerText = "Edit User";
                    document.getElementById("userId").value = id;
                    document.getElementById("username").value = username;
                    document.getElementById("password").value = password; // Consider hashing in backend
                    document.getElementById("role").value = role;
                    modal.style.display = "block";
                }

                // Close modal
                closeModalSpan.onclick = function () {
                    modal.style.display = "none";
                }

                // Close modal when clicking outside
                window.onclick = function (event) {
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }
                }
                document.getElementById('submitBtn').addEventListener('click', function () {
                    showNotification();
                });

                function showNotification() {
                    const notification = document.getElementById('notification');
                    notification.style.display = 'block';

                    // Start fading out after 3 seconds
                    setTimeout(() => {
                        notification.style.opacity = '0';
                    }, 5000);

                    // Hide completely after fading
                    setTimeout(() => {
                        notification.style.display = 'none';
                        notification.style.opacity = '1'; // Reset for next use
                    }, 3500);
                }
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
