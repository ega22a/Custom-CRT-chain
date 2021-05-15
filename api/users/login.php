<?php
    header('Content-Type: application/json');
    require __DIR__ . '/../../configurations/config.php';

    if (!IS_FIRST_START) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!($not_found = check_payload('POST', [
                'email',
                'password'
            ]))) {
                $dbase = new new_mysqli();
                $email = $dbase -> protect_string($_POST['email']);
                $user = $dbase -> query("SELECT `id`, `password_hash`, `tokens` FROM `auth` WHERE `email` = '{$email}';");
                if ($user -> num_rows) {
                    $user = $user -> fetch_assoc();
                    if (!empty($user['tokens'])) {
                        if (mb_stripos($user['tokens'], CLIENT_IP)) {
                            $tokens = json_decode($user['tokens']);
                            $token = '';
                            $_del = -1;
                            foreach ($tokens as $key => $value)
                                if ($value -> ip == CLIENT_IP) {
                                    if ($value -> expire_at > time())
                                        $token = $value -> token;
                                    else {
                                        $token = false;
                                        $_del = $key;
                                    }
                                }
                            if ($token)
                                print(json_encode([
                                    'status' => 'OK',
                                    'token' => $token
                                ]));
                            else {
                                $t = hash('sha256', $user['id'] . $user['password_hash'] . time() . CLIENT_IP);
                                $token = json_encode([[
                                    'token' => $t,
                                    'ip' => CLIENT_IP,
                                    'expire_at' => time() + 15770000,
                                    'sign' => password_hash($t, PASSWORD_DEFAULT)
                                ]]);
                                $new_tokens = [];
                                foreach ($tokens as $key => $value)
                                    if ($key != $_del)
                                        $new_tokens[] = $value;
                                $new_tokens[] = $token;
                                $new_tokens = json_encode($new_tokens);
                                $dbase -> query("UPDATE `auth` SET `tokens` = '{$new_tokens}' WHERE `id` = {$user["id"]};");
                                if (!$dbase -> errno) {
                                    print(json_encode([
                                        'status' => 'OK',
                                        'token' => $t,
                                    ]));
                                } else {
                                    http_response_code(500);
                                    print(json_encode([
                                        'status' => 'INTERNAL_SERVER_ERROR'
                                    ]));
                                }
                            }
                        } else {
                            $t = hash('sha256', $user['id'] . $user['password_hash'] . time() . CLIENT_IP);
                            $token = [
                                'token' => $t,
                                'ip' => CLIENT_IP,
                                'expire_at' => time() + 15770000,
                                'sign' => password_hash($t, PASSWORD_DEFAULT)
                            ];
                            $tokens[] = $token;
                            $tokens = json_encode($tokens);
                            $dbase -> query("UPDATE `auth` SET `tokens` = '{$tokens}' WHERE `id` = {$user["id"]};");
                            if (!$dbase -> errno) {
                                print(json_encode([
                                    'status' => 'OK',
                                    'token' => $t,
                                ]));
                            } else {
                                http_response_code(500);
                                print(json_encode([
                                    'status' => 'INTERNAL_SERVER_ERROR',
                                ]));
                            }
                        }
                    } else {
                        $t = hash('sha256', $user['id'] . $user['password_hash'] . time() . CLIENT_IP);
                        $token = json_encode([[
                            'token' => $t,
                            'ip' => CLIENT_IP,
                            'expire_at' => time() + 15770000,
                            'sign' => password_hash($t, PASSWORD_DEFAULT)
                        ]]);
                        $dbase -> query("UPDATE `auth` SET `tokens` = '{$token}' WHERE `id` = {$user["id"]};");
                        if (!$dbase -> errno) {
                            print(json_encode([
                                'status' => 'OK',
                                'token' => $t,
                            ]));
                        } else {
                            http_response_code(500);
                            print(json_encode([
                                'status' => 'INTERNAL_SERVER_ERROR'
                            ]));
                        }
                    }
                } else {
                    http_response_code(401);
                    print(json_encode([
                        'status' => 'INCORRECT_AUTH_DATA',
                    ]));
                }
                $dbase -> close();
            } else {
                http_response_code(403);
                print(json_encode([
                    'status' => 'QUERY_IS_NOT_FULL',
                    'where' => $not_found,
                ]));
            }
        } else {
            http_response_code(405);
            print(json_encode([
                'status' => 'ALLOWED_METHOD_IS_POST',
            ]));
        } 
    } else {
        http_response_code(403);
        print(json_encode([
            'status' => 'NEED_TO_SETUP',
        ]));
    }