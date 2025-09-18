<?php
$catalogConfig = [
    'table' => 'assessments',
    'title' => 'Каталог: Оценяване',
    'path' => '/admin/catalog/assessments.php',
    'fields' => [
        'name' => ['label' => 'Тип оценяване', 'required' => true],
    ],
];
require __DIR__ . '/catalog_page.php';
