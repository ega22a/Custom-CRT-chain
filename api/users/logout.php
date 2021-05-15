<?php
    header('Content-Type: application/json');
    require __DIR__ . '/../../configurations/config.php';

    if (!IS_FIRST_START) {
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            $user = new user();
            if ($user -> logout()) {
                $user -> close();
                print(json_encode([
                    'status' => 'OK',
                ]));
            } else {
                http_response_code(403);
                print(json_encode([
                    'status' => 'UNAUTHORIZED',
                ]));
            }
        } else {
            http_response_code(405);
            print(json_encode([
                'status' => 'ALLOWED_METHOD_IS_DELETE',
            ]));
        } 
    } else {
        http_response_code(403);
        print(json_encode([
            'status' => 'NEED_TO_SETUP',
        ]));
    }