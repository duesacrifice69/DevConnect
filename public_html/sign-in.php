<?php
session_start();
if (isset($_SESSION["username"])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once "../config/db.php";

    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $db->prepare("SELECT username, email, role, id, password, verified, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user)
            throw new Exception('Invalid username or password.');

        if (!$user['verified']) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM verification_codes WHERE user_id = ? AND expires_at > NOW()");
            $stmt->execute([$user['id']]);
            $has_valid_code = $stmt->fetchColumn() > 0;

            if (!$has_valid_code) {
                header("Location: verification.php?id=" . $user['id'] . "&resend=true");
                exit();
            } else {
                $_SESSION['toast'] = ['type' => 'error', 'message' => "Your account is not verified. Please check your email for the verification link."];
                header("Location: verification.php?id=" . $user['id']);
                exit();
            }
        }

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];

            if ($user['role'] == 'admin') {
                $_SESSION['admin_mode'] = false;
            }
            $redirect_to = isset($_SESSION["redirect_to"]) ? $_SESSION["redirect_to"] : "dashboard.php";
            unset($_SESSION["redirect_to"]);
            header("Location: $redirect_to");
            exit();
        } else {
            throw new Exception('Invalid username or password.');
        }
    } catch (Exception $e) {
        $_SESSION['toast'] = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
        header("Location: sign-in.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | DevConnect</title>
    <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <button onclick="window.history.back()" class="back-btn auth-back"><img src="assets/images/arrow-left.svg" alt="back"></button>
    <div class="auth-container">
        <form name="signin" method="post">
            <h1 style="margin-bottom: 24px;">Sign In</h1>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" class="button" value="Sign In">
        </form>
    </div>
    <?php include "../includes/toast.php"; ?>
</body>

</html>