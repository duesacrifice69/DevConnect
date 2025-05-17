<?php
session_start();
if (!isset($_SESSION["username"])) {
    $_SESSION["redirect_to"] = "tutorials";
    header("Location: ../");
    exit();
}

require_once "../config/db.php";

switch ($_SERVER["REQUEST_METHOD"]) {
    case 'GET':
        $tutorials = [];
        try {
            $stmt = $db->prepare("SELECT id, category, url FROM tutorials");
            $stmt->execute();
            $result = $stmt->fetchAll();

            foreach ($result as $row) {
                $category = $row['category'];

                if (!isset($tutorials[$category])) {
                    $tutorials[$category] = [];
                };
                $tutorials[$category][] = ['url' => $row['url'], 'id' => $row['id']];
            }
        } catch (PDOException $e) {
            $toast = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
        }
        break;

    // Add new tutorial
    case 'POST':
        try {
            if (!isset($_SESSION["admin_mode"]) || $_SESSION["admin_mode"] === false) {
                throw new Exception("You do not have permission to perform this action.");
            }
            $url = $_POST['url'];
            $category = $_POST['category'];
            if (empty($url) || empty($category)) {
                throw new Exception("Please fill in all required fields.");
            }
            $stmt = $db->prepare("INSERT INTO tutorials (url, category) VALUES (?, ?)");
            $success = $stmt->execute([$url, $category]);
            if (!$success) {
                throw new Exception("Failed to add tutorial.");
            }
            $_SESSION["toast"] = ['type' => 'success', 'message' => 'Tutorial added successfully.'];
        } catch (Exception $e) {
            $_SESSION["toast"]  = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
        } finally {
            header("Location: tutorials.php");
            exit();
        }
        break;

    // Remove tutorial
    case 'DELETE':
        try {
            if (!isset($_SESSION["admin_mode"]) || $_SESSION["admin_mode"] === false) {
                throw new Exception("You do not have permission to perform this action.");
            }
            $id = $_GET['id'];
            if (empty($id)) {
                throw new Exception("Invalid parameters.");
            }
            $stmt = $db->prepare("DELETE FROM tutorials WHERE id = ?");
            $success = $stmt->execute([$id]);
            if (!$success) {
                throw new Exception("Failed to remove tutorial.");
            }
            $_SESSION["toast"] = ['type' => 'success', 'message' => 'Tutorial removed successfully.'];
            http_response_code(200);
        } catch (Exception $e) {
            $_SESSION["toast"] = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
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
    <title>Tutorials | DevConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
</head>

<body>
    <?php include "../includes/sidebar.php"; ?>
    <div class="main-content">
        <?php include "../includes/header.php"; ?>
        <div style="display: flex;justify-content: space-between;align-items: center;">
            <div>
                <h1>Tutorials</h1>
                <p>Welcome to the educational tutorials for IT students!</p>
            </div>
            <?php if ($adminModeEnabled): ?>
                <button onclick="handleOpenPopup()" class="button">Add Tutorial</button>
            <?php endif; ?>
        </div>
        <br>
        <br>
        <?php foreach ($tutorials as $category => $videos) : ?>
            <h3><?php echo htmlspecialchars($category); ?></h3>
            <br>
            <div>
                <?php foreach ($videos as $video) : ?>
                    <div class="tutorial-wrapper">
                        <iframe width="225" height="150" loading="lazy" src="<?php echo htmlspecialchars($video["url"]); ?>"
                            title="Video player" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                        </iframe>
                        <?php if ($adminModeEnabled): ?>
                            <div class="tutorial-remove" onclick="handleRemoveVideo('<?php echo $video['id']; ?>')">REMOVE</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <br>
            <br>
        <?php endforeach; ?>
    </div>
    <?php include "../includes/toast.php"; ?>

    <?php if ($adminModeEnabled): ?>
        <div class="popup">
            <form action="tutorials.php" class="popup-form" method="POST" enctype="multipart/form-data">
                <h2>Add Tutorial</h2>
                <div class="close-btn" onclick="handleClosePopup()">
                    X
                </div>
                <label for="url">URL:</label>
                <input type="url" id="url" name="url" required>
                <label for="url">Category:</label>
                <input type="text" id="category" name="category" required>
                <input type="submit" class="button" value="Add">
            </form>
        </div>

        <script src="assets/js/popup.js"></script>
        <script>
            function handleRemoveVideo(videoId) {
                showConfirm("Are you sure you want to remove this video?", (success) => {
                    if (success) {
                        fetch("tutorials.php?id=" + videoId, {
                            method: 'DELETE',
                        }).then(() => location.reload());
                    }
                });
            }
        </script>
    <?php endif; ?>
</body>

</html>