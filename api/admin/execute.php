<?php
    header('Content-Type: application/json');
    require __DIR__ . '/../../configurations/config.php';

    if (IS_FIRST_START) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!($not_found = check_payload('POST', [
                'passphrase',
                'lastname',
                'firstname',
                'patronymic',
                'countryName',
                'stateOrProvinceName',
                'localityName',
                'organizationName',
                'organizationalUnitName',
                'email',
                'db_host',
                'db_login',
                'db_password',
                'db_name',
                'folder_path'
            ]))) {
                if (!empty(CONFIGURATION['first_start']['passphrase'])) {
                    if (CONFIGURATION['first_start']['passphrase'] == $_POST['passphrase']) {
                        $dbase = new new_mysqli([
                            'host' => $_POST['db_host'],
                            'login' => $_POST['db_login'],
                            'password' => $_POST['db_password'],
                            'db_name' => $_POST['db_name']
                        ]);
                        if (empty($dbase -> error) && !is_bool($dbase -> error)) {
                            if (is_dir($_POST['folder_path'])) {
                                if ($admin = $dbase -> new_database(
                                    $_POST['email'],
                                    $_POST['countryName'],
                                    $_POST['stateOrProvinceName'],
                                    $_POST['localityName'],
                                    $_POST['organizationName'],
                                    $_POST['organizationalUnitName'],
                                    $_POST['firstname'],
                                    $_POST['lastname'],
                                    $_POST['patronymic']
                                )) {
                                    mkdir($_POST['folder_path'] . '/zones');
                                    mkdir($_POST['folder_path'] . '/zones/root');
                                    mkdir($_POST['folder_path'] . '/zones/intermediates');
                                    mkdir($_POST['folder_path'] . '/signatures');
                                    mkdir($_POST['folder_path'] . '/sigs');
                                    mkdir($_POST['folder_path'] . '/tmp');
                                    save_configuration([
                                        'first_start' => [
                                            'is_first_start' => 'false',
                                            'is_root_zone_created' => 'false',
                                        ],
                                        'database' => [
                                            'host' => $_POST['db_host'],
                                            'login' => $_POST['db_login'],
                                            'password' => $_POST['db_password'],
                                            'db_name' => $_POST['db_name'],
                                        ],
                                        'system' => [
                                            'path' => $_POST['folder_path'],
                                            'roles' => [1, 2, 3],
                                        ]
                                    ]);
                                    print(json_encode([
                                        'status' => 'OK',
                                        'admin' => $admin,
                                        'message' => 'ROOT_ZONE_IS_NOT_SET',
                                    ]));
                                } else {
                                    http_response_code(500);
                                    print(json_encode([
                                        'status' => 'DATABASE_IS_NOT_CREATED',
                                    ]));
                                }
                            } else {
                                http_response_code(500);
                                print(json_encode([
                                    'status' => 'GIVEN_PATH_IS_WRONG',
                                ]));
                            }
                        } else {
                            http_response_code(500);
                            print(json_encode([
                                'status' => 'CAN\'T_CONNECT_TO_DBASE',
                            ]));
                        }
                        $dbase -> close();
                    } else {
                        http_response_code(401);
                        print(json_encode([
                            'status' => 'PASSPHRASES_ARE_NOT_EQUAL',
                        ]));
                    }
                } else {
                    http_response_code(401);
                    print(json_encode([
                        'status' => 'PASSPHRASE_IS_NOT_SET',
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
            http_response_code(405);
            print(json_encode([
                'status' => 'ALLOWED_METHOD_IS_POST',
            ]));
        }
    } else {
        http_response_code(403);
        print(json_encode([
            'status' => 'SYSTEM_ALREADY_WORK',
        ]));
    }