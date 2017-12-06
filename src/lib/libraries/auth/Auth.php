<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * Created by xsh on Feb 24, 2014
 *
 */

class Auth{

    var $user;
    var $auth;
    var $storage;

    var $ci;

    public function __construct(){
        $this->ci =& get_instance();

        $this->ci->load->library('auth/AuthStragegy');
        $this->auth =& $this->ci->authstragegy;
        $this->ci->load->library('auth/AuthStorage');
        $this->storage =& $this->ci->authstorage;

        $this->user = $this->storage->get();
    }

    public function getUser(){
        return $this->user;
    }

    public function loggedIn(){
        return !empty($this->user);
    }

    public function login($clientIdentity, $loginName, $password, $authCode){
        $result = $this->auth->login($clientIdentity, $loginName, $password, $authCode);
        //var_dump($result);exit;
        if($result->success){
            $this->storage->save($result->data);
        }
        return $result;
    }

    public function logout(){
        $this->storage->remove();
    }
}
?>
