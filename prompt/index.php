<?php
$configPath = __DIR__ . '/config.php';
$config = null;
if (file_exists($configPath)) {
    $config = require_once $configPath;
}

require_once __DIR__ . '/db.php';

$pdoAvailable = false;
$settingsRow = null;

$defaultGrades = array('Preschool', 'Elementary School', 'Middle School', 'High School');
$defaultPeriods = array('Semester 1', 'Semester 2', 'Summer Session');
$defaultBloomLevels = array('Remembering', 'Understanding', 'Applying', 'Analyzing', 'Evaluating', 'Creating');
$defaultFormats = array('Lesson Plan', 'Worksheet', 'Presentation', 'Project Outline');
$defaultAssessments = array('Quiz', 'Project', 'Presentation', 'Discussion');
$defaultDurations = array(
    array('label' => '30 minutes', 'minutes' => 30),
    array('label' => '45 minutes', 'minutes' => 45),
    array('label' => '60 minutes', 'minutes' => 60),
);

$defaultCompetencies = 'Critical thinking, collaboration, creativity';
$defaultConcepts = 'Core concepts: topic fundamentals, applied examples';
$defaultLanguage = 'English';
$globalConstraints = '';

try {
    $pdo = db();
    $pdoAvailable = true;

    $gradesDb = rows('SELECT name FROM grades ORDER BY name ASC');
    if (!empty($gradesDb)) {
        $defaultGrades = array();
        foreach ($gradesDb as $item) {
            if (isset($item['name'])) {
                $defaultGrades[] = $item['name'];
            }
        }
    }

    $periodsDb = rows('SELECT name FROM periods ORDER BY name ASC');
    if (!empty($periodsDb)) {
        $defaultPeriods = array();
        foreach ($periodsDb as $item) {
            if (isset($item['name'])) {
                $defaultPeriods[] = $item['name'];
            }
        }
    }

    $bloomDb = rows('SELECT name FROM bloom_levels ORDER BY name ASC');
    if (!empty($bloomDb)) {
        $defaultBloomLevels = array();
        foreach ($bloomDb as $item) {
            if (isset($item['name'])) {
                $defaultBloomLevels[] = $item['name'];
            }
        }
    }

    $formatsDb = rows('SELECT name FROM formats ORDER BY name ASC');
    if (!empty($formatsDb)) {
        $defaultFormats = array();
        foreach ($formatsDb as $item) {
            if (isset($item['name'])) {
                $defaultFormats[] = $item['name'];
            }
        }
    }

    $assessmentsDb = rows('SELECT name FROM assessments ORDER BY name ASC');
    if (!empty($assessmentsDb)) {
        $defaultAssessments = array();
        foreach ($assessmentsDb as $item) {
            if (isset($item['name'])) {
                $defaultAssessments[] = $item['name'];
            }
        }
    }

    $durationsDb = rows('SELECT label, minutes FROM durations ORDER BY minutes ASC');
    if (!empty($durationsDb)) {
        $defaultDurations = array();
        foreach ($durationsDb as $item) {
            $label = isset($item['label']) ? $item['label'] : '';
            $minutes = isset($item['minutes']) ? (int) $item['minutes'] : null;
            if ($label !== '') {
                $defaultDurations[] = array('label' => $label, 'minutes' => $minutes);
            }
        }
    }

    $settingsRow = row('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
    if ($settingsRow) {
        if (isset($settingsRow['default_competencies']) && $settingsRow['default_competencies'] !== '') {
            $defaultCompetencies = $settingsRow['default_competencies'];
        }
        if (isset($settingsRow['default_concepts']) && $settingsRow['default_concepts'] !== '') {
            $defaultConcepts = $settingsRow['default_concepts'];
        }
        if (isset($settingsRow['default_language']) && $settingsRow['default_language'] !== '') {
            $defaultLanguage = $settingsRow['default_language'];
        }
        if (isset($settingsRow['global_constraints']) && $settingsRow['global_constraints'] !== '') {
            $globalConstraints = $settingsRow['global_constraints'];
        }
    }
} catch (Exception $exception) {
    $pdoAvailable = false;
}

function first_value($array, $defaultValue)
{
    if (!is_array($array)) {
        return $defaultValue;
    }
    foreach ($array as $item) {
        if (is_array($item) && isset($item['label'])) {
            return $item['label'];
        }
        if (!is_array($item)) {
            return $item;
        }
    }
    return $defaultValue;
}

function get_post_value($key, $default)
{
    if (isset($_POST[$key])) {
        if (is_array($_POST[$key])) {
            return $_POST[$key];
        }
        return trim((string) $_POST[$key]);
    }
    return $default;
}

$selectedGrade = get_post_value('grade', first_value($defaultGrades, ''));
$selectedPeriod = get_post_value('period', first_value($defaultPeriods, ''));
$selectedBloom = get_post_value('bloom', first_value($defaultBloomLevels, ''));
$selectedFormat = get_post_value('format', first_value($defaultFormats, ''));
$selectedAssessment = get_post_value('assessment', first_value($defaultAssessments, ''));
$selectedDuration = get_post_value('duration', first_value($defaultDurations, ''));
$selectedLanguage = get_post_value('language', $defaultLanguage);
$theme = get_post_value('theme', '');
$competencies = get_post_value('competencies', $defaultCompetencies);
$concepts = get_post_value('concepts', $defaultConcepts);
$requirements = get_post_value('requirements', '');
$links = get_post_value('links', array(''));

if (!is_array($links) || empty($links)) {
    $links = array('');
}

function sanitize_text($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function build_constraints($requirementsText, $globalConstraintsText)
{
    $constraints = array();

    $requirementsLines = preg_split('/\r?\n/', (string) $requirementsText);
    if ($requirementsLines) {
        foreach ($requirementsLines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $constraints[] = $trimmed;
            }
        }
    }

    $globalLines = preg_split('/\r?\n/', (string) $globalConstraintsText);
    if ($globalLines) {
        foreach ($globalLines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $constraints[] = $trimmed;
            }
        }
    }

    return $constraints;
}

function build_prompt($data)
{
    $lines = array();
    $lines[] = 'PROMPT: Generate a comprehensive lesson plan.';
    $lines[] = '';
    $lines[] = 'GRADE LEVEL: ' . $data['grade'];
    $lines[] = 'DURATION: ' . $data['duration'];
    if ($data['duration_minutes'] !== null) {
        $lines[] = 'DURATION MINUTES: ' . $data['duration_minutes'];
    }
    $lines[] = 'PERIOD: ' . $data['period'];
    $lines[] = 'BLOOM LEVEL: ' . $data['bloom'];
    $lines[] = 'THEME OR TOPIC: ' . $data['theme'];
    $lines[] = 'FORMAT: ' . $data['format'];
    $lines[] = 'ASSESSMENT: ' . $data['assessment'];
    $lines[] = 'LANGUAGE: ' . $data['language'];
    $lines[] = '';
    $lines[] = 'COMPETENCIES TO HIGHLIGHT: ' . $data['competencies'];
    $lines[] = 'KEY CONCEPTS: ' . $data['concepts'];
    $lines[] = '';

    $constraints = build_constraints($data['requirements'], $data['global_constraints']);
    if (!empty($constraints)) {
        $lines[] = 'CONSTRAINTS:';
        foreach ($constraints as $constraint) {
            $lines[] = '- ' . $constraint;
        }
    }

    $links = array();
    if (isset($data['links']) && is_array($data['links'])) {
        foreach ($data['links'] as $link) {
            $trimmed = trim($link);
            if ($trimmed !== '') {
                $links[] = $trimmed;
            }
        }
    }

    if (!empty($links)) {
        $lines[] = '';
        $lines[] = 'REFERENCE LINKS:';
        foreach ($links as $link) {
            $lines[] = '- ' . $link;
        }
    }

    return implode("\n", $lines);
}

function resolve_duration_minutes($durations, $selectedLabel)
{
    if (!is_array($durations)) {
        return null;
    }
    foreach ($durations as $item) {
        if (is_array($item) && isset($item['label']) && $item['label'] === $selectedLabel) {
            if (isset($item['minutes'])) {
                return $item['minutes'] === null ? null : (int) $item['minutes'];
            }
        } elseif (!is_array($item) && $item === $selectedLabel) {
            return null;
        }
    }
    return null;
}

$durationMinutes = resolve_duration_minutes($defaultDurations, $selectedDuration);

$promptData = array(
    'grade' => $selectedGrade,
    'duration' => $selectedDuration,
    'duration_minutes' => $durationMinutes,
    'period' => $selectedPeriod,
    'bloom' => $selectedBloom,
    'theme' => $theme,
    'format' => $selectedFormat,
    'assessment' => $selectedAssessment,
    'language' => $selectedLanguage,
    'competencies' => $competencies,
    'concepts' => $concepts,
    'requirements' => $requirements,
    'global_constraints' => $globalConstraints,
    'links' => $links,
);

$promptText = build_prompt($promptData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prompt Generator</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f7f7f7; color: #333; }
        h1 { margin-bottom: 10px; }
        form { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        select, input[type="text"], textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        textarea { min-height: 80px; }
        .buttons { margin-top: 20px; }
        .buttons button { margin-right: 10px; padding: 10px 16px; }
        pre { background: #fff; border: 1px solid #ddd; padding: 15px; white-space: pre-wrap; border-radius: 8px; }
        .links-wrapper .link-input { display: flex; margin-bottom: 8px; }
        .links-wrapper .link-input input { flex: 1; }
        .add-link { margin-top: 5px; }
        .status { margin-top: 10px; color: #2c7a7b; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>Prompt Generator</h1>
    <form method="post">
        <div class="form-group">
            <label for="grade">Grade</label>
            <select id="grade" name="grade">
                <?php foreach ($defaultGrades as $gradeOption): ?>
                    <?php $value = is_array($gradeOption) && isset($gradeOption['label']) ? $gradeOption['label'] : $gradeOption; ?>
                    <option value="<?php echo sanitize_text($value); ?>" <?php echo $value === $selectedGrade ? 'selected' : ''; ?>><?php echo sanitize_text($value); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="duration">Duration</label>
            <select id="duration" name="duration">
                <?php foreach ($defaultDurations as $durationOption): ?>
                    <?php
                    if (is_array($durationOption)) {
                        $durationLabel = isset($durationOption['label']) ? $durationOption['label'] : '';
                        $minutes = isset($durationOption['minutes']) && $durationOption['minutes'] !== null ? ' (' . (int) $durationOption['minutes'] . ' min)' : '';
                        $text = $durationLabel . $minutes;
                    } else {
                        $durationLabel = $durationOption;
                        $text = $durationOption;
                    }
                    ?>
                    <option value="<?php echo sanitize_text($durationLabel); ?>" <?php echo $durationLabel === $selectedDuration ? 'selected' : ''; ?>><?php echo sanitize_text($text); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="period">Period</label>
            <select id="period" name="period">
                <?php foreach ($defaultPeriods as $periodOption): ?>
                    <?php $value = is_array($periodOption) && isset($periodOption['label']) ? $periodOption['label'] : $periodOption; ?>
                    <option value="<?php echo sanitize_text($value); ?>" <?php echo $value === $selectedPeriod ? 'selected' : ''; ?>><?php echo sanitize_text($value); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="bloom">Bloom level</label>
            <select id="bloom" name="bloom">
                <?php foreach ($defaultBloomLevels as $bloomOption): ?>
                    <?php $value = is_array($bloomOption) && isset($bloomOption['label']) ? $bloomOption['label'] : $bloomOption; ?>
                    <option value="<?php echo sanitize_text($value); ?>" <?php echo $value === $selectedBloom ? 'selected' : ''; ?>><?php echo sanitize_text($value); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="theme">Theme</label>
            <input id="theme" type="text" name="theme" value="<?php echo sanitize_text($theme); ?>">
        </div>
        <div class="form-group">
            <label for="format">Format</label>
            <select id="format" name="format">
                <?php foreach ($defaultFormats as $formatOption): ?>
                    <?php $value = is_array($formatOption) && isset($formatOption['label']) ? $formatOption['label'] : $formatOption; ?>
                    <option value="<?php echo sanitize_text($value); ?>" <?php echo $value === $selectedFormat ? 'selected' : ''; ?>><?php echo sanitize_text($value); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="assessment">Assessment</label>
            <select id="assessment" name="assessment">
                <?php foreach ($defaultAssessments as $assessmentOption): ?>
                    <?php $value = is_array($assessmentOption) && isset($assessmentOption['label']) ? $assessmentOption['label'] : $assessmentOption; ?>
                    <option value="<?php echo sanitize_text($value); ?>" <?php echo $value === $selectedAssessment ? 'selected' : ''; ?>><?php echo sanitize_text($value); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="language">Language</label>
            <input id="language" type="text" name="language" value="<?php echo sanitize_text($selectedLanguage); ?>">
        </div>
        <div class="form-group">
            <label for="competencies">Competencies</label>
            <textarea id="competencies" name="competencies"><?php echo sanitize_text($competencies); ?></textarea>
        </div>
        <div class="form-group">
            <label for="concepts">Concepts</label>
            <textarea id="concepts" name="concepts"><?php echo sanitize_text($concepts); ?></textarea>
        </div>
        <div class="form-group">
            <label for="requirements">Requirements / Constraints</label>
            <textarea id="requirements" name="requirements"><?php echo sanitize_text($requirements); ?></textarea>
        </div>
        <div class="form-group links-wrapper">
            <label>Reference Links</label>
            <div id="linksContainer">
                <?php foreach ($links as $index => $linkValue): ?>
                    <div class="link-input">
                        <input type="text" name="links[]" value="<?php echo sanitize_text($linkValue); ?>" placeholder="https://example.com/resource">
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="add-link" type="button" id="addLink">Добави линк</button>
        </div>
        <div class="buttons">
            <button type="submit" name="action" value="generate">Генерирай</button>
            <button type="button" id="copyPrompt">Копирай</button>
            <button type="button" id="downloadPrompt">Свали .txt</button>
        </div>
    </form>
    <div class="status">
        <?php if ($pdoAvailable): ?>
            Данните са заредени от база данни.
        <?php else: ?>
            Използват се вградените стойности по подразбиране.
        <?php endif; ?>
    </div>
    <h2>Generated Prompt</h2>
    <pre id="generatedPrompt"><?php echo sanitize_text($promptText); ?></pre>
    <script>
        (function() {
            var addLinkButton = document.getElementById('addLink');
            var linksContainer = document.getElementById('linksContainer');
            if (addLinkButton && linksContainer) {
                addLinkButton.addEventListener('click', function() {
                    var wrapper = document.createElement('div');
                    wrapper.className = 'link-input';
                    var input = document.createElement('input');
                    input.type = 'text';
                    input.name = 'links[]';
                    input.placeholder = 'https://example.com/resource';
                    wrapper.appendChild(input);
                    linksContainer.appendChild(wrapper);
                });
            }

            var copyButton = document.getElementById('copyPrompt');
            if (copyButton) {
                copyButton.addEventListener('click', function() {
                    var promptElement = document.getElementById('generatedPrompt');
                    if (!promptElement) {
                        return;
                    }
                    var text = promptElement.textContent || promptElement.innerText;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text);
                    } else {
                        var textarea = document.createElement('textarea');
                        textarea.value = text;
                        document.body.appendChild(textarea);
                        textarea.select();
                        try {
                            document.execCommand('copy');
                        } catch (error) {
                        }
                        document.body.removeChild(textarea);
                    }
                });
            }

            var downloadButton = document.getElementById('downloadPrompt');
            if (downloadButton) {
                downloadButton.addEventListener('click', function() {
                    var promptElement = document.getElementById('generatedPrompt');
                    if (!promptElement) {
                        return;
                    }
                    var text = promptElement.textContent || promptElement.innerText;
                    var blob = new Blob([text], { type: 'text/plain' });
                    var url = window.URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = 'prompt.txt';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                });
            }
        })();
    </script>
</body>
</html>
