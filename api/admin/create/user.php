<?php
    header('Content-Type: application/json');
    require __DIR__ . '/../../../configurations/config.php';

    if (!IS_FIRST_START) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $user = new user();
            if ($user -> is_found && $user -> is_granted) {
                if ($user -> data -> role == 1) {
                    if (!($not_found = check_payload('POST', [
                        'countryName',
                        'stateOrProvinceName',
                        'localityName',
                        'organizationName',
                        'organizationalUnitName',
                        'emailAddress',
                        'lastname',
                        'firstname',
                        'patronymic',
                        'role'
                    ]))) {
                        $role = intval($_POST['role']);
                        if (in_array($role, CONFIGURATION['system']['roles'])) {
                            $dbase = new new_mysqli();
                            if ($new_user = $user -> create_user(
                                $_POST['emailAddress'],
                                $_POST['countryName'],
                                $_POST['stateOrProvinceName'],
                                $_POST['localityName'],
                                $_POST['organizationName'],
                                $_POST['organizationalUnitName'],
                                $_POST['firstname'],
                                $_POST['lastname'],
                                $_POST['patronymic'],
                                $role
                            )) {
                                print(json_encode([
                                    'status' => 'OK',
                                    'user' => $new_user,
                                ]));
                            } else {
                                http_response_code(403);
                                print(json_encode([
                                    'status' => $new_user,
                                ]));
                            }
                            $dbase -> close();
                        } else {
                            http_response_code(403);
                            print(json_encode([
                                'status' => 'UNDEFINED_ROLE',
                            ]));
                        }
                    } else {
                        http_response_code(403);
                        print(json_encode([
                            'status' => 'QUERY_IS_NOT_FULL',
                            'where' => $not_found,
                        ]));
                    }
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
                'status' => 'ALLOWED_METHOD_IS_POST',
            ]));
        } 
    } else {
        http_response_code(403);
        print(json_encode([
            'status' => 'NEED_TO_SETUP',
        ]));
    }