<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../csrf.php';
require_login(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/templates/list.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Невалиден CSRF токен.');
    redirect('/admin/templates/list.php');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0 || !$pdo) {
    set_flash('error', 'Липсващ или невалиден шаблон.');
    redirect('/admin/templates/list.php');
}

$stmt = $pdo->prepare("UPDATE templates SET status = 'approved', updated_at = NOW() WHERE id = :id");
$stmt->execute(['id' => $id]);
set_flash('success', 'Шаблонът е одобрен.');
redirect('/admin/templates/list.php');
