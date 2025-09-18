<?php
$fallbackSettings = [
    'global_constraints' => "Спазвай добри практики за структурирането на учебно съдържание и използвай ясен, подкрепящ тон.",
    'default_competencies' => "Критическо мислене; Работа с исторически източници; Устна комуникация;",
    'default_concepts' => "История; Култура; Общество;",
    'default_language' => 'BG',
];

$fallbackCatalogs = [
    'grades' => [
        ['id' => 1, 'name' => '5. клас'],
        ['id' => 2, 'name' => '6. клас'],
        ['id' => 3, 'name' => '7. клас'],
    ],
    'periods' => [
        ['id' => 1, 'name' => 'Древни цивилизации'],
        ['id' => 2, 'name' => 'Средновековие'],
        ['id' => 3, 'name' => 'Възраждане'],
    ],
    'bloom_levels' => [
        ['id' => 1, 'name' => 'Разбиране'],
        ['id' => 2, 'name' => 'Приложение'],
        ['id' => 3, 'name' => 'Анализ'],
    ],
    'formats' => [
        ['id' => 1, 'name' => 'Работен лист'],
        ['id' => 2, 'name' => 'Проект'],
    ],
    'assessments' => [
        ['id' => 1, 'name' => 'Формативно оценяване'],
        ['id' => 2, 'name' => 'Самооценка'],
    ],
    'durations' => [
        ['id' => 1, 'label' => 'Кратка активност', 'minutes' => 15],
        ['id' => 2, 'label' => 'Стандартен урок', 'minutes' => 40],
    ],
];

$fallbackTemplate = [
    'name' => 'Стандартен шаблон',
    'content_md' => <<<MD
# Работен лист: {{topic}}

## Исторически период
- Период: {{period}}
- Основни понятия: {{concepts}}

## Материали
{{materials}}

## Изисквания
{{requirements}}

## Очаквани компетентности
{{competencies}}

## Ниво по Блум
{{bloom_level}}

## Формат
{{format}}

## Продължителност
{{duration}}

## Оценяване
{{assessment}}

## Език
{{language}}
MD,
    'placeholders' => [
        ['key' => 'topic', 'label' => 'Тема', 'type' => 'string', 'required' => true],
        ['key' => 'period', 'label' => 'Период', 'type' => 'enum', 'source' => 'periods', 'required' => true],
        ['key' => 'concepts', 'label' => 'Ключови понятия', 'type' => 'tags', 'required' => false],
        ['key' => 'materials', 'label' => 'Материали (URL)', 'type' => 'urls', 'required' => false],
        ['key' => 'requirements', 'label' => 'Изисквания', 'type' => 'markdown', 'required' => true],
        ['key' => 'competencies', 'label' => 'Компетентности', 'type' => 'markdown', 'required' => false],
        ['key' => 'bloom_level', 'label' => 'Ниво по Блум', 'type' => 'enum', 'source' => 'bloom_levels', 'required' => false],
        ['key' => 'format', 'label' => 'Формат', 'type' => 'enum', 'source' => 'formats', 'required' => false],
        ['key' => 'duration', 'label' => 'Продължителност', 'type' => 'enum', 'source' => 'durations', 'required' => false],
        ['key' => 'assessment', 'label' => 'Оценяване', 'type' => 'enum', 'source' => 'assessments', 'required' => false],
        ['key' => 'language', 'label' => 'Език', 'type' => 'enum', 'options' => ['BG', 'EN'], 'required' => true],
    ],
];

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function load_database(): array
{
    $pdo = null;
    $error = null;
    if (file_exists(__DIR__ . '/config.php')) {
        try {
            require __DIR__ . '/config.php';
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
            $pdo = null;
        }
    }

    if (!isset($pdo) || !$pdo instanceof PDO) {
        $pdo = null;
    }

    return [$pdo, $error ?? null];
}

function fetch_catalog(PDO $pdo, string $table): array
{
    $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY name ASC");
    return $stmt->fetchAll();
}

function fetch_durations(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM durations ORDER BY minutes ASC');
    return $stmt->fetchAll();
}

function fetch_settings(PDO $pdo): ?array
{
    $stmt = $pdo->query('SELECT * FROM settings ORDER BY updated_at DESC LIMIT 1');
    return $stmt->fetch() ?: null;
}

function fetch_latest_template(PDO $pdo): ?array
{
    $stmt = $pdo->query("SELECT * FROM templates WHERE status = 'approved' ORDER BY updated_at DESC, id DESC LIMIT 1");
    return $stmt->fetch() ?: null;
}

[$pdo, $pdoError] = load_database();
$databaseActive = $pdo instanceof PDO;

$settings = $fallbackSettings;
$catalogs = $fallbackCatalogs;
$template = $fallbackTemplate;
$templateSource = 'fallback';

if ($databaseActive) {
    try {
        if ($dbSettings = fetch_settings($pdo)) {
            $settings = array_merge($settings, array_filter($dbSettings, fn($value) => $value !== null));
        }

        $catalogsFromDb = [
            'grades' => fetch_catalog($pdo, 'grades'),
            'periods' => fetch_catalog($pdo, 'periods'),
            'bloom_levels' => fetch_catalog($pdo, 'bloom_levels'),
            'formats' => fetch_catalog($pdo, 'formats'),
            'assessments' => fetch_catalog($pdo, 'assessments'),
            'durations' => fetch_durations($pdo),
        ];

        foreach ($catalogsFromDb as $key => $rows) {
            if (!empty($rows)) {
                $catalogs[$key] = $rows;
            }
        }

        if ($latestTemplate = fetch_latest_template($pdo)) {
            $placeholders = json_decode($latestTemplate['placeholders_json'] ?? '', true);
            if (is_array($placeholders) && !empty($placeholders)) {
                $template = [
                    'name' => $latestTemplate['name'],
                    'content_md' => $latestTemplate['content_md'],
                    'placeholders' => $placeholders,
                ];
            } else {
                $template = [
                    'name' => $latestTemplate['name'],
                    'content_md' => $latestTemplate['content_md'],
                    'placeholders' => $fallbackTemplate['placeholders'],
                ];
            }
            $templateSource = 'database';
        }
    } catch (Throwable $exception) {
        $databaseActive = false;
        $pdoError = $exception->getMessage();
        $settings = $fallbackSettings;
        $catalogs = $fallbackCatalogs;
        $template = $fallbackTemplate;
        $templateSource = 'fallback';
    }
}

function placeholder_options(array $placeholder, array $catalogs): array
{
    $options = [];
    if (!empty($placeholder['options']) && is_array($placeholder['options'])) {
        foreach ($placeholder['options'] as $option) {
            $label = is_array($option) ? ($option['label'] ?? $option['value'] ?? '') : (string) $option;
            $value = is_array($option) ? ($option['value'] ?? $label) : (string) $option;
            if ($label !== '') {
                $options[] = ['value' => $value, 'label' => $label];
            }
        }
    }

    if (!empty($placeholder['source']) && isset($catalogs[$placeholder['source']])) {
        foreach ($catalogs[$placeholder['source']] as $item) {
            $label = $item['name'] ?? $item['label'] ?? '';
            if ($label === '') {
                continue;
            }
            $value = $label;
            if ($placeholder['source'] === 'durations') {
                $minutes = $item['minutes'] ?? null;
                if ($minutes !== null) {
                    $value = sprintf('%s (%s мин.)', $label, $minutes);
                }
            }
            $options[] = ['value' => $value, 'label' => $value];
        }
    }

    return $options;
}

function normalize_array_field(string $value): array
{
    $parts = preg_split('/[;,\n]+/', $value);
    $parts = array_map('trim', $parts);
    return array_values(array_filter($parts, static fn($item) => $item !== ''));
}

function normalize_urls_field(string $value, array &$errors, string $label): array
{
    $lines = preg_split('/\r?\n/', $value);
    $urls = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (!filter_var($line, FILTER_VALIDATE_URL)) {
            $errors[] = "Невалиден URL в поле '{$label}'.";
        } else {
            $urls[] = $line;
        }
    }
    return $urls;
}

function format_value_for_prompt(string $type, $value): string
{
    switch ($type) {
        case 'tags':
        case 'tags[]':
            return $value ? implode(', ', $value) : '';
        case 'urls':
        case 'url[]':
            if (empty($value)) {
                return '';
            }
            return implode("\n", array_map(static fn($url) => '- ' . $url, $value));
        default:
            return is_array($value) ? implode(', ', $value) : (string) $value;
    }
}

$placeholders = $template['placeholders'];
$placeholderOptions = [];
$formValues = [];
$formErrors = [];
$generatedPrompt = null;

foreach ($placeholders as $placeholder) {
    $key = $placeholder['key'];
    $placeholderOptions[$key] = placeholder_options($placeholder, $catalogs);
    $default = '';
    if ($key === 'language') {
        $default = $settings['default_language'] ?? 'BG';
    }
    if ($key === 'concepts' && !empty($settings['default_concepts'])) {
        $default = $settings['default_concepts'];
    }
    if ($key === 'competencies' && !empty($settings['default_competencies'])) {
        $default = $settings['default_competencies'];
    }
    if (in_array($placeholder['type'], ['enum'], true) && $default === '' && !empty($placeholderOptions[$key])) {
        $default = $placeholderOptions[$key][0]['value'];
    }
    $formValues[$key] = $default;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($placeholders as $placeholder) {
        $key = $placeholder['key'];
        $label = $placeholder['label'] ?? $key;
        $type = $placeholder['type'] ?? 'string';
        $required = (bool) ($placeholder['required'] ?? false);
        $rawValue = $_POST[$key] ?? '';

        if ($type === 'enum') {
            $validValues = array_column($placeholderOptions[$key], 'value');
            $formValues[$key] = $rawValue;
            if ($required && $rawValue === '') {
                $formErrors[] = "Полето '{$label}' е задължително.";
            } elseif ($rawValue !== '' && !in_array($rawValue, $validValues, true)) {
                $formErrors[] = "Невалидна стойност за поле '{$label}'.";
            }
            continue;
        }

        if (in_array($type, ['tags', 'tags[]'], true)) {
            $formValues[$key] = $rawValue;
            $normalized = normalize_array_field($rawValue);
            if ($required && empty($normalized)) {
                $formErrors[] = "Полето '{$label}' е задължително.";
            }
            $formValues[$key] = $rawValue;
            $formValues[$key . '_parsed'] = $normalized;
            continue;
        }

        if (in_array($type, ['urls', 'url[]'], true)) {
            $formValues[$key] = $rawValue;
            $normalized = normalize_urls_field($rawValue, $formErrors, $label);
            if ($required && empty($normalized)) {
                $formErrors[] = "Полето '{$label}' е задължително.";
            }
            $formValues[$key . '_parsed'] = $normalized;
            continue;
        }

        if (in_array($type, ['markdown', 'text', 'longtext'], true)) {
            $value = is_string($rawValue) ? $rawValue : '';
            if ($required && trim($value) === '') {
                $formErrors[] = "Полето '{$label}' е задължително.";
            }
            $formValues[$key] = $value;
            continue;
        }

        $value = trim(is_string($rawValue) ? $rawValue : '');
        if ($required && $value === '') {
            $formErrors[] = "Полето '{$label}' е задължително.";
        }
        $formValues[$key] = $value;
    }

    if (empty($formErrors)) {
        $content = $template['content_md'];
        foreach ($placeholders as $placeholder) {
            $key = $placeholder['key'];
            $type = $placeholder['type'] ?? 'string';
            if ($type === 'enum') {
                $value = $formValues[$key];
            } elseif (in_array($type, ['tags', 'tags[]'], true)) {
                $value = format_value_for_prompt($type, $formValues[$key . '_parsed'] ?? []);
            } elseif (in_array($type, ['urls', 'url[]'], true)) {
                $value = format_value_for_prompt($type, $formValues[$key . '_parsed'] ?? []);
            } else {
                $value = $formValues[$key] ?? '';
            }
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        $parts = [];
        if (!empty($settings['global_constraints'])) {
            $parts[] = trim($settings['global_constraints']);
        }
        $parts[] = trim($content);
        $generatedPrompt = implode("\n\n", array_filter($parts));
    }
}

?><!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f1f4f9;
        }
        header {
            background: #3949ab;
            color: #fff;
            padding: 1.5rem 2rem;
        }
        header h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        main {
            max-width: 960px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        .status {
            background: #e8f0fe;
            border-left: 4px solid #3949ab;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        form label {
            display: block;
            font-weight: 600;
            margin-top: 1rem;
        }
        form input[type="text"],
        form textarea,
        form select {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #cbd2e0;
            border-radius: 6px;
            font-size: 1rem;
            margin-top: 0.3rem;
        }
        textarea {
            min-height: 120px;
        }
        .errors {
            background: #ffebee;
            color: #b71c1c;
            border: 1px solid #ffcdd2;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .btn {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            background: #3949ab;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
        }
        .result {
            margin-top: 2rem;
        }
        .result textarea {
            min-height: 260px;
            background: #f9fafc;
        }
        .hint {
            color: #607d8b;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<header>
    <h1>Генератор на промптове</h1>
</header>
<main>
    <div class="status">
        <strong>Източник на данни:</strong>
        <?= $databaseActive ? 'База данни' : 'Статични стойности'; ?>
        <?php if (!$databaseActive && $pdoError): ?>
            <div class="hint">Грешка при връзка: <?= sanitize($pdoError); ?></div>
        <?php endif; ?>
        <div class="hint">Активен шаблон: <?= sanitize($template['name']); ?> (<?= $templateSource === 'database' ? 'от БД' : 'статичен'; ?>)</div>
    </div>

    <?php if ($formErrors): ?>
        <div class="errors">
            <ul>
                <?php foreach ($formErrors as $error): ?>
                    <li><?= sanitize($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php foreach ($placeholders as $placeholder): ?>
            <?php
            $key = $placeholder['key'];
            $label = $placeholder['label'] ?? $key;
            $type = $placeholder['type'] ?? 'string';
            $required = !empty($placeholder['required']);
            $value = $formValues[$key] ?? '';
            ?>
            <label for="<?= sanitize($key); ?>">
                <?= sanitize($label); ?><?= $required ? ' *' : ''; ?>
            </label>
            <?php if ($type === 'enum'): ?>
                <select id="<?= sanitize($key); ?>" name="<?= sanitize($key); ?>" <?= $required ? 'required' : ''; ?>>
                    <option value="">-- избор --</option>
                    <?php foreach ($placeholderOptions[$key] as $option): ?>
                        <option value="<?= sanitize($option['value']); ?>" <?= $value === $option['value'] ? 'selected' : ''; ?>><?= sanitize($option['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php elseif (in_array($type, ['markdown', 'text', 'longtext'], true)): ?>
                <textarea id="<?= sanitize($key); ?>" name="<?= sanitize($key); ?>" <?= $required ? 'required' : ''; ?>><?= sanitize($value); ?></textarea>
            <?php elseif (in_array($type, ['tags', 'tags[]'], true)): ?>
                <textarea id="<?= sanitize($key); ?>" name="<?= sanitize($key); ?>" placeholder="Въведете стойности, разделени със запетая или нов ред" <?= $required ? 'required' : ''; ?>><?= sanitize($value); ?></textarea>
            <?php elseif (in_array($type, ['urls', 'url[]'], true)): ?>
                <textarea id="<?= sanitize($key); ?>" name="<?= sanitize($key); ?>" placeholder="Всеки URL на нов ред" <?= $required ? 'required' : ''; ?>><?= sanitize($value); ?></textarea>
            <?php else: ?>
                <input type="text" id="<?= sanitize($key); ?>" name="<?= sanitize($key); ?>" value="<?= sanitize($value); ?>" <?= $required ? 'required' : ''; ?>>
            <?php endif; ?>
        <?php endforeach; ?>
        <button class="btn" type="submit">Генерирай промпт</button>
    </form>

    <?php if ($generatedPrompt !== null): ?>
        <div class="result">
            <h2>Генериран промпт</h2>
            <textarea readonly><?= sanitize($generatedPrompt); ?></textarea>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
