<?php
session_start();
if (isset($_SESSION["username"])) {
    header("Location: dashboard.php");
    exit();
}
require_once "../db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $db->prepare("SELECT username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $redirect_to = isset($_SESSION["redirect_to"]) ? $_SESSION["redirect_to"] : "dashboard.php";
            unset($_SESSION["redirect_to"]);
            header("Location: $redirect_to");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
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
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <a href="index.php" class="auth-back"><img src="assets/images/arrow-left.svg" alt="back"></a>
    <div class="auth-container">
        <form name="signin" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" class="button" value="Sign In">

            <p class="error">
                <?php if (isset($error)): ?>
                    <?php echo $error; ?>
                <?php endif; ?>
            </p>
        </form>
    </div>
</body>

</html>