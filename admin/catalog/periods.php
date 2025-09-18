<?php
$catalogConfig = [
    'table' => 'periods',
    'title' => 'Каталог: Исторически периоди',
    'path' => '/admin/catalog/periods.php',
    'fields' => [
        'name' => ['label' => 'Име на периода', 'required' => true],
    ],
];
require __DIR__ . '/catalog_page.php';
