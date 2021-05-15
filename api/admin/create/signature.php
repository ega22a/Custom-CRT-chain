<?php
    header('Content-Type: application/json');
    require __DIR__ . '/../../../configurations/config.php';

    if (!IS_FIRST_START) {
        
    } else {
        http_response_code(403);
        print(json_encode([
            'status' => 'NEED_TO_SETUP',
        ]));
    }