<?php
$max_file_size = 10 * 1024 * 1024; // 10 MB

session_start();
if (!isset($_SESSION["username"])) {
  $_SESSION["redirect_to"] = "forum";
  header("Location: ../");
  exit();
}

require_once "../../config/db.php";
require_once "../../config/cloudinary.php";

use Cloudinary\Api\Upload\UploadApi;


switch ($_SERVER["REQUEST_METHOD"]) {
  case 'GET':
    $edit_post_id = isset($_GET['edit']) ? $_GET['edit'] : null;
    if (!empty($edit_post_id)) {
      try {
        $stmt = $db->prepare("SELECT id, title, description, tags, image_id, author_id FROM posts WHERE id = ?");
        $stmt->execute([$edit_post_id]);
        $edit_post = $stmt->fetch();
        if (!$edit_post) {
          throw new Exception("Post not found.");
        }
        if ($edit_post["author_id"] != $_SESSION["user_id"]) {
          throw new Exception("You do not have permission to edit this post.");
        }
      } catch (Exception $e) {
        $_SESSION["toast"] = ['type' => 'error', 'message' => "Error: " . $e->getMessage()];
        header("Location: ./");
        exit();
      }
    }

    $tag = isset($_GET['tag']) ? $_GET['tag'] : null;
    $posts = [];
    $tags = [];

    try {
      $stmt = $db->prepare("SELECT p.id, title, description, tags, image_id, username as author, 
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
      ORDER BY p.created_at DESC");
      $stmt->execute(!empty($tag) ? ["%$tag%"] : null);
      $posts = $stmt->fetchAll();

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

  // Add or Edit forum post
  case 'POST':
    try {
      $id = $_POST['id'];
      $title = $_POST['title'];
      $description = $_POST['description'];
      $tags = $_POST['tags'];
      $image = $_FILES['image'];
      $image_missing = $image['error'] == UPLOAD_ERR_NO_FILE;

      if (!empty($id)) {
        $stmt = $db->prepare("SELECT public_id, author_id FROM posts p INNER JOIN resources r ON r.id = p.image_id WHERE p.id = ?");
        $stmt->execute([$id]);
        $_post = $stmt->fetch();
        if (!$_post) {
          throw new Exception("Post not found.");
        }
        if ($_post["author_id"] != $_SESSION["user_id"]) {
          throw new Exception("You do not have permission to edit this post.");
        }
      }

      if (empty($title) || empty($description) || ($image_missing && empty($id))) {
        throw new Exception("Please fill in required fields.");
      }

      if (!$image_missing) {
        if ($image['size'] > $max_file_size) {
          throw new Exception("File size exceeds the maximum limit of 10 MB.");
        }
        $result = (new UploadApi())->upload($image['tmp_name'], [
          'asset_folder' => 'DevConnect/Posts',
          'use_asset_folder_as_public_id_prefix' => $_post["public_id"] ? false : true,
          'public_id' => $_post["public_id"] ?? null,
          'overwrite' => true,
          'display_name' => $image['name'],
        ]);

        $query = "INSERT INTO resources (public_id, name, url) VALUES (:public_id, :name, :url) ON DUPLICATE KEY UPDATE name = :name, url = :url";
        $stmt = $db->prepare($query);
        $success = $stmt->execute([
          ':public_id' => $result["public_id"],
          ':name' => $result['display_name'],
          ':url' => $result["secure_url"]
        ]);
        if (!$success) {
          throw new Exception("Failed to create resource.");
        }
        $image_id = $db->lastInsertId();
      }

      $query = "INSERT INTO posts (id, title, description, tags, image_id, author_id) VALUES (:id, :title, :description, :tags, :image_id, :author_id) ON DUPLICATE KEY UPDATE title = :title, description = :description, tags = :tags";
      $stmt = $db->prepare($query);
      $success = $stmt->execute([
        ':id' => $id,
        ':title' => $title,
        ':description' => $description,
        ':tags' => $tags,
        ':image_id' => $image_id ?? 0,
        ':author_id' => $_SESSION["user_id"]
      ]);
      if (!$success) {
        throw new Exception("Failed to " . ($id ? "edit" : "create") . " post.");
      }
      $_SESSION["toast"] = ['type' => 'success', 'message' => 'Post ' . ($id ? "edited" : "created") . ' successfully.'];
    } catch (Exception $e) {
      $_SESSION["toast"] = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
      http_response_code(500);
    } finally {
      header("Location: ./");
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

      $stmt = $db->prepare("SELECT public_id, username as author FROM posts p INNER JOIN users u ON u.id = p.author_id INNER JOIN resources r ON r.id = p.image_id WHERE p.id = ?");
      $stmt->execute([$post_id]);
      $_post = $stmt->fetch();
      if (!$_post) {
        throw new Exception("Post not found.");
      }
      if ($_post["author"] != $_SESSION["username"] && $_SESSION["admin_mode"] != true) {
        throw new Exception("You do not have permission to remove this post.");
      }
      $result = (new UploadApi())->destroy($_post["public_id"], [
        'resource_type' => 'image',
        'invalidate' => true
      ]);
      if ($result["result"] != "ok") {
        throw new Exception("Failed to remove resource.");
      }
      $stmt = $db->prepare("DELETE FROM resources WHERE public_id = ?");
      $success = $stmt->execute([$_post["public_id"]]);
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

<body <?php if (isset($edit_post)): ?> onload="handleOpenPopup()" <?php endif; ?>>
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
        <div><span style="font-weight: 600;"><?php echo htmlspecialchars($post["author"]) ?></span> &bull; <?php echo $post["days_posted"] ?>
        </div>
        <h2><a href="forum/post.php?id=<?php echo htmlspecialchars($post["id"]) ?>">
            <?php echo htmlspecialchars($post["title"]) ?>
          </a></h2>
        <div style="display: flex; align-items: flex-start;">
          <img class="image" loading="lazy" alt="image" src="resource.php?id=<?php echo $post["image_id"] ?>&width=225&height=150" />
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
      <h2>
        <?php echo isset($edit_post) ? "Edit" : "Create a"; ?>
        Post</h2>
      <div class="close-btn" onclick="handleClosePostPopup()">
        X
      </div>
      <?php if (isset($edit_post)): ?>
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_post["id"]) ?>">
      <?php endif; ?>

      <label for="title">Title:</label>
      <input type="text" id="title" name="title" required maxlength="255" value="<?php echo isset($edit_post) ? htmlspecialchars($edit_post["title"]) : ''; ?>">

      <label for="description">Description:</label>
      <textarea name="description" id="description" rows="20" required><?php echo isset($edit_post) ? htmlspecialchars($edit_post["description"]) : ''; ?></textarea>

      <label for="tags">Tags:</label>
      <input type="text" id="tags" name="tags" placeholder="Python, HTML, CSS,..." maxlength="100" value="<?php echo isset($edit_post) ? htmlspecialchars($edit_post["tags"]) : ''; ?>">

      <label for="image">Image:</label>
      <div class="image-uploader" data-id="image" data-name="image" data-required="<?php echo isset($edit_post) ? "false" : "true" ?>" data-src="<?php echo isset($edit_post) ? "resource.php?id=" . $edit_post["image_id"] : null; ?>"></div>

      <input type="submit" class="button" value="<?php echo isset($edit_post) ? "Update" : "Post" ?>">
    </form>
  </div>
  <script src="assets/js/popup.js"></script>
  <script src="assets/js/image-uploader.js"></script>
  <script>
    function handleClosePostPopup() {
      handleClosePopup();
      <?php if (isset($edit_post)): ?>
        setTimeout(() => {
          window.location.href = "forum";
        }, 200);
      <?php endif; ?>
    }
  </script>
</body>

</html>