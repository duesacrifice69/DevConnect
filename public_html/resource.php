<?php
$id = $_GET['id'];
if (empty($id) || !is_numeric($id)) {
    header("HTTP/1.1 400 Bad Request");
    exit("Invalid or missing ID parameter.");
}

session_start();
if (!isset($_SESSION["username"])) {
    $_SESSION["redirect_to"] = "resource.php?id=" . $id;
    header("Location: ./");
    exit();
}

require_once "../config/db.php";
$stmt = $db->prepare("SELECT name, url FROM resources WHERE id = ?");
$stmt->execute([$id]);
$resource = $stmt->fetch();
if (!$resource) {
    header("HTTP/1.1 404 Not Found");
    exit("Resource not found.");
}
$url = $resource["url"];

// Dymamic image resizing
if (isset($_GET['width']) && isset($_GET['height'])) {
    $width = $_GET['width'];
    $height = $_GET['height'];
    if (is_numeric($width) && is_numeric($height)) {
        $parts = explode("image/upload", $url);

        if (count($parts) > 1) {
            $url = $parts[0] . "image/upload/c_fit,w_" . $width . ",h_" . $height . $parts[1];
        }
    }
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    exit('Failed to fetch image');
}


// Serve the image
header("Content-Type: $contentType");
header('Content-Length: ' . strlen($imageData));
header("Content-Disposition: inline; filename=\"" . htmlspecialchars($resource["name"]) . "\"");
header("Cache-Control: max-age=86400");
echo $imageData;
exit();
