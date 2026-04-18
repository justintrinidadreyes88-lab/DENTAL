<?php
session_start();

include("dbcon.php");

$fname = '';
$lname = '';
$usertype = '';
$contact = '';
$email = '';
$successMessage = '';
$errorMessage = '';
$showVerificationStep = false;
$pendingEmail = '';

if (isset($_SESSION['pending_signup'])) {
    $pendingEmail = $_SESSION['pending_signup']['email'] ?? '';
    if (isset($_GET['verify']) && $_GET['verify'] === '1') {
        $showVerificationStep = true;
    }
}

if($_SERVER['REQUEST_METHOD'] == "POST")
{
    $action = $_POST['action'] ?? 'register';
    if ($action === 'register') {
        $fname = trim($_POST['fname']);
        $lname = trim($_POST['lname']);
        $usertype = $_POST['usertype'];
        $contact = trim($_POST['contact']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $createdAt = date('Y-m-d H:i:s');

        // Ensure required columns exist in tbl_users
        $alterQueries = [
            "ALTER TABLE tbl_users ADD COLUMN IF NOT EXISTS firstname VARCHAR(100) NOT NULL AFTER role",
            "ALTER TABLE tbl_users ADD COLUMN IF NOT EXISTS lastname VARCHAR(100) NOT NULL AFTER firstname",
            "ALTER TABLE tbl_users ADD COLUMN IF NOT EXISTS email VARCHAR(255) NOT NULL AFTER lastname",
            "ALTER TABLE tbl_users ADD COLUMN IF NOT EXISTS contact VARCHAR(20) DEFAULT '' AFTER email",
            "ALTER TABLE tbl_users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER contact"
        ];

        foreach ($alterQueries as $alterQuery) {
            mysqli_query($con, $alterQuery);
        }

        $contactDigits = preg_replace('/\D/', '', $contact);
        if (strlen($contactDigits) === 11) {
            $contact = substr($contactDigits, 0, 4) . '-' . substr($contactDigits, 4, 3) . '-' . substr($contactDigits, 7, 4);
        }

        if (empty($fname) || empty($lname) || empty($usertype) || empty($contact) || empty($email) || empty($password)) {
            $errorMessage = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Please enter a valid email address.';
        } elseif (!preg_match('/^09\d{2}-\d{3}-\d{4}$/', $contact)) {
            $errorMessage = 'Contact must use 09XX-XXX-XXXX format.';
        } else {
            $emailCheckQuery = "SELECT * FROM tbl_users WHERE email = ? LIMIT 1";
            $stmt = $con->prepare($emailCheckQuery);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $errorMessage = 'Email already registered. Please use a different email.';
                $stmt->close();
            } else {
                $verificationCode = strval(rand(100000, 999999));
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $_SESSION['pending_signup'] = [
                    'fname' => $fname,
                    'lname' => $lname,
                    'usertype' => $usertype,
                    'contact' => $contact,
                    'email' => $email,
                    'password' => $hashedPassword,
                    'createdAt' => $createdAt,
                    'code' => $verificationCode
                ];

                $pendingEmail = $email;
                $showVerificationStep = true;

                $subject = 'DentalClinic Email Verification Code';
                $message = "Hello $fname,\n\nYour verification code is: $verificationCode\n\nEnter this code on the sign-up page to complete registration.\n\nIf you did not request this, please ignore this email.";

                // Save code to file for debugging
                $log_file = __DIR__ . '/verification_codes.txt';
                $log_entry = date('Y-m-d H:i:s') . " | Email: " . $email . " | Code: " . $verificationCode . "\n";
                @file_put_contents($log_file, $log_entry, FILE_APPEND);

                // Try to send email via Gmail
                $mail_to = $email;
                $mail_from = 'dentalclinic.donotreply@gmail.com';
                $smtp_host = 'smtp.gmail.com';
                $smtp_port = 587;
                $smtp_user = 'dentalclinic.donotreply@gmail.com';
                $smtp_pass = 'uaay ipwa vzwg wzcj';

                $mail_sent = sendEmailViaGmailSMTP($mail_to, $subject, $message, $mail_from, $smtp_host, $smtp_port, $smtp_user, $smtp_pass);

                if ($mail_sent) {
                    $_SESSION['signup_success'] = 'Verification code sent to your email. Please check your inbox.';
                } else {
                    $_SESSION['signup_success'] = 'Verification code generated. Check email_debug.txt for sending details.';
                }
                $stmt->close();
                header('Location: signup.php?verify=1');
                exit();
            }
        }
    } elseif ($action === 'verify') {
        $enteredCode = trim($_POST['code'] ?? '');

        if (!isset($_SESSION['pending_signup'])) {
            $errorMessage = 'No pending verification found. Please register again.';
        } elseif ($enteredCode === '') {
            $errorMessage = 'Please enter the verification code.';
            $showVerificationStep = true;
        } elseif ($enteredCode !== ($_SESSION['pending_signup']['code'] ?? '')) {
            $errorMessage = 'Invalid verification code.';
            $showVerificationStep = true;
        } else {
            $pending = $_SESSION['pending_signup'];
            $insertQuery = "INSERT INTO tbl_users (firstname, lastname, role, contact, email, password, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $con->prepare($insertQuery);
            $insertStmt->bind_param("sssssss", $pending['fname'], $pending['lname'], $pending['usertype'], $pending['contact'], $pending['email'], $pending['password'], $pending['createdAt']);

            if ($insertStmt->execute()) {
                unset($_SESSION['pending_signup']);
                $_SESSION['signup_success'] = 'Email verified and registration complete. You may now sign in.';
                $insertStmt->close();
                header('Location: signin.php');
                exit();
            } else {
                $errorMessage = 'Verification failed. Please try again.';
                $showVerificationStep = true;
            }
            $insertStmt->close();
        }
    }
}

function sendEmailViaGmailSMTP($to, $subject, $body, $from, $smtp_host, $smtp_port, $smtp_user, $smtp_pass) {
    $log_file = __DIR__ . '/email_debug.txt';
    
    try {
        $socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 15);
        
        if (!$socket) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Socket Error: $errstr ($errno)\n", FILE_APPEND);
            return false;
        }

        file_put_contents($log_file, date('Y-m-d H:i:s') . " | Connected to SMTP\n", FILE_APPEND);

        stream_set_timeout($socket, 5);
        
        // Read initial response
        $response = fgets($socket, 1024);
        file_put_contents($log_file, "Initial: $response", FILE_APPEND);

        // Send EHLO
        fwrite($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 1024);
        file_put_contents($log_file, "EHLO: $response", FILE_APPEND);

        // Start TLS
        fwrite($socket, "STARTTLS\r\n");
        $response = fgets($socket, 1024);
        file_put_contents($log_file, "STARTTLS: $response", FILE_APPEND);

        // Enable crypto
        $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
        if (!$crypto) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | TLS crypto failed\n", FILE_APPEND);
            @fclose($socket);
            return false;
        }

        file_put_contents($log_file, date('Y-m-d H:i:s') . " | TLS enabled\n", FILE_APPEND);

        // EHLO again after TLS
        fwrite($socket, "EHLO localhost\r\n");
        while ($response = fgets($socket, 1024)) {
            file_put_contents($log_file, "EHLO2: $response", FILE_APPEND);
            if (substr($response, 3, 1) != '-') break;
        }

        // AUTH LOGIN
        fwrite($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 1024);
        file_put_contents($log_file, "AUTH: $response", FILE_APPEND);

        // Send username
        $username_b64 = base64_encode($smtp_user);
        fwrite($socket, "$username_b64\r\n");
        $response = fgets($socket, 1024);
        file_put_contents($log_file, "USER: $response", FILE_APPEND);

        // Send password
        $password_b64 = base64_encode($smtp_pass);
        fwrite($socket, "$password_b64\r\n");
        $response = fgets($socket, 1024);
        file_put_contents($log_file, "PASS: $response", FILE_APPEND);

        if (strpos($response, '235') === false) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Authentication failed\n", FILE_APPEND);
            @fclose($socket);
            return false;
        }

        file_put_contents($log_file, date('Y-m-d H:i:s') . " | Authentication successful\n", FILE_APPEND);

        // MAIL FROM
        fwrite($socket, "MAIL FROM:<$from>\r\n");
        $response = fgets($socket, 1024);
        file_put_contents($log_file, "MAILFROM: $response", FILE_APPEND);

        // RCPT TO
        fwrite($socket, "RCPT TO:<$to>\r\n");
        $response = fgets($socket, 1024);
        file_put_contents($log_file, "RCPTTO: $response", FILE_APPEND);

        // DATA
        fwrite($socket, "DATA\r\n");
        $response = fgets($socket, 1024);
        file_put_contents($log_file, "DATA: $response", FILE_APPEND);

        // Send message
        $message = "Subject: $subject\r\n";
        $message .= "From: $from\r\n";
        $message .= "To: $to\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "MIME-Version: 1.0\r\n\r\n";
        $message .= "$body\r\n.\r\n";

        fwrite($socket, $message);
        $response = fgets($socket, 1024);
        file_put_contents($log_file, "SEND: $response", FILE_APPEND);

        // QUIT
        fwrite($socket, "QUIT\r\n");
        $response = fgets($socket, 1024);
        file_put_contents($log_file, "QUIT: $response", FILE_APPEND);

        @fclose($socket);

        if (strpos($response, '221') !== false) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Email sent successfully\n\n", FILE_APPEND);
            return true;
        }

        return false;

    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " | Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}


if (empty($successMessage) && isset($_SESSION['signup_success'])) {
    $successMessage = $_SESSION['signup_success'];
}
if (empty($errorMessage) && isset($_SESSION['signup_error'])) {
    $errorMessage = $_SESSION['signup_error'];
}
unset($_SESSION['signup_success'], $_SESSION['signup_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/signup.css">
    <title>Sign Up - DentalClinic</title>
</head>

<body>

<nav>
  <div class="nav-left">
    <a href="index.php" class="logo"><h1>DentalClinic</h1></a>
  </div>
</nav>

<div class="signup-section">
  <div class="signup-container">
    <h1>Create Account</h1>
    <p>Join our dental care community</p>

    <?php if (!empty($successMessage)): ?>
      <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
      <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <?php if ($showVerificationStep && !empty($pendingEmail)): ?>
      <form class="signup-form" action="signup.php" method="POST">
        <div class="form-group">
          <label>Verification Email</label>
          <input type="text" value="<?php echo htmlspecialchars($pendingEmail); ?>" disabled>
        </div>
        <div class="form-group">
          <label for="code">Enter Verification Code</label>
          <input type="text" id="code" name="code" placeholder="123456" maxlength="6" required>
        </div>
        <input type="hidden" name="action" value="verify">
        <button type="submit">Verify Email</button>
      </form>
      <div class="signup-footer">
        <p>Didn't receive a code? <a href="signup.php">Start over</a></p>
      </div>
    <?php else: ?>
      <form class="signup-form" action="signup.php" method="POST">
        <input type="hidden" name="action" value="register">
        <div class="name-row">
          <div class="form-group">
            <label for="fname">First Name</label>
            <input type="text" id="fname" name="fname" placeholder="Enter first name" value="<?php echo htmlspecialchars($fname); ?>" required>
          </div>
          <div class="form-group">
            <label for="lname">Last Name</label>
            <input type="text" id="lname" name="lname" placeholder="Enter last name" value="<?php echo htmlspecialchars($lname); ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label for="usertype">User Type</label>
          <select id="usertype" name="usertype" required>
            <option value="">-- Select User Type --</option>
            <option value="1" <?php echo $usertype === '1' ? 'selected' : ''; ?>>Admin</option>
            <option value="2" <?php echo $usertype === '2' ? 'selected' : ''; ?>>Doctor</option>
            <option value="3" <?php echo $usertype === '3' ? 'selected' : ''; ?>>Dental Assistant</option>
          </select>
        </div>

        <div class="form-group">
          <label for="contact">Contact Number</label>
          <input type="text" id="contact" name="contact" placeholder="09XX-XXX-XXXX" pattern="09\d{2}-\d{3}-\d{4}" title="Format: 09XX-XXX-XXXX" value="<?php echo htmlspecialchars($contact); ?>" required>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="Enter email address" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-wrapper">
            <input type="password" id="password" name="password" placeholder="Create password" required>
            <i class="fas fa-eye toggle-password" id="togglePassword"></i>
          </div>
          <div class="password-strength">
            <span id="passwordStrengthLabel">Very Weak</span>
            <div class="strength-bar"><div id="passwordStrengthBar"></div></div>
          </div>
        </div>

        <button type="submit">Create Account</button>
      </form>

      <div class="signup-footer">
        <p>Already have an account? <a href="signin.php">Sign in here</a></p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  const contactField = document.getElementById('contact');
  if (contactField) {
    if (!contactField.value.trim()) {
      contactField.value = '09';
    }

    const formatContactValue = (digits) => {
      if (!digits.startsWith('09')) {
        digits = '09' + digits.replace(/^0+/, '');
      }
      if (digits.length > 11) {
        digits = digits.slice(0, 11);
      }
      if (digits.length < 2) {
        digits = '09';
      }

      let formatted = digits;
      if (formatted.length > 2) {
        formatted = formatted.slice(0, 3) + (formatted.length > 3 ? '-' + formatted.slice(3) : '');
      }
      if (formatted.length > 7) {
        const raw = formatted.replace(/-/g, '');
        formatted = raw.slice(0, 3) + '-' + raw.slice(3, 6) + (raw.length > 6 ? '-' + raw.slice(6) : '');
      }
      return formatted;
    };

    contactField.addEventListener('input', function(e) {
      let digits = e.target.value.replace(/\D/g, '');

      if (!digits.startsWith('09')) {
        digits = '09' + digits.replace(/^0+/, '');
      }
      if (digits.length > 11) {
        digits = digits.slice(0, 11);
      }
      if (digits.length < 2) {
        digits = '09';
      }

      let formatted = digits;
      if (digits.length > 4) {
        formatted = digits.slice(0, 4) + '-' + digits.slice(4, 7) + (digits.length > 7 ? '-' + digits.slice(7) : '');
      }

      e.target.value = formatted;
    });

    contactField.addEventListener('keydown', function(e) {
      if (e.key === 'Backspace') {
        const digits = e.target.value.replace(/\D/g, '');
        if (digits.length <= 2) {
          e.preventDefault();
        }
      }
    });
  }

  const passwordField = document.getElementById('password');
  const strengthLabel = document.getElementById('passwordStrengthLabel');
  const strengthBar = document.getElementById('passwordStrengthBar');

  if (passwordField && strengthLabel && strengthBar) {
    const evaluatePasswordStrength = (password) => {
    let score = 0;
    if (password.length >= 8) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/[a-z]/.test(password)) score += 1;
    if (/[0-9]/.test(password)) score += 1;
    if (/[^A-Za-z0-9]/.test(password)) score += 1;

    if (password.length === 0) {
      return { label: 'Very Weak', value: 0, color: '#e9ecef' };
    }
    if (password.length < 5 || score <= 2) {
      return { label: 'Very Weak', value: 1, color: '#d32f2f' };
    }
    if (score === 3) {
      return { label: 'Weak', value: 2, color: '#f39c12' };
    }
    if (score === 4) {
      return { label: 'Good', value: 3, color: '#2c7a4d' };
    }
    return { label: 'Strong', value: 4, color: '#1e5a3a' };
  };

  const updatePasswordStrength = () => {
    const strength = evaluatePasswordStrength(passwordField.value);
    strengthLabel.textContent = strength.label;
    strengthBar.style.width = `${strength.value * 25}%`;
    strengthBar.style.backgroundColor = strength.color;
  };

  passwordField.addEventListener('input', updatePasswordStrength);
  updatePasswordStrength();

  const togglePassword = document.getElementById('togglePassword');

  togglePassword.addEventListener('click', function() {
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    this.classList.toggle('fa-eye');
    this.classList.toggle('fa-eye-slash');
  });
}
</script>

</body>

</html>
