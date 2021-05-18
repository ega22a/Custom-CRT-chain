<?php
    header('Content-Type: application/json');
    require __DIR__ . '/../../configurations/config.php';

    if (!IS_FIRST_START) {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $user = new user();
            if ($user -> is_found && $user -> is_granted) {
                print(json_encode([
                    'status' => 'OK',
                    'user' => $user -> data,
                ]));
            } else {
                http_response_code(401);
                print(json_encode([
                    'status' => $user -> status,
                ]));
            }
            $user -> close();
        } else {
            http_response_code(405);
            print(json_encode([
                'status' => 'ALLOWED_METHOD_IS_GET',
            ]));
        } 
    } else {
        http_response_code(403);
        print(json_encode([
            'status' => 'NEED_TO_SETUP',
        ]));
    }