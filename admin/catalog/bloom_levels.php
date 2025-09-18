<?php
$catalogConfig = [
    'table' => 'bloom_levels',
    'title' => 'Каталог: Нива по Блум',
    'path' => '/admin/catalog/bloom_levels.php',
    'fields' => [
        'name' => ['label' => 'Ниво', 'required' => true],
    ],
];
require __DIR__ . '/catalog_page.php';
