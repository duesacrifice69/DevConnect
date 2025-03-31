<header>
    <div class="profile">
        <img src="assets/images/user.svg" alt="Admin Profile">
        <h1>Hello <?php echo $_SESSION["username"] ?>!</h1>
    </div>
    <div>
        <div class="notification">
            <span>🔔</span>
        </div>
        <a href="sign-out.php" class="logout-btn"><img src="assets/images/logout.svg" alt="logout"></a>
    </div>
</header>