<?php
// データベース設定ファイル（ヘテムル対応版）

// 環境変数を読み込み
require_once __DIR__ . '/env.php';

return [
    'default' => getenv('DB_CONNECTION') ?: 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => (int)(getenv('DB_PORT') ?: 3306),
            'database' => getenv('DB_DATABASE') ?: '_shinkenchiku_db',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ],
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ],
        
        // 将来の拡張用（PostgreSQL、SQLite等）
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => (int)(getenv('DB_PORT') ?: 5432),
            'database' => getenv('DB_DATABASE') ?: 'pocketnavi',
            'username' => getenv('DB_USERNAME') ?: 'postgres',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ],
        
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => getenv('DB_DATABASE') ?: ':memory:',
            'prefix' => '',
        ],
    ],
    
    // マイグレーション設定
    'migrations' => [
        'table' => 'migrations',
        'batch' => 1,
    ],
    
    // レプリケーション設定（将来の拡張用）
    'replication' => [
        'write' => [
            'host' => getenv('DB_WRITE_HOST') ?: getenv('DB_HOST') ?: 'localhost',
        ],
        'read' => [
            'host' => getenv('DB_READ_HOST') ?: getenv('DB_HOST') ?: 'localhost',
        ],
    ],
];