<?php

require_once __DIR__ . '/config.php';

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => DATABASE_HOST,
            'name' => DATABASE_NAME,
            'user' => DATABASE_USER,
            'pass' => DATABASE_PASS,
            'port' => 3306,
            'charset' => SQL_CHARACTER_SET,
        ],
    ],
];
