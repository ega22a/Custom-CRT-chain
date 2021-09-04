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
                    $zones = $dbase -> query($id == -1 ? "SELECT * FROM `zones`;" : "SELECT * FROM `zones` WHERE `id` = {$id};");
                    if ($zones -> num_rows) {
                        $show = [];
                        while ($item = $zones -> fetch_assoc())
                            $show[] = [
                                'id' => intval($item['id']),
                                'name' => $item['name'],
                                'dn' => json_decode($item['dn']),
                                'ids' => [
                                    'creater' => intval($item['creater_id']),
                                    'admin' => intval($item['admin_id']),
                                    'signature' => intval($item['signature_id'])
                                ],
                            ];
                        print(json_encode([
                            'status' => 'OK',
                            'zones' => $show,
                        ]));
                    } else print(json_encode([
                            'status' => 'OK',
                            'zones' => null
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