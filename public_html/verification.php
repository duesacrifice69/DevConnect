<?php
session_start();
if (isset($_SESSION["username"])) {
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'] ?? null;

if (empty($id) || !is_numeric($id)) {
    header("Location: ./");
    exit();
}
$resend = isset($_GET['resend']);

require_once "../config/db.php";

try {
    $stmt = $db->prepare("SELECT username, email, verified FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: ./");
        exit();
    }
    if ($user['verified']) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => "Your email is already verified."];
        header("Location: sign-in.php");
        exit();
    }
    $stmt = $db->prepare("SELECT TIME_TO_SEC(TIMEDIFF(expires_at, NOW())) FROM verification_codes WHERE user_id = ? AND expires_at > NOW()");
    $stmt->execute([$id]);
    $seconds_left = (int) $stmt->fetchColumn();

    $can_resend = $seconds_left < 8 * 60;

    if (!$seconds_left || ($resend && $can_resend)) {
        $verification_code = random_int(100000, 999999);

        // Send the verification email
        require_once "../config/mail.php";
        $message = "
            <div style='background:linear-gradient(45deg, rgb(6, 12, 31), rgb(11, 22, 54));padding: 20px; color: white; font-family: Arial, sans-serif;border-radius: 10px;'>
                <h1>DevConnect 🚀</h1>
                <h2>Email Verification</h2>
                <p>Hi {$user['username']},<br/>
                Please use the following code to verify your email.<br/>
                This code will expire in 10 minutes.</p>
                <h2>$verification_code</h2>
                <p>If you did not request this, please ignore this email.</p>
                <p>Thank you,<br/>DevConnect Team</p>
                <p style='font-size: 12px; color: #ccc;'>This is an automated message. Please do not reply.</p>
            </div>
            ";

        $mail->addAddress($user['email']);
        $mail->Subject = "Email Verification";
        $mail->Body = $message;
        $mail->send();
        $mail->clearAddresses();

        $stmt = $db->prepare("INSERT INTO verification_codes (user_id, code, expires_at) VALUES (:user_id, :verification_code, DATE_ADD(NOW(), INTERVAL 10 MINUTE)) ON DUPLICATE KEY UPDATE code = :verification_code, expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE)");
        $stmt->execute([
            ":user_id" => $id,
            ":verification_code" => $verification_code
        ]);

        $_SESSION['toast'] = ['type' => 'success', 'message' => "A new verification code has been sent to your email. Please check your inbox."];
    }
    if ($resend) {
        header("Location: verification.php?id=" . $id);
        exit();
    }
} catch (Exception $e) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => "Error: " . $e->getMessage()];
    header("Location: verification.php?id=" . $id);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once "../config/db.php";

    $verification_code = $_POST['verification_code'];

    try {
        if (empty($verification_code)) {
            throw new Exception('Verification code is required.');
        }
        $stmt = $db->prepare("SELECT COUNT(*) FROM verification_codes WHERE user_id = ? AND code = ? AND expires_at > NOW()");
        $stmt->execute([$id, $verification_code]);
        $is_valid_code = $stmt->fetchColumn() > 0;

        if (!$is_valid_code) {
            throw new Exception('Invalid or expired verification code.');
        }
        $stmt = $db->prepare("UPDATE users SET verified = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $stmt = $db->prepare("DELETE FROM verification_codes WHERE user_id = ?");
        $stmt->execute([$id]);

        $_SESSION['toast'] = ['type' => 'success', 'message' => "Your email has been verified successfully."];

        header("Location: sign-in.php");
    } catch (Exception $e) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => "Error: " . $e->getMessage()];
        header("Location: verification.php?id=" . $id);
    } finally {
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification | DevConnect</title>
    <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <button onclick="window.history.back()" class="back-btn auth-back"><img src="assets/images/arrow-left.svg" alt="back"></button>
    <div class="auth-container">
        <form name="verification" method="post">
            <h1 style="margin-bottom: 24px;">Email Verification</h1>
            <label for="verification_code">Code:</label>
            <input type="number" id="verification_code" name="verification_code" max="1000000" min="99999" required>

            <input type="submit" class="button" value="Verify">
            <p>Didn't receive the code? <a <?php echo $can_resend ? 'href="verification.php?id=' . $id . '&resend=true"' : 'style="cursor:not-allowed;"'; ?>>Resend</a>
                <?php if (!$can_resend): ?>
                    <span class="time-remaining">(in <span class="time-remaining-value"><?php echo $seconds_left - 8 * 60; ?></span>s)</span>
                <?php endif; ?>
            </p>
        </form>
    </div>

    <?php include "../includes/toast.php"; ?>

    <?php if (!$can_resend): ?>
        <script>
            const timeRemaining = document.querySelector(".time-remaining");
            const timeRemainingValue = document.querySelector(".time-remaining-value");

            if (timeRemaining) {
                let interval;
                let secondsLeft = <?php echo $seconds_left; ?> - 8 * 60;

                interval = setInterval(() => {
                    timeRemainingValue.textContent = --secondsLeft;
                    if (secondsLeft <= 0) {
                        clearInterval(interval);
                        location.reload();
                    }
                }, 1000);
            }
        </script>
    <?php endif; ?>
</body>

</html>