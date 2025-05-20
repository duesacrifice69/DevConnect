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
$edit_comment_id = isset($_GET['edit_comment']) ? $_GET['edit_comment'] : null;

try {
    if (!empty($edit_comment_id)) {
        try {
            $stmt = $db->prepare("SELECT id, comment, user_id FROM comments WHERE id = ?");
            $stmt->execute([$edit_comment_id]);
            $edit_comment = $stmt->fetch();
            if (!$edit_comment) {
                throw new Exception("Comment not found.");
            }
            if ($edit_comment["user_id"] != $_SESSION["user_id"]) {
                throw new Exception("You do not have permission to edit this comment.");
            }
        } catch (Exception $e) {
            $_SESSION["toast"] = ['type' => 'error', 'message' => "Error: " . $e->getMessage()];
            header("Location: ./");
            exit();
        }
    }

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

    // Fetch comments
    $stmt = $db->prepare("SELECT c.id, c.comment, username as author, 
        IF(DATEDIFF(CURRENT_TIMESTAMP(),c.created_at) = 0,
            IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),c.created_at)) = 0,
                IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),c.created_at)) = 0,
                    'just now',
                    IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),c.created_at)) = 1,
                        '1 min ago',
                        CONCAT(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),c.created_at)), ' mins ago')
                    )
                ),
                IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),c.created_at)) = 1,
                    '1 hour ago',
                    CONCAT(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),c.created_at)), ' hours ago')
                )
            ),
            IF(DATEDIFF(CURRENT_TIMESTAMP(),c.created_at) = 1,
                '1 day ago',
                CONCAT(DATEDIFF(CURRENT_TIMESTAMP(),c.created_at), ' days ago')
            )
        ) 
        AS days_posted,
        IF(c.created_at = c.updated_at,
            NULL,
            IF(DATEDIFF(CURRENT_TIMESTAMP(),c.updated_at) = 0,
                IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),c.updated_at)) = 0,
                    IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),c.updated_at)) = 0,
                        'just now',
                        IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),c.updated_at)) = 1,
                            '1 min ago',
                            CONCAT(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),c.updated_at)), ' mins ago')
                        )
                    ),
                    IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),c.updated_at)) = 1,
                        '1 hour ago',
                        CONCAT(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),c.updated_at)), ' hours ago')
                    )
                ),
                IF(DATEDIFF(CURRENT_TIMESTAMP(),c.updated_at) = 1,
                    '1 day ago',
                    CONCAT(DATEDIFF(CURRENT_TIMESTAMP(),c.updated_at), ' days ago')
                )
            )
        )
        AS days_edited
        FROM comments c 
        INNER JOIN users u ON u.id = c.user_id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC");
    $stmt->execute([$post_id]);
    $post["comments"] = $stmt->fetchAll();
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

<body <?php if (isset($edit_comment)): ?> onload="handleOpenPopup()" <?php endif; ?>>
    <?php include "../../includes/sidebar.php"; ?>
    <div class="main-content">
        <?php include "../../includes/header.php"; ?>

        <button onclick="window.history.back()" class="back-btn"><img src="assets/images/arrow-left.svg" alt="back">
            <h1>Back</h1>
        </button>

        <div class="post full-view">
            <?php if ($post["author"] == $_SESSION["username"] || $adminModeEnabled): ?>
                <button class="more button">&vellip;
                    <div class="menu">
                        <ul>
                            <?php if ($post["author"] == $_SESSION["username"]): ?>
                                <li onclick="handleEditPost()">Edit</li>
                            <?php endif; ?>
                            <li onclick="handleRemovePost('<?php echo $post['id'] ?>')">Remove</li>
                        </ul>
                    </div>
                </button>
            <?php endif; ?>

            <h2><?php echo htmlspecialchars($post["title"]) ?></h2>
            <div style="display: flex;justify-content: space-between;">
                <div class="author">
                    <img src="assets/images/user-circle.svg" alt="user">
                    <?php echo htmlspecialchars($post["author"]) ?>
                </div>
                <div>
                    <span style="font-weight: 600;">Posted </span><?php echo $post["days_posted"] ?>
                    <?php if (isset($post["days_edited"])): ?>
                        &nbsp;&bull;&nbsp;
                        <span style="font-weight: 600;">Edited </span><?php echo $post["days_edited"] ?>
                    <?php endif; ?>
                </div>
            </div>
            <div style="display: flex;justify-content: space-between;">
            </div>
            <img class="image" loading="lazy" alt="image" src="<?php echo "resource.php?id=" . $post["image_id"] ?>" />
            <div>
                <p class="description"><?php echo htmlspecialchars($post["description"]) ?></p>
            </div>

            <?php if (!empty($post["tags"])): ?>
                <div class="tags">
                    <?php foreach (explode(',', $post["tags"]) as $_tag): ?>
                        <a href="<?php echo "forum/index.php?tag=" . htmlspecialchars(trim($_tag)) ?>"><?php echo htmlspecialchars(trim($_tag)) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="comments" id="comments">
            <div class="comments-header">
                <h2>Comments</h2>
                <button class="button" onclick="handleOpenPopup()">Add</button>
            </div>
            <?php if (count($post["comments"]) > 0): ?>
                <?php foreach ($post["comments"] as $comment): ?>
                    <div class="comment">
                        <div class="comment-time">
                            <?php if (isset($comment["days_edited"])): ?>
                                <span style="font-weight: 600;">Edited </span><?php echo $comment["days_edited"] ?>
                            <?php else: ?>
                                <?php echo $comment["days_posted"] ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($comment["author"] == $_SESSION["username"] || $adminModeEnabled): ?>
                            <button class="more button">&vellip;
                                <div class="menu">
                                    <ul>
                                        <?php if ($comment["author"] == $_SESSION["username"]): ?>
                                            <li onclick="handleEditComment('<?php echo $comment['id'] ?>')">Edit</li>
                                        <?php endif; ?>
                                        <li onclick="handleRemoveComment('<?php echo $comment['id'] ?>')">Remove</li>
                                    </ul>
                                </div>
                            </button>
                        <?php endif; ?>
                        <div class="author">
                            <img src="assets/images/user-circle.svg" alt="user">
                            <?php echo htmlspecialchars($comment["author"]) ?>
                        </div>
                        <p class="description"><?php echo htmlspecialchars($comment["comment"]) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="not-found">No comments yet...</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="popup">
        <form action="forum/comment.php" method="POST">
            <h2><?php echo isset($edit_comment) ? "Edit Comment" : "Add Comment" ?></h2>
            <div class="close-btn" onclick="handleCloseCommentPopup()">
                X
            </div>
            <input type="hidden" name="<?php echo isset($edit_comment) ? 'id' : 'post_id' ?>" value="<?php echo isset($edit_comment) ? $edit_comment["id"] : $post['id'] ?>">
            <textarea name="comment" rows="10" required><?php echo isset($edit_comment) ? htmlspecialchars($edit_comment["comment"]) : "" ?></textarea>
            <input type="submit" class="button" value="<?php echo isset($edit_comment) ? "Save" : "Add" ?>">
        </form>
    </div>
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
            window.location.href = "<?php echo "forum/index.php?edit={$post['id']}" ?> ";
        }

        function handleRemoveComment(commentId) {
            showConfirm("Are you sure you want to remove this comment?", (success) => {
                if (success) {
                    fetch("forum/comment.php?id=" + commentId, {
                        method: "DELETE",
                    }).then(location.reload());
                }
            })
        }

        function handleEditComment(commentId) {
            window.location.href = "<?php echo "forum/post.php?id={$post['id']}&edit_comment=" ?>" + commentId;
        }

        function handleCloseCommentPopup() {
            handleClosePopup();
            <?php if (isset($edit_comment)): ?>
                setTimeout(() => {
                    window.location.href = "forum/post.php?id=<?php echo $post['id'] ?>";
                }, 200);
            <?php endif; ?>
        }
    </script>
    <?php include "../../includes/toast.php"; ?>
</body>

</html>