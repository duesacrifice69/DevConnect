<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: ./");
    exit();
}

require_once "../config/db.php";

try {
    $stmt = $db->query("SELECT p.id, title, description, tags, image_id, username as author, 
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
      INNER JOIN users u ON u.id = p.author_id
      ORDER BY created_at DESC
      LIMIT 5");
    $recent_posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $toast = ["type" => "error", "message" => "Error: " . $e->getMessage()];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | DevConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
    <script defer src="assets/js/carousel.js"></script>
</head>

<body>
    <?php include "../includes/sidebar.php"; ?>
    <div class="main-content">
        <?php include "../includes/header.php"; ?>
        <h1>Recent Posts</h1>
        <div class="recent-posts carousel">
            <div class="carousel-container">
                <?php foreach ($recent_posts as $post) : ?>
                    <div class="post carousel-item">
                        <div><span style="font-weight: 600;"><?php echo htmlspecialchars($post["author"]) ?></span> &bull; <?php echo $post["days_posted"] ?>
                        </div>
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
            </div>
        </div>
        <iframe loading="lazy" style="width: 100%;height:220px;" src="https://quotes-github-readme.vercel.app/api?type=horizontal&theme=monokai" frameborder="0" />
    </div>
</body>

</html>