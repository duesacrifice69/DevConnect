<?php
session_start();
if (!isset($_SESSION["username"])) {
    $_SESSION["redirect_to"] = "forum.php";
    header("Location: ./");
    exit();
}

require_once "../../config/db.php";
$post_id = $_GET['id'];

if (!isset($post_id)) {
    header("Location: ./");
    exit();
}

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
        AS days_posted,
        IF(p.created_at = p.updated_at,
            NULL,
            IF(DATEDIFF(CURRENT_TIMESTAMP(),p.updated_at) = 0,
                IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),p.updated_at)) = 0,
                    IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),p.updated_at)) = 0,
                        'just now',
                        IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),p.updated_at)) = 1,
                            '1 min ago',
                            CONCAT(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),p.updated_at)), ' mins ago')
                        )
                    ),
                    IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),p.updated_at)) = 1,
                        '1 hour ago',
                        CONCAT(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),p.updated_at)), ' hours ago')
                    )
                ),
                IF(DATEDIFF(CURRENT_TIMESTAMP(),p.updated_at) = 1,
                    '1 day ago',
                    CONCAT(DATEDIFF(CURRENT_TIMESTAMP(),p.updated_at), ' days ago')
                )
            )
        )
        AS days_edited
        FROM posts p 
        INNER JOIN users u ON u.id = p.author_id
        WHERE p.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        header("Location: ./");
        exit();
    }
} catch (PDOException $e) {
    $toast = ['type' => 'error', 'message' =>  "Error: " . $e->getMessage()];
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

        <a href="./forum" class="back-btn"><img src="assets/images/arrow-left.svg" alt="back">
            <h1>Back</h1>
        </a>

        <div class="post full-view">
            <?php if ($post["author"] == $_SESSION["username"] || $adminModeEnabled): ?>
                <button class="more button">&vellip;
                    <div class="menu">
                        <ul>
                            <?php if ($post["author"] == $_SESSION["username"]): ?>
                                <li onclick="handleEditPost()">Edit</li>
                            <?php endif; ?>
                            <li onclick="handleRemovePost('<?php echo htmlspecialchars($post['id']) ?>')">Remove</li>
                        </ul>
                    </div>
                </button>
            <?php endif; ?>

            <h2><?php echo htmlspecialchars($post["title"]) ?></h2>
            <div style="display: flex;justify-content: space-between;">
                <div>
                    <span style="font-weight: 600;">Posted </span><?php echo $post["days_posted"] ?>
                    <?php if (isset($post["days_edited"])): ?>
                        &nbsp;&bull;&nbsp;
                        <span style="font-weight: 600;">Edited </span><?php echo $post["days_edited"] ?>
                    <?php endif; ?>
                </div>
                <div>
                    <span style="font-weight: 600;">By </span><a style="color:lightskyblue;text-decoration: none;" href="forum/user?username=<?php echo htmlspecialchars($post["author"]) ?>"><?php echo htmlspecialchars($post["author"]) ?></a>
                </div>
            </div>
            <?php if (!empty($post["tags"])): ?>
                <div class="tags">
                    <?php foreach (explode(',', $post["tags"]) as $_tag): ?>
                        <a href="<?php echo "forum/index.php?tag=" . htmlspecialchars(trim($_tag)) ?>"><?php echo htmlspecialchars(trim($_tag)) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div style="display: flex;justify-content: space-between;">
            </div>
            <img class="image" loading="lazy" alt="image" src="<?php echo "resource.php?id=" . $post["image_id"] ?>" />
            <div>
                <p class="description"><?php echo htmlspecialchars($post["description"]) ?></p>
            </div>
            </a>
        </div>

    </div>
    <?php include "../../includes/toast.php"; ?>

    <?php if ($post["author"] == $_SESSION["username"] || $adminModeEnabled): ?>
        <script src="assets/js/popup.js"></script>
        <script>
            function handleRemovePost(postId) {
                showConfirm("Are you sure you want to remove this post?", (success) => {
                    if (success) {
                        fetch("forum/index.php?id=" + postId, {
                            method: "DELETE",
                        }).then(location.reload());
                    }
                })
            }

            function handleEditPost() {
                window.location.href = "<?php echo "forum/index.php?edit=" . $post['id'] ?> ";
            }
        </script>
    <?php endif; ?>
</body>

</html>