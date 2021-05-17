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
                            $dbase = new new_mysqli();
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
                            $CA_root_private_key = openssl_pkey_new([
                                'private_key_bits' => 4096,
                                'private_key_type' => OPENSSL_KEYTYPE_RSA
                            ]);
                            $CA_root_csr = openssl_csr_new(
                                $dn,
                                $CA_root_private_key,
                                [
                                    'digest_alg' => 'sha256'
                                ]
                            );
                            $CA_root_x509 = openssl_csr_sign(
                                $CA_root_csr,
                                null,
                                $CA_root_private_key,
                                $days = 10000,
                                [
                                    'digest_alg' => 'sha256'
                                ]
                            );
                            $raw_data = [
                                'x509' => null,
                                'pkey' => null
                            ];
                            openssl_x509_export($CA_root_x509, $raw_data['x509']);
                            openssl_pkey_export($CA_root_private_key, $raw_data['pkey'], $passphrase['primary']);
                            file_put_contents(CONFIGURATION['system']['path'] . '/zones/root/public.crt', $raw_data['x509']);
                            file_put_contents(CONFIGURATION['system']['path'] . '/zones/root/private.key', $raw_data['pkey']);
                            $dn = json_encode($dn, JSON_UNESCAPED_UNICODE);
                            $dbase -> query("INSERT INTO `signatures` (`dn`, `path`, `creater_id`, `owner_id`, `passphrase`, `valid_to`) VALUES ('{$dn}', '/zones/root', 1, 1, '{$passphrase["hash"]}', '" . (time() + 864000000) . "');");
                            $tmp_conf = CONFIGURATION;
                            $tmp_conf['first_start']['is_root_zone_created'] = 'true';
                            save_configuration($tmp_conf);
                            print(json_encode([
                                'status' => 'ok',
                                'passphrase' => $passphrase['primary'],
                            ]));
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