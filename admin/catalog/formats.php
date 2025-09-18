<?php
$catalogConfig = [
    'table' => 'formats',
    'title' => 'Каталог: Формати',
    'path' => '/admin/catalog/formats.php',
    'fields' => [
        'name' => ['label' => 'Формат', 'required' => true],
    ],
];
require __DIR__ . '/catalog_page.php';
