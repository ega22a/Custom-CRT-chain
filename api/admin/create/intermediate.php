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
                        'passphrase',
                        'zoneName',
                        'adminId',
                    ]))) {
                        $dbase = new new_mysqli();
                        $admin_id = intval($_POST['adminId']);
                        $zone_name = $dbase -> protect_string($_POST['zoneName']);
                        if ($dbase -> query("SELECT `id` FROM `zones` WHERE `name` = '{$zone_name}';") -> num_rows == 0) {
                            if ($dbase -> query("SELECT `id` FROM `users` WHERE `id` = {$admin_id};") -> num_rows) {
                                $root_cert = $dbase -> query("SELECT * FROM `signatures` WHERE `id` = 1;") -> fetch_assoc();
                                if (password_verify($_POST['passphrase'], $root_cert['passphrase'])) {
                                    $passphrase['primary'] = get_random_string(32);
                                    $passphrase['hash'] = password_hash($passphrase['primary'], PASSWORD_DEFAULT);
                                    $dn = [
                                        'countryName' => $dbase -> protect_string($_POST['countryName']),
                                        'stateOrProvinceName' => $dbase -> protect_string($_POST['stateOrProvinceName']),
                                        'localityName' => $dbase -> protect_string($_POST['localityName']),
                                        'organizationName' => $dbase -> protect_string($_POST['organizationName']),
                                        'organizationalUnitName' => $dbase -> protect_string($_POST['organizationalUnitName']),
                                        'emailAddress' => $dbase -> protect_string($_POST['emailAddress'])
                                    ];
                                    $CA_intermediate_private_key = openssl_pkey_new([
                                        'private_key_bits' => 4096,
                                        'private_key_type' => OPENSSL_KEYTYPE_RSA,
                                    ]);
                                    $CA_intermediate_csr = openssl_csr_new(
                                        $dn,
                                        $CA_intermediate_private_key,
                                        [
                                            'digest_alg' => 'sha256',
                                        ]
                                    );
                                    $CA_intermediate_x509 = openssl_csr_sign(
                                        $CA_intermediate_csr,
                                        sprintf("file://%s/%s/public.crt", CONFIGURATION['system']['path'], $root_cert['path']),
                                        [
                                            sprintf("file://%s/%s/private.key", CONFIGURATION['system']['path'], $root_cert['path']),
                                            $_POST['passphrase']
                                        ],
                                        $days = 8000,
                                    );
                                    $raw_data = [
                                        'x509' => null,
                                        'pkey' => null
                                    ];
                                    openssl_x509_export($CA_intermediate_x509, $raw_data['x509']);
                                    openssl_pkey_export($CA_intermediate_private_key, $raw_data['pkey'], $passphrase['primary']);
                                    $dn = json_encode($dn, JSON_UNESCAPED_UNICODE);
                                    $md5_name = md5($zone_name . json_encode($dn));
                                    mkdir(CONFIGURATION['system']['path'] . "/zones/intermediates/{$md5_name}");
                                    file_put_contents(CONFIGURATION['system']['path'] . "/zones/intermediates/{$md5_name}/public.crt", $raw_data['x509']);
                                    file_put_contents(CONFIGURATION['system']['path'] . "/zones/intermediates/{$md5_name}/private.key", $raw_data['pkey']);
                                    $dbase -> query("INSERT INTO `signatures` (`dn`, `path`, `creater_id`, `owner_id`, `passphrase`, `valid_to`) VALUES ('{$dn}', '/zones/intermediate/{$md5_name}', 1, {$admin_id}, '{$passphrase["hash"]}', '" . (time() + 691200000) . "');");
                                    $insert_id = $dbase -> insert_id;
                                    $dbase -> query("INSERT INTO `zones` (`name`, `dn`, `creater_id`, `admin_id`, `signature_id`) VALUES ('{$zone_name}', '{$dn}', 1, {$admin_id}, $insert_id);");
                                    print(json_encode([
                                        'status' => 'OK',
                                        'zone' => [
                                            'name' => $zone_name,
                                            'passphrase' => $passphrase['primary'],
                                        ],
                                    ]));
                                } else {
                                    http_response_code(401);
                                    print(json_encode([
                                        'status' => 'ROOT_UNAUTHORIZED',
                                    ]));
                                }
                            } else {
                                http_response_code(403);
                                print(json_encode([
                                    'status' => 'USER_IS_NOT_FOUND',
                                ]));
                            }
                        } else {
                            http_response_code(403);
                            print(json_encode([
                                'status' => 'NOT_UNIQUE_ZONENAME',
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