<?php

return [
    'default' => 'local',

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root'   => home_path('.gphoto'),
        ],
    ],
];
