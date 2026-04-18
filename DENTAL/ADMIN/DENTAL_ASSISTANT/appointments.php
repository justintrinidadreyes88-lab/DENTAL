<?php
session_start();

// Check if the user is logged in and has the role of dental assistant (role '3')
// If not, redirect to the login page
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['3'])) {
    header("Location: ../signin.php");
    exit();
}

// Include the database connection file
include("../dbcon.php");

// Check the database connection and terminate if it fails
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle the form submission for updating a record
if (isset($_POST['update'])) {
    // Retrieve and sanitize form data to prevent SQL injection
    $id = $_POST['id'];
    $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
    $middle_name = mysqli_real_escape_string($con, $_POST['middle_name']);
    $contact = mysqli_real_escape_string($con, $_POST['contact']);
    $modified_date = mysqli_real_escape_string($con, $_POST['modified_date']);
    $modified_time = mysqli_real_escape_string($con, $_POST['modified_time']);
    $service_type = mysqli_real_escape_string($con, $_POST['service_type']);

    // Check if the new date and time conflict with any existing records
    $conflict_query = "
        SELECT id 
        FROM tbl_appointments 
        WHERE 
            (date = '$modified_date' AND TIME(time) = TIME('$modified_time')) OR 
            (modified_date = '$modified_date' AND TIME(modified_time) = TIME('$modified_time'))
        AND id != $id";

    // Execute the conflict check query
    $conflict_result = mysqli_query($con, $conflict_query);

    // If a conflict is found, display an alert message
    if (mysqli_num_rows($conflict_result) > 0) {
        echo "<script>alert('The selected date and time are already booked. Please choose a different time.');</script>";
    } else {
        // If no conflict, proceed with updating the records

        // Query to update the tbl_patient table
        $update_patient_query = "UPDATE tbl_patient 
                                 SET first_name='$first_name', middle_name='$middle_name', last_name='$last_name'
                                 WHERE id=$id";

        // Query to update the tbl_appointments table
        $update_appointment_query = "UPDATE tbl_appointments 
                                     SET contact='$contact', modified_date='$modified_date', modified_time='$modified_time', modified_by = '3', service_type='$service_type' 
                                     WHERE id=$id";

        // Execute both update queries
        if (mysqli_query($con, $update_patient_query) && mysqli_query($con, $update_appointment_query)) {
            // If both updates are successful, display a success message and redirect
            echo "<script>
                alert('Record updated successfully!');
                window.location.href = 'appointments.php';
            </script>";
            exit();
        } else {
            // If an update fails, display an error message
            echo "<script>alert('Error updating record: " . mysqli_error($con) . "');</script>";
        }
    }
}
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
            ORDER BY 
            CASE 
            WHEN a.modified_date IS NOT NULL THEN a.modified_date
            ELSE a.date
            END DESC,
            CASE 
            WHEN a.modified_time IS NOT NULL THEN a.modified_time
            ELSE a.time
            END ASC
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
              ORDER BY 
            CASE 
            WHEN a.modified_date IS NOT NULL THEN a.modified_date
            ELSE a.date
            END DESC,
            CASE 
            WHEN a.modified_time IS NOT NULL THEN a.modified_time
            ELSE a.time
            END ASC
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
              ORDER BY 
            CASE 
            WHEN a.modified_date IS NOT NULL THEN a.modified_date
            ELSE a.date
            END DESC,
            CASE 
            WHEN a.modified_time IS NOT NULL THEN a.modified_time
            ELSE a.time
            END ASC
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
                        <td style='width: 230px'>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                        <td>{$row['contact']}</td>
                        <td style='width: 110px'>{$dateToDisplay}</td>
                        <td style='width: 110px'>{$timeToDisplay}</td>
                        <td style='width: 110px'>{$modified_date}</td>
                        <td style='width: 110px'>{$modified_time}</td>
                        <td>{$row['service_name']}</td>
                        <td style='width: 10px'>
                            <button type='button' onclick='openModal({$row['id']}, \"{$row['first_name']}\", \"{$row['middle_name']}\", \"{$row['last_name']}\", \"{$row['contact']}\", \"{$dateToDisplay}\", \"{$timeToDisplay}\", \"{$row['service_name']}\")' 
                            style='background-color:#083690; color:white; border:none; padding:7px 9px; border-radius:10px; cursor:pointer; box-shadow: 1px 2px 5px 0px #414141;'>Update</button>
                        </td>
                    </tr>";
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
                        <td style='width: 230px'>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                        <td>{$row['contact']}</td>
                        <td style='width: 110px'>{$dateToDisplay}</td>
                        <td style='width: 110px'>{$timeToDisplay}</td>
                        <td style='width: 110px'>{$modified_date}</td>
                        <td style='width: 110px'>{$modified_time}</td>
                        <td>{$row['service_name']}</td>
                        <td style='width: 10px'>
                            <button type='button' onclick='openModal({$row['id']}, \"{$row['first_name']}\", \"{$row['middle_name']}\", \"{$row['last_name']}\", \"{$row['contact']}\", \"{$dateToDisplay}\", \"{$timeToDisplay}\", \"{$row['service_name']}\")' 
                            style='background-color:#083690; color:white; border:none; padding:7px 9px; border-radius:10px; cursor:pointer; box-shadow: 1px 2px 5px 0px #414141;'>Update</button>
                        </td>
                    </tr>";
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
                        <td style='width: 230px'>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                        <td>{$row['contact']}</td>
                        <td style='width: 110px'>{$dateToDisplay}</td>
                        <td style='width: 110px'>{$timeToDisplay}</td>
                        <td style='width: 110px'>{$modified_date}</td>
                        <td style='width: 110px'>{$modified_time}</td>
                        <td>{$row['service_name']}</td>
                        <td style='width: 10px'>
                            <button type='button' onclick='openModal({$row['id']}, \"{$row['first_name']}\", \"{$row['middle_name']}\", \"{$row['last_name']}\", \"{$row['contact']}\", \"{$dateToDisplay}\", \"{$timeToDisplay}\", \"{$row['service_name']}\")' 
                            style='background-color:#083690; color:white; border:none; padding:7px 9px; border-radius:10px;  cursor:pointer; box-shadow: 1px 2px 5px 0px #414141;'>Update</button>
                        </td>
                    </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8'>No records found</td></tr>";
                        }
                        ?>
                    </tbody>
                    </tbody>
                </table>
            </div>
            <!-- Edit Modal -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <form method="POST" action="">
                        <h1>EDIT DETAILS</h1><br>
                        <input type="hidden" name="id" id="modal-id">
                        <label for="modal-name">Full Name: <br> (Last Name, First Name, Middle Initial)</label>
                        <div class="name-fields">
                            <input type="text" name="last_name" id="modal-last-name" maxlength="50"
                                placeholder="Enter Last Name" required>
                            <input type="text" name="first_name" id="modal-first-name" maxlength="50"
                                placeholder="Enter First Name" required>
                            <input type="text" name="middle_name" id="modal-middle-name" maxlength="2"
                                placeholder="Enter Middle Initial">
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
                        <label for="service_type">Type of Services:</label>
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
            </div>

            <script>
                // Open the modal and populate it with data
                function openModal(id, first_name, middle_name, last_name, contact, modified_date, modified_time, service_type) {
                    document.getElementById('modal-id').value = id;
                    document.getElementById('modal-first-name').value = first_name;
                    document.getElementById('modal-middle-name').value = middle_name;
                    document.getElementById('modal-last-name').value = last_name;
                    document.getElementById('modal-contact').value = contact;
                    document.getElementById('modal-modified_date').value = modified_date;
                    document.getElementById('modal-modified_time').value = modified_time;
                    document.getElementById('modal-service_type').value = service_type;
                    document.getElementById('editModal').style.display = 'block';
                }

                // Close the modal
                function closeModal() {
                    document.getElementById('editModal').style.display = 'none';
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
