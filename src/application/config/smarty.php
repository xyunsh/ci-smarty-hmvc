<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * CI Smarty
 *
 * Smarty templating for Codeigniter
 *
 * @package   CI Smarty
 * @author      Dwayne Charrington
 * @copyright  2014 Dwayne Charrington and Github contributors
 * @link           http://ilikekillnerds.com
 * @license     http://www.apache.org/licenses/LICENSE-2.0.html
 * @version     2.0
 */

$config['smarty'] = array(
    // Smarty caching enabled by default unless explicitly set to FALSE
    'cache_status'             => FALSE,
    // Cache lifetime. Default value is 3600 seconds (1 hour) Smarty's default value
    //'cache_lifetime' => 3600,
    // Where templates are compiled
    'compile_directory'        => APPPATH . "cache/smarty/compiled/",
    // Where templates are cached
    'cache_directory'          => APPPATH . "cache/smarty/cached/",
    // Where Smarty configs are located
    'config_directory'         => APPPATH . "third_party/Smarty/configs/",
    // Default extension of templates if one isn't supplied
    'template_ext'             => 'html',
    // Error reporting level to use while processing templates
    'template_error_reporting' => E_ALL & ~E_NOTICE,
    // Debug mode turned on or off (TRUE / FALSE)
    'smarty_debug'             => FALSE,
);

if (defined('ENVIRONMENT')) {
    switch (ENVIRONMENT) {
        case 'development':
        //break;

        case 'testing':
        case 'production':
            $config['smarty']['template_error_reporting'] = 0;
            break;
    }
}