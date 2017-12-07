<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once LIBPATH . 'core/base_mx_controller.php';

class MY_Controller extends base_mx_controller {

}

class MY_AuthController extends MY_Controller {

    public function __construct($db) {
        parent::__construct($db);
    }

    function before() {
        parent::before();
        if (!$this->auth->loggedIn()) {
            redirect('user/login');
        } else {
            $user = $this->user;
        }
    }
}

?>
