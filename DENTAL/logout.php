<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();
echo "Logging out...";
// Redirect to login page
header("Location: signin.php");
exit();
?>

