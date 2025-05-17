<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: ./");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | DevConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
</head>

<body>
    <?php include "../includes/sidebar.php"; ?>
    <div class="main-content">
        <?php include "../includes/header.php"; ?>
        <iframe loading="lazy" style="width: 100%;height:200px;" src="https://quotes-github-readme.vercel.app/api?type=horizontal&theme=monokai" frameborder="0" />
    </div>
</body>

</html>