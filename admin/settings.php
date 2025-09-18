<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../csrf.php';
require_login(['superadmin', 'admin']);

$settings = null;
if ($pdo) {
    $stmt = $pdo->query('SELECT * FROM settings ORDER BY updated_at DESC LIMIT 1');
    $settings = $stmt->fetch();
}

$formData = [
    'global_constraints' => $settings['global_constraints'] ?? '',
    'default_competencies' => $settings['default_competencies'] ?? '',
    'default_concepts' => $settings['default_concepts'] ?? '',
    'default_language' => $settings['default_language'] ?? 'BG',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Невалиден CSRF токен.');
        redirect('/admin/settings.php');
    }

    $formData['global_constraints'] = trim($_POST['global_constraints'] ?? '');
    $formData['default_competencies'] = trim($_POST['default_competencies'] ?? '');
    $formData['default_concepts'] = trim($_POST['default_concepts'] ?? '');
    $formData['default_language'] = in_array($_POST['default_language'] ?? 'BG', ['BG', 'EN'], true) ? $_POST['default_language'] : 'BG';

    if (!$pdo) {
        $errors[] = 'Базата данни не е достъпна.';
    }

    if (empty($errors)) {
        if ($settings) {
            $stmt = $pdo->prepare('UPDATE settings SET global_constraints = :global_constraints, default_competencies = :default_competencies, default_concepts = :default_concepts, default_language = :default_language, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                'global_constraints' => $formData['global_constraints'],
                'default_competencies' => $formData['default_competencies'],
                'default_concepts' => $formData['default_concepts'],
                'default_language' => $formData['default_language'],
                'id' => $settings['id'],
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO settings (global_constraints, default_competencies, default_concepts, default_language, updated_at) VALUES (:global_constraints, :default_competencies, :default_concepts, :default_language, NOW())');
            $stmt->execute([
                'global_constraints' => $formData['global_constraints'],
                'default_competencies' => $formData['default_competencies'],
                'default_concepts' => $formData['default_concepts'],
                'default_language' => $formData['default_language'],
            ]);
        }
        set_flash('success', 'Настройките са записани.');
        redirect('/admin/settings.php');
    }
}

$pageTitle = 'Глобални настройки';
require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/nav.php';
require __DIR__ . '/partials/messages.php';
?>
<div class="card">
    <h2>Глобален контекст</h2>
    <?php if ($errors): ?>
        <?php foreach ($errors as $error): ?>
            <div class="flash flash-error"><?= sanitize($error); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()); ?>">

        <label for="global_constraints">Глобални ограничения / контекст</label>
        <textarea id="global_constraints" name="global_constraints" rows="5"><?= sanitize($formData['global_constraints']); ?></textarea>

        <label for="default_competencies">Компетентности по подразбиране</label>
        <textarea id="default_competencies" name="default_competencies" rows="4"><?= sanitize($formData['default_competencies']); ?></textarea>

        <label for="default_concepts">Ключови понятия по подразбиране</label>
        <textarea id="default_concepts" name="default_concepts" rows="4"><?= sanitize($formData['default_concepts']); ?></textarea>

        <label for="default_language">Език по подразбиране</label>
        <select id="default_language" name="default_language">
            <option value="BG" <?= $formData['default_language'] === 'BG' ? 'selected' : ''; ?>>Български</option>
            <option value="EN" <?= $formData['default_language'] === 'EN' ? 'selected' : ''; ?>>Английски</option>
        </select>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Запази</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/partials/footer.php';
