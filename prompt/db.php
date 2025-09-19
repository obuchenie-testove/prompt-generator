<?php
function db()
{
    static $pdo = null;
    static $config = null;
    static $configLoaded = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!$configLoaded) {
        $configPath = __DIR__ . '/config.php';
        if (!file_exists($configPath)) {
            throw new RuntimeException('Database configuration file not found.');
        }

        $config = require $configPath;
        $configLoaded = true;
    }

    if (!is_array($config)) {
        throw new RuntimeException('Invalid database configuration.');
    }

    $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
    $port = isset($config['port']) ? $config['port'] : 3306;
    $name = isset($config['name']) ? $config['name'] : '';
    $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';

    $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=' . $charset;

    $pdo = new PDO($dsn, isset($config['user']) ? $config['user'] : '', isset($config['pass']) ? $config['pass'] : '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}

function rows($sql, $params = array())
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function row($sql, $params = array())
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function col($sql, $params = array())
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}
