<?php
session_start();
if (!isset($_SESSION["username"])) {
    $_SESSION["redirect_to"] = basename($_SERVER['PHP_SELF']);
    header("Location: index.php");
    exit();
}
