<?php
// Set a default title if one isn't provided by the main page
if (!isset($page_title)) {
    $page_title = 'DMA ELECTRICALS';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- The $page_title variable is set on each page (e.g., products.php) -->
    <title><?php echo htmlspecialchars($page_title); ?> - DMA ELECTRICALS</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { transition: transform 0.3s ease-in-out; }
        .modal { transition: opacity 0.25s ease, visibility 0.25s ease; }
        .modal-content { transition: transform 0.25s ease; }
    </style>
</head>
<body class="bg-gray-100 flex">