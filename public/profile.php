<?php
require "../auth-check.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | DevConnect</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">

    <style>
        .white-box {
            width: 250px;
            /* Adjust size as needed */
            height: 300px;
            background-color: white;
            opacity: 40%;
            color: black;
            /* Text color */
            padding: 20px;
            border-radius: 10px;
            /* Rounded corners */
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
            /* Soft shadow */
            display: flex;
            align-items: center;
            justify-content: top;
            text-align: top;
        }
    </style>
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include "../includes/header.php"; ?>
        <section class="content">
            <p>Approve Posts</p>
            <div class="white-box">

            </div>
        </section>
    </div>
</body>

</html>