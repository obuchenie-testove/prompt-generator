<?php
require_once __DIR__ . '/../../auth.php';
$messages = get_flash_messages();
if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="flash flash-<?= sanitize($message['type']); ?>">
            <?= sanitize($message['message']); ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
