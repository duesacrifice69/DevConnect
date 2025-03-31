<?php
require "../auth-check.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forum | DevConnect</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
</head>

<body>
  <?php include "../includes/sidebar.php"; ?>
  <div class="main-content">
    <?php include "../includes/header.php"; ?>
    <h1>Forum</h1>

    <div style="margin: 40px 0;" class="all-tags">
      <a href="" class="button">Python</a>
      <a href="" class="button">HTML</a>
      <a href="" class="button">CSS</a>
      <a href="" class="button">JavaScript</a>
      <a href="" class="button">SQL</a>
    </div>

    <div class="post">
      <h2>Had a problem with this code</h2>
      <div style="display: flex; align-items: flex-start;">
        <img class="image" alt="image" src="assets/images/code.svg">
        <div>
          <p>Had a problem with this code</p>
          <div class="tags"">
            <span class=" tag">Python</span>
            <span class="tag">Functions</span>
          </div>
        </div>
      </div>
    </div>

    <div class="post">
      <h2>Had a problem with this code</h2>
      <div style="display: flex; align-items: flex-start;">
        <img class="image" alt="image" src="assets/images/code.svg">
        <div>
          <p>Had a problem with this code</p>
          <div class="tags"">
            <span class=" tag">Python</span>
            <span class="tag">Functions</span>
          </div>
        </div>
      </div>
    </div>

  </div>
  <?php include "../includes/toast.php"; ?>
</body>

</html>