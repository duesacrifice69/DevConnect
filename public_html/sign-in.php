<?php
session_start();
if (isset($_SESSION["username"])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once "../db.php";

    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $db->prepare("SELECT username, role, email, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

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
    <a href="./" class="auth-back"><img src="assets/images/arrow-left.svg" alt="back"></a>
    <div class="auth-container">
        <form name="signin" method="post">
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