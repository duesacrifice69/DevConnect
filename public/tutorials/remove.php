<?php
session_start();
if (!isset($_SESSION["username"])) {
    $_SESSION["redirect_to"] = "tutorials/add.php";
    header("Location: ../");
    exit();
}

if (!(isset($_SESSION["admin_mode"]) && $_SESSION["admin_mode"])) {
    header("Location: ../tutorials");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once "../../db.php";
    $id = $_POST['id'];

    try {
        $stmt = $db->prepare("DELETE FROM tutorials WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        $_SESSION["toast"] = ['type' => 'error', 'message' =>  "Something went wrong."];
        http_response_code(500);
        echo json_encode(['error' => "Error: " . $e->getMessage()]);
    }
    $_SESSION["toast"] = ['type' => 'success', 'message' => 'Tutorial removed successfully.'];
    echo json_encode(['success' => true]);
}
