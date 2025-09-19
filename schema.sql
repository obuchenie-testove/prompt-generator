SET NAMES utf8mb4;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `global_constraints` text,
  `default_competencies` text,
  `default_concepts` text,
  `default_language` varchar(100) DEFAULT 'English',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `grades` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `periods` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `bloom_levels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `formats` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `mime` varchar(120) DEFAULT NULL,
  `ext` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `assessments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `durations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(120) NOT NULL,
  `minutes` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `content_md` mediumtext NOT NULL,
  `placeholders_json` text,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `version` varchar(20) DEFAULT '1.0.0',
  `changelog` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`email`, `password_hash`, `role`) VALUES
('admin@local', '$2y$10$UwGJPD7W82dManIeZDV4pe.j13GNrHu4WQJKF8ZUb6Ye/kaZ91cCy', 'admin');

INSERT INTO `settings` (`global_constraints`, `default_competencies`, `default_concepts`, `default_language`) VALUES
('Align with curriculum standards. Include differentiation strategies for diverse learners.',
'Collaboration, communication, critical thinking, creativity.',
'Foundational understanding of the theme, real-world applications, reflective practice.',
'Bulgarian');

INSERT INTO `grades` (`name`) VALUES
('Kindergarten'),
('Grade 1'),
('Grade 2'),
('Grade 3'),
('Grade 4'),
('Grade 5'),
('Grade 6'),
('Grade 7'),
('Grade 8'),
('High School');

INSERT INTO `periods` (`name`) VALUES
('Semester 1'),
('Semester 2'),
('Trimester 1'),
('Trimester 2'),
('Trimester 3');

INSERT INTO `bloom_levels` (`name`) VALUES
('Remember'),
('Understand'),
('Apply'),
('Analyze'),
('Evaluate'),
('Create');

INSERT INTO `formats` (`name`, `mime`, `ext`) VALUES
('Lesson Plan', 'text/markdown', 'md'),
('Worksheet', 'application/pdf', 'pdf'),
('Presentation', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'pptx'),
('Project Brief', 'text/plain', 'txt');

INSERT INTO `assessments` (`name`) VALUES
('Quiz'),
('Project'),
('Presentation'),
('Class Discussion'),
('Portfolio');

INSERT INTO `durations` (`label`, `minutes`) VALUES
('30 minutes', 30),
('45 minutes', 45),
('60 minutes', 60),
('90 minutes', 90);

INSERT INTO `templates` (`content_md`, `placeholders_json`, `status`, `version`, `changelog`) VALUES
('# Lesson Template\n\n## Objectives\n- {{objective_1}}\n- {{objective_2}}\n\n## Activities\n1. {{activity_1}}\n2. {{activity_2}}',
'{"objective_1":"Introduce topic","objective_2":"Assess understanding","activity_1":"Warm-up discussion","activity_2":"Group project"}',
'published',
'1.0.0',
'Initial template seed.');
