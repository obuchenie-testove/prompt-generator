<?php
$catalogConfig = [
    'table' => 'durations',
    'title' => 'Каталог: Продължителности',
    'path' => '/admin/catalog/durations.php',
    'fields' => [
        'label' => ['label' => 'Етикет', 'required' => true],
        'minutes' => ['label' => 'Минути', 'required' => true, 'type' => 'int'],
    ],
];
require __DIR__ . '/catalog_page.php';
