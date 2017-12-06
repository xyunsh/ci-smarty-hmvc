<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class base_mx_controller extends MX_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Utilizing the CodeIgniter's _remap function
     * to call extra functions with the controller action
     * @see http://codeigniter.com/user_guide/general/controllers.html#remapping
     **/
    public function _remap($method, $args) {
        // Call before action
        $this->before();

        call_user_func_array(array($this, $method), $args);

        // Call after action
        $this->after();
    }

    /**
     * These shouldn't be accessible from outside the controller
     **/
    protected function before() {return;}
    protected function after() {return;}

    function _callback($callback, $args) {
        if (is_string($callback)) {
            $data = $this->$callback($args);
        } else {
            $data = $callback($args);
        }

        $this->smarty->assign('data', $data);
    }

    function display($file, $callback = NULL, $cache_lifetime = 0, $cache_id = NULL, $args = NULL) {
        if ($callback != NULL) {
            if ($cache_lifetime > 0 && $cache_id) {
                $this->smarty->caching        = Smarty::CACHING_LIFETIME_SAVED;
                $this->smarty->cache_lifetime = $cache_lifetime;

                if ($args) {
                    $cache_id = string_format('{0}_{1}', array_implode('_', '_', $args));
                }

                if (!$this->smarty->isCached($file, $cache_id)) {
                    $this->_callback($callback, $args);
                }
            } else {
                $this->_callback($callback, $args);
            }
            $this->smarty->display($file, $cache_id);
        } else {
            $this->smarty->display($file);
        }
    }

    function assign($tpl_var, $value = null, $nocache = false) {
        $this->smarty->assign($tpl_var, $value, $nocache);
    }

    function fetch($template = null, $cache_id = null, $compile_id = null) {
        return $this->smarty->fetch($template, $cache_id, $compile_id);
    }

    function json($data) {
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($data));
    }

    function get($index, $defaultValue = '') {
        $v = $this->input->get($index);
        return $this->_input($v, $defaultValue);
    }

    function post($index, $defaultValue = '') {
        $v = $this->input->post($index);
        return $this->_input($v, $defaultValue);
    }

    function get_post($index, $defaultValue = '') {
        $v = $this->get($index);
        if (empty($v)) {
            $v = $this->post($index);
        }

        return $v;
    }

    function segment($index, $defaultValue = '') {
        $v = $this->uri->segment($index);
        return $this->_input($v, $defaultValue);
    }

    function _input($v, $defaultValue = '') {
        if (is_int($defaultValue)) {
            $v = (int) $v;
        }

        if (empty($v)) {
            return $defaultValue;
        }

        return $v;
    }

    function isPost() {
        return !empty($_POST);
    }
}

?>
