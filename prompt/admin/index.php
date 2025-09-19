<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: /prompt/admin/login.php');
    exit;
}

$configPath = __DIR__ . '/../config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background-color: #f9fafb; color: #1a202c; }
        h1 { margin-top: 0; }
        .nav { margin-top: 20px; }
        .nav a { display: inline-block; margin-right: 15px; text-decoration: none; color: #2b6cb0; font-weight: bold; }
        .nav a:hover { text-decoration: underline; }
        .meta { margin-top: 10px; font-size: 0.9rem; color: #4a5568; }
    </style>
</head>
<body>
    <h1>Admin Dashboard</h1>
    <p class="meta">Вие сте влезли като <?php echo htmlspecialchars(isset($_SESSION['role']) ? $_SESSION['role'] : 'user', ENT_QUOTES, 'UTF-8'); ?>.</p>
    <div class="nav">
        <a href="#">Settings (скоро)</a>
        <a href="#">Catalogs (скоро)</a>
        <a href="/prompt/admin/login.php?logout=1">Изход</a>
    </div>
</body>
</html>
