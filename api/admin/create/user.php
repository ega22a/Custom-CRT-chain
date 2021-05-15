<?php
    header('Content-Type: application/json');
    require __DIR__ . '/../../../configurations/config.php';

    if (!IS_FIRST_START) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!boolval(CONFIGURATION['first_start']['is_root_zone_created'])) {
                $user = new user();
                if ($user -> is_found && $user -> is_granted) {
                    if ($user -> data -> role == 1) {
                        if (!($not_found = check_payload('POST', [
                            'countryName',
                            'stateOrProvinceName',
                            'localityName',
                            'organizationName',
                            'organizationalUnitName',
                            'emailAddress'
                        ]))) {
                            
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
                http_response_code(403);
                print(json_encode([
                    'status' => 'ROOT_ZONE_IS_CREATED',
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