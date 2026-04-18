<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['1', '2'])) {
    header("Location: ../signin.php");
    exit();
}

include("../dbcon.php");

// Check database connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
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
    header("Location: doctor_dashboard.php");
}

// SQL query to count total records
$countQuery = "SELECT COUNT(*) as total FROM tbl_appointments WHERE status = '3'";
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
    <link rel="stylesheet" href="doctor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Doctor Dashboard</title>
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
        </a>
    </nav>
    <div>
        <aside class="sidebar">
            <ul>
                <br>
                <a href="doctor_dashboard.php">
                    <h3>DOCTOR <br>DASHBOARD</h3>
                </a>
                <br>
                <br>
                <hr>
                <br>
                <li><a href="appointments.php">Approved Appointments</a></li>
                <li><a href="services.php">Services</a></li>
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
            $resultsPerPage = 5;

            // Get the current page number from query parameters, default to 1
            $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;

            // Calculate the starting row for the SQL query
            $startRow = ($currentPage - 1) * $resultsPerPage;

            // SQL query to count total records
            $countQuery = "SELECT COUNT(*) as total FROM tbl_appointments WHERE status = '3'";
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
          WHERE a.status = '3'
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
            ?>

            <!-- HTML Table -->

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
        <!-- Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Service</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Prepare data for display
                        $dateToDisplay = !empty($row['modified_date']) ? $row['modified_date'] : $row['date'];
                        $timeToDisplay = !empty($row['modified_time']) ? $row['modified_time'] : $row['time'];
                        $timeToDisplayFormatted = date("h:i A", strtotime($timeToDisplay));

                        echo "<tr>
                        <td style='width:230px;'>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                        <td>{$row['contact']}</td>
                        <td style='width:110px;'>{$dateToDisplay}</td>
                        <td style='width:110px;'>{$timeToDisplayFormatted}</td>
                        <td>{$row['service_name']}</td>
                    </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No records found</td></tr>";
                }
                ?>
                <div id="declined" class="notification" style="display: none;">
                    <p>Successfully Declined!</p>
                </div>
            </tbody>
        </table>
        <br><br>

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
                            style="width: 30%; font-size: 25px; font-weight: bold;" min="0" step="0.01" required>
                        <button type="submit" name="submit" id="proceed">Proceed to Dental Assistant</button>
                    </div>
                </form>
            </div>
        </div>
        <div id="notification" class="notification" style="display: none;">
            <p>Successfully Submitted!</p>
        </div>
        <script>
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
                tabcontent = document.getElementsByClassName("tabcontent");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }
                tablinks = document.getElementsByClassName("tablinks");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].classList.remove("active");
                }
                document.getElementById(tabName).style.display = "block";
                evt.currentTarget.classList.add("active");
            }
            function switchTab(tabName) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabName); // Update 'tab' parameter
                window.location.href = url.toString(); // Reload with updated URL
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
