<?php

return [
    'retention_days' => (int) env('CAMERA_EVENT_RETENTION_DAYS', 30),

    'presets' => [
        'vehicle_detected' => 'Обнаружен транспорт',
        'line_crossed' => 'Пересечение линии',
        'area_intrusion' => 'Вторжение в область',
        'person_detected' => 'Обнаружен человек',
        'motion_detected' => 'Обнаружено движение',
    ],
];
