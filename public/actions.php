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
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit();
            break;
    }
}

