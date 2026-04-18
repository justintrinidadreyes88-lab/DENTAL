<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['3'])) {
    header("Location: ../signin.php");
    exit();
}

include("../dbcon.php");

// Check database connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle update request
if (isset($_POST['update'])) {
    // Get form data from modal
    $id = $_POST['id'];
    $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
    $middle_name = mysqli_real_escape_string($con, $_POST['middle_name']);
    $contact = mysqli_real_escape_string($con, $_POST['contact']);
    $modified_date = mysqli_real_escape_string($con, $_POST['modified_date']);
    $modified_time = mysqli_real_escape_string($con, $_POST['modified_time']);
    $service_type = mysqli_real_escape_string($con, $_POST['service_type']);

    // Check for conflicts in both original date/time and modified date/time
    $conflict_query = "
        SELECT id 
        FROM tbl_appointments 
        WHERE 
            (date = '$modified_date' AND TIME(time) = TIME('$modified_time')) OR 
            (modified_date = '$modified_date' AND TIME(modified_time) = TIME('$modified_time'))
        AND id != $id"; // Exclude the current appointment being updated

    $conflict_result = mysqli_query($con, $conflict_query);

    if (mysqli_num_rows($conflict_result) > 0) {
        // Conflict found
        echo "<script>alert('The selected date and time are already booked. Please choose a different time.');</script>";
    } else {
        // No conflict - proceed with the update

        // Update query for tbl_patient
        $update_patient_query = "UPDATE tbl_patient 
                                 SET first_name='$first_name', middle_name='$middle_name', last_name='$last_name'
                                 WHERE id=$id";

        // Update query for tbl_appointments
        $update_appointment_query = "UPDATE tbl_appointments 
                                     SET contact='$contact', modified_date='$modified_date', modified_time='$modified_time', modified_by = '3', service_type='$service_type' 
                                     WHERE id=$id";

        // Execute both queries
        if (mysqli_query($con, $update_patient_query) && mysqli_query($con, $update_appointment_query)) {
            // Display success message and redirect using JavaScript
            echo "<script>
                alert('Record updated successfully!');
                window.location.href = 'pending.php';
            </script>";
            exit();
        } else {
            // Display error if the query fails
            echo "<script>alert('Error updating record: " . mysqli_error($con) . "');</script>";
        }
    }
}
date_default_timezone_set('Asia/Hong_Kong');

if (isset($_POST['delete'])) {
    // Get the ID from the form data
    $id = $_POST['id'];

    // Echo a confirmation alert using JavaScript
    echo "<script>
        if (confirm('Are you sure you want to delete this record?')) {
            // Continue processing in PHP if confirmed
    ";

    // Fetch the appointment data to transfer to the bin
    $appointment_query = "SELECT * FROM tbl_appointments WHERE id=$id";

    $appointment_result = mysqli_query($con, $appointment_query);

    if ($appointment_row = mysqli_fetch_assoc($appointment_result)) {
        $name = mysqli_real_escape_string($con, $appointment_row['name']);
        $contact = mysqli_real_escape_string($con, $appointment_row['contact']);
        $date = mysqli_real_escape_string($con, $appointment_row['date']);
        $time = mysqli_real_escape_string($con, $appointment_row['time']);
        $modified_date = mysqli_real_escape_string($con, $appointment_row['modified_date']);
        $modified_time = mysqli_real_escape_string($con, $appointment_row['modified_time']);
        $service_type = mysqli_real_escape_string($con, $appointment_row['service_type']);
        $status = mysqli_real_escape_string($con, $appointment_row['status']);

        // Set the current date and time for deleted_at
        $deleted_at = date('Y-m-d H:i:s');

        // Insert into tbl_bin including the deleted_at field
        $insert_archives_query = "INSERT INTO tbl_bin (id, name, contact, date, time, modified_date, modified_time, service_type, status, deleted_at)
                             VALUES ('$id', '$name', '$contact', '$date', '$time', '$modified_date', '$modified_time', '$service_type', '$status', '$deleted_at')";

        // Execute the insert query
        if (mysqli_query($con, $insert_archives_query)) {
            // Delete the appointment from tbl_appointments
            $delete_appointment_query = "DELETE FROM tbl_appointments WHERE id=$id";

            // Execute the delete query
            if (mysqli_query($con, $delete_appointment_query)) {
                // Display a success message and redirect
                echo "
                    alert('Successfully deleted the record.');
                    window.location.href = 'declined.php';
                ";
            } else {
                echo "alert('Error deleting appointment record: " . mysqli_error($con) . "');";
            }
        } else {
            echo "alert('Error transferring appointment record to Archives: " . mysqli_error($con) . "');";
        }
    } else {
        echo "alert('No appointment found with this ID.');";
    }

    echo "}
    </script>";
}

if (isset($_POST['restore'])) {
    $id = $_POST['id'];

    // Update the record to set status to '1' (restored)
    $restoreQuery = "UPDATE tbl_appointments SET status = '1' WHERE id = $id";
    if (mysqli_query($con, $restoreQuery)) {
        // Display success alert and redirect
        echo "<script>
            alert('Appointment successfully restored.');
            window.location.href = 'declined.php';
        </script>";
    } else {
        // Display error alert if the query fails
        echo "<script>
            alert('Error restoring record: " . mysqli_error($con) . "');
            window.location.href = 'declined.php';
        </script>";
    }
}

// SQL query to count total records
$countQuery = "SELECT COUNT(*) as total FROM tbl_appointments WHERE status = '1'";
$countResult = mysqli_query($con, $countQuery);
$totalCount = mysqli_fetch_assoc($countResult)['total'];

// SQL query with JOIN to fetch the limited number of records
$query = "SELECT a.*, 
            s.service_type AS service_name, 
            p.first_name, p.middle_name, p.last_name
          FROM tbl_appointments a
          JOIN tbl_service_type s ON a.service_type = s.id
          JOIN tbl_patient p ON a.id = p.id
          WHERE a.status = '3'
          LIMIT 15";  // Limit to 15 rows

$result = mysqli_query($con, $query);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dental.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
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
        <form method="POST" class="s-buttons" action="../logout.php">
            <a href="DENTAL_ASSISTANT_ARCHIVES/archives.php"><i class="fas fa-trash trash"></i></a>
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </nav>
    <div>
        <aside class="sidebar">
            <ul>
                <br>
                <a class="active" href="dental_assistant_dashboard.php">
                    <h3>DENTAL ASSISTANT<br>DASHBOARD</h3>
                </a>
                <br>
                <hr>
                <br>
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
            $resultsPerPage = 6;

            // Get the current page number from query parameters, default to 1
            $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;

            // Calculate the starting row for the SQL query
            $startRow = ($currentPage - 1) * $resultsPerPage;

            // SQL query to count total records
            $countQuery = "SELECT COUNT(*) as total FROM tbl_appointments WHERE status = '2'";
            $countResult = mysqli_query($con, $countQuery);
            $totalCount = mysqli_fetch_assoc($countResult)['total'];
            $totalPages = ceil($totalCount / $resultsPerPage); // Calculate total pages
            
            // SQL query with JOIN to fetch the limited number of records with OFFSET
            $query = "SELECT a.*, 
            s.service_type AS service_name, 
            p.first_name, p.middle_name, p.last_name 
          FROM tbl_appointments a
          JOIN tbl_service_type s ON a.service_type = s.id
          JOIN tbl_patient p ON a.id = p.id
          WHERE a.status = '2'
          ORDER BY 
            CASE 
            WHEN a.modified_date IS NOT NULL THEN a.modified_date
            ELSE a.date
            END DESC,
            CASE 
            WHEN a.modified_time IS NOT NULL THEN a.modified_time
            ELSE a.time
            END ASC
          LIMIT $resultsPerPage OFFSET $startRow";  // Limit to 15 rows
            
            $result = mysqli_query($con, $query);
            ?><br>

            <!-- HTML Table -->
            <div class="pagination-container">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-btn">
                        < </a>
                        <?php endif; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-btn"> > </a>
                        <?php endif; ?>

                        <?php if ($totalCount > 15): ?>
                        <?php endif; ?>
            </div>
        </div>
        <!-- Table -->
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Check if modified_date and modified_time are empty
                        $modified_date = !'' && !empty($row['modified_date']) ? $row['modified_date'] : 'N/A';
                        $modified_time = !'' && !empty($row['modified_time']) ? date("h:i A", strtotime($row['modified_time'])) : 'N/A';

                        $dateToDisplay = !empty($row['date']) ? $row['date'] : 'N/A';
                        $timeToDisplay = !empty($row['time']) ? date("h:i A", strtotime($row['time'])) : 'N/A';

                        echo "<tr>
                        <td style='width:230px;'>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                        <td >{$row['contact']}</td>
                        <td style=' width:110px;'>{$dateToDisplay}</td>
                        <td style=' width:110px;'>{$timeToDisplay}</td>
                        <td style=' width:110px;'>{$modified_date}</td>
                        <td style=' width:110px;'>{$modified_time}</td>
                        <td style=' font-size: 15px'>{$row['service_name']}</td>
                        <td style=' width: 180px;'>
                        <button type='button' onclick='openModal({$row['id']}, \"{$row['first_name']}\", \"{$row['middle_name']}\", \"{$row['last_name']}\", \"{$row['contact']}\", \"{$dateToDisplay}\", \"{$timeToDisplay}\", \"{$row['service_name']}\")' 
                        style='background-color:#083690; color:white; border:none; padding:10px 5px; border-radius:10px; cursor:pointer;  box-shadow: 1px 2px 5px 0px #414141;'>Update</button>
                        <form method='POST' action='' style='display:inline;'>
                            <input type='hidden' name='id' value='{$row['id']}'>
                        </form>";
                        if ($row['status'] != 'Restore') {
                            echo "<form method='POST' action='' style='display:inline;'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <input type='submit' name='restore' value='Restore' 
                                style='background-color:green; color:white; border:none;  padding:10px 5px; border-radius:10px; cursor:pointer; box-shadow: 1px 2px 5px 0px #414141;'>
                            </form>";
                        }
                        if ($row['status'] != 'Delete') {
                            echo "<form method='POST' action='' style='display:inline;'>
                            <input type='hidden' name='id' value='{$row['id']}'>
                            <input type='submit' name='delete' value='Delete' 
                            style='background-color: rgb(196, 0, 0); color:white; border:none;  padding:10px 5px; border-radius:10px; cursor:pointer;  box-shadow: 1px 2px 5px 0px #414141;'>
                        </form>";
                        }

                        echo "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No records found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <br><br>
        <!-- Edit Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <form method="POST" action="">
                    <h1>EDIT DETAILS</h1><br>
                    <input type="hidden" name="id" id="modal-id">
                    <label for="modal-name">Full Name: <br> (Last Name, First Name, Middle Initial)</label>
                <div class="name-fields">
                  <input type="text" name="last_name" id="modal-last-name" maxlength="50" placeholder="Enter Last Name" required>
                  <input type="text" name="first_name" id="modal-first-name" maxlength="50" placeholder="Enter First Name" required>
                  <input type="text" name="middle_name" id="modal-middle-name" maxlength="2" placeholder="Enter Middle Initial">
                </div>
                    <label for="contact">Contact:</label>
                    <input type="text" name="contact" id="modal-contact" placeholder="Enter your contact number"
                        maxlength="11" required pattern="\d{11}" title="Please enter exactly 11 digits"><br>
                    <label for="date">Date:</label>
                    <input type="date" name="modified_date" id="modal-modified_date" required>
                    <br>
                    <label for="time">Time: <br> (Will only accept appointments from 9:00 a.m to 6:00 p.m)</label>
                    <select name="modified_time" id="modal-modified_time" required>
                        <option value="09:00 AM">09:00 AM</option>
                        <option value="10:30 AM">10:30 AM</option>
                        <option value="12:00 PM" disabled>12:00 AM (Lunch Break)</option>
                        <option value="12:30 PM">12:30 PM</option>
                        <option value="13:30 PM">01:30 PM</option>
                        <option value="15:00 PM">03:00 PM</option>
                        <option value="16:30 PM">04:30 PM</option>
                    </select>
                    <label for="service_type">Types of Services:</label>
                    <select name="service_type" id="modal-service_type" required>
                        <option value="">--Select Service Type--</option>
                        <option value="1">All Porcelain Veneers & Zirconia</option>
                        <option value="2">Crown & Bridge</option>
                        <option value="3">Dental Cleaning</option>
                        <option value="4">Dental Implants</option>
                        <option value="5">Dental Whitening</option>
                        <option value="6">Dentures</option>
                        <option value="7">Extraction</option>
                        <option value="8">Full Exam & X-Ray</option>
                        <option value="9">Orthodontic Braces</option>
                        <option value="10">Restoration</option>
                        <option value="11">Root Canal Treatment</option>
                    </select>
                    <br>
                    <input type="submit" name="update" value="Save">
                </form>
            </div>
            <script>
                // Open the modal and populate it with data
                function openModal(id, first_name, middle_name, last_name, contact, modified_date, modified_time, service_type) {
                    // Populate modal fields with the received values
                    document.getElementById('modal-id').value = id;
                    document.getElementById('modal-first-name').value = first_name;
                    document.getElementById('modal-middle-name').value = middle_name;
                    document.getElementById('modal-last-name').value = last_name;
                    document.getElementById('modal-contact').value = contact;
                    document.getElementById('modal-modified_date').value = modified_date;
                    document.getElementById('modal-modified_time').value = modified_time;
                    document.getElementById('modal-service_type').value = service_type;

                    // Get today's date
                    const today = new Date();

                    // Calculate the start (today) and end (six days from today) of the current week
                    const firstDay = new Date(today); // Start of the week (today)
                    const lastDay = new Date(firstDay);
                    lastDay.setDate(firstDay.getDate() + 6); // End of the week (six days from today)

                    // Set min and max for the date input
                    document.getElementById('modal-modified_date').setAttribute('min', formatDate(firstDay));
                    document.getElementById('modal-modified_date').setAttribute('max', formatDate(lastDay));

                    // Display the moving week in the console
                    const weekDays = [];
                    for (let i = 0; i < 7; i++) {
                        const currentDay = new Date(firstDay);
                        currentDay.setDate(firstDay.getDate() + i); // Get each day of the week
                        weekDays.push(formatDate(currentDay)); // Format and add to array
                    }
                    console.log(weekDays.join(' ')); // You can also display this in the UI instead

                    // Set time input limits
                    document.getElementById('modal-modified_time').setAttribute('min', '09:00');
                    document.getElementById('modal-modified_time').setAttribute('max', '18:00');

                    // Show the modal
                    document.getElementById('editModal').style.display = 'block';
                }

                // Close the modal
                function closeModal() {
                    document.getElementById('editModal').style.display = 'none';
                }

                // Format date as YYYY-MM-DD
                function formatDate(date) {
                    const year = date.getFullYear();
                    const month = (date.getMonth() + 1).toString().padStart(2, '0');
                    const day = date.getDate().toString().padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }

                // Close modal when clicking outside of it
                window.onclick = function (event) {
                    if (event.target == document.getElementById('editModal')) {
                        closeModal();
                    }
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
    </div>
</body>

</html>
