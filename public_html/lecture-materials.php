<?php
$max_file_size = 10 * 1024 * 1024; // 10 MB

session_start();
if (!isset($_SESSION["username"])) {
  $_SESSION["redirect_to"] = "lecture-materials.php";
  header("Location: ./");
  exit();
}
require_once "../config/db.php";
require_once "../config/cloudinary.php";

use Cloudinary\Api\Upload\UploadApi;

switch ($_SERVER["REQUEST_METHOD"]) {
  case 'GET':
    $lecture_materials = [];
    try {
      $stmt = $db->prepare("SELECT * FROM resources INNER JOIN lecture_materials lm ON resources.id = lm.resource_id ORDER BY uploaded_at DESC");
      $stmt->execute();
      $lecture_materials = $stmt->fetchAll();
    } catch (PDOException $e) {
      $toast = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
    }
    break;

  // Add new lecture material
  case 'POST':
    try {
      if (!isset($_SESSION["admin_mode"]) || $_SESSION["admin_mode"] === false) {
        throw new Exception("You do not have permission to perform this action.");
      }

      $file = $_FILES['lecture_material'];
      if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        throw new Exception("No file uploaded.");
      }
      if ($file['size'] > $max_file_size) {
        throw new Exception("File size exceeds the maximum limit of 10 MB.");
      }
      
      $result = (new UploadApi())->upload($file['tmp_name'], [
        'asset_folder' => 'DevConnect/LectureMaterials',
        'resource_type' => 'auto',
        'use_asset_folder_as_public_id_prefix' => true,
        'display_name' => $file['name'],
      ]);

      $stmt = $db->prepare("INSERT INTO resources (public_id, name, url) VALUES (?, ?, ?)");
      $stmt->execute([
        $result["public_id"],
        $file['name'],
        $result["secure_url"]
      ]);
      $file_id = $db->lastInsertId();
      if (!($file_id > 0)) {
        throw new Exception("Failed to add resource.");
      }
      $stmt = $db->prepare("INSERT INTO lecture_materials VALUES (?)");
      $success = $stmt->execute([$file_id]);
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
    try {
      if (!isset($_SESSION["admin_mode"]) || $_SESSION["admin_mode"] === false) {
        throw new Exception("You do not have permission to perform this action.");
      }
      $id = $_GET['id'];
      if (empty($id) || !is_numeric($id)) {
        throw new Exception("Invalid parameters.");
      }
      $stmt = $db->prepare("SELECT public_id FROM resources WHERE id = ?");
      $stmt->execute([$id]);
      $resource = $stmt->fetch();
      if (!$resource) {
        throw new Exception("Resource not found.");
      }
      $result = (new UploadApi())->destroy($resource["public_id"], [
        'invalidate' => true
      ]);
      echo json_encode($result["result"]);
      if ($result["result"] != "ok") {
        throw new Exception("Failed to remove resource.");
      }
      $stmt = $db->prepare("DELETE FROM resources WHERE id = ?");
      $success = $stmt->execute([$id]);
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
          <?php echo htmlspecialchars($lecture_material['name']); ?>
          <?php if ($adminModeEnabled): ?>
            <div onclick="handleRemoveLectureMaterial('<?php echo $lecture_material['id']; ?>')">Remove</div>
          <?php else: ?>
            <a href="<?php echo "resource.php?id=" . $lecture_material['id']; ?>" download><img src="assets/images/download.svg" alt="download"></a>
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
      function handleRemoveLectureMaterial(id) {
        showConfirm("Are you sure you want to remove this lecture material?", (success) => {
          if (success) {
            fetch("lecture-materials.php?id=" + id, {
              method: 'DELETE'
            }).then(() => location.reload());
          }
        })
      }
    </script>
  <?php endif; ?>
</body>

</html>