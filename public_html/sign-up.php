<?php
session_start();
if (isset($_SESSION["username"])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once "../config/db.php";

    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    try {
        $stmt = $db->prepare("SELECT (SELECT COUNT(*) FROM users WHERE username = ?) as username_count, (SELECT COUNT(*) FROM users WHERE email = ?) as email_count");
        $stmt->execute([$username, $email]);
        $result = $stmt->fetch();

        if ($result["email_count"] > 0) {
            throw new Exception('Email already taken.');
        } else if ($result["username_count"] > 0) {
            throw new Exception('Username already taken.');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
            $stmt->execute([$email, $username, $hashed_password]);
            header("Location: sign-in.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['toast'] = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
        header("Location: sign-up.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | DevConnect</title>
    <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <a href="./" class="auth-back"><img src="assets/images/arrow-left.svg" alt="back"></a>
    <div class="auth-container">
        <form method="post" onsubmit="return validateForm(this);">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="retype_password">Retype Password:</label>
            <input type="password" id="retype_password" name="retype_password" required>

            <input type="submit" class="button" value="Register">
        </form>
    </div>
    <?php include "../includes/toast.php"; ?>

    <script>
        function validateForm(form) {
            const email = form["email"].value.trim();
            const username = form["username"].value.trim();
            const password = form["password"].value.trim();
            const retype_password = form["retype_password"].value.trim();

            if (username.length < 6) {
                showToast("Username must be at least 6 characters long", "error");
                return false;
            } else if (password.length < 8) {
                showToast("Password must be at least 8 characters long", "error");
                return false;
            } else if (password !== retype_password) {
                showToast("Passwords do not match", "error");
                return false;
            } else {
                return true;
            }
        };
    </script>
</body>

</html>