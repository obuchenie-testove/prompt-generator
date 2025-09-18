<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../csrf.php';
require_login(['superadmin', 'admin']);

$errors = [];
$formData = [
    'name' => '',
    'description' => '',
    'content_md' => '',
    'placeholders_json' => '',
    'version' => 1,
    'changelog' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Невалиден CSRF токен.');
        redirect('/admin/templates/list.php');
    }

    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['description'] = trim($_POST['description'] ?? '');
    $formData['content_md'] = trim($_POST['content_md'] ?? '');
    $formData['placeholders_json'] = trim($_POST['placeholders_json'] ?? '');
    $formData['version'] = (int) ($_POST['version'] ?? 1);
    $formData['changelog'] = trim($_POST['changelog'] ?? '');

    if ($formData['name'] === '') {
        $errors[] = 'Името е задължително.';
    }
    if ($formData['content_md'] === '') {
        $errors[] = 'Съдържанието е задължително.';
    }
    if ($formData['version'] < 1) {
        $errors[] = 'Версията трябва да е положително число.';
    }

    if ($formData['placeholders_json'] !== '') {
        $decoded = json_decode($formData['placeholders_json'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            $errors[] = 'Placeholders JSON трябва да е валиден масив.';
        }
    }

    if (!$pdo) {
        $errors[] = 'Базата данни не е достъпна.';
    }

    if (empty($errors) && $pdo) {
        $stmt = $pdo->prepare('INSERT INTO templates (name, description, content_md, placeholders_json, version, changelog, status, created_at, updated_at) VALUES (:name, :description, :content_md, :placeholders_json, :version, :changelog, :status, NOW(), NOW())');
        $stmt->execute([
            'name' => $formData['name'],
            'description' => $formData['description'],
            'content_md' => $formData['content_md'],
            'placeholders_json' => $formData['placeholders_json'],
            'version' => $formData['version'],
            'changelog' => $formData['changelog'],
            'status' => 'draft',
        ]);
        set_flash('success', 'Шаблонът е създаден като чернова.');
        redirect('/admin/templates/list.php');
    }
}

$pageTitle = 'Нов шаблон';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/nav.php';
require __DIR__ . '/../partials/messages.php';
?>
<div class="card">
    <h2>Нов шаблон</h2>
    <?php if ($errors): ?>
        <?php foreach ($errors as $error): ?>
            <div class="flash flash-error"><?= sanitize($error); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()); ?>">
        <label for="name">Име</label>
        <input type="text" id="name" name="name" value="<?= sanitize($formData['name']); ?>" required>

        <label for="description">Описание</label>
        <textarea id="description" name="description" rows="3"><?= sanitize($formData['description']); ?></textarea>

        <label for="content_md">Markdown съдържание</label>
        <textarea id="content_md" name="content_md" rows="12" required><?= sanitize($formData['content_md']); ?></textarea>

        <label for="placeholders_json">Placeholders JSON</label>
        <textarea id="placeholders_json" name="placeholders_json" rows="6" placeholder='[{"key":"topic","type":"string","required":true}]'><?= sanitize($formData['placeholders_json']); ?></textarea>

        <label for="version">Версия</label>
        <input type="number" id="version" name="version" min="1" value="<?= (int) $formData['version']; ?>" required>

        <label for="changelog">Changelog</label>
        <textarea id="changelog" name="changelog" rows="4"><?= sanitize($formData['changelog']); ?></textarea>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Запази</button>
            <a class="btn btn-secondary" href="/admin/templates/list.php">Отказ</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../partials/footer.php';
