<?php

return [
    'name' => 'test',
    'version' => '0.0.1',
    'brokerServerList' => ['10.13.11.27', '10.13.11.28', '10.13.11.29'],
    'servicesClassPath' => './Full/',
    'servicesClassNameSpace' => 'Vpg\\Disturb\\Example\\Test',
    'storage' => [
        'adapter'=> 'elasticsearch',
        'config'=> [
            'host'=> 'http://10.13.22.227:9200'
        ]
    ],
    'steps' => [
        [
            'name' => 'start',
            'instances' => 3
        ],
        [
            'name' => 'bar'
        ],
        [
            'name' => 'foo'
        ],
        [
            [
                'name' => 'far',
                'instances' => 3
            ],
            [
                'name' => 'boo'
            ]
        ],
        [
            'name' => 'end'
        ]
    ]
];