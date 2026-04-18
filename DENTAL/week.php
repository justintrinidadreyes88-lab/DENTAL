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

    // Update query for tbl_patient
    $update_patient_query = "UPDATE tbl_patient 
                             SET first_name='$first_name', middle_name='$middle_name', last_name='$last_name'
                             WHERE id=$id";

    // Update query for tbl_appointments
    $update_appointment_query = "UPDATE tbl_appointments 
                                 SET contact='$contact', modified_date='$modified_date', modified_time='$modified_time', modified_by = '3', service_type='$service_type' 
                                 WHERE id=$id";  // Assuming patient_id is used as foreign key in tbl_appointments

    // Execute both queries
    if (mysqli_query($con, $update_patient_query) && mysqli_query($con, $update_appointment_query)) {
        // Redirect to the same page after updating
        header("Location: week.php");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($con);
    }
}


if (isset($_POST['accept'])) {
    // Check if the connection exists
    if (!$con) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Get the appointment ID from the form
    $id = $_POST['id'];

    // Prepare the query to update the status to 'finished' using a prepared statement
    $stmt = $con->prepare("UPDATE tbl_appointments SET status=? WHERE id=?");
    $status = 3; // Assuming '4' represents finished
    $stmt->bind_param("ii", $status, $id);

    // Execute the query
    if ($stmt->execute()) {
        // Redirect back to the dashboard
        header("Location: week.php");
        exit();
    } else {
        echo "Error updating status: " . $stmt->error;
    }

    $stmt->close();
}

if (isset($_POST['decline'])) {
    $id = $_POST['id'];
    $deleteQuery = "UPDATE tbl_appointments SET status = '2' WHERE id = $id";
    mysqli_query($con, $deleteQuery);

    // Redirect to refresh the page and show updated records
    header("Location: week.php");
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
        <form method="POST" action="../logout.php">
            <button type="submit" class="logout-button">Logout</button>
        </form>
        <a href="archives.php"><i class="fas fa-trash trash"></i></a>
    </nav>
    <div>
        <aside class="sidebar">
            <ul>
                <br>
                <a class="active" href="dental_assistant_dashboard.php">
                    <h3>DENTAL ASSISTANT<br>DASHBOARD</h3>
                </a>
                <br>
                <br>
                <hr>
                <br>
                <li><a href="pending.php">Pending Appointments</a></a></li>
                <li><a href="appointments.php">Approved Appointments</a></li>
                <li><a href="week.php">Appointment for next week</a></li>
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
                              WHERE (DATE(date) = '$today' OR DATE(modified_date) = '$today') AND status = '3'";



                $result_today = mysqli_query($con, $sql_today);

                // Check for SQL errors
                if (!$result_today) {
                    die("Query failed: " . mysqli_error($con));
                }

                $row_today = mysqli_fetch_assoc($result_today);
                $appointments_today = $row_today['total_appointments_today'];

                echo $appointments_today ? $appointments_today : 'No data available';
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

                echo $pending_appointments ? $pending_appointments : 'No data available';
                ?>
            </div>
            <div class="round-box">
                <p>APPOINTMENT FOR THE WEEK:</p>
                <?php
                // Get the start and end date of the current week
                $start_of_week = date('Y-m-d', strtotime('monday this week'));
                $end_of_week = date('Y-m-d', strtotime('sunday this week'));

                // Query to count appointments for the current week
                $sql_week = "SELECT COUNT(*) as total_appointments_week 
                 FROM tbl_appointments 
                 WHERE (DATE(date) BETWEEN '$start_of_week' AND '$end_of_week' 
                 OR DATE(modified_date) BETWEEN '$start_of_week' AND '$end_of_week') 
                 AND status = '3'";

                $result_week = mysqli_query($con, $sql_week);

                // Check for SQL errors
                if (!$result_week) {
                    die("Query failed: " . mysqli_error($con));
                }

                $row_week = mysqli_fetch_assoc($result_week);
                $appointments_for_week = $row_week['total_appointments_week'];

                echo $appointments_for_week ? $appointments_for_week : 'No data available';
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

                echo $finished_appointments ? $finished_appointments : 'No data available';
                ?>
            </div>

            <?php
            // Set the number of results per page
            $resultsPerPage = 20;

            // Get the current page number from query parameters, default to 1
            $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;

            // Calculate the starting row for the SQL query
            $startRow = ($currentPage - 1) * $resultsPerPage;

            // Get today's date
            $today = date('Y-m-d');

            // Calculate the start of next week (next Sunday)
            $start_of_next_week = date('Y-m-d', strtotime('next Sunday', strtotime($today)));

            // Calculate the start of the week after that (18th)
            $start_of_week_after_next = date('Y-m-d', strtotime('+1 days', strtotime($start_of_next_week)));

            // Calculate the end of the next week (Saturday 24th)
            $end_of_week_after_next = date('Y-m-d', strtotime('+6 days', strtotime($start_of_week_after_next)));

            // SQL query to count total records for the next week
            $countQuery = "SELECT COUNT(*) as total FROM tbl_appointments 
               WHERE (DATE(date) BETWEEN '$start_of_week_after_next' AND '$end_of_week_after_next' 
               OR DATE(modified_date) BETWEEN '$start_of_week_after_next' AND '$end_of_week_after_next') 
               AND status = '3'"; // Ensure the status is 3
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
          WHERE (DATE(a.date) BETWEEN '$start_of_week_after_next' AND '$end_of_week_after_next'  
          OR DATE(a.modified_date) BETWEEN '$start_of_week_after_next' AND '$end_of_week_after_next') 
          AND a.status = '3'
          ORDER BY a.date DESC, a.time DESC, a.modified_date DESC, a.modified_time DESC 
          LIMIT $resultsPerPage OFFSET $startRow";  // Limit to 20 rows
            
            $result = mysqli_query($con, $query);
            ?>

            <br><br><br>

            <!-- HTML Table -->
            <div class="pagination-container">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-btn"> &lt; </a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-btn"> &gt; </a>
                <?php endif; ?>
            </div>

            <!-- Table -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Modified_Date</th>
                        <th>Modified_Time</th>
                        <th>Type Of Service</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Check if modified_date and modified_time are empty
                            $modified_date = !'0000-00-00' && !empty($row['modified_date']) ? $row['modified_date'] : 'N/A';
                            $modified_time = !'00:00:00' && !empty($row['modified_time']) ? date("h:i A", strtotime($row['modified_time'])) : 'N/A';

                            $dateToDisplay = !empty($row['date']) ? $row['date'] : 'N/A';
                            $timeToDisplay = !empty($row['time']) ? date("h:i A", strtotime($row['time'])) : 'N/A';

                            echo "<tr>
                    <td>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                    <td>{$row['contact']}</td>
                    <td>{$dateToDisplay}</td>
                    <td>{$timeToDisplay}</td>
                    <td>{$modified_date}</td>
                    <td>{$modified_time}</td>
                    <td>{$row['service_name']}</td>
                    <td>
                        <button type='button' onclick='openModal({$row['id']}, \"{$row['first_name']}\", \"{$row['middle_name']}\", \"{$row['last_name']}\", \"{$row['contact']}\", \"{$dateToDisplay}\", \"{$timeToDisplay}\", \"{$row['service_name']}\")' 
                        style='background-color:#083690; color:white; border:none; padding:7px 9px; border-radius:10px; margin:11px 3px; cursor:pointer;'>Update</button>
                        <form method='POST' action='' style='display:inline;'>
                            <input type='hidden' name='id' value='{$row['id']}'>
                        </form>";
                            if ($row['status'] != 'Decline') {
                                echo "<form method='POST' action='' style='display:inline;'>
                        <input type='hidden' name='id' value='{$row['id']}'>
                        <input type='submit' name='decline' value='Decline' 
                        style='background-color: rgb(196, 0, 0); color:white; border:none; padding:7px 9px; border-radius:10px; margin:11px 3px; cursor:pointer;'>
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
                        <h1>EDIT DELAILS</h1><br>
                        <input type="hidden" name="id" id="modal-id">
                        <br>
                        <label for="modal-first-name">First Name:</label>
                        <input type="text" name="first_name" id="modal-first-name" required>
                        <br>
                        <label for="modal-last-name">Last Name:</label>
                        <input type="text" name="last_name" id="modal-last-name" required>
                        <br>
                        <label for="modal-middle-name">Middle Name:</label>
                        <input type="text" name="middle_name" id="modal-middle-name" required>
                        <br>
                        <label for="contact">Contact:</label>
                        <input type="text" name="contact" id="modal-contact" required>
                        <br>
                        <label for="date">Date:</label>
                        <input type="date" name="modified_date" id="modal-modified_date" required>
                        <br>
                        <p>
                            <label for="time">Time:</label>
                            <input type="time" name="modified_time" id="modal-modified_time" min="09:00" max="18:00"
                                required>
                            CLINIC HOURS 9:00 AM TO 6:00 PM
                        </p>
                        <label for="service_type">Type Of Service:</label>
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
                </script>
            </div>
        </div>
</body>

</html>
