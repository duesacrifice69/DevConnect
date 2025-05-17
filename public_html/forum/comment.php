<?php
session_start();
if (!isset($_SESSION["username"])) {
    $_SESSION["redirect_to"] = "forum.php";
    header("Location: ./");
    exit();
}

require_once "../../config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $comment = $_POST["comment"];
    $post_id = $_POST["post_id"];
    $comment_id = $_POST["id"];
    $user_id = $_SESSION["user_id"];

    try {
        if (!empty($comment_id)) {
            $stmt = $db->prepare("SELECT user_id, post_id FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $_comment = $stmt->fetch();
            if (!$_comment) {
                throw new Exception("Comment not found.");
            }
            if ($_comment["user_id"] != $_SESSION["user_id"]) {
                throw new Exception("You do not have permission to edit this comment.");
            }
            $post_id = $_comment["post_id"];
            $user_id = $_comment["user_id"];
        }
        if (empty($comment) || (empty($post_id) && empty($comment_id))) {
            throw new Exception("Missing required fields.");
        }

        $stmt = $db->prepare("INSERT INTO comments (id, post_id, user_id, comment) VALUES (:id, :post_id, :user_id, :comment) ON DUPLICATE KEY UPDATE comment = :comment");
        $stmt->execute([
            'id' => $comment_id,
            'post_id' => $post_id,
            'user_id' => $user_id,
            'comment' => $comment
        ]);
        $_SESSION["toast"] = ['type' => 'success', 'message' => 'Comment ' . ($comment_id ? 'updated' : 'added') . ' successfully.'];
    } catch (Exception $e) {
        $_SESSION["toast"] = ['type' => 'error', 'message' => "Error: " . $e->getMessage()];
    } finally {
        header("Location: ./post.php?id=" . $post_id);
        exit();
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    $comment_id = $_GET["id"];
    try {
        if (empty($comment_id)) {
            throw new Exception("Invalid parameters.");
        }

        $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $_SESSION["toast"] = ['type' => 'success', 'message' => 'Comment removed successfully.'];
        http_response_code(200);
    } catch (Exception $e) {
        $_SESSION["toast"] = ['type' => 'error', 'message' => "Error: " . $e->getMessage()];
        http_response_code(500);
    } finally {
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed."]);
}
