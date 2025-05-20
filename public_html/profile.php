<?php
session_start();
if (!isset($_SESSION["username"])) {
    $_SESSION["redirect_to"] = "profile.php";
    header("Location: ./");
    exit();
}
require_once "../config/db.php";

try {
    // User's posts
    $stmt = $db->prepare("SELECT id, title, description, tags, image_id, 
    IF(DATEDIFF(CURRENT_TIMESTAMP(),created_at) = 0,
      IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),created_at)) = 0,
        IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),created_at)) = 0,
          'just now',
          IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),created_at)) = 1,
            '1 min ago',
            CONCAT(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),created_at)), ' mins ago')
          )
        ),
        IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),created_at)) = 1,
          '1 hour ago',
          CONCAT(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),created_at)), ' hours ago')
        )
      ),
      IF(DATEDIFF(CURRENT_TIMESTAMP(),created_at) = 1,
        '1 day ago',
        CONCAT(DATEDIFF(CURRENT_TIMESTAMP(),created_at), ' days ago')
      )
    ) 
    AS days_posted FROM posts
    WHERE author_id = ?
    ORDER BY created_at DESC");
    $stmt->execute([$_SESSION["user_id"]]);
    $data["posts"] = $stmt->fetchAll();

    // User's comments
    $stmt = $db->prepare("SELECT id, comment, post_id, 
    IF(DATEDIFF(CURRENT_TIMESTAMP(),created_at) = 0,
        IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),created_at)) = 0,
            IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),created_at)) = 0,
                'just now',
                IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),created_at)) = 1,
                    '1 min ago',
                    CONCAT(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),created_at)), ' mins ago')
                )
            ),
            IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),created_at)) = 1,
                '1 hour ago',
                CONCAT(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),created_at)), ' hours ago')
            )
        ),
        IF(DATEDIFF(CURRENT_TIMESTAMP(),created_at) = 1,
            '1 day ago',
            CONCAT(DATEDIFF(CURRENT_TIMESTAMP(),created_at), ' days ago')
        )
    ) 
    AS days_posted,
    IF(created_at = updated_at,
        NULL,
        IF(DATEDIFF(CURRENT_TIMESTAMP(),updated_at) = 0,
            IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),updated_at)) = 0,
                IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),updated_at)) = 0,
                    'just now',
                    IF(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),updated_at)) = 1,
                        '1 min ago',
                        CONCAT(MINUTE(TIMEDIFF(CURRENT_TIMESTAMP(),updated_at)), ' mins ago')
                    )
                ),
                IF(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),updated_at)) = 1,
                    '1 hour ago',
                    CONCAT(HOUR(TIMEDIFF(CURRENT_TIMESTAMP(),updated_at)), ' hours ago')
                )
            ),
            IF(DATEDIFF(CURRENT_TIMESTAMP(),updated_at) = 1,
                '1 day ago',
                CONCAT(DATEDIFF(CURRENT_TIMESTAMP(),updated_at), ' days ago')
            )
        )
    )
    AS days_edited,
    (SELECT username FROM users WHERE id = (SELECT author_id FROM posts WHERE id = post_id)) AS post_author
    FROM comments c 
    WHERE user_id = ?
    ORDER BY created_at DESC");
    $stmt->execute([$_SESSION["user_id"]]);
    $data["comments"] = $stmt->fetchAll();

    // User's profile details
    $stmt = $db->prepare("SELECT username, email, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION["user_id"]]);
    $data["profile"] = $stmt->fetch();
    $data["profile"]["created_at"] = date("F j, Y", strtotime($data["profile"]["created_at"]));
} catch (PDOException $e) {
    $toast = ["type" => "error", "message" => "Error: " . $e->getMessage()];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | DevConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
</head>

<body>
    <?php include "../includes/sidebar.php"; ?>
    <div class="main-content">
        <?php include "../includes/header.php"; ?>
        <div class="profile">
            <h1>Profile</h1>
            <div class="profile-details">
                <img src="assets/images/user.svg" alt="Profile Image" />
                <div>
                    <p><span class="label">Username:</span> <?php echo htmlspecialchars($data["profile"]["username"]) ?></p>
                    <p><span class="label">Email:</span> <?php echo htmlspecialchars($data["profile"]["email"]) ?></p>
                    <p style="text-transform: capitalize;"><span class="label">Role:</span> <?php echo htmlspecialchars($data["profile"]["role"]) ?></p>
                    <p><span class="label">Joined on:</span> <?php echo htmlspecialchars($data["profile"]["created_at"]) ?></p>
                </div>
            </div>
        </div>
        <div class="activity">
            <h1>Your Activity</h1>

            <div>
                <h2>Posts</h2>
                <div>
                    <?php if (count($data["posts"]) > 0) : ?>
                        <?php foreach ($data["posts"] as $post) : ?>
                            <div class="post">
                                <div><?php echo $post["days_posted"] ?></div>
                                <h2><a href="forum/post.php?id=<?php echo htmlspecialchars($post["id"]) ?>">
                                        <?php echo htmlspecialchars($post["title"]) ?>
                                    </a></h2>
                                <div style="display: flex; align-items: flex-start;">
                                    <img class="image" loading="lazy" alt="image" src="<?php echo "resource.php?id=" . $post["image_id"] ?>" />
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
                    <?php else : ?>
                        <p class="not-found">No posts found</p>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <h2>Comments</h2>
                <div>
                    <?php if (count($data["comments"]) > 0) : ?>
                        <?php foreach ($data["comments"] as $comment) : ?>
                            <div style="margin-top: 20px;">
                                Commented on <a href="forum/post.php?id=<?php echo htmlspecialchars($comment["post_id"]) ?>#comments">
                                    <?php echo htmlspecialchars($comment["post_author"]) ?>'s post
                                </a>
                                <div class="comment">
                                    <div class="comment-time">
                                        <?php if (isset($comment["days_edited"])): ?>
                                            <span style="font-weight: 600;">Edited </span><?php echo $comment["days_edited"] ?>
                                        <?php else: ?>
                                            <?php echo $comment["days_posted"] ?>
                                        <?php endif; ?>
                                    </div>
                                    <p class="description"><?php echo htmlspecialchars($comment["comment"]) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="not-found">No comments found</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</body>

</html>