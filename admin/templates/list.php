<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../csrf.php';
require_login(['superadmin', 'admin', 'editor']);

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(name LIKE :search OR description LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($statusFilter !== '' && in_array($statusFilter, ['draft', 'approved', 'deprecated'], true)) {
    $conditions[] = 'status = :status';
    $params['status'] = $statusFilter;
}

$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$total = 0;
$templates = [];

if ($pdo) {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM templates {$whereSql}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $sql = "SELECT * FROM templates {$whereSql} ORDER BY updated_at DESC, id DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $templates = $stmt->fetchAll();
}

$totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

$pageTitle = 'Шаблони';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/nav.php';
require __DIR__ . '/../partials/messages.php';
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <h2>Списък с шаблони</h2>
        <a class="btn btn-primary" href="/admin/templates/create.php">Нов шаблон</a>
    </div>
    <form method="get" style="margin-top:1rem;display:flex;gap:0.75rem;flex-wrap:wrap;">
        <div>
            <label for="q">Търсене</label>
            <input type="text" id="q" name="q" value="<?= sanitize($search); ?>">
        </div>
        <div>
            <label for="status">Статус</label>
            <select id="status" name="status">
                <option value="">Всички</option>
                <?php foreach (['draft' => 'Чернова', 'approved' => 'Одобрен', 'deprecated' => 'Оттеглен'] as $value => $label): ?>
                    <option value="<?= $value; ?>" <?= $statusFilter === $value ? 'selected' : ''; ?>><?= sanitize($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="align-self:flex-end;">
            <button class="btn btn-secondary" type="submit">Филтрирай</button>
        </div>
    </form>

    <?php if (!$pdo): ?>
        <p>Базата данни не е достъпна.</p>
    <?php elseif (!$templates): ?>
        <p>Няма намерени шаблони.</p>
    <?php else: ?>
        <table style="margin-top:1rem;">
            <thead>
                <tr>
                    <th>Име</th>
                    <th>Описание</th>
                    <th>Версия</th>
                    <th>Статус</th>
                    <th>Последна промяна</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $template): ?>
                    <tr>
                        <td><?= sanitize($template['name']); ?></td>
                        <td><?= sanitize(mb_strimwidth((string) $template['description'], 0, 120, '...')); ?></td>
                        <td><?= (int) $template['version']; ?></td>
                        <td>
                            <span class="status-badge status-<?= sanitize($template['status']); ?>"><?= sanitize($template['status']); ?></span>
                        </td>
                        <td><?= sanitize($template['updated_at']); ?></td>
                        <td class="actions">
                            <a class="btn btn-secondary" href="/admin/templates/edit.php?id=<?= (int) $template['id']; ?>">Редакция</a>
                            <?php if ($template['status'] !== 'approved'): ?>
                                <form method="post" action="/admin/templates/approve.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?= (int) $template['id']; ?>">
                                    <button class="btn btn-success" type="submit">Одобри</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($template['status'] !== 'deprecated'): ?>
                                <form method="post" action="/admin/templates/deprecate.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?= (int) $template['id']; ?>">
                                    <button class="btn btn-danger" type="submit">Оттегли</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($totalPages > 1): ?>
            <div style="margin-top:1rem;display:flex;gap:0.5rem;flex-wrap:wrap;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php $query = http_build_query(['page' => $i, 'q' => $search, 'status' => $statusFilter]); ?>
                    <a class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary'; ?>" href="?<?= sanitize($query); ?>">Страница <?= $i; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/footer.php';
