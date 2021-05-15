<?php
    // Константы НАЧАЛО
    define('IS_FIRST_START', parse_ini_file(__DIR__ . '/const.ini', true)['first_start']['is_first_start'] == '1');
    define('CONFIGURATION', parse_ini_file(__DIR__ . '/const.ini', true));
    define('CLIENT_IP', !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']));
    // Константы КОНЕЦ
    // Функции НАЧАЛО
    function get_random_string(int $len = 8) {
        $alphabet = str_split('qazwsxedcrfvtgbyhnujmikolp1234567890');
        $ret = '';
        for ($i = 0; $i < $len; $i++)
            $ret .= $alphabet[random_int(0, count($alphabet) - 1)];
        return $ret;
    }

    function check_payload(string $type = 'POST', array $needles = []) {
        if (!empty($needles)) {
            $not_found = [];
            foreach ($needles as $value) {
                if ($type == 'POST' && empty($_POST[$value]))
                    $not_found[] = $value;
                elseif ($type == 'GET' && empty($_GET[$value]))
                    $not_found[] = $value;
                elseif ($type == 'BOTH' && empty($_REQUEST[$value]))
                    $not_found[] = $value;
            }
            return $not_found;
        } else return [];
    }

    function save_configuration(array $ini = null) {
        if (!is_null($ini)) {
            $return = '';
            foreach ($ini as $key => $value) {
                if (is_array($value)) {
                    $return .= "[$key]\n";
                    foreach ($value as $sub_key => $sub_value)
                        $return .= "{$sub_key} = {$sub_value}\n";
                } else $return .= "{$key} = {$value}\n";
            }
            file_put_contents(__DIR__ . '/const.ini', $return);
            return true;
        } else return false;
    }
    // Функции КОНЕЦ
    // Классы НАЧАЛО
    class new_mysqli extends mysqli {
        public function __construct($connection = []) {
            if (!IS_FIRST_START) {
                parent::__construct(CONFIGURATION['database']['host'], CONFIGURATION['database']['login'], CONFIGURATION['database']['password'], CONFIGURATION['database']['db_name']);
                $this -> set_charset('utf8mb4');
            } else {
                if (!empty($connection)) {
                    parent::__construct($connection['host'], $connection['login'], $connection['password'], $connection['db_name']);
                    if (!empty($this -> error))
                        return false;
                } else return false;
            }
        }

        public function protect_string(string $str = '') {
            return mb_strlen($str) != 0 ? htmlspecialchars($this -> real_escape_string($str)) : false;
        }

        public function decode_string(string $str = '') {
            return mb_strlen($str) != 0 ? htmlspecialchars_decode($str) : false;
        }

        public function new_database(
            string $email = '',
            string $countryName = '',
            string $stateOrProvinceName = '',
            string $localityName = '',
            string $organizationName = '',
            string $organizationalUnitName = '',
            string $firstname = '',
            string $lastname = '',
            string $patronymic = ''
        ) {
            if (IS_FIRST_START) {
                $password['primary'] = get_random_string(16);
                $password['hash'] = password_hash($password['primary'], PASSWORD_DEFAULT);
                $fullname = json_encode([
                    'lastname' => $this -> protect_string($lastname),
                    'firstname' => $this -> protect_string($firstname),
                    'patronymic' => $this -> protect_string($patronymic)
                ], JSON_UNESCAPED_UNICODE);
                $location = json_encode([
                    'countryName' => $this -> protect_string($countryName),
                    'stateOrProvinceName' => $this -> protect_string($stateOrProvinceName),
                    'localityName' => $this -> protect_string($localityName)
                ], JSON_UNESCAPED_UNICODE);
                $organization = json_encode([
                    'organizationName' => $this -> protect_string($organizationName),
                    'organizationalUnitName' => $this -> protect_string($organizationalUnitName)
                ], JSON_UNESCAPED_UNICODE);
                $this -> multi_query(file_get_contents(__DIR__ . '/database.sql') . "INSERT INTO `users` (`fullname`, `location`, `organization`, `role`) VALUES ('{$fullname}', '{$location}', '{$organization}', 1); INSERT INTO `auth` (`email`, `password_hash`, `user_id`) VALUES ('{$email}', '{$password["hash"]}', 1);");
                if (!$this -> errno) {
                    return [
                        'email' => $email,
                        'password' => $password['primary'],
                    ];
                } else return false;
            } else return false;
        }
    }

    class signatures {
        private $database = null;

        public function __construct() {
            if (!IS_FIRST_START) {
                $this -> database = new new_mysqli();
            } else return false;
        }

        public function close() {
            $this -> database -> close();
        }

    }

    class user {
        private $database = null;
        private $auth = [];
        private $token = '';
        
        public $data = [];
        public $is_found = false;
        public $is_granted = false;
        public $status = '';

        public function __construct() {
            if (!IS_FIRST_START) {
                $this -> token = !empty(getallheaders()['Authorization']) ? explode(' ', getallheaders()['Authorization'])[1] : '';
                if (!empty($this -> token)) {
                    $this -> database = new new_mysqli();
                    $this -> token = $this -> database -> protect_string($this -> token);
                    $query = $this -> database -> query("SELECT * FROM `auth` WHERE `tokens` LIKE '%{$this -> token}%';");
                    if ($query -> num_rows) {
                        $this -> is_found = true;
                        $cache = $query -> fetch_assoc();
                        $this -> auth = (object) [
                            'email' => $cache['email'],
                            'tokens' => json_decode($cache['tokens']),
                            'user_id' => intval($cache['user_id']),
                        ];
                        $cache = $this -> database -> query("SELECT * FROM `users` WHERE `id` = {$this -> auth -> user_id};") -> fetch_assoc();
                        $this -> data = (object) [
                            'fullname' => json_decode($cache['fullname']),
                            'location' => json_decode($cache['location']),
                            'organization' => json_decode($cache['organization']),
                            'role' => intval($cache['role']),
                        ];
                        $_del = -1;
                        foreach ($this -> auth -> tokens as $key => $value)
                            if ($value -> token == $this -> token) {
                                if ($value -> ip == CLIENT_IP) {
                                    if ($value -> expire_at > time()) {
                                        if (password_verify($this -> token, $value -> sign)) {
                                            $this -> is_granted = true;
                                            $this -> status = 'OK';
                                        } else $this -> status = 'FAKE_TOKEN';
                                    } else $this -> status = 'EXPIRED_TOKEN';
                                } else $this -> status = 'NEED_TO_VERIFY_IP';
                            }
                        if (in_array($this -> status, ['FAKE_TOKEN', 'EXPIRED_TOKEN'])) {
                            $new_tokens = [];
                            foreach ($this -> auth -> tokens as $value)
                                if ($this -> token != $value -> token)
                                    $new_tokens[] = $value;
                            $new_tokens = json_encode($new_tokens);
                            $this -> database -> query("UPDATE `auth` SET `tokens` = '{$new_tokens}' WHERE `tokens` LIKE '%{$this -> token}%';");
                        }
                    }
                } else $this -> status = 'NEED_TO_LOGIN';
            } else return 'NEED_TO_SETUP';
        }

        public function logout() {
            if ($this -> is_found) {
                $new_tokens = [];
                foreach ($this -> auth -> tokens as $key => $value)
                    if ($value -> token != $this -> token)
                        $new_tokens[] = $value;
                $new_tokens = json_encode($new_tokens);
                $this -> database -> query("UPDATE `auth` SET `tokens` = '{$new_tokens}' WHERE `tokens` LIKE '%{$this -> token}%';");
                return true;
            } else return false;
        }

        public function create_user(
            string $email = '',
            string $countryName = '',
            string $stateOrProvinceName = '',
            string $localityName = '',
            string $organizationName = '',
            string $organizationalUnitName = '',
            string $firstname = '',
            string $lastname = '',
            string $patronymic = '',
            int $role = null
        ) {
            if ($this -> data -> role == 1) {
                $email = $this -> database -> protect_string($email);
                if ($this -> database -> query("SELECT `id` FROM `auth` WHERE `email` = '{$email}';") -> num_rows == 0) {
                    $password['primary'] = get_random_string(16);
                    $password['hash'] = password_hash($password['primary'], PASSWORD_DEFAULT);
                    $fullname = json_encode([
                        'lastname' => $this -> database -> protect_string($lastname),
                        'firstname' => $this -> database -> protect_string($firstname),
                        'patronymic' => $this -> database -> protect_string($patronymic)
                    ], JSON_UNESCAPED_UNICODE);
                    $location = json_encode([
                        'countryName' => $this -> database -> protect_string($countryName),
                        'stateOrProvinceName' => $this -> database -> protect_string($stateOrProvinceName),
                        'localityName' => $this -> database -> protect_string($localityName)
                    ], JSON_UNESCAPED_UNICODE);
                    $organization = json_encode([
                        'organizationName' => $this -> database -> protect_string($organizationName),
                        'organizationalUnitName' => $this -> database -> protect_string($organizationalUnitName)
                    ], JSON_UNESCAPED_UNICODE);
                    $this -> database -> query("INSERT INTO `users` (`fullname`, `location`, `organization`, `role`) VALUES ('{$fullname}', '{$location}', '{$organization}', {$role});");
                    $this -> database -> query("INSERT INTO `auth` (`email`, `password_hash`, `user_id`) VALUES ('{$email}', '{$password["hash"]}', {$this -> database -> insert_id});");
                    if (!$this -> errno) {
                        return [
                            'email' => $email,
                            'password' => $password['primary'],
                        ];
                    } else return 'INTERNAL_SERVER_ERROR';
                } else return 'EMAIL_IS_NOT_UNIQUE';
            } else return 'ACCESS_DENIED';
        }

        public function close() {
            $this -> database -> close();
        }
    }