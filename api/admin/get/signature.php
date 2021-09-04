<?php
    header('Content-Type: application/json');
    require __DIR__ . '/../../../configurations/config.php';

    if (!IS_FIRST_START) {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $user = new user();
            if ($user -> is_found && $user -> is_granted) {
                if ($user -> data -> role == 1) {
                    $dbase = new new_mysqli();
                    $id = !empty($_GET['id']) ? intval($_GET['id']) : -1;
                    $signatures = $dbase -> query($id == -1 ? "SELECT * FROM `signatures`;" : "SELECT * FROM `signatures` WHERE `id` = {$id};");
                    if ($signatures -> num_rows) {
                        $show = [];
                        while ($item = $signatures -> fetch_assoc())
                            $show[] = $item;
                        print(json_encode([
                            'status' => 'OK',
                            'signatures' => $show,
                        ]));
                    } else print(json_encode([
                            'status' => 'OK',
                            'signatures' => null
                        ]));
                    $dbase -> close();
                } else {
                        http_response_code(401);
                        print(json_encode([
                            'status' => 'UNAUTHORIZED',
                        ]));
                    }
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