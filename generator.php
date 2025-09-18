<?php
declare(strict_types=1);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function post(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

$selectOptions = [
    'grade' => ['5 клас', '6 клас', '7 клас', '8 клас', '9 клас', '10 клас'],
    'duration' => ['20 мин', '40 мин', '60 мин', '80 мин'],
    'period' => [
        'Първа българска държава',
        'Втора българска държава',
        'Османски период',
        'Българско възраждане',
        'Княжество/Царство (1878–1944)',
        'България след 1944',
    ],
    'bloom' => ['Помня/Разбирам', 'Прилагам/Анализирам', 'Оценявам/Създавам'],
    'format' => [
        'Работен лист (индивидуално)',
        'Работен лист (по двойки)',
        'Екипна задача (3–4)',
        'Станции за учене',
    ],
    'assessment' => [
        'Формиращо (rubric 0–2)',
        'Формативно + самооценка',
        'Тест в края (5 въпроса)',
    ],
    'language' => ['BG', 'EN'],
];

$defaults = [
    'grade' => '7 клас',
    'duration' => '40 мин',
    'period' => 'Втора българска държава',
    'theme' => 'Управлението на цар Иван Асен II',
    'bloom' => 'Прилагам/Анализирам',
    'format' => 'Работен лист (индивидуално)',
    'assessment' => 'Формиращо (rubric 0–2)',
    'language' => 'BG',
    'competencies' => 'Историческо мислене, Работа с източници, Аргументация',
    'concepts' => 'Икономика, Дипломация, Търговия, Териториално разширение',
    'requirements' => "1) 2 извора + 5 въпроса; 2) 1 карта; 3) 1 аргумент (5–6 изр.).",
];

$data = $defaults;
$linkInputs = [''];
$validLinks = [];
$linkErrors = [];
$prompt = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($defaults as $key => $defaultValue) {
        $value = is_array(post($key)) ? $defaultValue : trim((string) post($key, $defaultValue));
        if (isset($selectOptions[$key]) && !in_array($value, $selectOptions[$key], true)) {
            $data[$key] = $defaultValue;
        } elseif ($value === '') {
            $data[$key] = $defaultValue;
        } else {
            $data[$key] = $value;
        }
    }

    $linkInputs = post('links', []);
    if (!is_array($linkInputs) || $linkInputs === []) {
        $linkInputs = [''];
    }

    $trimmedLinks = [];
    foreach ($linkInputs as $link) {
        $trimmed = trim((string) $link);
        $trimmedLinks[] = $trimmed;
        if ($trimmed === '') {
            continue;
        }
        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            $validLinks[] = $trimmed;
        } else {
            $linkErrors[] = $trimmed;
        }
    }
    $linkInputs = $trimmedLinks;

    $language = $data['language'];
    $constraints = [
        "Адаптирай задачите за {$data['grade']}.",
        "Фокус върху периода: {$data['period']}.",
        "Наблягай на тема: {$data['theme']}.",
        "Поддържай трудност, отговаряща на ниво Bloom: {$data['bloom']}.",
        "Използвай формат: {$data['format']}.",
        "Планирай оценяване: {$data['assessment']}.",
    ];

    $requirements = array_filter(array_map('trim', preg_split('/[\r\n]+/', (string) $data['requirements'])));
    if ($requirements === []) {
        $requirements = ['Следвай зададената тема и включи подходящи учебни дейности.'];
    }

    $outputStructure = [
        'Кратко въведение с основни факти и водещ въпрос.',
        'Два исторически извора (текстови/визуални) с указания за анализ.',
        'Пет аналитични въпроса по изворите (минимум два с аргументация).',
        'Карта или визуализация с задача за географско осмисляне.',
        'Задача за сравнение или оценка, свързана с темата.',
        'Практическа дейност или мини-проект според формата на работа.',
        'Критерии за оценяване/самооценка в съответствие с планираното оценяване.',
        'Финален рефлексивен въпрос или предизвикателство за следващ урок.',
    ];

    $materialsSection = $validLinks !== []
        ? implode("\n", array_map(fn($url) => "- $url", $validLinks))
        : 'генерирай подходящи, ако липсват';

    $promptLines = [
        'ROLE: Експерт по история на България и instructional designer.',
        'GOAL: Създай работен лист по история за тема "' . $data['theme'] . '" за ' . $data['grade'] . '.',
    ];

    if ($language === 'EN') {
        $promptLines[] = 'Language: English.';
    }

    $promptLines[] = 'Продължителност: ' . $data['duration'] . '.';
    $promptLines[] = 'Формат: ' . $data['format'] . '.';
    $promptLines[] = 'Bloom: ' . $data['bloom'] . '.';
    $promptLines[] = 'Оценяване: ' . $data['assessment'] . '.';
    $promptLines[] = 'CONSTRAINTS:';
    foreach ($constraints as $item) {
        $promptLines[] = '- ' . $item;
    }
    $promptLines[] = 'COMPETENCIES: ' . $data['competencies'] . '.';
    $promptLines[] = 'KEY CONCEPTS: ' . $data['concepts'] . '.';
    $promptLines[] = 'MATERIALS:';
    $promptLines[] = $materialsSection;
    $promptLines[] = 'REQUIREMENTS:';
    foreach ($requirements as $req) {
        $promptLines[] = '- ' . $req;
    }
    $promptLines[] = 'OUTPUT STRUCTURE:';
    foreach ($outputStructure as $index => $step) {
        $promptLines[] = ($index + 1) . '. ' . $step;
    }

    $prompt = implode("\n", $promptLines);
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Генератор на промпт за работни листове по история</title>
    <style>
        :root {
            color-scheme: light dark;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            --bg: #f3f4f6;
            --card: #ffffff;
            --border: #d1d5db;
            --accent: #1f2937;
            --accent-light: #4b5563;
            --output-bg: #111827;
            --output-text: #f9fafb;
        }
        body {
            margin: 0;
            background: var(--bg);
            color: #111;
        }
        header {
            padding: 2rem 1rem 1rem;
            text-align: center;
        }
        h1 {
            margin: 0 0 0.5rem;
            color: var(--accent);
        }
        main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 1rem 3rem;
        }
        form {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem 2rem;
        }
        @media (min-width: 900px) {
            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        label {
            font-weight: 600;
            color: var(--accent);
            display: block;
            margin-bottom: 0.35rem;
        }
        input[type="text"],
        select,
        textarea {
            width: 100%;
            padding: 0.6rem 0.7rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            font-size: 0.95rem;
            box-sizing: border-box;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        .full {
            grid-column: 1 / -1;
        }
        .links-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .links-group input {
            display: block;
        }
        .link-row {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .link-row input[type="text"] {
            flex: 1 1 auto;
        }
        .link-row button {
            padding: 0.45rem 0.8rem;
            border: none;
            border-radius: 6px;
            background: #e5e7eb;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .link-row button:hover {
            background: #d1d5db;
        }
        .btn-row {
            margin-top: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        button.primary {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.7rem 1.4rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
        }
        button.primary:hover {
            background: #1d4ed8;
        }
        button.secondary {
            background: #111827;
            color: white;
            border: none;
            padding: 0.7rem 1.3rem;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
        }
        button.secondary:disabled {
            background: #6b7280;
            cursor: not-allowed;
        }
        .output {
            margin-top: 2rem;
            background: var(--output-bg);
            color: var(--output-text);
            padding: 1.5rem;
            border-radius: 12px;
            white-space: pre-wrap;
            font-family: 'Fira Code', 'Courier New', monospace;
            min-height: 200px;
        }
        .errors {
            margin-top: 0.75rem;
            color: #b91c1c;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<header>
    <h1>Генератор на AI промпт за работни листове</h1>
    <p>Конфигурирай параметрите и създай ясен промпт за умен асистент.</p>
</header>
<main>
    <form method="post">
        <div class="grid">
            <div>
                <label for="grade">Клас</label>
                <select id="grade" name="grade">
                    <?php foreach ($selectOptions['grade'] as $option): ?>
                        <option value="<?= h($option) ?>" <?= $data['grade'] === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="duration">Продължителност</label>
                <select id="duration" name="duration">
                    <?php foreach ($selectOptions['duration'] as $option): ?>
                        <option value="<?= h($option) ?>" <?= $data['duration'] === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="period">Епоха/Период</label>
                <select id="period" name="period">
                    <?php foreach ($selectOptions['period'] as $option): ?>
                        <option value="<?= h($option) ?>" <?= $data['period'] === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="bloom">Ниво по Bloom</label>
                <select id="bloom" name="bloom">
                    <?php foreach ($selectOptions['bloom'] as $option): ?>
                        <option value="<?= h($option) ?>" <?= $data['bloom'] === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="full">
                <label for="theme">Тема</label>
                <input type="text" id="theme" name="theme" value="<?= h($data['theme']) ?>" required>
            </div>
            <div>
                <label for="format">Формат</label>
                <select id="format" name="format">
                    <?php foreach ($selectOptions['format'] as $option): ?>
                        <option value="<?= h($option) ?>" <?= $data['format'] === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="assessment">Оценяване</label>
                <select id="assessment" name="assessment">
                    <?php foreach ($selectOptions['assessment'] as $option): ?>
                        <option value="<?= h($option) ?>" <?= $data['assessment'] === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="language">Език</label>
                <select id="language" name="language">
                    <?php foreach ($selectOptions['language'] as $option): ?>
                        <option value="<?= h($option) ?>" <?= $data['language'] === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="full">
                <label for="competencies">Компетентности</label>
                <input type="text" id="competencies" name="competencies" value="<?= h($data['competencies']) ?>">
            </div>
            <div class="full">
                <label for="concepts">Ключови понятия</label>
                <input type="text" id="concepts" name="concepts" value="<?= h($data['concepts']) ?>">
            </div>
            <div class="full">
                <label for="requirements">Изисквания към AI</label>
                <textarea id="requirements" name="requirements"><?= h($data['requirements']) ?></textarea>
            </div>
            <div class="full">
                <label>Линкове (по избор)</label>
                <div class="links-group" id="links-group">
                    <?php foreach ($linkInputs as $index => $value): ?>
                        <div class="link-row">
                            <input type="text" name="links[]" placeholder="https://example.com" value="<?= h($value) ?>">
                            <button type="button" class="remove-link" aria-label="Премахни">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add-link" class="secondary" style="margin-top:0.5rem;">+ Добави линк</button>
                <?php if ($linkErrors !== []): ?>
                    <div class="errors">
                        Невалидни URL: <?= h(implode(', ', $linkErrors)) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="btn-row">
            <button type="submit" class="primary">Генерирай промпт</button>
            <button type="button" id="copy-btn" class="secondary" <?= $prompt === '' ? 'disabled' : '' ?>>Копирай</button>
            <button type="button" id="download-btn" class="secondary" <?= $prompt === '' ? 'disabled' : '' ?>>Свали .txt</button>
        </div>
    </form>

    <section class="output" id="prompt-output" aria-live="polite"><?= $prompt !== '' ? h($prompt) : 'Резултатът ще се появи тук след генериране.' ?></section>
</main>
<script>
(function() {
    const linksGroup = document.getElementById('links-group');
    const addLinkBtn = document.getElementById('add-link');
    const copyBtn = document.getElementById('copy-btn');
    const downloadBtn = document.getElementById('download-btn');
    const output = document.getElementById('prompt-output');

    function bindRemoveButtons() {
        const removeButtons = linksGroup.querySelectorAll('.remove-link');
        removeButtons.forEach(btn => {
            btn.onclick = () => {
                const rows = linksGroup.querySelectorAll('.link-row');
                if (rows.length > 1) {
                    btn.parentElement.remove();
                } else {
                    btn.parentElement.querySelector('input').value = '';
                }
            };
        });
    }

    addLinkBtn.addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'link-row';
        row.innerHTML = '<input type="text" name="links[]" placeholder="https://example.com" value="">' +
            '<button type="button" class="remove-link" aria-label="Премахни">✕</button>';
        linksGroup.appendChild(row);
        bindRemoveButtons();
    });

    bindRemoveButtons();

    function isDisabled(button) {
        return button.hasAttribute('disabled');
    }

    copyBtn?.addEventListener('click', async () => {
        if (isDisabled(copyBtn)) {
            return;
        }
        try {
            await navigator.clipboard.writeText(output.textContent.trim());
            copyBtn.textContent = 'Копирано!';
            setTimeout(() => copyBtn.textContent = 'Копирай', 1800);
        } catch (err) {
            copyBtn.textContent = 'Грешка при копиране';
            setTimeout(() => copyBtn.textContent = 'Копирай', 1800);
        }
    });

    downloadBtn?.addEventListener('click', () => {
        if (isDisabled(downloadBtn)) {
            return;
        }
        const blob = new Blob([output.textContent.trim()], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'history-prompt.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
})();
</script>
</body>
</html>
