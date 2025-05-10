<?php
session_start();
if (isset($_SESSION["username"])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | DevConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
</head>

<body>
    <div class="welcome-container">
        <div>
            <h3>Welcome To</h3>
            <h1> DevConnect 🚀</h1>
            <div class="auth-buttons">
                <a href="sign-in.php" class="button">Sign In</a>
                <a href="sign-up.php" class="button">Sign Up</a>
            </div>
        </div>
    </div>
</body>

</html>