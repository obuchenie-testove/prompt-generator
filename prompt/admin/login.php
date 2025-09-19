<?php
session_start();

if (isset($_GET['logout'])) {
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

$configPath = __DIR__ . '/../config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

require_once __DIR__ . '/../db.php';

$error = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $emailValue = $email;

    try {
        $user = row('SELECT id, email, password_hash, role FROM users WHERE email = ? LIMIT 1', array($email));
        if ($user && isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
            $_SESSION['uid'] = $user['id'];
            $_SESSION['role'] = isset($user['role']) ? $user['role'] : null;
            header('Location: /prompt/admin/index.php');
            exit;
        }
        $error = 'Невалидни данни за вход.';
    } catch (Exception $exception) {
        $error = 'Невалидни данни за вход.';
    }
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-container { background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); width: 320px; }
        h1 { margin-top: 0; font-size: 1.4rem; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="email"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background-color: #1e88e5; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        button:hover { background-color: #1565c0; }
        .error { color: #c53030; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Admin Login</h1>
        <?php if ($error !== ''): ?>
            <div class="error"><?php echo h($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo h($emailValue); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Парола</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Вход</button>
        </form>
    </div>
</body>
</html>
