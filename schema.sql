-- Prompt Generator database schema and seed data
-- MySQL 5.7+ / 8.0+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS user_configs;
DROP TABLE IF EXISTS template_relations;
DROP TABLE IF EXISTS templates;
DROP TABLE IF EXISTS durations;
DROP TABLE IF EXISTS assessments;
DROP TABLE IF EXISTS formats;
DROP TABLE IF EXISTS bloom_levels;
DROP TABLE IF EXISTS periods;
DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    global_constraints TEXT NULL,
    default_competencies TEXT NULL,
    default_concepts TEXT NULL,
    default_language VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE grades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    UNIQUE KEY uq_grades_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE periods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    UNIQUE KEY uq_periods_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bloom_levels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    UNIQUE KEY uq_bloom_levels_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE formats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    UNIQUE KEY uq_formats_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE assessments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    UNIQUE KEY uq_assessments_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE durations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    minutes INT UNSIGNED NULL,
    UNIQUE KEY uq_durations_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    content_md MEDIUMTEXT NOT NULL,
    placeholders_json JSON NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    version VARCHAR(20) NOT NULL DEFAULT '1.0.0',
    changelog TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE template_relations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id INT UNSIGNED NOT NULL,
    relation_type VARCHAR(50) NOT NULL,
    relation_id INT UNSIGNED NOT NULL,
    relation_table VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_configs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    config_json JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(150) NOT NULL,
    details JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (email, password_hash, role)
VALUES ('admin@local', '$2y$12$QPxbWcPIVXpSnGKw0CIXzO3dZbI37wOSidt1sOLC9pJWZbnTyduPa', 'superadmin');

INSERT INTO settings (global_constraints, default_competencies, default_concepts, default_language)
VALUES (
    'Генерираните промпти трябва да бъдат подходящи за ученици и да спазват академична етика.',
    JSON_ARRAY('Критично мислене', 'Анализ на източници'),
    JSON_ARRAY('История', 'Балкански полуостров'),
    'bg'
);

INSERT INTO grades (name, description) VALUES
('1 клас', 'Начален етап'),
('2 клас', 'Начален етап'),
('3 клас', 'Начален етап'),
('4 клас', 'Начален етап'),
('5 клас', 'Прогимназиален етап'),
('6 клас', 'Прогимназиален етап');

INSERT INTO periods (name, description) VALUES
('Древна история', 'Събития и цивилизации до края на античността'),
('Средновековие', 'История на Европа и света през Средновековието'),
('Ново време', 'Световна история от XV до XIX век'),
('Съвременност', 'История на XX и XXI век');

INSERT INTO bloom_levels (name, description) VALUES
('Remember', 'Възпроизвеждане на факти и понятия'),
('Understand', 'Разбиране и интерпретиране на информация'),
('Apply', 'Приложение на знания в нови ситуации'),
('Analyze', 'Разглеждане на връзки и отношения'),
('Evaluate', 'Оценка на аргументи и източници'),
('Create', 'Създаване на оригинално съдържание');

INSERT INTO formats (name, description) VALUES
('Работен лист', 'Структуриран документ с упражнения и въпроси'),
('Презентация', 'Слайдове за представяне на тема'),
('Проектно задание', 'Насоки за проектна работа');

INSERT INTO assessments (name, description) VALUES
('Формативно', 'Проследяване на напредъка по време на обучението'),
('Обобщаващо', 'Оценка след приключване на обучителен модул');

INSERT INTO durations (name, minutes) VALUES
('15 минути', 15),
('30 минути', 30),
('45 минути', 45),
('60 минути', 60);

INSERT INTO templates (name, content_md, placeholders_json, status, version, changelog)
VALUES (
    'Работен лист по история',
    '# Работен лист: {{topic}}\n\n## Цели\n- Учениците анализират ключови събития от периода {{period}}.\n- Затвърждават знанията си за личности и процеси.\n\n## Инструкции\n1. Прочетете внимателно предоставения текст.\n2. Отговорете на въпросите, като използвате информация от урока и собствени разсъждения.\n\n## Въпроси\n- Опишете накратко значението на {{key_event}}.\n- Какви са последствията от {{cause_effect}}?\n- Посочете две прилики между {{comparison_a}} и {{comparison_b}}.\n\n## Допълнителни задачи\n- Създайте кратка времева линия за {{topic}}.\n- Обсъдете в група: Какви уроци можем да научим днес?\n',
    JSON_ARRAY('topic', 'period', 'key_event', 'cause_effect', 'comparison_a', 'comparison_b'),
    'active',
    '1.0.0',
    'Начална версия на шаблона за работен лист по история.'
);

INSERT INTO template_relations (template_id, relation_type, relation_id, relation_table)
VALUES
((SELECT id FROM templates WHERE name = 'Работен лист по история'), 'grade', (SELECT id FROM grades WHERE name = '5 клас'), 'grades'),
((SELECT id FROM templates WHERE name = 'Работен лист по история'), 'period', (SELECT id FROM periods WHERE name = 'Средновековие'), 'periods'),
((SELECT id FROM templates WHERE name = 'Работен лист по история'), 'bloom_level', (SELECT id FROM bloom_levels WHERE name = 'Analyze'), 'bloom_levels');

INSERT INTO user_configs (user_id, config_json)
VALUES ((SELECT id FROM users WHERE email = 'admin@local'), JSON_OBJECT('language', 'bg', 'default_template', 'Работен лист по история'));

INSERT INTO audit_logs (user_id, action, details)
VALUES ((SELECT id FROM users WHERE email = 'admin@local'), 'seed', JSON_OBJECT('message', 'Initial schema and seed data imported.'));

