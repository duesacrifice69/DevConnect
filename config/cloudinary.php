<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Configuration\Configuration;

$dotenv = parse_ini_file(__DIR__ . '/../.env');
Configuration::instance("cloudinary://{$dotenv['CLOUDINARY_API_KEY']}:{$dotenv['CLOUDINARY_API_SECRET']}@{$dotenv['CLOUDINARY_CLOUD_NAME']}?secure=true");
