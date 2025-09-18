<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_login(['superadmin', 'admin', 'editor']);

$pageTitle = 'Админ табло';
require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/nav.php';
require __DIR__ . '/partials/messages.php';
?>
<div class="card">
    <h2>Добре дошли!</h2>
    <p>Използвайте навигацията за управление на каталози, шаблони и глобални настройки на генератора.</p>
    <div class="actions">
        <a class="btn btn-primary" href="/admin/catalog/grades.php">Каталози</a>
        <a class="btn btn-secondary" href="/admin/templates/list.php">Шаблони</a>
        <a class="btn btn-success" href="/admin/settings.php">Настройки</a>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php';
