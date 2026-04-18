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

if (isset($_POST['approve'])) {
    // Check if the connection exists
    if (!$con) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Get the appointment ID from the form
    $id = $_POST['id'];

    // Prepare the query to update the status to 'finished' using a prepared statement
    $stmt = $con->prepare("UPDATE tbl_appointments SET status=? WHERE id=?");
    $status = 3; // Assuming '3' represents finished
    $stmt->bind_param("ii", $status, $id);

    // Execute the query
    if ($stmt->execute()) {
        // Display success message using JavaScript
        echo "<script>
            alert('Appointment successfully approved!');
            window.location.href = 'pending.php'; // Redirect after alert
        </script>";
        exit();  // Exit to ensure the script stops here
    } else {
        // Display error message if query fails
        echo "<script>
            alert('Error updating status: " . $stmt->error . "');
            window.location.href = 'pending.php'; // Redirect to same page
        </script>";
    }

    $stmt->close();
}

if (isset($_POST['decline'])) {
    // Get the appointment ID from the form
    $id = $_POST['id'];

    // Prepare the query to update the status to 'declined' (status = '2')
    $deleteQuery = "UPDATE tbl_appointments SET status = '2' WHERE id = $id";

    if (mysqli_query($con, $deleteQuery)) {
        // Display success message using JavaScript
        echo "<script>
            alert('Appointment successfully declined!');
            window.location.href = 'pending.php'; // Redirect after alert
        </script>";
        exit();  // Ensure no further code runs after the redirect
    } else {
        // Display error message if the query fails
        echo "<script>
            alert('Error declining appointment: " . mysqli_error($con) . "');
            window.location.href = 'pending.php'; // Redirect after error message
        </script>";
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
        <!-- Logo and link to the home page -->
        <a href="../HOME_PAGE/Home_page.php">
            <div class="logo">
                <h1><span>EHM</span> Dental Clinic</h1>
            </div>
        </a>
        <!-- Logout and archives button -->
        <form method="POST" class="s-buttons" action="../logout.php">
            <!-- Link to the archives page -->
            <a href="DENTAL_ASSISTANT_ARCHIVES/archives.php"><i class="fas fa-trash trash"></i></a>
            <!-- Logout button -->
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </nav>

    <div>
        <!-- Sidebar for navigation links -->
        <aside class="sidebar">
            <ul>
                <br>
                <!-- Link to the dental assistant dashboard -->
                <a class="active" href="dental_assistant_dashboard.php">
                    <h3>DENTAL ASSISTANT<br>DASHBOARD</h3>
                </a>
                <br>
                <hr>
                <br>
                <!-- Navigation links for different appointment statuses -->
                <li><a href="pending.php">Pending Appointments</a></li>
                <li><a href="appointments.php">Approved Appointments</a></li>
                <li><a href="declined.php">Declined Appointment</a></li>
                <li><a href="billing.php">Billing Approval</a></li>
            </ul>
        </aside>
    </div>

    <!-- Main Content/CRUD Section -->
    <div class="top">
        <div class="content-box">
            <?php
            // Include the appointments summary section
            include("appointments_status.php");
            ?>

            <?php
            // Set the number of results to display per page
            $resultsPerPage = 5;

            // Retrieve the current page number from the query parameter, default to page 1 if not set
            $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;

            // Calculate the starting row for the SQL query based on the current page
            $startRow = ($currentPage - 1) * $resultsPerPage;

            // SQL query to count the total number of appointments with status '1'
            $countQuery = "SELECT COUNT(*) as total FROM tbl_appointments WHERE status = '1'";
            $countResult = mysqli_query($con, $countQuery);
            $totalCount = mysqli_fetch_assoc($countResult)['total'];

            // Calculate the total number of pages needed based on results per page
            $totalPages = ceil($totalCount / $resultsPerPage);

            // SQL query with JOIN to fetch paginated appointments data
            $query = "SELECT a.*, 
                s.service_type AS service_name, 
                p.first_name, p.middle_name, p.last_name,
                t.status     
            FROM tbl_appointments a
            JOIN tbl_service_type s ON a.service_type = s.id
            JOIN tbl_patient p ON a.name = p.id
            JOIN tbl_status t ON a.status = t.id  
            WHERE a.status = '1'
            ORDER BY 
                CASE 
                WHEN a.modified_date IS NOT NULL THEN a.modified_date
                ELSE a.date
                END DESC,
                CASE 
                WHEN a.modified_time IS NOT NULL THEN a.modified_time
                ELSE a.time
                END ASC
            LIMIT $resultsPerPage OFFSET $startRow";  // Limit results and apply pagination
            
            // Execute the query to fetch data
            $result = mysqli_query($con, $query);
            ?>

            <!-- Pagination Controls -->
            <div class="pagination-container">
                <!-- Display 'Previous' button if not on the first page -->
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-btn">
                        < </a>
                        <?php endif; ?>

                        <!-- Display 'Next' button if not on the last page -->
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-btn">></a>
                        <?php endif; ?>

                        <!-- Additional condition for pagination if more than 15 records exist -->
                        <?php if ($totalCount > 15): ?>
                            <!-- Additional content could go here -->
                        <?php endif; ?>
            </div>
        </div>

        <!-- Appointment Records Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <!-- Table headers -->
                    <th>Name</th> <!-- Full name of the patient -->
                    <th>Contact</th> <!-- Contact details -->
                    <th>Date</th> <!-- Appointment date -->
                    <th>Time</th> <!-- Appointment time -->
                    <th style="font-size: 15px;">Rescheduled Date</th> <!-- Modified or rescheduled date -->
                    <th style="font-size: 15px;">Rescheduled Time</th> <!-- Modified or rescheduled time -->
                    <th>Service</th> <!-- Service type -->
                    <th>Actions</th> <!-- Action buttons: Update, Approve, or Decline -->
                </tr>
            </thead>
            <tbody>
                <?php
                // Check if there are records to display
                if (mysqli_num_rows($result) > 0) {
                    // Loop through each record
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Determine the modified date and time or display 'N/A' if not available
                        $modified_date = !'' && !empty($row['modified_date']) ? $row['modified_date'] : 'N/A';
                        $modified_time = !'' && !empty($row['modified_time']) ? date("h:i A", strtotime($row['modified_time'])) : 'N/A';

                        // Handle original date and time values
                        $dateToDisplay = !empty($row['date']) ? $row['date'] : 'N/A';
                        $timeToDisplay = !empty($row['time']) ? date("h:i A", strtotime($row['time'])) : 'N/A';

                        // Render table row for each appointment record
                        echo "<tr>
                <td style='width: 230px'>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td> <!-- Patient's full name -->
                <td>{$row['contact']}</td> <!-- Patient's contact number -->
                <td style='width: 110px'>{$dateToDisplay}</td> <!-- Original appointment date -->
                <td style='width: 110px'>{$timeToDisplay}</td> <!-- Original appointment time -->
                <td style='width: 110px'>{$modified_date}</td> <!-- Rescheduled date -->
                <td style='width: 110px'>{$modified_time}</td> <!-- Rescheduled time -->
                <td style='font-size: 15px'>{$row['service_name']}</td> <!-- Service name -->
                <td style='width: 180px'> <!-- Action buttons -->
                    <!-- Update button to open a modal with the record details -->
                    <button type='button' onclick='openModal({$row['id']}, \"{$row['first_name']}\", \"{$row['middle_name']}\", \"{$row['last_name']}\", \"{$row['contact']}\", \"{$dateToDisplay}\", \"{$timeToDisplay}\", \"{$row['service_type']}\")' 
                    style='background-color:#083690; color:white; border:none; padding:10px 5px; border-radius:10px; cursor:pointer;  box-shadow: 1px 2px 5px 0px #414141;'>Update</button>
                    
                    <form method='POST' action='' style='display:inline;'> <!-- Hidden form to handle actions -->
                        <input type='hidden' name='id' value='{$row['id']}'>
                    </form>";

                        // Approve button if the status is not already 'Approve'
                        if ($row['status'] != 'Approve') {
                            echo "<form method='POST' action='' style='display:inline;'>
                        <input type='hidden' name='id' value='{$row['id']}'>
                        <input type='submit' name='approve' value='Approve' 
                        style='background-color:green; color:white; border:none; padding:10px 5px; border-radius:10px; cursor:pointer;  box-shadow: 1px 2px 5px 0px #414141;'>
                    </form>";
                        }

                        // Decline button if the status is not already 'Decline'
                        if ($row['status'] != 'Decline') {
                            echo "<form method='POST' action='' style='display:inline;'>
                        <input type='hidden' name='id' value='{$row['id']}'>
                        <input type='submit' name='decline' value='Decline' 
                        style='background-color: rgb(196, 0, 0); color:white; border:none; padding:10px 5px; border-radius:10px; cursor:pointer;  box-shadow: 1px 2px 5px 0px #414141;'>
                    </form>";
                        }

                        echo "</td></tr>";
                    }
                } else {
                    // Display message if no records are found
                    echo "<tr><td colspan='8'>No records found</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Edit Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <!-- Close button for the modal -->
                <span class="close" onclick="closeModal()">&times;</span>

                <!-- Edit form -->
                <form method="POST" action="">
                    <h1>EDIT DETAILS</h1><br>

                    <!-- Hidden input to store the record ID -->
                    <input type="hidden" name="id" id="modal-id">

                    <!-- Full Name fields -->
                    <label for="modal-name">Full Name: <br> (Last Name, First Name, Middle Initial)</label>
                    <div class="name-fields">
                        <input type="text" name="last_name" id="modal-last-name" maxlength="50"
                            placeholder="Enter Last Name" required>
                        <input type="text" name="first_name" id="modal-first-name" maxlength="50"
                            placeholder="Enter First Name" required>
                        <input type="text" name="middle_name" id="modal-middle-name" maxlength="2"
                            placeholder="Enter Middle Initial">
                    </div>

                    <!-- Contact number field -->
                    <label for="contact">Contact:</label>
                    <input type="text" name="contact" id="modal-contact" placeholder="Enter your contact number"
                        maxlength="11" required pattern="\d{11}" title="Please enter exactly 11 digits"><br>

                    <!-- Date picker with restrictions -->
                    <label for="date">Date:</label>
                    <input type="date" name="modified_date" id="modal-modified_date" required>
                    <br>

                    <!-- Time selector -->
                    <label for="time">Time: <br> (Will only accept appointments from 9:00 a.m to 6:00 p.m)</label>
                    <select name="modified_time" id="modal-modified_time" required>
                        <option value="09:00 AM">09:00 AM</option>
                        <option value="10:30 AM">10:30 AM</option>
                        <option value="12:00 PM" disabled>12:00 PM (Lunch Break)</option>
                        <option value="12:30 PM">12:30 PM</option>
                        <option value="13:30 PM">01:30 PM</option>
                        <option value="15:00 PM">03:00 PM</option>
                        <option value="16:30 PM">04:30 PM</option>
                    </select>

                    <!-- Service type dropdown -->
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

                    <!-- Submit button -->
                    <input type="submit" name="update" id="update" value="Save">
                </form>
            </div>

            <!-- Notification for success message -->
            <div id="notification" class="notification" style="display: none;">
                <p>Your appointment has been successfully updated!</p>
            </div>

            <!-- Modal scripts -->
            <script>
                // Open the modal and populate fields with provided data
                function openModal(id, first_name, middle_name, last_name, contact, modified_date, modified_time, service_type) {
                    document.getElementById('modal-id').value = id;
                    document.getElementById('modal-first-name').value = first_name;
                    document.getElementById('modal-middle-name').value = middle_name;
                    document.getElementById('modal-last-name').value = last_name;
                    document.getElementById('modal-contact').value = contact;
                    document.getElementById('modal-modified_date').value = modified_date;
                    document.getElementById('modal-modified_time').value = modified_time;
                    document.getElementById('modal-service_type').value = service_type;

                    // Calculate date range for valid inputs (7-day window)
                    const today = new Date();
                    const firstDay = new Date(today);
                    const lastDay = new Date(firstDay);
                    lastDay.setDate(firstDay.getDate() + 6); // End of week

                    // Set date restrictions in the modal
                    document.getElementById('modal-modified_date').setAttribute('min', formatDate(firstDay));
                    document.getElementById('modal-modified_date').setAttribute('max', formatDate(lastDay));

                    // Open the modal
                    document.getElementById('editModal').style.display = 'block';
                }

                // Close the modal
                function closeModal() {
                    document.getElementById('editModal').style.display = 'none';
                }

                // Format a date to 'YYYY-MM-DD'
                function formatDate(date) {
                    const year = date.getFullYear();
                    const month = (date.getMonth() + 1).toString().padStart(2, '0');
                    const day = date.getDate().toString().padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }

                // Display success notification
                function showNotification() {
                    const notification = document.getElementById('notification');
                    notification.style.display = 'block';
                    setTimeout(() => {
                        notification.style.opacity = '0';
                    }, 5000); // Fade out after 5 seconds
                    setTimeout(() => {
                        notification.style.display = 'none';
                        notification.style.opacity = '1'; // Reset for reuse
                    }, 3500);
                }

                // Close modal when clicking outside the modal
                window.onclick = function (event) {
                    if (event.target == document.getElementById('editModal')) {
                        closeModal();
                    }
                }

                // Show notification when 'Save' is clicked
                document.getElementById('update').addEventListener('click', function () {
                    showNotification();
                });
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
