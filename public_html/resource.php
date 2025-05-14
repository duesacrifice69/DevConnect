<?php
$base_dir = '../uploads/';

$path = $_GET['path'];

if (empty($path)) {
    header("HTTP/1.1 400 Bad Request");
    exit("File parameter is required.");
}

session_start();
if (!isset($_SESSION["username"])) {
    $_SESSION["redirect_to"] = "resource.php?path=" . urlencode($path);
    header("Location: ./");
    exit();
}

$file = realpath($base_dir . $path);

if (!file_exists($file)) {
    header("HTTP/1.1 404 Not Found");
    exit("File not found.");
}

header("Content-Type: " . mime_content_type($file));
header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");
header("Content-Length: " . filesize($file));
readfile($file);
exit;
