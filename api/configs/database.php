<?php

return [
    'default' => [
        'db_type' => 'mysql',
        'host' => getProEnv('db.host'),
        'port' => getProEnv('db.port'),
        'database' => getProEnv('db.database'),
        'name' => getProEnv('db.name'),
        'password' => getProEnv('db.password'),
        'log' => 1,
        'prepare' => 1,
        'real_delete' => getProEnv('db.realDel'),
        'charset' => getProEnv('db.charset'),
        'prefix' => getProEnv('db.prefix'),
        'option' => [
            PDO::ATTR_CASE => PDO::CASE_NATURAL
        ],
    ]
];