<?php

return [

    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [

        'pgsql' => [
            'driver'         => 'pgsql',
            'host'           => env('DB_HOST',     env('PGHOST',     '127.0.0.1')),
            'port'           => env('DB_PORT',     env('PGPORT',     '5432')),
            'database'       => env('DB_DATABASE', env('PGDATABASE', 'forge')),
            'username'       => env('DB_USERNAME', env('PGUSER',     'forge')),
            'password'       => env('DB_PASSWORD', env('PGPASSWORD', '')),
            'charset'        => 'utf8',
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => 'public',
            'sslmode'        => env('DB_SSLMODE',  env('PGSSLMODE',  'prefer')),
        ],

        'sqlite' => [
            'driver'                  => 'sqlite',
            'database'                => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ],

    ],

    'migrations' => [
        'table'                  => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client'  => env('REDIS_CLIENT', 'phpredis'),
        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
    ],

];
