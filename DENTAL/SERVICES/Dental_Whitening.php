<?php
// Database connection (adjust connection settings as needed)
include("../../dbcon.php");

// Check connection
if ($con->connect_error) {
  die("Connection failed: " . $con->connect_error);
}

// Fetch the service name from GET or POST (sanitize the input)
$service_name = isset($_GET['service_name']) ? htmlspecialchars($_GET['service_name']) : 'Dental Whitening';

// Prepare the SQL query to fetch the service by name
$sql = "SELECT * FROM tbl_services WHERE service_name = ?";
$stmt = $con->prepare($sql);
if (!$stmt) {
  echo "Error preparing statement: " . $con->error;
  exit;
}

$stmt->bind_param("s", $service_name);
$stmt->execute();
$result = $stmt->get_result();

// Check if a row was found
if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $service_description = $row['service_description'];
  $price = number_format($row['price'], 2);
  $service_image = !empty($row['service_image']) ? basename($row['service_image']) : 'default.jpg'; // Use default if image not found
} else {
  echo "Service not found.";
  exit;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="master.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet">
  <title>Services</title>
  <style>

  </style>
</head>

<body>
  <nav>
    <a href="../Home_page.php#Services">
      <div class="logo">
        <h1>EHM Dental Clinic</h1>
      </div>
    </a>
  </nav>
  <div class="img-container">
    <!-- Display the dynamically fetched image -->
    <img src="/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/<?php echo htmlspecialchars(basename($service_image)); ?>"
      alt="<?php echo htmlspecialchars($service_name); ?>"
      onerror="this.src='/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/default.jpg';">
  </div>

  <div class="container">
    <h1><?php echo htmlspecialchars($service_name); ?></h1>
    <p><?php echo htmlspecialchars($service_description); ?></p>

    <div class="price-item">
      <h2>
        - Per Cycle(3): ₱ <?php echo $price; ?>
      </h2>
    </div>
  </div>
</body>

</html>