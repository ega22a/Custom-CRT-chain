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
                    $users = $dbase -> query($id == -1 ? "SELECT * FROM `users`;" : "SELECT * FROM `users` WHERE `id` = {$id};");
                    if ($users -> num_rows) {
                        $show = [];
                        while ($item = $users -> fetch_assoc())
                            $show[] = [
                                'id' => intval($item['id']),
                                'fullname' => json_decode($item['fullname']),
                                'location' => json_decode($item['location']),
                                'organization' => json_decode($item['organization'])
                            ];
                        print(json_encode([
                            'status' => 'OK',
                            'users' => $show,
                        ]));
                    } else print(json_encode([
                            'status' => 'OK',
                            'users' => null
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