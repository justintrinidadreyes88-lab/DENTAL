<?php
session_start();


// Check if the user is logged in and has the required role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['1'])) {
    header("Location: ../signin.php");
    exit();
}

include("../dbcon.php"); // Your database connection

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

function fetchService($con, $service_name)
{
    $sql = "SELECT * FROM tbl_services WHERE service_name = ?";
    $stmt = $con->prepare($sql);

    if (!$stmt) {
        echo "Error preparing statement: " . $con->error;
        return null; // Return null on failure
    }

    // Bind parameter
    $stmt->bind_param("s", $service_name);

    // Execute the statement
    if ($stmt->execute()) {
        $result = $stmt->get_result();

        // Check if there are results
        if ($result->num_rows > 0) {
            return $result->fetch_assoc(); // Return the fetched data
        } else {
            return null; // No results found
        }
    } else {
        echo "Error executing statement: " . $stmt->error;
        return null; // Return null on execution failure
    }
}

$veneersData = fetchService($con, 'All Porcelain Veneers & Zirconia');
$crownBridgeData = fetchService($con, 'Crown & Bridge');
$cleaningData = fetchService($con, 'Dental Cleaning');
$implantsData = fetchService($con, 'Dental Implants');
$whiteningData = fetchService($con, 'Dental Whitening');
$dentureData = fetchService($con, 'Dentures');
$extractionData = fetchService($con, 'Extraction');
$examData = fetchService($con, 'Full Exam & X-Ray');
$bracesData = fetchService($con, 'Orthodontic Braces');
$restorationData = fetchService($con, 'Restoration');
$rootData = fetchService($con, 'Root Canal Treatment');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = $_POST['service_description'] ?? null;
    $price = $_POST['price'] ?? null;
    $service_name = $_POST['service_name'] ?? null;

    if (empty($service_name)) {
        echo "Service name is required.";
        exit();
    }

    // Handle image upload
    $target_file = null; // Initialize target_file as null
    if (!empty($_FILES["service_image"]["tmp_name"])) {
        $target_dir = "C:/xampp/htdocs/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/";
        $target_file = $target_dir . basename($_FILES["service_image"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if the file is an actual image
        $check = getimagesize($_FILES["service_image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size (limit 500KB)
        if ($_FILES["service_image"]["size"] > 500000) {
            echo "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // Allow only certain file formats
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
            $target_file = null;
        } else {
            // Create the target directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Move the uploaded file
            if (!move_uploaded_file($_FILES["service_image"]["tmp_name"], $target_file)) {
                echo "Sorry, there was an error uploading your file.";
                $target_file = null;
            }
        }
    }

    // Build dynamic SQL query based on non-empty inputs
    $updates = [];
    $params = [];
    $types = "";

    if ($target_file) {
        $updates[] = "service_image = ?";
        $params[] = $target_file;
        $types .= "s";
    }
    if (!empty($description)) {
        $updates[] = "service_description = ?";
        $params[] = $description;
        $types .= "s";
    }
    if (!empty($price)) {
        $updates[] = "price = ?";
        $params[] = $price;
        $types .= "d";
    }

    // Only update if there are fields to update
    if (!empty($updates)) {
        $params[] = $service_name;
        $types .= "s";

        $sql = "UPDATE tbl_services SET " . implode(", ", $updates) . " WHERE service_name = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param($types, ...$params);

        // Execute the statement
        if ($stmt->execute()) {
            // Redirect to prevent form resubmission
            header("Location: services.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "No fields to update.";
    }
}

// Close the connection
$con->close();
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
            <?php
            // Include the appointments summary
            include("appointments_status.php");
            ?>

            <h1>Services</h1>
            <div id="crvs-container">
                <!-- Img-box and Modal for Orthodontic Braces -->
                <div class="img-box" id="openModalBtnOrthodonticBraces">
                    <div class="img-wrapper">
                        <p>
                            <?php echo htmlspecialchars($bracesData ? $bracesData['service_name'] : 'Orthodontic Braces'); ?>
                        </p>
                        <img src="/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/<?php echo htmlspecialchars($bracesData ? basename($bracesData['service_image']) : 'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($bracesData ? $bracesData['service_name'] : 'Orthodontic Braces'); ?>"
                            onerror="this.src='/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/default.jpg';">
                    </div>
                </div>

                <!-- Modal Template for Orthodontic Braces -->
                <div id="serviceModalBraces" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Edit Orthodontic Braces</h2>
                        <form id="serviceFormBraces" method="POST" action="services.php" enctype="multipart/form-data">
                            <input type="hidden" name="service_name" id="serviceNameBraces">

                            <label for="imageInputBraces">Image Upload:</label>
                            <input type="file" name="service_image" id="imageInputBraces" accept="image/*"
                                onchange="previewImage(event, 'imagePreviewBraces')"><br>
                            <img id="imagePreviewBraces" src="" alt="Image Preview"
                                style="display: none; width: 200px; margin-top: 10px;" />

                            <label for="serviceDescriptionBraces">Description:</label>
                            <br>
                            <br>
                            <textarea name="service_description" id="serviceDescriptionBraces" required></textarea><br>

                            <label for="priceBraces">Start at:</label>
                            <input type="number" name="price" id="priceBraces" placeholder="Enter Price" required><br>

                            <button type="submit" id="s9">Save Changes</button>
                        </form>
                    </div>
                </div>
                <div id="notification" class="notification" style="display: none;">
                    <p>Successfully Submitted!</p>
                </div>


                <script>
                    // Modal Elements for Orthodontic Braces
                    const modalBraces = document.getElementById("serviceModalBraces");
                    const spanBraces = modalBraces.querySelector(".close");

                    // Function to handle modal population and opening
                    function openBracesModal(serviceData) {
                        document.getElementById("serviceNameBraces").value = serviceData.service_name || 'Orthodontic Braces';
                        document.getElementById("serviceDescriptionBraces").value = serviceData.service_description || '';
                        document.getElementById("priceBraces").value = serviceData.price || '';
                        modalBraces.style.display = "block";
                    }

                    // Function to reset modal
                    function resetBracesModal() {
                        document.getElementById("serviceFormBraces").reset();
                        document.getElementById("imagePreviewBraces").style.display = 'none';
                    }

                    // Close modal on 'X' button click
                    spanBraces.onclick = () => {
                        resetBracesModal();
                        modalBraces.style.display = "none";
                    };

                    // Close modal when clicking outside the modal
                    window.onclick = (event) => {
                        if (event.target === modalBraces) {
                            resetBracesModal();
                            modalBraces.style.display = "none";
                        }
                    };

                    // Open modal for Orthodontic Braces
                    document.getElementById("openModalBtnOrthodonticBraces").onclick = () => {
                        const bracesData = <?php echo json_encode($bracesData); ?>;
                        openBracesModal(bracesData);
                    };
                    document.getElementById('s9').addEventListener('click', function () {
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
                </script>

                <!-- Img-box and Modal for Dental Cleaning -->
                <div class="img-box" id="openModalBtnCleaning">
                    <div class="img-wrapper">
                        <p>
                            <?php echo htmlspecialchars($cleaningData ? $cleaningData['service_name'] : 'Dental Cleaning'); ?>
                        </p>
                        <img src="/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/<?php echo htmlspecialchars($cleaningData ? basename($cleaningData['service_image']) : 'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($cleaningData ? $cleaningData['service_name'] : 'Dental Cleaning'); ?>"
                            onerror="this.src='/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/default.jpg';">
                    </div>
                </div>

                <!-- Modal Template for Dental Cleaning -->
                <div id="serviceModalCleaning" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Edit Dental Cleaning</h2>
                        <form id="serviceFormCleaning" method="POST" action="services.php"
                            enctype="multipart/form-data">
                            <input type="hidden" name="service_name" id="serviceNameCleaning" value="Dental Cleaning">

                            <label for="imageInputCleaning">Image Upload:</label>
                            <input type="file" name="service_image" id="imageInputCleaning" accept="image/*"
                                onchange="previewImage(event, 'imagePreviewCleaning')"><br>
                            <img id="imagePreviewCleaning" src="" alt="Image Preview"
                                style="display: none; width: 200px; margin-top: 10px;" />

                            <label for="serviceDescriptionCleaning">Description:</label>
                            <br>
                            <br>
                            <textarea name="service_description" id="serviceDescriptionCleaning"
                                required></textarea><br>

                            <label for="priceCleaning">Start at:</label>
                            <input type="number" name="price" id="priceCleaning" placeholder="Enter Price" required><br>

                            <button type="submit" id="s10">Save Changes</button>
                        </form>
                    </div>
                </div>
                <div id="notification" class="notification" style="display: none;">
                    <p>Successfully Submitted!</p>
                </div>


                <script>
                    // Modal Elements for Dental Cleaning
                    const modalCleaning = document.getElementById("serviceModalCleaning");
                    const spanCleaning = modalCleaning.querySelector(".close");

                    // Function to handle modal population and opening
                    function openServiceModalCleaning(serviceData) {
                        document.getElementById("serviceNameCleaning").value = serviceData.service_name || 'Dental Cleaning';
                        document.getElementById("serviceDescriptionCleaning").value = serviceData.service_description || '';
                        document.getElementById("priceCleaning").value = serviceData.price || '';
                        modalCleaning.style.display = "block";
                    }

                    // Function to reset modal
                    function resetModalCleaning() {
                        document.getElementById("serviceFormCleaning").reset();
                        document.getElementById("imagePreviewCleaning").style.display = 'none';
                    }

                    // Close modal on 'X' button click
                    spanCleaning.onclick = () => {
                        resetModalCleaning();
                        modalCleaning.style.display = "none";
                    };

                    // Close modal when clicking outside the modal
                    window.onclick = (event) => {
                        if (event.target === modalCleaning) {
                            resetModalCleaning();
                            modalCleaning.style.display = "none";
                        }
                    };

                    // Example: Open modal for Dental Cleaning
                    document.getElementById("openModalBtnCleaning").onclick = () => {
                        const cleaningData = <?php echo json_encode($cleaningData); ?>;
                        openServiceModalCleaning(cleaningData);
                    };
                    document.getElementById('s10').addEventListener('click', function () {
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
                </script>

                <!-- Img-box and Modal for Dental Whitening -->
                <div class="img-box" id="openModalBtnWhitening">
                    <div class="img-wrapper">
                        <p>
                            <?php echo htmlspecialchars($whiteningData ? $whiteningData['service_name'] : 'Dental Whitening'); ?>
                        </p>
                        <img src="/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/<?php echo htmlspecialchars($whiteningData ? basename($whiteningData['service_image']) : 'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($whiteningData ? $whiteningData['service_name'] : 'Dental Whitening'); ?>"
                            onerror="this.src='/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/default.jpg';">
                    </div>
                </div>

                <!-- Modal Template for Dental Whitening -->
                <div id="serviceModalWhitening" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Edit Dental Whitening</h2>
                        <form id="serviceFormWhitening" method="POST" action="services.php"
                            enctype="multipart/form-data">
                            <input type="hidden" name="service_name" id="serviceNameWhitening">

                            <label for="imageInputWhitening">Image Upload:</label>
                            <input type="file" name="service_image" id="imageInputWhitening" accept="image/*"
                                onchange="previewImage(event, 'imagePreviewWhitening')"><br>
                            <img id="imagePreviewWhitening" src="" alt="Image Preview"
                                style="display: none; width: 200px; margin-top: 10px;" />

                            <label for="serviceDescriptionWhitening">Description:</label>
                            <br>
                            <br>
                            <textarea name="service_description" id="serviceDescriptionWhitening"
                                required></textarea><br>

                            <label for="priceWhitening">Per Cycle(3):</label>
                            <input type="number" name="price" id="priceWhitening" placeholder="Enter Price"
                                required><br>
                            <button type="submit" id="s11">Save Changes</button>
                        </form>
                    </div>
                </div>
                <div id="notification" class="notification" style="display: none;">
                    <p>Successfully Submitted!</p>
                </div>


                <script>
                    // Modal Elements for Dental Whitening
                    const modalWhitening = document.getElementById("serviceModalWhitening");
                    const spanWhitening = modalWhitening.querySelector(".close");

                    // Function to handle modal population and opening
                    function openServiceModal(serviceData) {
                        document.getElementById("serviceNameWhitening").value = serviceData.service_name || 'Dental Whitening';
                        document.getElementById("serviceDescriptionWhitening").value = serviceData.service_description || '';
                        document.getElementById("priceWhitening").value = serviceData.price || '';
                        modalWhitening.style.display = "block";
                    }

                    // Function to reset modal
                    function resetModalWhitening() {
                        document.getElementById("serviceFormWhitening").reset();
                        document.getElementById("imagePreviewWhitening").style.display = 'none';
                    }

                    // Close modal on 'X' button click
                    spanWhitening.onclick = () => {
                        resetModalWhitening();
                        modalWhitening.style.display = "none";
                    };

                    // Close modal when clicking outside the modal
                    window.onclick = (event) => {
                        if (event.target === modalWhitening) {
                            resetModalWhitening();
                            modalWhitening.style.display = "none";
                        }
                    };

                    // Example: Open modal for Dental Whitening
                    document.getElementById("openModalBtnWhitening").onclick = () => {
                        const whiteningData = <?php echo json_encode($whiteningData); ?>;
                        openServiceModal(whiteningData);
                    }; document.getElementById('s11').addEventListener('click', function () {
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
                </script>

                <!-- Img-box and Modal for Dental Implants -->
                <div class="img-box" id="openModalBtnImplants">
                    <div class="img-wrapper">
                        <p>
                            <?php echo htmlspecialchars($implantsData ? $implantsData['service_name'] : 'Dental Implants'); ?>
                        </p>
                        <img src="/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/<?php echo htmlspecialchars($implantsData ? basename($implantsData['service_image']) : 'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($implantsData ? $implantsData['service_name'] : 'Dental Implants'); ?>"
                            onerror="this.src='/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/default.jpg';">
                    </div>
                </div>

                <!-- Modal Template for Dental Implants -->
                <div id="serviceModalImplants" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Edit Dental Implants</h2>
                        <form id="serviceFormImplants" method="POST" action="services.php"
                            enctype="multipart/form-data">
                            <input type="hidden" name="service_name" id="serviceNameImplants" value="Dental Implants">

                            <label for="imageInputImplants">Image Upload:</label>
                            <input type="file" name="service_image" id="imageInputImplants" accept="image/*"
                                onchange="previewImage(event, 'imagePreviewImplants')"><br>
                            <img id="imagePreviewImplants" src="" alt="Image Preview"
                                style="display: none; width: 200px; margin-top: 10px;" />

                            <label for="serviceDescriptionImplants">Description:</label>
                            <br>
                            <br>
                            <textarea name="service_description" id="serviceDescriptionImplants"
                                required></textarea><br>

                            <label for="priceImplants">Start at:</label>
                            <input type="number" name="price" id="priceImplants" placeholder="Enter Price" required><br>

                            <button type="submit" id="s1">Save Changes</button>
                        </form>
                    </div>
                </div>
                <div id="notification" class="notification" style="display: none;">
                    <p>Successfully Submitted!</p>
                </div>
                <script>
                    // Modal Elements for Dental Implants
                    const modalImplants = document.getElementById("serviceModalImplants");
                    const spanImplants = modalImplants.querySelector(".close");

                    // Function to handle modal population and opening
                    function openServiceModalImplants(serviceData) {
                        document.getElementById("serviceNameImplants").value = serviceData.service_name || 'Dental Implants';
                        document.getElementById("serviceDescriptionImplants").value = serviceData.service_description || '';
                        document.getElementById("priceImplants").value = serviceData.price || '';
                        modalImplants.style.display = "block";
                    }

                    // Function to reset modal
                    function resetModalImplants() {
                        document.getElementById("serviceFormImplants").reset();
                        document.getElementById("imagePreviewImplants").style.display = 'none';
                    }

                    // Close modal on 'X' button click
                    spanImplants.onclick = () => {
                        resetModalImplants();
                        modalImplants.style.display = "none";
                    };

                    // Close modal when clicking outside the modal
                    window.onclick = (event) => {
                        if (event.target === modalImplants) {
                            resetModalImplants();
                            modalImplants.style.display = "none";
                        }
                    };

                    // Example: Open modal for Dental Implants
                    document.getElementById("openModalBtnImplants").onclick = () => {
                        const implantsData = <?php echo json_encode($implantsData); ?>;
                        openServiceModalImplants(implantsData);
                    };


                    document.getElementById('s1').addEventListener('click', function () {
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
                </script>

                <!-- Img-box and Modal for Restoration -->
                <div class="img-box" id="openModalBtnRestoration">
                    <div class="img-wrapper">
                        <p>
                            <?php echo htmlspecialchars($restorationData ? $restorationData['service_name'] : 'Restoration'); ?>
                        </p>
                        <img src="/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/<?php echo htmlspecialchars($restorationData ? basename($restorationData['service_image']) : 'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($restorationData ? $restorationData['service_name'] : 'Restoration'); ?>"
                            onerror="this.src='/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/default.jpg';">
                    </div>
                </div>

                <!-- Modal Template for Restoration -->
                <div id="serviceModalRestoration" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Edit Restoration</h2>
                        <form id="serviceFormRestoration" method="POST" action="services.php"
                            enctype="multipart/form-data">
                            <input type="hidden" name="service_name" id="serviceNameRestoration" value="Restoration">

                            <label for="imageInputRestoration">Image Upload:</label>
                            <input type="file" name="service_image" id="imageInputRestoration" accept="image/*"
                                onchange="previewImage(event, 'imagePreviewRestoration')"><br>
                            <img id="imagePreviewRestoration" src="" alt="Image Preview"
                                style="display: none; width: 200px; margin-top: 10px;" />

                            <label for="serviceDescriptionRestoration">Description:</label>
                            <br>
                            <br>
                            <textarea name="service_description" id="serviceDescriptionRestoration"
                                required></textarea><br>

                            <label for="priceRestoration">Start at:</label>
                            <input type="number" name="price" id="priceRestoration" placeholder="Enter Price"
                                required><br>
                            <button type="submit" id="s2">Save Changes</button>
                        </form>
                    </div>
                </div>
                <div id="notification" class="notification" style="display: none;">
                    <p>Successfully Submitted!</p>
                </div>

                <script>
                    // Modal Elements for Restoration
                    const modalRestoration = document.getElementById("serviceModalRestoration");
                    const spanRestoration = modalRestoration.querySelector(".close");

                    // Function to handle modal population and opening
                    function openServiceModalRestoration(serviceData) {
                        document.getElementById("serviceNameRestoration").value = serviceData.service_name || 'Restoration';
                        document.getElementById("serviceDescriptionRestoration").value = serviceData.service_description || '';
                        document.getElementById("priceRestoration").value = serviceData.price || '';
                        modalRestoration.style.display = "block";
                    }

                    // Function to reset modal
                    function resetModalRestoration() {
                        document.getElementById("serviceFormRestoration").reset();
                        document.getElementById("imagePreviewRestoration").style.display = 'none';
                    }

                    // Close modal on 'X' button click
                    spanRestoration.onclick = () => {
                        resetModalRestoration();
                        modalRestoration.style.display = "none";
                    };

                    // Close modal when clicking outside the modal
                    window.onclick = (event) => {
                        if (event.target === modalRestoration) {
                            resetModalRestoration();
                            modalRestoration.style.display = "none";
                        }
                    };

                    // Example: Open modal for Restoration
                    document.getElementById("openModalBtnRestoration").onclick = () => {
                        const restorationData = <?php echo json_encode($restorationData); ?>;
                        openServiceModalRestoration(restorationData);
                    };

                    document.getElementById('s2').addEventListener('click', function () {
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
                </script>

                <!-- Img-box and Modal for Extraction -->
                <div class="img-box" id="openModalBtnExtraction">
                    <div class="img-wrapper">
                        <p>
                            <?php echo htmlspecialchars($extractionData ? $extractionData['service_name'] : 'Extraction'); ?>
                        </p>
                        <img src="/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/<?php echo htmlspecialchars($extractionData ? basename($extractionData['service_image']) : 'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($extractionData ? $extractionData['service_name'] : 'Extraction'); ?>"
                            onerror="this.src='/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/default.jpg';">
                    </div>
                </div>

                <!-- Modal Template for Extraction -->
                <div id="serviceModalExtraction" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Edit Extraction</h2>
                        <form id="serviceFormExtraction" method="POST" action="services.php"
                            enctype="multipart/form-data">
                            <input type="hidden" name="service_name" id="serviceNameExtraction" value="Extraction">

                            <label for="imageInputExtraction">Image Upload:</label>
                            <input type="file" name="service_image" id="imageInputExtraction" accept="image/*"
                                onchange="previewImage(event, 'imagePreviewExtraction')"><br>
                            <img id="imagePreviewExtraction" src="" alt="Image Preview"
                                style="display: none; width: 200px; margin-top: 10px;" />

                            <label for="serviceDescriptionExtraction">Description:</label>
                            <br>
                            <br>
                            <textarea name="service_description" id="serviceDescriptionExtraction"
                                required></textarea><br>

                            <label for="priceExtraction">Start at:</label>
                            <input type="number" name="price" id="priceExtraction" placeholder="Enter Price"
                                required><br>

                            <button type="submit" id="s3">Save Changes</button>
                        </form>
                    </div>
                </div>
                <div id="notification" class="notification" style="display: none;">
                    <p>Successfully Submitted!</p>
                </div>


                <script>
                    // Modal Elements for Extraction
                    const modalExtraction = document.getElementById("serviceModalExtraction");
                    const spanExtraction = modalExtraction.querySelector(".close");

                    // Function to handle modal population and opening
                    function openServiceModalExtraction(serviceData) {
                        document.getElementById("serviceNameExtraction").value = serviceData.service_name || 'Extraction';
                        document.getElementById("serviceDescriptionExtraction").value = serviceData.service_description || '';
                        document.getElementById("priceExtraction").value = serviceData.price || '';
                        modalExtraction.style.display = "block";
                    }

                    // Function to reset modal
                    function resetModalExtraction() {
                        document.getElementById("serviceFormExtraction").reset();
                        document.getElementById("imagePreviewExtraction").style.display = 'none';
                    }

                    // Close modal on 'X' button click
                    spanExtraction.onclick = () => {
                        resetModalExtraction();
                        modalExtraction.style.display = "none";
                    };

                    // Close modal when clicking outside the modal
                    window.onclick = (event) => {
                        if (event.target === modalExtraction) {
                            resetModalExtraction();
                            modalExtraction.style.display = "none";
                        }
                    };

                    // Example: Open modal for Extraction
                    document.getElementById("openModalBtnExtraction").onclick = () => {
                        const extractionData = <?php echo json_encode($extractionData); ?>;
                        openServiceModalExtraction(extractionData);
                    };
                    document.getElementById('s3').addEventListener('click', function () {
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
                </script>

                <!-- Img-box and Modal for Veneers -->
                <div class="img-box" id="openModalBtnVeneers">
                    <div class="img-wrapper">
                        <p>
                            <?php echo htmlspecialchars($veneersData ? $veneersData['service_name'] : 'All Porcelain Veneers & Zirconia'); ?>
                        </p>
                        <img src="/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/<?php echo htmlspecialchars($veneersData ? basename($veneersData['service_image']) : 'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($veneersData ? $veneersData['service_name'] : 'All Porcelain Veneers & Zirconia'); ?>"
                            onerror="this.src='/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/default.jpg';">
                    </div>
                </div>

                <!-- Modal Template for Veneers -->
                <div id="serviceModalVeneers" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Edit All Porcelain Veneers & Zirconia</h2>
                        <form id="serviceFormVeneers" method="POST" action="services.php" enctype="multipart/form-data">
                            <input type="hidden" name="service_name" id="serviceNameVeneers"
                                value="All Porcelain Veneers & Zirconia">

                            <label for="imageInputVeneers">Image Upload:</label>
                            <input type="file" name="service_image" id="imageInputVeneers" accept="image/*"
                                onchange="previewImage(event, 'imagePreviewVeneers')"><br>
                            <img id="imagePreviewVeneers" src="" alt="Image Preview"
                                style="display: none; width: 200px; margin-top: 10px;" />

                            <label for="serviceDescriptionVeneers">Description:</label><br>
                            <br>
                            <br>
                            <textarea name="service_description" id="serviceDescriptionVeneers" required></textarea><br>

                            <label for="priceVeneers">Per Unit:</label>
                            <input type="number" name="price" id="priceVeneers" placeholder="Enter Price" required><br>

                            <button type="submit" id="s4">Save Changes</button>
                        </form>
                    </div>
                </div>
                <div id="notification" class="notification" style="display: none;">
                    <p>Successfully Submitted!</p>
                </div>


                <script>
                    // Modal Elements for Veneers
                    const modalVeneers = document.getElementById("serviceModalVeneers");
                    const spanVeneers = modalVeneers.querySelector(".close");

                    // Function to handle modal population and opening
                    function openServiceModalVeneers(serviceData) {
                        document.getElementById("serviceNameVeneers").value = serviceData.service_name || 'All Porcelain Veneers & Zirconia';
                        document.getElementById("serviceDescriptionVeneers").value = serviceData.service_description || '';
                        document.getElementById("priceVeneers").value = serviceData.price || '';
                        modalVeneers.style.display = "block";
                    }

                    // Function to reset modal
                    function resetModalVeneers() {
                        document.getElementById("serviceFormVeneers").reset();
                        document.getElementById("imagePreviewVeneers").style.display = 'none';
                    }

                    // Close modal on 'X' button click
                    spanVeneers.onclick = () => {
                        resetModalVeneers();
                        modalVeneers.style.display = "none";
                    };

                    // Close modal when clicking outside the modal
                    window.onclick = (event) => {
                        if (event.target === modalVeneers) {
                            resetModalVeneers();
                            modalVeneers.style.display = "none";
                        }
                    };

                    // Example: Open modal for Veneers
                    document.getElementById("openModalBtnVeneers").onclick = () => {
                        const veneersData = <?php echo json_encode($veneersData); ?>;
                        openServiceModalVeneers(veneersData);
                    };
                    document.getElementById('s4').addEventListener('click', function () {
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
                </script>

                <!-- Img-box and Modal for Full Exam & X-Ray -->
                <div class="img-box" id="openModalBtnExam">
                    <div class="img-wrapper">
                        <p>
                            <?php echo htmlspecialchars($examData ? $examData['service_name'] : 'Full Exam & X-Ray'); ?>
                        </p>
                        <img src="/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/<?php echo htmlspecialchars($examData ? basename($examData['service_image']) : 'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($examData ? $examData['service_name'] : 'Full Exam & X-Ray'); ?>"
                            onerror="this.src='/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/default.jpg';">
                    </div>
                </div>

                <!-- Modal Template for Full Exam & X-Ray -->
                <div id="serviceModalExam" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Edit Full Exam & X-Ray</h2>
                        <form id="serviceFormExam" method="POST" action="services.php" enctype="multipart/form-data">
                            <input type="hidden" name="service_name" id="serviceNameExam" value="Full Exam & X-Ray">

                            <label for="imageInputExam">Image Upload:</label>
                            <input type="file" name="service_image" id="imageInputExam" accept="image/*"
                                onchange="previewImage(event, 'imagePreviewExam')"><br>
                            <img id="imagePreviewExam" src="" alt="Image Preview"
                                style="display: none; width: 200px; margin-top: 10px;" />

                            <label for="serviceDescriptionExam">Description:</label>
                            <br>
                            <br>
                            <textarea name="service_description" id="serviceDescriptionExam" required></textarea><br>

                            <label for="priceExam">Price:</label>
                            <input type="number" name="price" id="priceExam" placeholder="Enter Price" required><br>

                            <button type="submit" id="s5">Save Changes</button>
                        </form>
                    </div>
                </div>
                <div id="notification" class="notification" style="display: none;">
                    <p>Successfully Submitted!</p>
                </div>


                <script>
                    // Modal Elements for Full Exam & X-Ray
                    const modalExam = document.getElementById("serviceModalExam");
                    const spanExam = modalExam.querySelector(".close");

                    // Function to handle modal population and opening
                    function openServiceModalExam(serviceData) {
                        document.getElementById("serviceNameExam").value = serviceData.service_name || 'Full Exam & X-Ray';
                        document.getElementById("serviceDescriptionExam").value = serviceData.service_description || '';
                        document.getElementById("priceExam").value = serviceData.price || '';
                        modalExam.style.display = "block";
                    }

                    // Function to reset modal
                    function resetModalExam() {
                        document.getElementById("serviceFormExam").reset();
                        document.getElementById("imagePreviewExam").style.display = 'none';
                    }

                    // Close modal on 'X' button click
                    spanExam.onclick = () => {
                        resetModalExam();
                        modalExam.style.display = "none";
                    };

                    // Close modal when clicking outside the modal
                    window.onclick = (event) => {
                        if (event.target === modalExam) {
                            resetModalExam();
                            modalExam.style.display = "none";
                        }
                    };

                    // Example: Open modal for Full Exam & X-Ray
                    document.getElementById("openModalBtnExam").onclick = () => {
                        const examData = <?php echo json_encode($examData); ?>;
                        openServiceModalExam(examData);
                    };
                    document.getElementById('s5').addEventListener('click', function () {
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
                </script>

                <!-- Img-box and Modal for Root Canal Treatment -->
                <div class="img-box" id="openModalBtnRootCanal">
                    <div class="img-wrapper">
                        <p><?php echo htmlspecialchars($rootData ? $rootData['service_name'] : 'Root Canal Treatment'); ?>
                        </p>
                        <img src="/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/<?php echo htmlspecialchars($rootData ? basename($rootData['service_image']) : 'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($rootData ? $rootData['service_name'] : 'Root Canal Treatment'); ?>"
                            onerror="this.src='/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/default.jpg';">
                    </div>
                </div>

                <!-- Modal Template for Root Canal Treatment -->
                <div id="serviceModalRootCanal" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Edit Root Canal Treatment</h2>
                        <form id="serviceFormRootCanal" method="POST" action="services.php"
                            enctype="multipart/form-data">
                            <input type="hidden" name="service_name" id="serviceNameRootCanal"
                                value="Root Canal Treatment">

                            <label for="imageInputRootCanal">Image Upload:</label>
                            <input type="file" name="service_image" id="imageInputRootCanal" accept="image/*"
                                onchange="previewImage(event, 'imagePreviewRootCanal')"><br>
                            <img id="imagePreviewRootCanal" src="" alt="Image Preview"
                                style="display: none; width: 200px; margin-top: 10px;">

                            <label for="serviceDescriptionRootCanal">Description:</label>
                            <br>
                            <br>
                            <textarea name="service_description" id="serviceDescriptionRootCanal"
                                required></textarea><br>

                            <label for="priceRootCanal">Per Canal:</label>
                            <input type="number" name="price" id="priceRootCanal" placeholder="Enter Price"
                                required><br>

                            <button type="submit" id="s6">Save Changes</button>
                        </form>
                    </div>
                </div>
                <div id="notification" class="notification" style="display: none;">
                    <p>Successfully Submitted!</p>
                </div>

                <script>
                    // Modal Elements for Root Canal Treatment
                    const modalRootCanal = document.getElementById("serviceModalRootCanal");
                    const spanRootCanal = modalRootCanal.querySelector(".close");

                    // Function to handle modal population and opening
                    function openServiceModalRootCanal(serviceData) {
                        document.getElementById("serviceNameRootCanal").value = serviceData.service_name || 'Root Canal Treatment';
                        document.getElementById("serviceDescriptionRootCanal").value = serviceData.service_description || '';
                        document.getElementById("priceRootCanal").value = serviceData.price || '';
                        modalRootCanal.style.display = "block";
                    }

                    // Function to reset modal
                    function resetModalRootCanal() {
                        document.getElementById("serviceFormRootCanal").reset();
                        document.getElementById("imagePreviewRootCanal").style.display = 'none';
                    }

                    // Close modal on 'X' button click
                    spanRootCanal.onclick = () => {
                        resetModalRootCanal();
                        modalRootCanal.style.display = "none";
                    };

                    // Close modal when clicking outside the modal
                    window.onclick = (event) => {
                        if (event.target === modalRootCanal) {
                            resetModalRootCanal();
                            modalRootCanal.style.display = "none";
                        }
                    };

                    // Example: Open modal for Root Canal Treatment
                    document.getElementById("openModalBtnRootCanal").onclick = () => {
                        const rootData = <?php echo json_encode($rootData); ?>;
                        openServiceModalRootCanal(rootData);
                    };
                    document.getElementById('s6').addEventListener('click', function () {
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
                </script>

                <!-- Img-box and Modal for Dentures -->
                <div class="img-box" id="openModalBtnDentures">
                    <div class="img-wrapper">
                        <p><?php echo htmlspecialchars($dentureData ? $dentureData['service_name'] : 'Dentures'); ?></p>
                        <img src="/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/<?php echo htmlspecialchars($dentureData ? basename($dentureData['service_image']) : 'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($dentureData ? $dentureData['service_name'] : 'Dentures'); ?>"
                            onerror="this.src='/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/default.jpg';">
                    </div>
                </div>

                <!-- Modal Template for Dentures -->
                <div id="serviceModalDentures" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Edit Dentures</h2>
                        <form id="serviceFormDentures" method="POST" action="services.php"
                            enctype="multipart/form-data">
                            <input type="hidden" name="service_name" id="serviceNameDentures" value="Dentures">

                            <label for="imageInputDentures">Image Upload:</label>
                            <input type="file" name="service_image" id="imageInputDentures" accept="image/*"
                                onchange="previewImage(event, 'imagePreviewDentures')"><br>
                            <img id="imagePreviewDentures" src="" alt="Image Preview"
                                style="display: none; width: 200px; margin-top: 10px;">

                            <label for="serviceDescriptionDentures">Description:</label>
                            <br>
                            <br>
                            <textarea name="service_description" id="serviceDescriptionDentures"
                                required></textarea><br>

                            <label for="priceDentures">Start at:</label>
                            <input type="number" name="price" id="priceDentures" placeholder="Enter Price" required><br>

                            <button type="submit" id="s7">Save Changes</button>
                        </form>
                    </div>
                </div>
                <div id="notification" class="notification" style="display: none;">
                    <p>Successfully Submitted!</p>
                </div>


                <script>
                    // Modal Elements for Dentures
                    const modalDentures = document.getElementById("serviceModalDentures");
                    const spanDentures = modalDentures.querySelector(".close");

                    // Function to handle modal population and opening
                    function openServiceModalDentures(serviceData) {
                        document.getElementById("serviceNameDentures").value = serviceData.service_name || 'Dentures';
                        document.getElementById("serviceDescriptionDentures").value = serviceData.service_description || '';
                        document.getElementById("priceDentures").value = serviceData.price || '';
                        modalDentures.style.display = "block";
                    }

                    // Function to reset modal
                    function resetModalDentures() {
                        document.getElementById("serviceFormDentures").reset();
                        document.getElementById("imagePreviewDentures").style.display = 'none';
                    }

                    // Close modal on 'X' button click
                    spanDentures.onclick = () => {
                        resetModalDentures();
                        modalDentures.style.display = "none";
                    };

                    // Close modal when clicking outside the modal
                    window.onclick = (event) => {
                        if (event.target === modalDentures) {
                            resetModalDentures();
                            modalDentures.style.display = "none";
                        }
                    };

                    // Example: Open modal for Dentures
                    document.getElementById("openModalBtnDentures").onclick = () => {
                        const dentureData = <?php echo json_encode($dentureData); ?>;
                        openServiceModalDentures(dentureData);
                    }; document.getElementById('s7').addEventListener('click', function () {
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
                </script>

                <!-- Img-box and Modal for Crown & Bridge -->
                <div class="img-box" id="openModalBtnCrownBridge">
                    <div class="img-wrapper">
                        <p><?php echo htmlspecialchars($crownBridgeData ? $crownBridgeData['service_name'] : 'Crown & Bridge'); ?>
                        </p>
                        <img src="/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/<?php echo htmlspecialchars($crownBridgeData ? basename($crownBridgeData['service_image']) : 'default.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($crownBridgeData ? $crownBridgeData['service_name'] : 'Crown & Bridge'); ?>"
                            onerror="this.src='/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/default.jpg';">
                    </div>
                </div>

                <!-- Modal Template for Crown & Bridge -->
                <div id="serviceModalCrownBridge" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Edit Crown & Bridge</h2>
                        <form id="serviceFormCrownBridge" method="POST" action="services.php"
                            enctype="multipart/form-data">
                            <input type="hidden" name="service_name" id="serviceNameCrownBridge" value="Crown & Bridge">

                            <label>Image Upload:</label>
                            <input type="file" name="service_image" id="imageInputCrownBridge" accept="image/*"
                                onchange="previewImage(event, 'imagePreviewCrownBridge')"><br>
                            <img id="imagePreviewCrownBridge" src="" alt="Image Preview"
                                style="display: none; width: 200px; margin-top: 10px;">

                            <label>Description:</label>
                            <br>
                            <br>
                            <textarea name="service_description" id="serviceDescriptionCrownBridge"
                                required></textarea><br>

                            <label>Start at:</label>
                            <input type="number" name="price" id="priceCrownBridge" placeholder="Enter Price"
                                required><br>

                            <button type="submit" id="s8">Save Changes</button>
                        </form>
                    </div>
                </div>
                <div id="notification" class="notification" style="display: none;">
                    <p>Successfully Submitted!</p>
                </div>


                <script>
                    // Modal Elements for Crown & Bridge
                    const modalCrownBridge = document.getElementById("serviceModalCrownBridge");
                    const spanCrownBridge = modalCrownBridge.querySelector(".close");

                    // Function to handle modal population and opening
                    function openServiceModalCrownBridge(serviceData) {
                        document.getElementById("serviceNameCrownBridge").value = serviceData.service_name || 'Crown & Bridge';
                        document.getElementById("serviceDescriptionCrownBridge").value = serviceData.service_description || '';
                        document.getElementById("priceCrownBridge").value = serviceData.price || '';
                        modalCrownBridge.style.display = "block";
                    }

                    // Function to reset modal form
                    function resetModalCrownBridge() {
                        document.getElementById("serviceFormCrownBridge").reset();
                        document.getElementById("imagePreviewCrownBridge").style.display = 'none';
                    }

                    // Close modal on 'X' button click
                    spanCrownBridge.onclick = () => {
                        resetModalCrownBridge();
                        modalCrownBridge.style.display = "none";
                    };

                    // Close modal when clicking outside the modal
                    window.onclick = (event) => {
                        if (event.target === modalCrownBridge) {
                            resetModalCrownBridge();
                            modalCrownBridge.style.display = "none";
                        }
                    };

                    // Example: Open modal for Crown & Bridge
                    document.getElementById("openModalBtnCrownBridge").onclick = () => {
                        const crownBridgeData = <?php echo json_encode($crownBridgeData); ?>;
                        openServiceModalCrownBridge(crownBridgeData);
                    };
                    document.getElementById('s8').addEventListener('click', function () {
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

                <script>
                    var modal = document.getElementById("serviceModalCrownBridge");
                    var btn = document.getElementById("openModalBtn"); // Ensure this button exists in your HTML
                    var span = document.getElementsByClassName("close")[0];
                    var imagePreview = document.getElementById("imagePreviewCrownBridge"); // Updated to use the correct preview ID
                    var serviceForm = document.getElementById("serviceFormCrownBridge");

                    btn.onclick = function () {
                        modal.style.display = "block";
                    }

                    span.onclick = function () {
                        resetModal(); // Reset the modal when closed
                        modal.style.display = "none";
                    }

                    window.onclick = function (event) {
                        if (event.target == modal) {
                            resetModal(); // Reset the modal when closed
                            modal.style.display = "none";
                        }
                    }

                    function previewImage(event) {
                        imagePreview.style.display = "block";
                        imagePreview.src = URL.createObjectURL(event.target.files[0]);
                    }

                    function resetModal() {
                        serviceForm.reset(); // Reset the form fields
                        imagePreview.style.display = "none"; // Hide the image preview
                        imagePreview.src = ""; // Clear the image preview source
                    }
                </script>
            </div>
        </div>

    </div>
    </div>
</body>

</html>
