<?php
session_start();
if (!isset($_SESSION["username"])) {
    $_SESSION["redirect_to"] = "tutorials";
    header("Location: ../");
    exit();
}

require_once "../../db.php";

$tutorials = [];
try {
    $stmt = $db->prepare("SELECT id, category, url FROM tutorials");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

if (isset($_SESSION["toast"])) {
    $toast = $_SESSION["toast"];
    unset($_SESSION["toast"]);
}

?>

<!----------------------- This is Tutorial page  -------------------------->

<!----------------------- last edit: Geenod 25/03/27 -------------------------->



<!----------------------- 
                 
                 tutorial page completed with HTML and CSS 
                 add links to menu 
                                                              -------------------------->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutorials | DevConnect</title>
    <base href="../">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="assets/images/rocket.svg" type="image/x-icon">
</head>

<body>
    <?php include "../../includes/sidebar.php"; ?>
    <!-- Main Content -->
    <div class="main-content">
        <?php include "../../includes/header.php"; ?>
        <div style="display: flex;justify-content: space-between;align-items: center;">
            <div>
                <h1>Tutorials</h1>
                <p>Welcome to the educational tutorials for IT students!</p>
            </div>
            <?php if ($adminModeEnabled): ?>
                <a href="tutorials/add.php" class="button">Add Tutorial</a>
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
                        <iframe width="225" height="150" src="<?php echo htmlspecialchars($video["url"]); ?>"
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
    <?php include "../../includes/toast.php"; ?>

    <script>
        function handleRemoveVideo(videoId) {
            if (!confirm("Are you sure you want to remove this video?")) {
                return;
            }

            fetch("tutorials/remove.php", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${videoId}`,
                })
                .then(() => location.reload());

        }
    </script>
</body>

</html>


<!----------------------- last edit: Geenod 25/03/27 -------------------------->