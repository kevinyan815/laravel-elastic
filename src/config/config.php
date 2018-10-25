<?php
/*
 * This file is part of laravel-elastic.
 *
 * (c) Kevin Yan <kevinyan815@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [


    /*
    |--------------------------------------------------------------------------
    | Default Elastic Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('ELASTIC_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | ElasticSearch 连接的配置信息
    |--------------------------------------------------------------------------
    | index和type是ElasticSearch执行时操作才需要的配置信息, 其它几项配置信息是连接
    | ElasticSearch时需要的配置信息
    */

    'connections' => [
        'default' => [
            'host' => env('ELASTIC_HOST', 'localhost'),
            'port' => env('ELASTIC_PORT', '9200'),
            'scheme' => env('ELASTIC_SCHEME', 'http'),
            'user' => env('ELASTIC_USER', ''),
            'pass' => env('ELASTIC_PASS', ''),
            'index' => env('ELASTIC_INDEX', 'default'),//index name can ending with asterisk as a wildcard to match group of index
            'type'  => env('ELASTIC_TYPE', 'default'),
            'time_zone' => '+8:00',
        ]
    ]
];
