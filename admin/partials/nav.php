<?php
require_once __DIR__ . '/../../auth.php';
$user = current_user();
?>
<nav>
    <?php if ($user): ?>
        <a href="/admin/index.php">Табло</a>
        <a href="/admin/catalog/grades.php">Каталози</a>
        <a href="/admin/templates/list.php">Шаблони</a>
        <a href="/admin/settings.php">Настройки</a>
        <a href="/admin/logout.php">Изход (<?= sanitize($user['email']); ?>)</a>
    <?php else: ?>
        <a href="/admin/login.php">Вход</a>
    <?php endif; ?>
</nav>
<div class="container">
