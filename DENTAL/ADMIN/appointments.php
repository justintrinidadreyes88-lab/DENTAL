<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['1'])) {
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
    $conflict_query = "SELECT id 
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
                                     SET contact='$contact', modified_date='$modified_date', modified_time='$modified_time', modified_by = '2', service_type='$service_type' 
                                     WHERE id=$id";  // Assuming patient_id is used as foreign key in tbl_appointments

        // Execute both queries
        if (mysqli_query($con, $update_patient_query) && mysqli_query($con, $update_appointment_query)) {
            // Redirect to the same page after updating
            header("Location: appointments.php");
            exit();
        } else {
            echo "Error updating record: " . mysqli_error($con);
        }
    }
}

if (isset($_POST['submit'])) {
    // Get and sanitize the posted data
    $id = intval($_POST['id']); // Appointment ID
    $note = mysqli_real_escape_string($con, $_POST['note']);
    $price = floatval($_POST['price']); // Price

    // Fetch appointment details
    $stmt = $con->prepare("SELECT * FROM tbl_appointments WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $appointment = $result->fetch_assoc();

            // Archive appointment data
            $archive_stmt = $con->prepare("INSERT INTO tbl_archives 
                (name, contact, date, time, modified_date, modified_time, service_type, note, price, completion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '1')");
            $archive_stmt->bind_param(
                "ssssssssd",
                $appointment['name'],
                $appointment['contact'],
                $appointment['date'],
                $appointment['time'],
                $appointment['modified_date'],
                $appointment['modified_time'],
                $appointment['service_type'],
                $note,
                $price
            );

            if (!$archive_stmt->execute()) {
                die("Error inserting into tbl_archives: " . $archive_stmt->error);
            }

            // Remove the appointment from tbl_appointments
            $delete_stmt = $con->prepare("DELETE FROM tbl_appointments WHERE id = ?");
            $delete_stmt->bind_param("i", $id);

            if (!$delete_stmt->execute()) {
                die("Error deleting appointment: " . $delete_stmt->error);
            }

            // Redirect to appointments page
            header("Location: appointments.php");
            exit();
        } else {
            die("Error: Appointment not found.");
        }
    } else {
        die("Error executing fetch query: " . $stmt->error);
    }
}

if (isset($_POST['decline'])) {
    $id = $_POST['id'];
    $deleteQuery = "UPDATE tbl_appointments SET status = '2' WHERE id = $id";
    mysqli_query($con, $deleteQuery);

    // Redirect to refresh the page and show updated records
    header("Location: appointments.php");
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
    <title>Dental Assistant Dashboard</title>
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
            <?php
            // Include the appointments summary
            include("appointments_status.php");
            ?>

<?php
            // Set the number of results per page
            $resultsPerPage = 4;

            // Get the current page number from query parameters, default to 1
            $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;

            // Calculate the starting row for the SQL query
            $startRow = ($currentPage - 1) * $resultsPerPage;

            // Get today's date
            $today = date('Y-m-d');

            // SQL query to count total records for Day
            $countQueryDay = "SELECT COUNT(*) as total FROM tbl_appointments 
                    WHERE (
                     (modified_date IS NOT NULL AND 
                    DATE(modified_date) = CURDATE()) 
                  OR 
                  (modified_date IS NULL AND 
                    DATE(date) = CURDATE())
                    )
            AND status = '3'";
            $countResultDay = mysqli_query($con, $countQueryDay);
            $totalCountDay = mysqli_fetch_assoc($countResultDay)['total'];
            $totalPagesDay = ceil($totalCountDay / $resultsPerPage); // Calculate total pages for Day
            
            // SQL query to count total records for Week
            $start_of_week = date('Y-m-d', strtotime('last Sunday')); // Get the start of the week
            $end_of_week = date('Y-m-d', strtotime('next Saturday')); // Get the end of the week
            
            $countQueryWeek = "SELECT COUNT(*) as total FROM tbl_appointments 
                    WHERE (
                    (modified_date IS NOT NULL AND 
                     WEEK(DATE(modified_date), 1) = WEEK(CURDATE(), 1) AND DATE(modified_date) != CURDATE())
                    OR 
                    (date IS NOT NULL AND 
                     WEEK(DATE(date), 1) = WEEK(CURDATE(), 1) AND DATE(date) > CURDATE())
                        )
                    AND status = '3'";
            $countResultWeek = mysqli_query($con, $countQueryWeek);
            $totalCountWeek = mysqli_fetch_assoc($countResultWeek)['total'];
            $totalPagesWeek = ceil($totalCountWeek / $resultsPerPage); // Calculate total pages for Week
            
            $countQueryNextWeek = "SELECT COUNT(*) as total FROM tbl_appointments 
                    WHERE (
                    (modified_date IS NOT NULL AND 
                    WEEK(DATE(modified_date), 1) = WEEK(CURDATE(), 1) + 1 AND DATE(modified_date) != CURDATE())
                    OR 
                    (date IS NOT NULL AND 
                    WEEK(DATE(date), 1) = WEEK(CURDATE(), 1) + 1 AND DATE(date) > CURDATE())
                    )
                    AND status = '3'";
            $countResultNextWeek = mysqli_query($con, $countQueryNextWeek);
            $totalCountNextWeek = mysqli_fetch_assoc($countResultNextWeek)['total'];
            $totalPagesNextWeek = ceil($totalCountNextWeek / $resultsPerPage); // Calculate total pages for Week
            
            // SQL query for Day with JOIN to fetch the limited number of records with OFFSET
            $queryDay = "SELECT a.*, 
                s.service_type AS service_name, 
                p.first_name, p.middle_name, p.last_name 
            FROM tbl_appointments a
            JOIN tbl_service_type s ON a.service_type = s.id
            JOIN tbl_patient p ON a.id = p.id  -- corrected join condition
            WHERE (
                  (a.modified_date IS NOT NULL AND 
                    DATE(a.modified_date) = CURDATE()) 
                  OR 
                  (a.modified_date IS NULL AND 
                    DATE(a.date) = CURDATE())
            )
            AND a.status = '3'
            ORDER BY  a.time DESC, a.modified_time DESC
            LIMIT $resultsPerPage OFFSET $startRow";

            // SQL query for Week with JOIN to fetch the limited number of records with OFFSET
            $queryWeek = "SELECT a.*, 
                      s.service_type AS service_name, 
                      p.first_name, p.middle_name, p.last_name 
              FROM tbl_appointments a
              JOIN tbl_service_type s ON a.service_type = s.id
              JOIN tbl_patient p ON a.id = p.id  -- corrected join condition
              WHERE (
                    (a.modified_date IS NOT NULL AND 
                     WEEK(DATE(a.modified_date), 1) = WEEK(CURDATE(), 1) AND DATE(a.modified_date) != CURDATE())
                    OR 
                    (a.date IS NOT NULL AND 
                     WEEK(DATE(a.date), 1) = WEEK(CURDATE(), 1) AND DATE(a.date) > CURDATE())
              )
              AND a.status = '3'
              ORDER BY a.date DESC, a.time DESC, a.modified_date DESC, a.modified_time DESC
              LIMIT $resultsPerPage OFFSET $startRow";

            $queryNextWeek = "SELECT a.*, 
            s.service_type AS service_name, 
                      p.first_name, p.middle_name, p.last_name 
              FROM tbl_appointments a
              JOIN tbl_service_type s ON a.service_type = s.id
              JOIN tbl_patient p ON a.id = p.id  -- corrected join condition
              WHERE (
                (a.modified_date IS NOT NULL AND 
                WEEK(DATE(a.modified_date), 1) = WEEK(CURDATE(), 1) + 1 AND DATE(a.modified_date) != CURDATE())
                OR 
                (a.date IS NOT NULL AND 
                WEEK(DATE(a.date), 1) = WEEK(CURDATE(), 1) + 1 AND DATE(a.date) > CURDATE())
                )
              AND a.status = '3'
              ORDER BY a.date DESC, a.time DESC, a.modified_date DESC, a.modified_time DESC
              LIMIT $resultsPerPage OFFSET $startRow";


            $resultNextWeek = mysqli_query($con, $queryNextWeek);
            $resultWeek = mysqli_query($con, $queryWeek);
            $resultDay = mysqli_query($con, $queryDay);

            // Default tab is 'Day'
            $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'Day';
            ?>

            <!-- Tab structure -->
            <div class="tab">
                <button class="tablinks" onclick="switchTab('Day')">Today</button>
                <button class="tablinks" onclick="switchTab('Week')">This Week</button>
                <button class="tablinks" onclick="switchTab('NextWeek')">Next Week</button>
            </div>

            <!-- Tab content for Day -->
            <div id="Day" class="tabcontent" style="display: <?php echo $activeTab == 'Day' ? 'block' : 'none'; ?>;">
                <br>
                <h3 style="color: #094514;">Today</h3>

                <!-- Pagination for Day -->
                <div class="pagination-container">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?php echo $currentPage - 1; ?>&tab=Day" class="pagination-btn">&lt;</a>
                    <?php endif; ?>
                    <?php if ($currentPage < $totalPagesDay): ?>
                        <a href="?page=<?php echo $currentPage + 1; ?>&tab=Day" class="pagination-btn">&gt;</a>
                    <?php endif; ?>
                </div>

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
                        if (mysqli_num_rows($resultDay) > 0) {
                            while ($row = mysqli_fetch_assoc($resultDay)) {
                                $modified_date = !empty($row['modified_date']) ? $row['modified_date'] : 'N/A';
                                $modified_time = !empty($row['modified_time']) ? date("h:i A", strtotime($row['modified_time'])) : 'N/A';
                                $dateToDisplay = !empty($row['date']) ? $row['date'] : 'N/A';
                                $timeToDisplay = !empty($row['time']) ? date("h:i A", strtotime($row['time'])) : 'N/A';

                                echo "<tr>
                        <td style='width: 230px;'>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                        <td>{$row['contact']}</td>
                        <td style='width: 110px;'>{$dateToDisplay}</td>
                        <td style='width: 110px;'>{$timeToDisplay}</td>
                        <td style='width: 110px;'>{$modified_date}</td>
                        <td style='width: 110px;'>{$modified_time}</td>
                        <td style='font-size: 15px;'>{$row['service_name']}</td>
                        <td style='width: 130px'>
                            <form method='POST' action='' style='display:inline;'>
                            <input type='hidden' name='id' value='{$row['id']}'>
                            <input type='submit' name='decline' value='Decline' onclick=\"return confirm('Are you sure you want to remove this record?');\" 
                            style='background-color: rgb(196, 0, 0); color:white; border:none;  padding:10px 9px; border-radius:10px; box-shadow: 1px 2px 5px 0px #414141; cursor:pointer;'>
                            </form>";

                                if ($row['status'] != 'finished') {
                                    echo "<button type='button' onclick='openFinishModal({$row['id']}, \"{$row['first_name']}\", \"{$row['middle_name']}\", \"{$row['last_name']}\", \"{$row['contact']}\", \"{$dateToDisplay}\", \"{$timeToDisplay}\", \"{$row['service_name']}\")' 
                    style='background-color:green; color:white; border:none; padding:10px 9px; border-radius:10px; box-shadow: 1px 2px 5px 0px #414141; cursor:pointer;'>Finish</button>";
                                }

                                echo "</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8'>No records found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Tab content for Week -->
            <div id="Week" class="tabcontent" style="display: <?php echo $activeTab == 'Week' ? 'block' : 'none'; ?>;">
                <br>
                <h3 style="color: #094514;">This Week</h3>
                <!-- Pagination for Week -->
                <div class="pagination-container">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?php echo $currentPage - 1; ?>&tab=Week" class="pagination-btn">&lt;</a>
                    <?php endif; ?>
                    <?php if ($currentPage < $totalPagesWeek): ?>
                        <a href="?page=<?php echo $currentPage + 1; ?>&tab=Week" class="pagination-btn">&gt;</a>
                    <?php endif; ?>
                </div>

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
                        if (mysqli_num_rows($resultWeek) > 0) {
                            while ($row = mysqli_fetch_assoc($resultWeek)) {
                                $modified_date = !empty($row['modified_date']) ? $row['modified_date'] : 'N/A';
                                $modified_time = !empty($row['modified_time']) ? date("h:i A", strtotime($row['modified_time'])) : 'N/A';
                                $dateToDisplay = !empty($row['date']) ? $row['date'] : 'N/A';
                                $timeToDisplay = !empty($row['time']) ? date("h:i A", strtotime($row['time'])) : 'N/A';

                                echo "<tr>
                        <td style='width: 230px;'>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                        <td>{$row['contact']}</td>
                        <td style='width: 110px;'>{$dateToDisplay}</td>
                        <td style='width: 110px;'>{$timeToDisplay}</td>
                        <td style='width: 110px;'>{$modified_date}</td>
                        <td style='width: 110px;'>{$modified_time}</td>
                        <td style='font-size: 15px;'>{$row['service_name']}</td>
                        <td style='width: 130px'>
                            <form method='POST' action='' style='display:inline;'>
                            <input type='hidden' name='id' value='{$row['id']}'>
                            <input type='submit' name='decline' value='Decline' onclick=\"return confirm('Are you sure you want to remove this record?');\" 
                            style='background-color: rgb(196, 0, 0); color:white; border:none;  padding:10px 9px; border-radius:10px; box-shadow: 1px 2px 5px 0px #414141; cursor:pointer;'>
                            </form>";

                                if ($row['status'] != 'finished') {
                                    echo "<button type='button' onclick='openFinishModal({$row['id']}, \"{$row['first_name']}\", \"{$row['middle_name']}\", \"{$row['last_name']}\", \"{$row['contact']}\", \"{$dateToDisplay}\", \"{$timeToDisplay}\", \"{$row['service_name']}\")' 
                    style='background-color:green; color:white; border:none; padding:10px 9px; border-radius:10px; box-shadow: 1px 2px 5px 0px #414141; cursor:pointer;'>Finish</button>";
                                }

                                echo "</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8'>No records found</td></tr>";
                        }
                        ?>
                    </tbody>
                    </tbody>
                </table>
            </div>

            <!-- Tab content for Next Week -->
            <div id="NextWeek" class="tabcontent"
                style="display: <?php echo $activeTab == 'NextWeek' ? 'block' : 'none'; ?>;">
                <br>
                <h3 style="color: #094514;">Next Week</h3>
                <!-- Pagination for Week -->
                <div class="pagination-container">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?php echo $currentPage - 1; ?>&tab=NextWeek" class="pagination-btn">&lt;</a>
                    <?php endif; ?>
                    <?php if ($currentPage < $totalPagesNextWeek): ?>
                        <a href="?page=<?php echo $currentPage + 1; ?>&tab=NextWeek" class="pagination-btn">&gt;</a>
                    <?php endif; ?>
                </div>

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
                        if (mysqli_num_rows($resultNextWeek) > 0) {
                            while ($row = mysqli_fetch_assoc($resultNextWeek)) {
                                $modified_date = !empty($row['modified_date']) ? $row['modified_date'] : 'N/A';
                                $modified_time = !empty($row['modified_time']) ? date("h:i A", strtotime($row['modified_time'])) : 'N/A';
                                $dateToDisplay = !empty($row['date']) ? $row['date'] : 'N/A';
                                $timeToDisplay = !empty($row['time']) ? date("h:i A", strtotime($row['time'])) : 'N/A';

                                echo "<tr>
                        <td style='width: 230px;'> {$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                        <td>{$row['contact']}</td>
                        <td style='width: 110px;'>{$dateToDisplay}</td>
                        <td style='width: 110px;'>{$timeToDisplay}</td>
                        <td style='width: 110px;'>{$modified_date}</td>
                        <td style='width: 110px;'>{$modified_time}</td>
                        <td style='widtfont-size: 15px;'>{$row['service_name']}</td>
                        <td style='width: 130px'>
                            <form method='POST' action='' style='display:inline;'>
                            <input type='hidden' name='id' value='{$row['id']}'>
                            <input type='submit' name='decline' value='Decline' onclick=\"return confirm('Are you sure you want to remove this record?');\" 
                            style='background-color: rgb(196, 0, 0); color:white; border:none;  padding:10px 9px; border-radius:10px; box-shadow: 1px 2px 5px 0px #414141; cursor:pointer;'>
                            </form>";

                                if ($row['status'] != 'finished') {
                                    echo "<button type='button' onclick='openFinishModal({$row['id']}, \"{$row['first_name']}\", \"{$row['middle_name']}\", \"{$row['last_name']}\", \"{$row['contact']}\", \"{$dateToDisplay}\", \"{$timeToDisplay}\", \"{$row['service_name']}\")' 
                    style='background-color:green; color:white; border:none; padding:10px 9px; border-radius:10px; box-shadow: 1px 2px 5px 0px #414141; cursor:pointer;'>Finish</button>";
                                }

                                echo "</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8'>No records found</td></tr>";
                        }
                        ?>
                    </tbody>
                    </tbody>
                </table>
            </div>

            <div id="finishModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <button style="background-color: transparent;" class="close">&times;</button>
                    <h3 style="text-align: center; font-size: 30px;">Service Completion</h3>
                    <hr>
                    <div id="modalDetails">
                        <p><strong>Name:</strong> <span id="modalName"></span></p>
                        <br>
                        <p><strong>Contact Number:</strong> <span id="modalContact"></span></p>
                        <br>
                        <p><strong>Date & Time:</strong> <span id="modalDateTime"></span></p>
                        <br>
                        <p><strong>Current Service:</strong> <span id="modalService"></span></p>
                    </div>
                    <hr>
                    <form id="newServiceForm" method="POST" action="">
                        <input type="hidden" name="id" value="">
                        <br>
                        <label style="font-size: 20px; font-weight: bold;" for="note">Note:</label>
                        <br>
                        <br>
                        <textarea id="note" name="note" placeholder="Enter your note here..."></textarea>
                        <br>

                        <label style="font-size: 20px; font-weight: bold;" for="price">Total Price (₱):</label>
                        <div class="price">
                            <input type="number" id="price" name="price"
                                style="width: 30%; font-size: 25px; font-weight: bold;" min="0" max="1000000"
                                step="0.01" required oninput="validateLength(this, 7)">
                            <button type="submit" name="submit" id="proceed">Proceed to Dental Assistant</button>
                        </div>
                        <p id="error-message" style="color: red; display: none;">Input exceeds maximum allowed length.
                        </p>
                </div>
                </form>
            </div>
        </div>
        <div id="notification" class="notification" style="display: none;">
            <p>Successfully Submitted!</p>
        </div>
        <script>
            function validateLength(input, maxLength) {
                const errorMessage = document.getElementById('error-message');

                // Prevent user from entering more than `maxLength` characters
                if (input.value.length > maxLength) {
                    input.value = input.value.slice(0, maxLength); // Truncate extra characters
                    errorMessage.style.display = 'block';
                } else {
                    errorMessage.style.display = 'none';
                }
            }

            function openFinishModal(id, firstName, middleName, lastName, contact, date, time, service) {
                // Set modal details dynamically
                document.getElementById('modalName').innerText = `${lastName}, ${firstName} ${middleName}`;
                document.getElementById('modalContact').innerText = contact;
                document.getElementById('modalDateTime').innerText = `${date} at ${time}`;
                document.getElementById('modalService').innerText = service;

                // Clear the price input field when opening the modal
                document.getElementById('price').value = '';

                // Set the hidden ID field in the form
                document.querySelector("#newServiceForm input[name='id']").value = id;

                // Display the modal
                document.getElementById('finishModal').style.display = 'block';
            }

            // Event listener to close the modal when the close button is clicked
            document.querySelector('.close').addEventListener('click', () => {
                document.getElementById('finishModal').style.display = 'none';
            });

            // Event listener to close the modal when clicking outside of it
            window.addEventListener('click', (event) => {
                if (event.target == document.getElementById('finishModal')) {
                    document.getElementById('finishModal').style.display = 'none';
                }
            });

            // Event listener for the proceed button to trigger a notification
            document.getElementById('proceed').addEventListener('click', function () {
                showNotification();
            });

            // Function to show a notification message
            function showNotification() {
                const notification = document.getElementById('notification, declined');
                if (notification) {
                    notification.style.display = 'block';

                    // Start fading out after 3 seconds
                    setTimeout(() => {
                        notification.style.opacity = '0';
                    }, 3000);

                    // Hide completely after fading
                    setTimeout(() => {
                        notification.style.display = 'none';
                        notification.style.opacity = '1'; // Reset for next use
                    }, 3500);
                }
            }
            // Switch between tabs
            function openTab(evt, tabName) {
                var i, tabcontent, tablinks;

                // Hide all tab content
                tabcontent = document.getElementsByClassName("tabcontent");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }

                // Remove 'active' class from all tab links
                tablinks = document.getElementsByClassName("tablinks");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].classList.remove("active");
                }

                // Display the clicked tab's content and add 'active' class to the clicked tab
                document.getElementById(tabName).style.display = "block";
                evt.currentTarget.classList.add("active");
            }

            function switchTab(tabName) {
                // Update the URL to reflect the selected tab without reloading
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabName);
                window.history.pushState({}, '', url);
                // Call openTab to display the selected tab content
                openTab(event, tabName);
            }

            // This runs when the page is loaded, ensuring the correct tab is shown based on the URL
            window.onload = function () {
                const params = new URLSearchParams(window.location.search);
                const activeTab = params.get('tab') || 'Day';
                openTab({ currentTarget: document.querySelector(`[onclick="switchTab('${activeTab}')"]`) }, activeTab);
            };
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
