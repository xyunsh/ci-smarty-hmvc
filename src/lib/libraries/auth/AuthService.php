<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * Created by xsh on Feb 24, 2014
 *
 */

require_once 'Result.php';

interface AuthServiceInterface {
    function login($userName, $plainPassword);
    function loginByEmail($email, $plainPassword);
}

class AuthService implements AuthServiceInterface {
    private $auth;

    public function __construct($auth = NULL) {
        $this->auth = $auth == NULL ? new DatabaseAuthService() : $auth;
    }

    public function login($userName, $plainPassword) {
        $user = $this->auth->login($userName, $plainPassword);
        return $user == NULL ? Result::Error() : Result::Success($user);
    }

    public function loginByEmail($email, $plainPassword) {
        $user = $this->auth->loginByEmail($email, $plainPassword);
        return $user == NULL ? Result::Error() : Result::Success($user);
    }
}

class DatabaseAuthService implements AuthServiceInterface {
    public function __construct() {
        $ci = &get_instance();
        $ci->load->database();
        $this->db = &$ci->db;
    }

    public function login($userName, $plainPassword) {
        $sql  = 'select * from user where userName = ?';
        $user = $this->db->query($sql, array($userName))->row_array();
        if (!$user) {
            return NULL;
        }

        if ($user['hashedPassword'] == md5($plainPassword . $user['salt'])) {
            return array('id' => $user['id'], 'name' => $user['userName'], 'email' => $user['email']);
        }

        return NULL;
    }

    public function loginByEmail($email, $plainPassword) {
        $sql  = 'select * from user where email = ?';
        $user = $this->db->query($sql, array($email))->row_array();
        if (!$user) {
            return NULL;
        }

        if ($user['hashedPassword'] == md5($plainPassword . $user['salt'])) {
            return array('id' => $user['id'], 'name' => $user['userName'], 'email' => $user['email']);
        }

        return NULL;
    }
}
?>
