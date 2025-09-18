<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../csrf.php';
require_login(['superadmin', 'admin', 'editor']);

if (!$pdo) {
    set_flash('error', 'Базата данни не е достъпна.');
}

$table = $catalogConfig['table'];
$fields = $catalogConfig['fields'];
$title = $catalogConfig['title'];
$redirectPath = $catalogConfig['path'];

$editingItem = null;
$errors = [];

if (isset($_GET['delete'], $_GET['token'])) {
    if (!verify_csrf_token($_GET['token'])) {
        set_flash('error', 'Невалиден CSRF токен при изтриване.');
        redirect($redirectPath);
    }

    $id = (int) $_GET['delete'];
    if ($pdo && $id > 0) {
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        set_flash('success', 'Записът беше изтрит.');
    }

    redirect($redirectPath);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Невалиден CSRF токен.');
        redirect($redirectPath);
    }

    $data = [];
    foreach ($fields as $fieldName => $fieldConfig) {
        $value = trim($_POST[$fieldName] ?? '');
        if (($fieldConfig['required'] ?? true) && $value === '') {
            $errors[] = 'Полето "' . ($fieldConfig['label'] ?? $fieldName) . '" е задължително.';
        }

        if (($fieldConfig['type'] ?? 'string') === 'int' && $value !== '') {
            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                $errors[] = 'Полето "' . ($fieldConfig['label'] ?? $fieldName) . '" трябва да е цяло число.';
            }
        }

        $data[$fieldName] = $value;
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if (empty($errors) && $pdo) {
        if ($id > 0) {
            $sets = [];
            foreach (array_keys($fields) as $fieldName) {
                $sets[] = "{$fieldName} = :{$fieldName}";
            }
            $data['id'] = $id;
            $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            set_flash('success', 'Записът беше обновен.');
        } else {
            $columns = implode(', ', array_keys($fields));
            $placeholders = ':' . implode(', :', array_keys($fields));
            $stmt = $pdo->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})");
            $stmt->execute($data);
            set_flash('success', 'Записът беше създаден.');
        }

        redirect($redirectPath);
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    if ($pdo && $id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $editingItem = $stmt->fetch();
        if (!$editingItem) {
            set_flash('error', 'Записът не беше намерен.');
            redirect($redirectPath);
        }
    }
}

$pageTitle = $title;
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/nav.php';
require __DIR__ . '/../partials/messages.php';
?>
<div class="card" style="padding: 1rem 1.5rem;">
    <strong>Бързи каталози:</strong>
    <?php
    $catalogLinks = [
        '/admin/catalog/grades.php' => 'Класове',
        '/admin/catalog/periods.php' => 'Исторически периоди',
        '/admin/catalog/bloom_levels.php' => 'Блум нива',
        '/admin/catalog/formats.php' => 'Формати',
        '/admin/catalog/assessments.php' => 'Оценяване',
        '/admin/catalog/durations.php' => 'Продължителности',
    ];
    foreach ($catalogLinks as $link => $label):
        ?>
        <a class="btn btn-secondary" style="margin:0.25rem 0.25rem 0 0;" href="<?= sanitize($link); ?>"><?= sanitize($label); ?></a>
    <?php endforeach; ?>
</div>
<div class="card">
    <h2><?= sanitize($title); ?></h2>
    <?php if ($errors): ?>
        <?php foreach ($errors as $error): ?>
            <div class="flash flash-error"><?= sanitize($error); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()); ?>">
        <input type="hidden" name="id" value="<?= $editingItem['id'] ?? ''; ?>">
        <?php foreach ($fields as $fieldName => $fieldConfig): ?>
            <label for="<?= sanitize($fieldName); ?>"><?= sanitize($fieldConfig['label'] ?? $fieldName); ?></label>
            <?php if (($fieldConfig['type'] ?? 'string') === 'int'): ?>
                <input type="number" id="<?= sanitize($fieldName); ?>" name="<?= sanitize($fieldName); ?>" value="<?= isset($editingItem[$fieldName]) ? sanitize((string) $editingItem[$fieldName]) : ''; ?>">
            <?php else: ?>
                <input type="text" id="<?= sanitize($fieldName); ?>" name="<?= sanitize($fieldName); ?>" value="<?= isset($editingItem[$fieldName]) ? sanitize((string) $editingItem[$fieldName]) : ''; ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editingItem ? 'Запази' : 'Добави'; ?></button>
            <?php if ($editingItem): ?>
                <a class="btn btn-secondary" href="<?= sanitize($redirectPath); ?>">Отказ</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2>Съществуващи записи</h2>
    <?php if ($pdo): ?>
        <?php
        $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY id DESC");
        $records = $stmt->fetchAll();
        ?>
        <?php if ($records): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <?php foreach ($fields as $fieldName => $fieldConfig): ?>
                            <th><?= sanitize($fieldConfig['label'] ?? $fieldName); ?></th>
                        <?php endforeach; ?>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= (int) $record['id']; ?></td>
                            <?php foreach (array_keys($fields) as $fieldName): ?>
                                <td><?= sanitize((string) $record[$fieldName]); ?></td>
                            <?php endforeach; ?>
                            <td class="actions">
                                <a class="btn btn-secondary" href="<?= sanitize($redirectPath); ?>?edit=<?= (int) $record['id']; ?>">Редакция</a>
                                <a class="btn btn-danger" href="<?= sanitize($redirectPath); ?>?delete=<?= (int) $record['id']; ?>&token=<?= sanitize(csrf_token()); ?>" onclick="return confirm('Сигурни ли сте, че искате да изтриете този запис?');">Изтриване</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Все още няма записи.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Базата данни не е достъпна.</p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/footer.php';
