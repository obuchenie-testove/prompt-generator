<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../csrf.php';

if (is_logged_in()) {
    redirect('/admin/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Невалиден CSRF токен.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Попълнете имейл и парола.';
    }

    if (empty($errors)) {
        if (!$pdo) {
            $errors[] = 'Базата данни не е конфигурирана.';
        } else {
            $stmt = $pdo->prepare('SELECT id, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                login_user($user);
                set_flash('success', 'Успешен вход.');
                redirect('/admin/index.php');
            } else {
                $errors[] = 'Невалидни данни за вход.';
            }
        }
    }
}

$pageTitle = 'Админ вход';
require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/nav.php';
require __DIR__ . '/partials/messages.php';
?>
<div class="card">
    <h2>Вход</h2>
    <?php if ($errors): ?>
        <?php foreach ($errors as $error): ?>
            <div class="flash flash-error"><?= sanitize($error); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()); ?>">
        <label for="email">Имейл</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Парола</label>
        <input type="password" id="password" name="password" required>

        <button class="btn btn-primary" type="submit">Влез</button>
    </form>
</div>
<?php require __DIR__ . '/partials/footer.php';
