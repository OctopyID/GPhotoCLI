<?php

return [
    'default' => 'file',

    'stores' => [
        'file' => [
            'driver' => 'file',
            'path'   => home_path('cache'),
        ],
    ],
];
