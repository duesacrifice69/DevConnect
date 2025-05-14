<?php
$upload_dir = "../../uploads/";
$max_size_mb = 10;

session_start();
if (!isset($_SESSION["username"])) {
  $_SESSION["redirect_to"] = "forum";
  header("Location: ../");
  exit();
}

require_once "../../db.php";

switch ($_SERVER["REQUEST_METHOD"]) {
  case 'GET':
    $tag = isset($_GET['tag']) ? $_GET['tag'] : null;
    $posts = [];
    $tags = [];

    try {
      $stmt = $db->prepare("SELECT p.id, title, description, tags, image_path, username as author, 
      IF(DATEDIFF(CURRENT_TIMESTAMP(),p.created_at) = 0,
        IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),p.created_at)) = 0,
          IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),p.created_at)) = 0,
            'just now',
            IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),p.created_at)) = 1,
              '1 min ago',
              CONCAT(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),p.created_at)), ' mins ago')
            )
          ),
          IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),p.created_at)) = 1,
            '1 hour ago',
            CONCAT(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),p.created_at)), ' hours ago')
          )
        ),
        IF(DATEDIFF(CURRENT_TIMESTAMP(),p.created_at) = 1,
          '1 day ago',
          CONCAT(DATEDIFF(CURRENT_TIMESTAMP(),p.created_at), ' days ago')
        )
      ) 
      AS days_posted FROM posts p 
      INNER JOIN users u ON u.id = p.author_id" . (!empty($tag) ? " WHERE tags LIKE ?" : "") . "
      ORDER BY created_at DESC");
      $stmt->execute(!empty($tag) ? ["%$tag%"] : null);
      $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (empty($tag)) {
        // Get all unique tags from posts
        foreach ($posts as $post) {
          $items = explode(',', $post["tags"]);
          foreach ($items as $item) {
            $trimmed = trim($item);
            if (!empty($trimmed)) {
              $tags[$trimmed] = true;
            }
          }
        }
        $tags = array_keys($tags);
      }
    } catch (PDOException $e) {
      $toast = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
    }
    break;

  // Add new forum post
  case 'POST':
    try {
      $title = $_POST['title'];
      $description = $_POST['description'];
      $tags = $_POST['tags'];
      $image = $_FILES['image'];
      if (empty($title) || empty($description) || $image['error'] == UPLOAD_ERR_NO_FILE) {
        throw new Exception("Please fill in required fields.");
      }

      $file_name = uniqid() . "_" . basename($image['name']);

      if ($image['size'] > $max_size_mb * 1024 * 1024) {
        throw new Exception("Maximum file size is " . $max_size_mb . " MB.");
      }

      if ($image['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("There was a problem uploading your image.");
      }

      $file_destination = $upload_dir . "posts/" . $file_name;

      if (!move_uploaded_file($image['tmp_name'], $file_destination)) {
        throw new Exception("Failed to move uploaded image.");
      }
      $stmt = $db->prepare("INSERT INTO posts (title, description, tags, image_path, author_id) VALUES (?,?, ?, ?, ?)");
      $success = $stmt->execute([
        $title,
        $description,
        $tags,
        "posts/" . $file_name,
        $_SESSION["user_id"]
      ]);
      if (!$success) {
        throw new Exception("Failed to create post.");
      }
      $_SESSION["toast"] = ['type' => 'success', 'message' => 'Post created successfully.'];
    } catch (Exception $e) {
      $_SESSION["toast"] = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
      http_response_code(500);
    } finally {
      header("Location: ../forum");
      exit();
    }

    break;

  // Remove forum post
  case 'DELETE':
    try {
      $post_id = $_GET['id'];
      if (empty($post_id)) {
        throw new Exception("Invalid parameters.");
      }

      $stmt = $db->prepare("SELECT image_path, username as author FROM posts p INNER JOIN users u ON u.id = p.author_id  WHERE p.id = ?");
      $stmt->execute([$post_id]);
      $_post = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$_post) {
        throw new Exception("Post not found.");
      }
      if ($_post["author"] != $_SESSION["username"] && $_SESSION["admin_mode"] != true) {
        throw new Exception("You do not have permission to remove this post.");
      }

      $file_path = $upload_dir . $_post["image_path"];
      unlink($file_path);
      $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
      $success = $stmt->execute([$post_id]);
      if (!$success) {
        throw new Exception("Failed to remove post.");
      }
      $_SESSION["toast"] = ['type' => 'success', 'message' => 'Post removed successfully.'];
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
  <title>Forum | DevConnect</title>
  <base href="../">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
</head>

<body>
  <?php include "../../includes/sidebar.php"; ?>
  <div class="main-content">
    <?php include "../../includes/header.php"; ?>
    <div style="display: flex;justify-content: space-between;">
      <h1>Forum</h1>
      <button onclick="handleOpenPopup()" class="button">Create Post</button>
    </div>

    <div style="margin: 40px 0;" class="tags">
      <?php foreach ($tags as $__tag): ?>
        <a href="<?php echo "forum/index.php?tag=" . htmlspecialchars($__tag) ?>"><?php echo htmlspecialchars($__tag) ?></a>
      <?php endforeach; ?>
      <?php if (!empty($tag)): ?>
        <a href="forum"><?php echo htmlspecialchars($tag) ?>&nbsp;&#10006;</a>
      <?php endif; ?>
    </div>

    <?php foreach ($posts as $post): ?>
      <div class="post">
        <div><span class="author"><?php echo htmlspecialchars($post["author"]) ?></span> &bull; <?php echo $post["days_posted"] ?>
        </div>
        <h2><a href="forum/post.php?id=<?php echo htmlspecialchars($post["id"]) ?>">
            <?php echo htmlspecialchars($post["title"]) ?>
          </a></h2>
        <div style="display: flex; align-items: flex-start;">
          <img class="image" alt="image" src="resource.php?path=<?php echo htmlspecialchars($post["image_path"]) ?>" />
          <div>
            <p class="description"><?php echo htmlspecialchars($post["description"]) ?></p>
            <?php if (!empty($post["tags"])): ?>
              <div class="tags">
                <?php foreach (explode(',', $post["tags"]) as $_tag): ?>
                  <a href="<?php echo "forum/index.php?tag=" . htmlspecialchars(trim($_tag)) ?>"><?php echo htmlspecialchars(trim($_tag)) ?></a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

  </div>
  <?php include "../../includes/toast.php"; ?>

  <div class="popup">
    <form action="forum/index.php" method="POST" enctype="multipart/form-data" style="width: 600px;">
      <h2>Create a Post</h2>
      <div class="close-btn" onclick="handleClosePopup()">
        X
      </div>
      <label for="title">Title:</label>
      <input type="text" id="title" name="title" required maxlength="255">

      <label for="description">Description:</label>
      <textarea name="description" id="description" rows="8" required></textarea>

      <label for="tags">Tags:</label>
      <input type="text" id="tags" name="tags" placeholder="Python, HTML, CSS,..." maxlength="100">

      <label for="url">Image:</label>
      <input type="file" id="image" name="image" accept="image/*" required>

      <input type="submit" class="button" value="Post">
    </form>
  </div>
  <script src="assets/js/popup.js"></script>
</body>

</html>