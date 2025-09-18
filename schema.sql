-- Schema for Prompt Generator admin panel
CREATE DATABASE IF NOT EXISTS prompt_generator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE prompt_generator;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('superadmin','admin','editor','viewer') NOT NULL DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    global_constraints TEXT,
    default_competencies TEXT,
    default_concepts TEXT,
    default_language ENUM('BG','EN') NOT NULL DEFAULT 'BG',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    UNIQUE KEY uniq_grades_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS periods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    UNIQUE KEY uniq_periods_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bloom_levels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    UNIQUE KEY uniq_bloom_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS formats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    UNIQUE KEY uniq_formats_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    UNIQUE KEY uniq_assessments_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS durations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(191) NOT NULL,
    minutes INT NOT NULL,
    UNIQUE KEY uniq_durations_label (label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    description TEXT,
    content_md LONGTEXT NOT NULL,
    placeholders_json LONGTEXT,
    status ENUM('draft','approved','deprecated') NOT NULL DEFAULT 'draft',
    version INT NOT NULL DEFAULT 1,
    changelog TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_templates_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data
INSERT INTO users (email, password_hash, role) VALUES
    ('admin@local', '$2y$10$UwGJPD7W82dManIeZDV4pe.j13GNrHu4WQJKF8ZUb6Ye/kaZ91cCy', 'superadmin')
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO settings (global_constraints, default_competencies, default_concepts, default_language) VALUES
    ('Следвай националните стандарти по история и поддържай академичен, но достъпен тон.',
     'Развитие на критическо мислене; Умения за работа с източници;',
     'Исторически термини; Културни влияния;',
     'BG')
ON DUPLICATE KEY UPDATE global_constraints = VALUES(global_constraints),
    default_competencies = VALUES(default_competencies),
    default_concepts = VALUES(default_concepts),
    default_language = VALUES(default_language);

INSERT INTO grades (name) VALUES
    ('5. клас'),
    ('6. клас'),
    ('7. клас'),
    ('8. клас')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO periods (name) VALUES
    ('Древни цивилизации'),
    ('Средновековие'),
    ('Възраждане'),
    ('Модерна история')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO bloom_levels (name) VALUES
    ('Запомняне'),
    ('Разбиране'),
    ('Приложение'),
    ('Анализ'),
    ('Синтез'),
    ('Оценяване')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO formats (name) VALUES
    ('Работен лист'),
    ('Проект'),
    ('Презентация')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO assessments (name) VALUES
    ('Самооценка'),
    ('Формативно оценяване'),
    ('Обобщаващо оценяване')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO durations (label, minutes) VALUES
    ('Кратка активност', 15),
    ('Стандартен урок', 40),
    ('Разширена сесия', 90)
ON DUPLICATE KEY UPDATE label = VALUES(label), minutes = VALUES(minutes);

INSERT INTO templates (name, description, content_md, placeholders_json, status, version, changelog)
VALUES (
    'Работен лист по история',
    'Шаблон за генериране на работни листове по история с фокус върху изследване на периоди.',
    CONCAT('# Работен лист: {{topic}}\n\n',
           '## Исторически период\n',
           '- Период: {{period}}\n',
           '- Основни понятия: {{concepts}}\n\n',
           '## Материали\n',
           '{{materials}}\n\n',
           '## Изисквания\n',
           '{{requirements}}\n\n',
           '## Очаквани компетентности\n',
           '{{competencies}}\n\n',
           '## Блум ниво\n',
           '{{bloom_level}}\n\n',
           '## Формат\n',
           '{{format}}\n\n',
           '## Продължителност\n',
           '{{duration}}\n\n',
           '## Оценяване\n',
           '{{assessment}}\n\n',
           '## Език\n',
           '{{language}}\n'),
    '[{"key":"topic","label":"Тема","type":"string","required":true},{"key":"period","label":"Период","type":"enum","source":"periods","required":true},{"key":"concepts","label":"Ключови понятия","type":"tags","required":false},{"key":"materials","label":"Материали","type":"urls","required":false},{"key":"requirements","label":"Изисквания","type":"markdown","required":true},{"key":"competencies","label":"Компетентности","type":"markdown","required":false},{"key":"bloom_level","label":"Ниво по Блум","type":"enum","source":"bloom_levels","required":false},{"key":"format","label":"Формат","type":"enum","source":"formats","required":false},{"key":"duration","label":"Продължителност","type":"enum","source":"durations","required":false},{"key":"assessment","label":"Оценяване","type":"enum","source":"assessments","required":false},{"key":"language","label":"Език","type":"enum","options":["BG","EN"],"required":true}]',
    'approved',
    1,
    'Първоначален одобрен шаблон.'
)
ON DUPLICATE KEY UPDATE name = VALUES(name);
