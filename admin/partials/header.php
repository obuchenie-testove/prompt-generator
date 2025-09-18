<?php
require_once __DIR__ . '/../../auth.php';
ensure_session();
$pageTitle = $pageTitle ?? 'Админ панел';
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle); ?></title>
    <style>
        :root {
            color-scheme: light dark;
            --bg: #f5f7fb;
            --card-bg: #ffffff;
            --primary: #3949ab;
            --accent: #5c6bc0;
            --text: #1a237e;
            --danger: #c62828;
            --success: #2e7d32;
            --border: #dfe3ee;
        }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: #222;
        }
        header {
            background: var(--primary);
            color: #fff;
            padding: 1rem 2rem;
        }
        header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        nav {
            background: var(--accent);
            padding: 0.5rem 2rem;
        }
        nav a {
            color: #fff;
            margin-right: 1rem;
            text-decoration: none;
            font-weight: 500;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1.5rem 4rem;
        }
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        .card h2 {
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid var(--border);
            padding: 0.75rem;
            text-align: left;
        }
        th {
            background: #eef1fb;
        }
        form label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        form input[type="text"],
        form input[type="email"],
        form input[type="number"],
        form input[type="password"],
        form textarea,
        form select {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.95rem;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }
        .btn-danger {
            background: var(--danger);
            color: #fff;
        }
        .btn-success {
            background: var(--success);
            color: #fff;
        }
        .flash {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .flash-success {
            background: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #c8e6c9;
        }
        .flash-error {
            background: #ffebee;
            color: #b71c1c;
            border: 1px solid #ffcdd2;
        }
        .flash-info {
            background: #e3f2fd;
            color: #0d47a1;
            border: 1px solid #bbdefb;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-draft {
            background: #fff3e0;
            color: #ef6c00;
        }
        .status-approved {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-deprecated {
            background: #eceff1;
            color: #546e7a;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
        }
        @media (max-width: 768px) {
            nav {
                padding: 0.75rem 1rem;
            }
            header, .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
<header>
    <h1><?= sanitize($pageTitle); ?></h1>
</header>
