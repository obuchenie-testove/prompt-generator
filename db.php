<?php

declare(strict_types=1);

/**
 * Get a shared PDO instance using configuration from config.php.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $configFile = __DIR__ . '/config.php';
    if (!is_file($configFile)) {
        throw new RuntimeException('Missing config.php. Copy config.php.example and adjust the credentials.');
    }

    /** @var array{db: array{host?: string, database?: string, user?: string, pass?: string, charset?: string, options?: array<mixed>}} $config */
    $config = require $configFile;

    if (!isset($config['db']) || !is_array($config['db'])) {
        throw new RuntimeException('Database configuration is invalid.');
    }

    $host = $config['db']['host'] ?? '127.0.0.1';
    $database = $config['db']['database'] ?? null;
    if ($database === null || $database === '') {
        throw new RuntimeException('Database name is not set in configuration.');
    }

    $charset = $config['db']['charset'] ?? 'utf8mb4';
    $user = $config['db']['user'] ?? '';
    $pass = $config['db']['pass'] ?? '';
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $database, $charset);

    $options = $config['db']['options'] ?? [];
    $defaults = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options + $defaults);

    return $pdo;
}

/**
 * Fetch all rows as an array of associative arrays.
 *
 * @param array<int|string, mixed> $params
 * @return array<int, array<string, mixed>>
 */
function rows(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Fetch the first row or null when no rows are returned.
 *
 * @param array<int|string, mixed> $params
 * @return array<string, mixed>|null
 */
function row(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();

    return $result === false ? null : $result;
}

/**
 * Fetch the first column from all rows.
 *
 * @param array<int|string, mixed> $params
 * @return array<int, mixed>
 */
function col(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

