<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class M1 extends MY_Controller {
    public function index() {
        $this->display('m1/index.html');
    }
}