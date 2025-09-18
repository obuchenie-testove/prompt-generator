<?php
$catalogConfig = [
    'table' => 'grades',
    'title' => 'Каталог: Класове',
    'path' => '/admin/catalog/grades.php',
    'fields' => [
        'name' => ['label' => 'Име на клас', 'required' => true],
    ],
];
require __DIR__ . '/catalog_page.php';
