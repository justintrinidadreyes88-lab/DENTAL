<?php
session_start();

// Retrieve error message if it exists
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['error_message']); // Clear after retrieving

include("dbcon.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_email = $_POST['email'];
    $input_password = $_POST['password'];

    // Prepare and execute SQL statement
    $stmt = $con->prepare("SELECT id, password, role FROM tbl_users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $input_email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $stored_password, $role);
        $stmt->fetch();

        // Verify hashed password
        if (password_verify($input_password, $stored_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['role'] = $role;

            // Redirect based on role
            if ($role == '2') {
                header("Location: DOCTOR/doctor_dashboard.php");
            } elseif ($role == '3') {
                header("Location: DENTAL_ASSISTANT/dental_assistant_dashboard.php");
            } elseif ($role == "1") {
                header("Location: ADMIN/admin_dashboard.php");
            }
            exit();
        } else {
            $_SESSION['error_message'] = "Invalid email or password.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid email or password.";
    }

    $stmt->close();
    $con->close();

    // Redirect back to signin page
    header("Location: signin.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/login.css">
    <title>Sign In - DentalClinic</title>
</head>

<body>

<nav>
  <div class="nav-left">
    <a href="index.php" class="logo"><h1>DentalClinic</h1></a>
  </div>
</nav>

<div class="login-section">
  <div class="login-container">
    <h1>Welcome Back</h1>
    <p>Sign in to your account</p>
    
    <?php if (!empty($error_message)): ?>
      <div id="error-message" style="display: block;">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>

    <form class="login-form" action="signin.php" method="POST" oninput="hideErrorMessage()">
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="Enter your email address" required 
          oncopy="return false" onpaste="return false" oncut="return false">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div class="password-wrapper">
          <input type="password" id="password" name="password" placeholder="Enter your password" required 
            oncopy="return false" onpaste="return false" oncut="return false">
          <i class="fas fa-eye toggle-password" id="togglePassword"></i>
        </div>
      </div>
      <button type="submit">Sign In</button>
    </form>

    <div class="login-footer">
      <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
    </div>
  </div>
</div>

<script>
  function hideErrorMessage() {
    const errorMessage = document.getElementById('error-message');
    if (errorMessage) {
      errorMessage.style.display = 'none';
    }
  }

  const togglePassword = document.getElementById('togglePassword');
  const passwordField = document.getElementById('password');

  togglePassword.addEventListener('click', function() {
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    this.classList.toggle('fa-eye');
    this.classList.toggle('fa-eye-slash');
  });
</script>

</body>

</html>
