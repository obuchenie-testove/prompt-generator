<?php
function ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start([
            'cookie_httponly' => true,
            'use_strict_mode' => true,
        ]);
    }
}

ensure_session();

function login_user(array $user): void
{
    ensure_session();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'] ?? null,
        'email' => $user['email'] ?? null,
        'role' => $user['role'] ?? 'viewer',
    ];
}

function logout_user(): void
{
    ensure_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function current_user(): ?array
{
    ensure_session();
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function user_has_role(array $roles): bool
{
    if (empty($roles)) {
        return true;
    }

    $user = current_user();
    if (!$user) {
        return false;
    }

    return in_array($user['role'], $roles, true);
}

function require_login(array $roles = []): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Моля, влезте в акаунта си.');
        header('Location: /admin/login.php');
        exit;
    }

    if (!user_has_role($roles)) {
        set_flash('error', 'Нямате права за достъп до тази страница.');
        header('Location: /admin/index.php');
        exit;
    }
}

function set_flash(string $type, string $message): void
{
    ensure_session();
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash_messages(): array
{
    ensure_session();
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
