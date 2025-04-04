<?php
session_start();
if (!isset($_SESSION["username"])) {
  $_SESSION["redirect_to"] = "notes.php";
  header("Location: ./");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notes | DevConnect</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
</head>

<body>
  <?php include "../includes/sidebar.php"; ?>
  <div class="main-content">
    <?php include "../includes/header.php"; ?>
    <h1>Lecture Notes</h1>

    <ul class="notes-list">
      <li class="note">
        Note1.pdf
        <a href=""><img src="assets/images/download.svg" alt="download"></a>
      </li>
      <li class="note">
        Note2.pdf
        <a href=""><img src="assets/images/download.svg" alt="download"></a>
      </li>
      <li class="note">
        Note3.pdf
        <a href=""><img src="assets/images/download.svg" alt="download"></a>
      </li>
      <li class="note">
        Note4.pdf
        <a href=""><img src="assets/images/download.svg" alt="download"></a>
      </li>
      <li class="note">
        Note5.pdf
        <a href=""><img src="assets/images/download.svg" alt="download"></a>
      </li>
      <li class="note">
        Note6.pdf
        <a href=""><img src="assets/images/download.svg" alt="download"></a>
      </li>
    </ul>
  </div>

</body>

</html>