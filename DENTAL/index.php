<?php
session_start();

include("dbcon.php");

// Check database connection
if (!$con) {
  die("Connection failed: " . mysqli_connect_error());
}

// Prepare the SQL query to fetch the service by name
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

if (isset($_POST['update'])) {
  $id = isset($_POST['id']) ? $_POST['id'] : '';
  $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
  $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
  $middle_name = mysqli_real_escape_string($con, $_POST['middle_name']);
  $suffix = isset($_POST['suffix']) ? mysqli_real_escape_string($con, $_POST['suffix']) : '';
  $contact = mysqli_real_escape_string($con, $_POST['contact']);
  $date = mysqli_real_escape_string($con, $_POST['date']); // Use the selected date from the form
  $time = mysqli_real_escape_string($con, $_POST['time']);
  $service_type = mysqli_real_escape_string($con, $_POST['service_type']);

  // Convert the selected time to 24-hour format
  $time_24hr = DateTime::createFromFormat('h:i A', $time)->format('H:i:s');

  // Check for time conflicts, prioritizing modified_date and modified_time if present
  $check_time_query = "
      SELECT id 
      FROM tbl_appointments 
      WHERE (
          (modified_date IS NOT NULL AND modified_date = '$date' AND TIME(modified_time) = TIME('$time_24hr')) OR 
          (modified_date IS NULL AND date = '$date' AND TIME(time) = TIME('$time_24hr'))
      )
  ";

  $time_result = mysqli_query($con, $check_time_query);
  $time_row = mysqli_fetch_assoc($time_result);

  if ($time_row) {
    // Conflict found
    echo "<script>alert('The selected time conflicts with another appointment on the same date ($date). Please choose a different time.');</script>";
  } else {
    // No conflict - proceed with inserting appointment
    $insert_patient_query = "
        INSERT INTO tbl_patient (first_name, last_name, middle_name, suffix) 
        VALUES ('$first_name', '$last_name', '$middle_name', '$suffix')
    ";

    if (mysqli_query($con, $insert_patient_query)) {
      $patient_id = mysqli_insert_id($con);
      $full_name = $last_name . ', ' . $first_name . ($middle_name ? ' ' . $middle_name : '') . ($suffix ? ' ' . $suffix : '');
      $insert_appointment_query = "
          INSERT INTO tbl_appointments (id, name, contact, date, time, service_type) 
          VALUES ('$patient_id', '$full_name', '$contact', '$date', '$time_24hr', '$service_type')
      ";

      if (mysqli_query($con, $insert_appointment_query)) {
        echo "
        <div id='success-toast' style='
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #FF9F00;
            padding: 20px 50px;
            font-size: 60px;
            font-weight: bold;
            border-radius: 10px;
            text-align: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden; 
            transition: opacity 1.5s ease-in-out, visibility 1.5s ease-in-out;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        '>
            <!-- Replace PNG with GIF -->
            <img src=\"img/teeth.gif\" alt=\"Success Animation\" style=\"
                width: 300px; 
                height: 300px; 
            \">
            <span class='success-text'>Booked Successfully!</span>
        </div>
    
        <style>
    /* Font style for success text */
    .success-text {
        font-family: 'Montserrat', sans-serif; 
    }

    /* Mobile styles */
    @media (max-width: 768px) {
        #success-toast {
            padding: 15px 30px;
            font-size: 30px;
        }

        #success-toast img {
            width: 150px;
            height: 150px;
        }
    }

    @media (max-width: 480px) {
        #success-toast {
            padding: 10px 20px;
            font-size: 20px;
        }

        #success-toast img {
            width: 100px;
            height: 100px;
        }
    }
</style>
    
        <script>
            const toast = document.getElementById('success-toast');
            // Show the toast with fade-in effect
            setTimeout(() => {
                toast.style.opacity = '1'; // Gradually become visible
                toast.style.visibility = 'visible'; // Ensure it's interactable
            }, 100); // Slight delay to ensure transition works
    
            // Hide the toast with fade-out effect
            setTimeout(() => {
                toast.style.opacity = '0'; // Gradually become invisible
                toast.style.visibility = 'hidden'; // Ensure it's not interactable
            }, 2000); // Visible for 5 seconds before fading out
    
            // Redirect after fade-out completes
            setTimeout(() => {
                window.location.href = 'Home_page.php';
            }, 4000); // Adjust timing for fade-out duration
        </script>
    ";
    
    // Link to Google Fonts for Montserrat font
    echo "<link href='https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap' rel='stylesheet'>";    
        exit();
      } else {
        echo "Error updating appointment record: " . mysqli_error($con);
      }
    } else {
      echo "Error updating patient record: " . mysqli_error($con);
    }
  }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>DentalClinic</title>
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Meddon&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

<nav>
  <div class="nav-left">
    <div class="navbar-icon" id="navbarIcon">☰</div>
    <a href="#home" class="logo"><h1>DentalClinic</h1></a>
  </div>
  <ul id="navLinks">
    <li><a href="#home">Home</a></li>
    <li><a href="#about">About</a></li>
    <li><a href="#services">Services</a></li>
    <li><a href="#appointment">Book</a></li>
    <li><a href="#contact">Contact</a></li>
  </ul>
</nav>

<div class="sidebar" id="sidebar">
  <div class="close-btn" id="closeSidebar">&times;</div>
  <ul id="sidebarLinks"></ul>
</div>

<!-- Hero -->
<section id="home" class="hero">
  <div class="hero-content">
    <h2>Your Smile, <span class="highlight">Our Passion</span></h2>
    <p>Modern, gentle dental care with a fresh minimalist approach. Experience high-quality treatments in a calming white & green atmosphere.</p>
    <a href="#appointment" class="btn-primary">Book an Appointment →</a>
  </div>
  <div class="hero-image">
    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 400'%3E%3Ccircle cx='200' cy='200' r='180' fill='%23e8f5e9'/%3E%3Cpath fill='%232c7a4d' d='M160,180 L240,180 L260,240 L140,240 Z'/%3E%3Crect x='180' y='130' width='40' height='60' fill='white'/%3E%3C/svg%3E" alt="smile icon">
  </div>
</section>

<!-- About -->
<section id="about" class="about">
  <h2 class="section-title">About Us</h2>
  <div class="about-grid">
    <div class="about-img">
      <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 300'%3E%3Crect width='400' height='300' fill='%23e8f5e9'/%3E%3Ccircle cx='130' cy='120' r='40' fill='%232c7a4d'/%3E%3Ccircle cx='270' cy='120' r='40' fill='%232c7a4d'/%3E%3Cpath d='M150 200 Q200 240 250 200' stroke='%232c7a4d' stroke-width='6' fill='none'/%3E%3C/svg%3E" alt="clinic">
    </div>
    <div class="about-text">
      <span>Since 2011</span>
      <p>EHM Dental Clinic (rebranded) delivers expert dental care with a focus on comfort and transparency. Our skilled team combines modern technology with a gentle touch — making every visit stress‑free. We believe a healthy smile changes everything.</p>
    </div>
  </div>
</section>

<!-- Why choose us -->
<section class="why-cards">
  <h2 class="section-title">Why Choose Us</h2>
  <div class="cards">
    <div class="card"><i class="fa-regular fa-heart"></i><h3>Gentle Care</h3><p>Comfort-first approach, anxiety-free environment.</p></div>
    <div class="card"><i class="fa-regular fa-clock"></i><h3>On Time</h3><p>Respect your schedule with punctual appointments.</p></div>
    <div class="card"><i class="fa-solid fa-leaf"></i><h3>Fresh & Clean</h3><p>Sterile, minimalist clinic with eco-friendly vibe.</p></div>
  </div>
</section>

<!-- Services -->
<section id="services" class="services">
  <h2 class="section-title">Our Services</h2>
  <div class="services-grid" id="servicesGrid"></div>
</section>

<!-- Appointment -->
<section id="appointment" class="appointment">
  <h2 class="section-title">Book Appointment</h2>
  <div class="apt-wrapper">
    <div class="map-box">
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3859.977401090534!2d121.00833437393719!3d14.657223975690215!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b66038db6f6b%3A0x77228c7173b33747!2sEHM%20Dental%20Clinic!5e0!3m2!1sen!2sph!4v1729854317715!5m2!1sen!2sph" loading="lazy"></iframe>
      <p style="text-align:center; margin-top:10px; font-weight:500;">📍 191 Kaingin Rd, Quezon City</p>
    </div>
    <div class="form-box">
      <h3>Request a visit</h3>
      <form id="appointmentForm" method="POST">
        <div class="name-row">
          <div class="form-group"><input type="text" name="last_name" placeholder="Last name" required></div>
          <div class="form-group"><input type="text" name="first_name" placeholder="First name" required></div>
          <div class="form-group"><input type="text" name="middle_name" placeholder="M.I" maxlength="2"></div>
          <div class="form-group">
            <select name="suffix" class="prefix-select">
              <option value="">Suffix</option>
              <option value="Jr">Jr</option>
              <option value="Sr">Sr</option>
              <option value="III">III</option>
              <option value="IV">IV</option>
            </select>
          </div>
        </div>
        <div class="form-group"><label class="required-field" for="contactNum">Contact Number</label><input type="text" name="contact" placeholder="09XX-XXX-XXXX" required id="contactNum" maxlength="13"></div>
        <div class="form-group"><input type="date" name="date" id="appDate" required></div>
        <div class="form-group">
          <select name="time" id="appTime" required>
            <option value="09:00 AM">09:00 AM</option>
                  <option value="10:30 AM">10:30 AM</option>
                  <option value="12:00 PM" disabled>12:00 AM (Lunch Break)</option>
                  <option value="12:30 PM">12:30 PM</option>
                  <option value="01:30 PM">01:30 PM</option>
                  <option value="03:00 PM">03:00 PM</option>
                  <option value="04:30 PM">04:30 PM</option>
          </select>
        </div>
        <div class="form-group">
          <select name="service_type" required>
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
        </div>
        <button type="button" class="book-btn" id="openTermsBtn">Book Now</button>
      </form>
    </div>
  </div>
</section>

<footer id="contact" class="contact">
  <div class="contact-flex">
    <div class="contact-info">
      <h3>Contact us</h3>
      <p><i class="fas fa-phone-alt"></i> +63 2 1234 5678</p>
      <p><i class="fas fa-envelope"></i> hello@dentalclinic.ph</p>
    </div>
  </div>
  <p style="text-align:center; margin-top:30px; font-size:0.8rem;">© 2025 DentalClinic — White & Green Minimal</p>
</footer>

<div class="btnup"><a href="#home"><i class="fas fa-angle-up"></i></a></div>

<div id="termsPopup" class="popup-overlay">
  <div class="popup">
    <div class="popup-header"><span>Terms & Conditions</span><button id="closePopupBtn" style="background:none; border:none; color:white; font-size:1.8rem; cursor:pointer;">&times;</button></div>
    <div class="popup-content">
      <p><strong>1.</strong> By booking you confirm you’re 18+ and accept clinic policies.<br>
      <strong>2.</strong> Cancellations require 24h notice.<br>
      <strong>3.</strong> Provide accurate medical info.<br>
      <strong>4.</strong> Data is used only for appointments.<br>
      <strong>5.</strong> Payments follow clinic guidelines.</p>
    </div>
    <div class="popup-buttons"><button id="acceptTermsBtn" class="book-btn" style="width:auto; padding:8px 35px;">Accept</button></div>
  </div>
</div>

<script>
  // Dynamic Services
  const serviceList = [
    { name: "All Porcelain Veneers & Zirconia", href: "SERVICES/All_Porcelain_Veneers_&_Zirconia.php" },
    { name: "Crown & Bridge", href: "SERVICES/Crown_&_Bridge.php" },
    { name: "Dental Cleaning", href: "SERVICES/Dental_Cleaning.php" },
    { name: "Dental Implants", href: "SERVICES/Dental_Implants.php" },
    { name: "Dental Whitening", href: "SERVICES/Dental_Whitening.php" },
    { name: "Dentures", href: "SERVICES/Dentures.php" },
    { name: "Extraction", href: "SERVICES/Extraction.php" },
    { name: "Full Exam & X-Ray", href: "SERVICES/Full_Exam_&_X-Ray.php" },
    { name: "Orthodontic Braces", href: "SERVICES/Orthodontic_Braces.php" },
    { name: "Restoration", href: "SERVICES/Restoration.php" },
    { name: "Root Canal Treatment", href: "SERVICES/Root_Canal_Treatment.php" }
  ];
  const container = document.getElementById('servicesGrid');
  if(container) {
    serviceList.forEach(service => {
      const card = document.createElement('a');
      card.className = 'service-card';
      card.href = service.href;
      card.innerHTML = `<div class="service-img"><i class="fa-solid fa-tooth"></i></div><p>${service.name}</p>`;
      container.appendChild(card);
    });
  }

  // Sidebar toggles with left icon
  const navbarIcon = document.getElementById('navbarIcon');
  const sidebar = document.getElementById('sidebar');
  const closeSide = document.getElementById('closeSidebar');
  const navLinks = document.getElementById('navLinks');
  const sidebarLinks = document.getElementById('sidebarLinks');

  if (navLinks && sidebarLinks) {
    sidebarLinks.innerHTML = navLinks.innerHTML;
    document.querySelectorAll('#sidebarLinks a').forEach(link => {
      link.addEventListener('click', () => sidebar.classList.remove('active'));
    });
  }

  if (navbarIcon && sidebar && closeSide) {
    navbarIcon.onclick = (e) => {
      e.stopPropagation();
      sidebar.classList.add('active');
    };
    closeSide.onclick = () => sidebar.classList.remove('active');
    document.addEventListener('click', (e) => {
      if (sidebar.classList.contains('active') && !sidebar.contains(e.target) && e.target !== navbarIcon) {
        sidebar.classList.remove('active');
      }
    });
  }

  // Appointment validation and popup logic
  const openBtn = document.getElementById('openTermsBtn');
  const popup = document.getElementById('termsPopup');
  const closePopup = document.getElementById('closePopupBtn');
  const acceptBtn = document.getElementById('acceptTermsBtn');

  function validateForm() {
    const form = document.getElementById('appointmentForm');
    const inputs = form.querySelectorAll('input[required], select[required]');
    for (let inp of inputs) {
      if (!inp.value.trim()) {
        alert("Please fill all required fields.");
        return false;
      }
    }
    const contact = document.querySelector('input[name="contact"]');
    if (contact && !contact.value.match(/^09\d{2}-\d{3}-\d{4}$/)) {
      alert("Contact must be in the format 09XX-XXX-XXXX and contain exactly 11 digits.");
      return false;
    }
    const dateInput = document.getElementById('appDate');
    if (dateInput && dateInput.value) {
      const selectedDate = new Date(dateInput.value);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const maxDate = new Date(today);
      maxDate.setDate(today.getDate() + 6);
      if (selectedDate < today || selectedDate > maxDate) {
        alert("Please select a date within the upcoming week (max 6 days from today).");
        return false;
      }
    }
    return true;
  }

  if (openBtn) {
    openBtn.onclick = () => {
      if (validateForm()) {
        popup.style.display = 'flex';
      }
    };
  }
  if (closePopup) {
    closePopup.onclick = () => popup.style.display = 'none';
  }
  if (acceptBtn) {
    acceptBtn.onclick = () => {
      popup.style.display = 'none';
      const toast = document.createElement('div');
      toast.innerText = "✓ Appointment requested! We'll confirm shortly.";
      toast.style.position = 'fixed';
      toast.style.bottom = '30px';
      toast.style.left = '50%';
      toast.style.transform = 'translateX(-50%)';
      toast.style.backgroundColor = '#2c7a4d';
      toast.style.color = 'white';
      toast.style.padding = '12px 28px';
      toast.style.borderRadius = '40px';
      toast.style.zIndex = '9999';
      toast.style.fontWeight = '500';
      toast.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
      toast.style.fontFamily = "'Montserrat', sans-serif";
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 2800);
      
      const form = document.getElementById('appointmentForm');
      const formData = new FormData(form);
      formData.append('update', '1');
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      }).catch(e => console.warn("Background submit:", e));

      setTimeout(() => {
        form.reset();
        const contactField = document.querySelector('input[name="contact"]');
        if (contactField) contactField.value = '09';
      }, 200);
    };
  }

  // Contact prefix fix
  const contactField = document.querySelector('input[name="contact"]');
  if (contactField) {
    if (!contactField.value) contactField.value = '09';
    contactField.addEventListener('input', () => {
      let val = contactField.value.replace(/[^0-9]/g, '');
      if (!val.startsWith('09')) {
        val = '09';
      }
      if (val.length > 11) {
        val = val.slice(0, 11);
      }
      const parts = [val.slice(0, 4), val.slice(4, 7), val.slice(7, 11)].filter(Boolean);
      contactField.value = parts.join('-');
    });
  }

  // Date picker min/max this week
  const dateInput = document.getElementById('appDate');
  if (dateInput) {
    const today = new Date();
    const maxDate = new Date(today);
    maxDate.setDate(today.getDate() + 6);
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    dateInput.min = `${yyyy}-${mm}-${dd}`;
    const maxY = maxDate.getFullYear();
    const maxM = String(maxDate.getMonth() + 1).padStart(2, '0');
    const maxD = String(maxDate.getDate()).padStart(2, '0');
    dateInput.max = `${maxY}-${maxM}-${maxD}`;
  }

  // Smooth highlight active nav on scroll
  const sections = document.querySelectorAll("section[id]");
  window.addEventListener("scroll", () => {
    let current = "";
    sections.forEach(section => {
      const sectionTop = section.offsetTop - 100;
      if (scrollY >= sectionTop) current = section.getAttribute("id");
    });
    document.querySelectorAll("nav ul li a, .sidebar ul li a").forEach(link => {
      link.classList.remove("active");
      if (link.getAttribute("href") === `#${current}`) link.classList.add("active");
    });
  });
</script>
</body>
</html>