<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['3'])) {
    header("Location: ../signin.php");
    exit();
}

// Database connection
include("../dbcon.php");

// Handle approve action
if (isset($_POST['Approve'])) {
    $id = $_POST['id'];
    $UpdateQuery = "UPDATE tbl_archives SET completion = '2' WHERE id = $id";
    if (mysqli_query($con, $UpdateQuery)) {
        // Set a session variable for the success message
        $_SESSION['notification'] = 'Record successfully approved and marked as complete.';

        // Redirect to refresh the page and show updated records
        header("Location: billing.php");
        exit; // Make sure to stop further code execution after redirect
    }
}

// On the billing.php or any page you want to show the notification
if (isset($_SESSION['notification'])) {
    echo "<div id='notification' class='notification' style='background-color: green; color: white; padding: 10px; border-radius: 5px;'>
            {$_SESSION['notification']}
          </div>";
    // Unset the session variable after displaying the message
    unset($_SESSION['notification']);
    echo "<script>
            setTimeout(() => {
                const notification = document.getElementById('notification');
                if (notification) {
                    notification.style.display = 'none';
                }
            }, 3000); // Adjust time in milliseconds (3000ms = 3   seconds)
          </script>";
}

if (isset($_POST['submit'])) {
    $id = intval($_POST['id']);
    $paid = floatval($_POST['paid']);
    $outstanding_balance = floatval($_POST['outstanding_balance']);

    $stmt = $con->prepare("SELECT * FROM tbl_archives WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $appointment = $result->fetch_assoc();

            $archive_stmt = $con->prepare("INSERT INTO tbl_transaction_history
                (name, contact, service_type, date, modified_date, bill, paid, outstanding_balance, note) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $archive_stmt->bind_param(
                "ssssssiis",
                $appointment['name'],
                $appointment['contact'],
                $appointment['service_type'],
                $appointment['date'],
                $appointment['modified_date'],
                $appointment['price'],
                $paid,
                $outstanding_balance,
                $appointment['note']
            );

            if (!$archive_stmt->execute()) {
                die("Error inserting into tbl_transaction_history: " . $archive_stmt->error);
            }

            $delete_stmt = $con->prepare("DELETE FROM tbl_archives WHERE id = ?");
            $delete_stmt->bind_param("i", $id);

            $_SESSION['notification'] = 'Record successfully approved and marked as complete.';

            if (!$delete_stmt->execute()) {
                die("Error deleting appointment: " . $delete_stmt->error);
            }

            header("Location: billing.php");
            exit();
        } else {
            die("Error: Appointment not found.");
        }
    } else {
        die("Error executing fetch query: " . $stmt->error);
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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100..900&display=swap" rel="stylesheet">
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

    <!-- Main Content/Crud -->
    <div class="top">
        <div class="content-box">
            <?php
            // Include the appointments summary
            include("appointments_status.php");
            ?>

            <?php
            $resultsPerPage = 4;

            // Get the current page number from query parameters, default to 1
            $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;

            // Calculate the starting row for the SQL query
            $startRow = ($currentPage - 1) * $resultsPerPage;

            // Get today's date
            $today = date('Y-m-d');

            // Count total records for Day (One-Time Payment Tab)
            $countQueryOnetimepayment = "SELECT COUNT(*) as total FROM tbl_archives 
                WHERE completion = 1 AND service_type != 9"; // Exclude service_type = 9
            $countResultOnetimepayment = mysqli_query($con, $countQueryOnetimepayment);

            // Check for query errors
            if (!$countResultOnetimepayment) {
                die("Query failed: " . mysqli_error($con)); // Debugging message
            }
            $totalCountOnetimepayment = mysqli_fetch_assoc($countResultOnetimepayment)['total'];
            $totalPagesOnetimepayment = ceil($totalCountOnetimepayment / $resultsPerPage); // Calculate total pages for One-Time Payment
            
            // Count total records for Week (Packages Tab)
            $countQueryPackages = "SELECT COUNT(*) as total FROM tbl_archives 
                WHERE completion = 1 AND service_type = 9"; // Only service_type = 9
            $countResultPackages = mysqli_query($con, $countQueryPackages);

            // Check for query errors
            if (!$countResultPackages) {
                die("Query failed: " . mysqli_error($con)); // Debugging message
            }
            $totalCountPackages = mysqli_fetch_assoc($countResultPackages)['total'];
            $totalPagesPackages = ceil($totalCountPackages / $resultsPerPage); // Calculate total pages for Packages
            
            // SQL query for One-Time Payment with JOIN to fetch the limited number of records with OFFSET
            $queryOnetimepayment = "SELECT a.*, 
                s.service_type AS service_name, 
                p.first_name, p.middle_name, p.last_name  
                FROM tbl_archives a
                JOIN tbl_service_type s ON a.service_type = s.id
                JOIN tbl_patient p ON a.name = p.id 
                WHERE a.completion = 1 AND a.service_type != 9  -- Exclude service_type = 9
                ORDER BY a.date DESC, a.time DESC, a.modified_date DESC, a.modified_time DESC
                LIMIT $resultsPerPage OFFSET $startRow";

            // SQL query for Packages with JOIN to fetch the limited number of records with OFFSET
            $queryPackages = "SELECT a.*, 
                s.service_type AS service_name, 
                p.first_name, p.middle_name, p.last_name  
                FROM tbl_archives a
                JOIN tbl_service_type s ON a.service_type = s.id
                JOIN tbl_patient p ON a.name = p.id 
                WHERE a.completion = 1 AND a.service_type = 9  -- Only service_type = 9
                ORDER BY a.date DESC, a.time DESC, a.modified_date DESC, a.modified_time DESC
                LIMIT $resultsPerPage OFFSET $startRow";

            $resultOnetimepayment = mysqli_query($con, $queryOnetimepayment);
            $resultPackages = mysqli_query($con, $queryPackages);

            // Check for query errors
            if (!$resultOnetimepayment) {
                die("Query failed: " . mysqli_error($con)); // Debugging message
            }
            if (!$resultPackages) {
                die("Query failed: " . mysqli_error($con)); // Debugging message
            }

            // Default tab is 'Day'
            $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'Onetimepayment';
            ?>

            <!-- Tab structure -->
            <div class="tab">
                <button class="tablinks" onclick="switchTab('Onetimepayment')">One Time</button>
                <button class="tablinks" onclick="switchTab('Packages')">This Packages</button>
            </div>
            <!-- Tab content for onetimepayment -->
            <div id="Onetimepayment" class="tabcontent"
                style="display: <?php echo $activeTab == 'Onetimepayment' ? 'block' : 'none'; ?>;">
                <br>
                <h3 style="color:#094514;">One Time</h3>

                <!-- Pagination for onetimepayment -->
                <div class="pagination-container">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?php echo $currentPage - 1; ?>&tab=Onetimepayment" class="pagination-btn">&lt;</a>
                    <?php endif; ?>
                    <?php if ($currentPage < $totalPagesOnetimepayment): ?>
                        <a href="?page=<?php echo $currentPage + 1; ?>&tab=Onetimepayment" class="pagination-btn">&gt;</a>
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
                            <th>Status</th>
                            <th>Price</th>
                            <th>Note</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($resultOnetimepayment) > 0) {
                            while ($row = mysqli_fetch_assoc($resultOnetimepayment)) {
                                // Validate modified_date and modified_time
                                $modified_date = (!empty($row['modified_date']) && $row['modified_date'] !== '0000-00-00') ? $row['modified_date'] : 'N/A';
                                $modified_time = (!empty($row['modified_time']) && $row['modified_time'] !== '00:00:00') ? date("h:i A", strtotime($row['modified_time'])) : 'N/A';

                                // Validate date and time
                                $dateToDisplay = (!empty($row['date']) && $row['date'] !== '0000-00-00') ? $row['date'] : 'N/A';
                                $timeToDisplay = (!empty($row['time']) && $row['time'] !== '00:00:00') ? date("h:i A", strtotime($row['time'])) : 'N/A';

                                $priceToDisplay = isset($row['price']) ? number_format($row['price']) : 'N/A';

                                // Translate ENUM values for completion
                                $completionStatus = 'Unknown';
                                if (isset($row['completion'])) {
                                    switch ($row['completion']) {
                                        case '1':
                                            $completionStatus = 'Pending';
                                            break;
                                        case '2':
                                            $completionStatus = 'One-time Payment';
                                            break;
                                        case '3':
                                            $completionStatus = 'Package Payment';
                                            break;
                                    }
                                }

                                // Check if the record can be approved
                                $canApprove = ($row['completion'] != 2); // Can approve only if not already marked as 'complete' (status = 2)
                        
                                echo "<tr>
                        <td style='width: 200px'>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                        <td>{$row['contact']}</td>
                        <td style='width: 100px'>{$dateToDisplay}</td>
                        <td style='width: 100px'>{$timeToDisplay}</td>
                        <td style='width: 100px'>{$modified_date}</td>
                        <td style='width: 100px'>{$modified_time}</td>
                        <td style='font-size: 15px;'>{$row['service_name']}</td>
                        <td>{$completionStatus}</td>
                        <td>{$priceToDisplay}</td>
                        <td style='width: 10px'>
                            <button type='button' onclick='openViewModal(\"{$row['note']}\")'
                                style='background-color:#083690; color:white; border:none; padding:7px 9px; border-radius:10px; box-shadow: 1px 2px 5px 0px #414141; cursor:pointer;'>
                                View
                            </button>
                        </td>
                        <td>";
                                if ($completionStatus != '1') {
                                    echo "<form method='POST' action='billing.php' style='display:inline;'>
                        <input type='hidden' name='id' value='{$row['id']}'>
                        <input type='submit' name='Approve' value='Approve' 
                        style='background-color:green; color:white; border:none; padding:7px 9px; border-radius:10px; cursor:pointer; box-shadow: 1px 2px 5px 0px #414141;'>
                    </form>";
                                }
                                echo "</td>
                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='11'>No records found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Tab content for Packages -->
            <div id="Packages" class="tabcontent"
                style="display: <?php echo $activeTab == 'Packages' ? 'block' : 'none'; ?>;">
                <br>
                <h3 style="color: #094514;">Packages</h3>
                <!-- Pagination for Packages -->
                <div class="pagination-container">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?php echo $currentPage - 1; ?>&tab=Packages" class="pagination-btn">&lt;</a>
                    <?php endif; ?>
                    <?php if ($currentPage < $totalPagesPackages): ?>
                        <a href="?page=<?php echo $currentPage + 1; ?>&tab=Packages" class="pagination-btn">&gt;</a>
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
                            <th>Status</th>
                            <th>Price</th>
                            <th>Note</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($resultPackages) > 0) {
                            while ($row = mysqli_fetch_assoc($resultPackages)) {
                                // Validate modified_date and modified_time
                                $modified_date = (!empty($row['modified_date']) && $row['modified_date'] !== '0000-00-00') ? $row['modified_date'] : 'N/A';
                                $modified_time = (!empty($row['modified_time']) && $row['modified_time'] !== '00:00:00') ? date("h:i A", strtotime($row['modified_time'])) : 'N/A';

                                // Validate date and time
                                $dateToDisplay = (!empty($row['date']) && $row['date'] !== '0000-00-00') ? $row['date'] : 'N/A';
                                $timeToDisplay = (!empty($row['time']) && $row['time'] !== '00:00:00') ? date("h:i A", strtotime($row['time'])) : 'N/A';

                                $priceToDisplay = isset($row['price']) ? number_format($row['price']) : 'N/A';

                                // Translate ENUM values for completion
                                $completionStatus = 'Unknown';
                                if (isset($row['completion'])) {
                                    switch ($row['completion']) {
                                        case '1':
                                            $completionStatus = 'Pending';
                                            break;
                                        case '2':
                                            $completionStatus = 'One-time Payment';
                                            break;
                                        case '3':
                                            $completionStatus = 'Package Payment';
                                            break;
                                    }
                                }

                                echo "<tr>
                        <td style='width: 200px'>{$row['last_name']}, {$row['first_name']} {$row['middle_name']}</td>
                        <td>{$row['contact']}</td>
                        <td style='width: 100px'>{$dateToDisplay}</td>
                        <td style='width: 100px'>{$timeToDisplay}</td>
                        <td style='width: 100px'>{$modified_date}</td>
                        <td style='width: 100px'>{$modified_time}</td>
                        <td style='font-size: 15px;'>{$row['service_name']}</td>
                        <td>{$completionStatus}</td>
                        <td>{$priceToDisplay}</td>
                        <td style='width: 10px'>
                            <button type='button' onclick='openViewModal(\"{$row['note']}\")'
                                style='background-color:#083690; color:white; border:none; padding:7px 9px; border-radius:10px; box-shadow: 1px 2px 5px 0px #414141; cursor:pointer;'>
                                View
                            </button>
                        </td>
                        <td>
                            <!-- Approve Button -->
                            <button type='button' onclick='openApproveModal({$row['id']}, \"{$row['first_name']}\", \"{$row['middle_name']}\", \"{$row['last_name']}\", \"{$row['contact']}\", \"{$dateToDisplay}\", \"{$timeToDisplay}\", \"{$row['service_name']}\", \"{$row['price']}\")' 
                            style='background-color:green; color:white; border:none; padding:7px 9px; border-radius:10px;box-shadow: 1px 2px 5px 0px #414141; cursor:pointer;'>Approve</button>
                        </td>
                    </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='11'>No records found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <script>
                function switchTab(tabName) {
                    // Switch between tabs
                    window.location.href = '?tab=' + tabName + '&page=1';
                }
            </script>

            <div id="approveModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3 style="text-align: center; color: black; font-size: 30px;">Service Completion</h3>
                    <hr>
                    <div id="modalDetails">
                        <p><strong>Name:</strong> <span id="modalName"></span></p>
                        <br>
                        <p><strong>Contact Number:</strong> <span id="modalContact"></span></p>
                        <br>
                        <p><strong>Date & Time:</strong> <span id="modalDateTime"></span></p>
                        <br>
                        <p><strong>Current Service:</strong> <span id="modalService"></span></p>
                        <br>
                        <p><strong>Price:</strong> <span id="modalPrice"></span></p>
                    </div>
                    <hr>
                    <form id="newServiceForm" method="POST" action="">
                        <input type="hidden" name="id" value="">
                        <br>
                        <label style="font-size: 20px; font-weight: bold;" for="paid">Paid (₱):</label>
                        <br>
                        <input type="number" id="paid" name="paid"
                            style="width: 40%; font-size: 25px; font-weight: bold;" min="0" step="0.01" required
                            oninput="validateLength(this, 7)">
                        <br>
                        <label style="font-size: 20px; font-weight: bold;" for="outstanding_balance">Outstanding Balance
                            (₱):</label>
                        <div class="price" style="gap: 35%;">
                            <input type="number" id="outstanding_balance" name="outstanding_balance"
                                style="width: 40%; font-size: 25px; font-weight: bold;" min="0" step="0.01" required
                                oninput="validateLength(this, 7)">
                            <button type="submit" name="submit" id="proceed">Proceed to Dental Assistant</button>
                        </div>
                        <p id="error-message" style="color: red; display: none;">Input exceeds maximum allowed length.
                        </p>
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

                function openApproveModal(id, firstName, middleName, lastName, contact, date, time, service, price) {
                    // Set modal details dynamically
                    document.getElementById('modalName').innerText = `${lastName}, ${firstName} ${middleName}`;
                    document.getElementById('modalContact').innerText = contact;
                    document.getElementById('modalDateTime').innerText = `${date} at ${time}`;
                    document.getElementById('modalService').innerText = service;
                    document.getElementById('modalPrice').innerText = price;

                    // Set the hidden ID field in the form
                    document.querySelector("#newServiceForm input[name='id']").value = id;

                    // Display the modal
                    document.getElementById('approveModal').style.display = 'block';
                }

                // Event listener to close the modal when the close button is clicked
                document.querySelector('.close').addEventListener('click', () => {
                    document.getElementById('approveModal').style.display = 'none';
                });

                // Event listener to close the modal when clicking outside of it
                window.addEventListener('click', (event) => {
                    if (event.target == document.getElementById('approveModal')) {
                        document.getElementById('approveModal').style.display = 'none';
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
            </script>

            <!-- Modal for Viewing Notes -->
            <div id="viewModal" class="modal">
                <div class="modal-content">
                    <span class="close-view"
                        style="float: right; font-weight: bold; font-size:25px; cursor: pointer;">&times;</span>
                    <h2 style="color: #0a0a0a;">NOTES FROM THE DOCTOR:</h2>
                    <br>
                    <div class="body">
                        <p id="viewModalText">note text</p>
                    </div>
                </div>
            </div>

            <script>
                // Function to open the view modal
                function openViewModal(note) {
                    document.getElementById('viewModalText').textContent = note;
                    document.getElementById('viewModal').style.display = "block";
                }

                // Close modals when the close button is clicked
                document.querySelector(".close-view").addEventListener("click", function () {
                    document.getElementById('viewModal').style.display = "none";
                });

                // Close modals if clicked outside of the modal content
                window.addEventListener("click", function (event) {
                    const viewModal = document.getElementById('viewModal');
                    if (event.target === viewModal) {
                        viewModal.style.display = "none";
                    }
                });
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
                    const activeTab = params.get('tab') || 'Onetimepayment';
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
