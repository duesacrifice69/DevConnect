<?php
session_start();
if (!isset($_SESSION["username"])) {
    $_SESSION["redirect_to"] = "tutorials/add.php";
    header("Location: ../");
    exit();
}

if (!(isset($_SESSION["admin_mode"]) && $_SESSION["admin_mode"])) {
    header("Location: ../tutorials");
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    require_once "../../db.php";
    $url = $_POST['url'];
    $category = $_POST['category'];

    try {
        $stmt = $db->prepare("INSERT INTO tutorials (url, category) VALUES (?, ?)");
        $stmt->execute([$url, $category]);
        $_SESSION["toast"] = ['type' => 'success' , 'message' => 'Tutorial added successfully.'];
        header("Location: ./");        
        exit();
    } catch (PDOException $e) {
        $toast = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutorials | DevConnect</title>
    <base href="../">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
</head>

<body>
    <?php include "../../includes/sidebar.php"; ?>
    <div class="main-content">
        <?php include "../../includes/header.php"; ?>
        <div style="position: relative;">
            <a href="tutorials" class="auth-back"><img src="assets/images/arrow-left.svg" alt="back"></a>
            <form action="tutorials/add.php" method="POST">
                <label for="url">URL:</label>
                <input type="url" id="url" name="url" required>
                <label for="url">Category:</label>
                <input type="text" id="category" name="category" required>

                <input type="submit" class="button" value="Add">
            </form>
        </div>


    </div>
    <?php include "../../includes/toast.php"; ?>
</body>

</html>