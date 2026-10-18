<?php
function db()
{
    static $database = null;

    if ($database === null) {
        $config = require __DIR__ . '/../config/database.php';
        $database = new Database($config);
    }

    return $database;
}