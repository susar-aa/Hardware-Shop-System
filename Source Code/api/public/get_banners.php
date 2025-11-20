<?php
// This API scans the assets/banner directory and returns a list of images
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$banner_dir = '../../assets/banner/'; // Physical path
$web_path = 'assets/banner/'; // Web accessible path

$images = [];

if (is_dir($banner_dir)) {
    $files = scandir($banner_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $images[] = $web_path . $file;
            }
        }
    }
}

echo json_encode($images);
?>