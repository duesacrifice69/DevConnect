<?php

$upload_dir = "../uploads/lecture-materials/";
$max_size_mb = 10;

session_start();
if (!isset($_SESSION["username"])) {
  $_SESSION["redirect_to"] = "lecture-materials.php";
  header("Location: ./");
  exit();
}
require_once "../db.php";

switch ($_SERVER["REQUEST_METHOD"]) {
  case 'GET':
    $lecture_materials = [];
    try {
      $stmt = $db->prepare("SELECT * FROM lecture_materials ORDER BY uploaded_at DESC");
      $stmt->execute();
      $lecture_materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      $toast = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
    }
    break;

  // Add new lecture material
  case 'POST':
    try {
      $file_error = $_FILES['lecture_material']['error'];

      if ($file_error === UPLOAD_ERR_NO_FILE) {
        throw new Exception("No file uploaded.");
      }

      $file_name = $_FILES['lecture_material']['name'];
      $file_tmp = $_FILES['lecture_material']['tmp_name'];
      $file_size = $_FILES['lecture_material']['size'];

      if (file_exists($upload_dir . $file_name)) {
        $file_name = pathinfo($file_name, PATHINFO_FILENAME) . "_" . time() . "." . pathinfo($file_name, PATHINFO_EXTENSION);
      }

      if ($file_size > $max_size_mb * 1024 * 1024) {
        throw new Exception("Maximum file size is " . $max_size_mb . " MB.");
      }

      if ($file_error !== UPLOAD_ERR_OK) {
        throw new Exception("There was a problem uploading your file.");
      }

      $file_destination = $upload_dir . $file_name;

      if (!move_uploaded_file($file_tmp, $file_destination)) {
        throw new Exception("Failed to move uploaded file.");
      }
      $stmt = $db->prepare("INSERT INTO lecture_materials (file_name) VALUES (?)");
      $success = $stmt->execute([$file_name]);
      if (!$success) {
        throw new Exception("Failed to add lecture material.");
      }
      $_SESSION["toast"] = ['type' => 'success', 'message' => 'Lecture Material uploaded successfully.'];
    } catch (Exception $e) {
      $_SESSION["toast"] = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
      http_response_code(500);
    } finally {
      header("Location: ./lecture-materials.php");
      exit();
    }

    break;

  // Remove lecture material
  case 'DELETE':
    $rawData = file_get_contents("php://input");
    parse_str($rawData, $queryParams);

    $file_name = $queryParams['file_name'];
    try {
      $file_path = $upload_dir . $file_name;
      unlink($file_path);
      $stmt = $db->prepare("DELETE FROM lecture_materials WHERE file_name = ?");
      $success = $stmt->execute([$file_name]);
      if (!$success) {
        throw new Exception("Failed to remove lecture material.");
      }
      $_SESSION["toast"] = ['type' => 'success', 'message' => 'Lecture material removed successfully.'];
      http_response_code(200);
    } catch (Exception $e) {
      $_SESSION["toast"] = ['type' => 'error', 'message' => "Error: " . $e->getMessage()];
      http_response_code(500);
    } finally {
      exit();
    }
    break;

  default:
    http_response_code(405);
    break;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lecture Materials | DevConnect</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
</head>

<body>
  <?php include "../includes/sidebar.php"; ?>
  <div class="main-content">
    <?php include "../includes/header.php"; ?>

    <div style="display: flex;justify-content: space-between;min-height: 41px;">
      <h1>Lecture Materials</h1>
      <?php if ($adminModeEnabled): ?>
        <button onclick="handleOpenPopup()" class="button">Upload File</button>
      <?php endif; ?>
    </div>

    <ul class="lecture_materials-list">
      <?php foreach ($lecture_materials as $lecture_material) : ?>
        <li class="lecture_material">
          <?php echo htmlspecialchars($lecture_material['file_name']); ?>
          <?php if ($adminModeEnabled): ?>
            <div onclick="handleRemoveLectureMaterial('<?php echo $lecture_material['file_name']; ?>')">Remove</div>
          <?php else: ?>
            <a href="request-file.php?path=lecture-materials/<?php echo urlencode(htmlspecialchars($lecture_material['file_name'])); ?>" download><img src="assets/images/download.svg" alt="download"></a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>

    </ul>
  </div>

  <?php include "../includes/toast.php"; ?>

  <?php if ($adminModeEnabled): ?>
    <div class="popup">
      <form action="lecture-materials.php" method="POST" enctype="multipart/form-data">
        <h2>Upload File</h2>
        <div class="close-btn" onclick="handleClosePopup()">
          X
        </div>
        <label for="url">File:</label>
        <input type="file" id="lecture_material" name="lecture_material" accept="application/*" required>

        <input type="submit" class="button" value="Add">
      </form>
    </div>

    <script src="assets/js/popup.js"></script>
    <script>
      function handleRemoveLectureMaterial(file_name) {
        showConfirm("Are you sure you want to remove this lecture material?", (success) => {
          if (success) {
            fetch("lecture-materials.php", {
              method: 'DELETE',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
              },
              body: `file_name=${file_name}`,
            }).then(() => location.reload());
          }
        })
      }
    </script>
  <?php endif; ?>
</body>

</html>