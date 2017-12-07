<?php

if (!defined('BASEPATH')) {exit('No direct script access allowed');
}

/**
 * Smarty Class
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Smarty
 * @author        Kepler Gelotte
 * @link        http://www.coolphptools.com/codeigniter-smarty
 */

require_once (LIBPATH . 'frameworks/smarty-3.1.30/libs/Smarty.class.php');

class CI_Smarty extends Smarty {
    public $template_ext = '.html';

    function __construct() {
        parent::__construct();

        $ci = &get_instance();
        $ci->load->config('smarty', FALSE, TRUE);

        $cfg = config_item('smarty');

        $default_cfg = array(
            'smarty_debug'             => FALSE,
            'compile_directory'        => APPPATH . "views/templates_c",
            'cache_directory'          => APPPATH . 'cache',
            'config_directory'         => LIBPATH . "third_party/Smarty/configs/",
            'template_ext'             => '.html',
            'cache_lifetime'           => 3600,
            'template_error_reporting' => E_ALL &~E_NOTICE,
            'template_directory'       => APPPATH . "views/templates",
            'plugins_directories'      => array(
                APPPATH . 'libraries/plugins',
                LIBPATH . 'libraries/plugins',
            ),
            'cache_status' => FALSE
        );

        if ($cfg) {
            $cfg = array_merge($default_cfg, $cfg);
        } else {
            $cfg = $default_cfg;
        }

        $this->debugging = $cfg['smarty_debug'];
        $this->setCompileDir($cfg['compile_directory']);
        $this->setCacheDir($cfg['cache_directory']);
        $this->setConfigDir($cfg['config_directory']);
        $this->template_ext    = $cfg['template_ext'];
        $this->cache_lifetime  = $cfg['cache_lifetime'];
        $this->error_reporting = $cfg['template_error_reporting'];
        $this->template_dir    = $cfg['template_directory'];

        foreach ($cfg['plugins_directories'] as $v) {
            $this->addPluginsDir($v);
        }

        if (method_exists($ci->router, 'fetch_module')) {
            $module = $ci->router->fetch_module();
            $this->addPluginsDir(APPPATH . 'modules/' . $module . '/libraries/plugins');
        }

        $this->assign('base_url', base_url());
        $this->assign('site_url', site_url());

        /*if ($cfg['cache_status'] === TRUE)
        {
        $this->enable_caching();
        }
        else
        {
        $this->disable_caching();
        }*/

        $this->assign('this', $ci);
        $this->assign('router', array('controller' => $ci->router->fetch_class(), 'method' => $ci->router->fetch_method()));

        log_message('debug', "Smarty Class Initialized");
    }

    /**
     *  Parse a template using the Smarty engine
     *
     * This is a convenience method that combines assign() and
     * display() into one step.
     *
     * Values to assign are passed in an associative array of
     * name => value pairs.
     *
     * If the output is to be returned as a string to the caller
     * instead of being output, pass true as the third parameter.
     *
     * @access    public
     * @param    string
     * @param    array
     * @param    bool
     * @return    string
     */

    function view($template, $data = array(), $return = FALSE) {
        foreach ($data as $key => $val) {
            $this->assign($key, $val);
        }
        if ($return == FALSE) {
            $CI = &get_instance();
            if (method_exists($CI->output, 'set_output')) {
                $CI->output->set_output($this->fetch($template));
            } else {
                $CI->output->final_output = $this->fetch($template);
            }
            return;
        } else {
            return $this->fetch($template);
        }
    }

    /**
     * Enable Caching
     *
     * Allows you to enable caching on a page by page basis
     * @example $this->smarty->enable_caching(); then do your parse call
     */
    public function enable_caching() {
        $this->caching = 1;
    }

    /**
     * Disable Caching
     *
     * Allows you to disable caching on a page by page basis
     * @example $this->smarty->disable_caching(); then do your parse call
     */
    public function disable_caching() {
        $this->caching = 0;
    }
}

// END Smarty Class