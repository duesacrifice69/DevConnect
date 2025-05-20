<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = parse_ini_file(__DIR__ . '/../.env');

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $dotenv['SMTP_HOST'];
$mail->SMTPAuth = true;
$mail->Username = $dotenv['SMTP_USERNAME'];
$mail->Password = $dotenv['SMTP_PASSWORD'];
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = $dotenv['SMTP_PORT'];
$mail->setFrom($dotenv['SMTP_USERNAME'], "DevConnect");
$mail->isHTML(true);
