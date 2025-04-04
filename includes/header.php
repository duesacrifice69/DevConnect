<?php
$isAdmin = isset($_SESSION["admin_mode"]);
$adminModeEnabled = $isAdmin ? $_SESSION["admin_mode"] : false;
?>

<header>
    <div class="profile">
        <img src="assets/images/user.svg" alt="Admin Profile">
        <h1>Hello <?php echo htmlspecialchars($_SESSION["username"]) ?>!</h1>
    </div>
    <div>
        <?php if ($isAdmin): ?>
            <button onclick="toggleEditMode()" class="admin-toggle-btn hover-accent">
                <?php echo $adminModeEnabled ? "User Mode" : "Admin Mode"; ?>
            </button>
            <script>
                function toggleEditMode() {
                    fetch('actions.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: "action=toggle_admin_mode",
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            }
                        });
                }
            </script>
        <?php endif; ?>
        <div class="notification-btn hover-accent">
            <span>🔔</span>
        </div>
        <a href="sign-out.php" class="logout-btn hover-accent"><img src="assets/images/logout.svg" alt="logout"></a>
    </div>
</header>