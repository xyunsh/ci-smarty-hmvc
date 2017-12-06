<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * Created by xsh on Mar 7, 2014
 *
 */

interface AuthStorageInterface {
    function save($user);
    function remove();
    function get();
}

class AuthStorage implements AuthStorageInterface {
    var $storage;

    function __construct($storage = null) {
        $this->storage = $storage == null ? new CookieAuthStorage() : $storage;
    }

    public function save($user) {
        $this->storage->save($user);
    }

    public function remove() {
        $this->storage->remove();
    }

    public function get() {
        return $this->storage->get();
    }

}

class CookieAuthStorage implements AuthStorageInterface {
    private $cookie_config;
    private $ci;

    public function __construct() {
        $this->ci = &get_instance();

        $this->ci->load->library('Crypter');
        $this->crypter = &$this->ci->crypter;

        $this->ci->config->load('auth');
        $config = &$this->ci->config;

        $this->cookie_config = array(
            'name'   => $config->item('cookie_auth_name'),
            'key'    => $config->item('cookie_auth_key'),
            'expire' => $config->item('cookie_auth_expire'),
        );
    }

    public function save($user) {
        $cookie_string  = json_encode($user);
        $cookie_encrypt = $this->crypter->encrypt($cookie_string, $this->cookie_config['key']);
        $cookie_encrypt = str_replace('=', '', base64_encode($cookie_encrypt));

        $cookie = array(
            'name'   => $this->cookie_config['name'],
            'value'  => $cookie_encrypt,
            'expire' => $this->cookie_config['expire'],
        );

        $this->ci->input->set_cookie($cookie);
    }

    public function remove() {
        delete_cookie($this->cookie_config['name']);
    }

    public function get() {
        $cookie = $this->ci->input->cookie($this->cookie_config['name']);

        if (empty($cookie)) {
            return null;
        }

        $cookie = base64_decode($cookie);

        $user_string = $this->crypter->decrypt($cookie, $this->cookie_config['key']);

        if (empty($user_string)) {
            return null;
        }

        return json_decode($user_string, TRUE);
    }
}
?>
