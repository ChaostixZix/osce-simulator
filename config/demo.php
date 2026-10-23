<?php

return [
    'enabled' => env('OSCE_DEMO_ENABLED', true),
    'case_title' => 'Chest Pain Assessment',
    // The authored chest-pain seed identifies these result templates by position.
    'investigations' => [
        'troponin' => ['name' => 'Troponin I', 'template' => '1'],
        'ecg' => ['name' => 'Electrocardiogram (ECG)', 'template' => '2'],
        'chest_xray' => ['name' => 'Chest X-Ray', 'template' => '3'],
    ],
];
