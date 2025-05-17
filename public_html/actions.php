<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    switch ($_POST['action']) {
        case 'toggle_admin_mode':
            if (!isset($_SESSION['admin_mode'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized action']);
                exit();
            }

            $_SESSION['admin_mode'] = !$_SESSION['admin_mode'];
            http_response_code(200);
            echo json_encode(['success' => true]);
            exit();

            break;
        case 'create_resource':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized action']);
                exit();
            }
            $public_id = $_POST['public_id'];
            $name = $_POST['name'];
            $mimeType = $_POST['mime_type'];
            $size = $_POST['size'];
            $url = $_POST['url'];

            if (empty($public_id) || empty($name) || empty($mimeType) || empty($size) || empty($url)) {
                http_response_code(400);
                echo json_encode(['error' => 'All fields are required']);
                exit();
            }

            require_once "../config/db.php";
            try {
                $stmt = $db->prepare("INSERT INTO resources (public_id, name, mime_type, size, url, user_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$public_id, $name, $mimeType, $size, $url, $_SESSION['user_id']]);
                if ($stmt->rowCount() == 0) {
                    throw new Exception('Failed to create resource');
                }
                http_response_code(200);
                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
            } finally {
                exit();
            }

            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit();
            break;
    }
}
