<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

class HMVCParser extends CI_Parser {

    protected $CI;

    protected $_module = '';
    protected $_template_locations = array();

    // Current theme location
    protected $_current_path = NULL;

    public function __construct()
    {
        // Codeigniter instance and other required libraries/files
        $this->CI = get_instance();
        $this->CI->load->library('smarty');

        // Detect if we have a current module
        $this->_module = $this->current_module();

        // What controllers or methods are in use
        $this->_controller  = $this->CI->router->fetch_class();
        $this->_method     = $this->CI->router->fetch_method();

        // Update template paths
        $this->_update_template_paths();
    }

    /**
    * Call
    * able to call native Smarty methods
    * @returns mixed
    */
    public function __call($method, $params=array())
    {
        if ( ! method_exists($this, $method) )
        {
            return call_user_func_array(array($this->CI->smarty, $method), $params);
        }
    }

    /**
     * Current Module
     *
     * Just a fancier way of getting the current module
     * if we have support for modules
     *
     * @access public
     * @return string
     */
    public function current_module()
    {
        // Modular Separation / Modular Extensions has been detected
        if (method_exists( $this->CI->router, 'fetch_module' ))
        {
            $module = $this->CI->router->fetch_module();
            return (!empty($module)) ? $module : '';
        }
        else
        {
            return '';
        }
    }

    /**
     * Parse
     *
     * Parses a template using Smarty 3 engine
     *
     * @access public
     * @param $template
     * @param $data
     * @param $return
     * @param $caching
     * @param $theme
     * @return string
     */
    public function parse($template, $data = array(), $return = FALSE, $caching = TRUE, $theme = '')
    {
        // If we don't want caching, disable it
        if ($caching === FALSE)
        {
            $this->CI->smarty->disable_caching();
        }

        // If no file extension dot has been found default to defined extension for view extensions
        if ( ! stripos($template, '.'))
        {
            $template = $template.".".$this->CI->smarty->template_ext;
        }

        // Get the location of our view, where the hell is it?
        // But only if we're not accessing a smart resource
        if ( ! stripos($template, ':'))
        {
            $template = $this->_find_view($template);
        }

        // If we have variables to assign, lets assign them
        if ( ! empty($data))
        {
            foreach ($data AS $key => $val)
            {
                $this->CI->smarty->assign($key, $val);
            }
        }

        // Load our template into our string for judgement
        $template_string = $this->CI->smarty->fetch($template);

        // If we're returning the templates contents, we're displaying the template
        if ($return === FALSE)
        {
            $this->CI->output->append_output($template_string);
            return TRUE;
        }

        // We're returning the contents, fo' shizzle
        return $template_string;
    }

    /**
    * Find View
    *
    * Searches through module and view folders looking for your view, sir.
    *
    * @access protected
    * @param $file
    * @return string The path and file found
    */
    protected function _find_view($file)
    {
        // We have no path by default
        $path = NULL;

        // Get template locations
        $locations = $this->_template_locations;

        // Get the current module
        $current_module = $this->current_module();

        if ($current_module !== $this->_module)
        {
            $new_locations = array(
                APPPATH . 'modules/' . $current_module . '/views/'
            );

            foreach ($new_locations AS $new_location)
            {
                array_unshift($locations, $new_location);
            }
        }

        // Iterate over our saved locations and find the file
        foreach($locations AS $location)
        {
            if (file_exists($location.$file))
            {
                // Store the file to load
                $path = $location.$file;

                $this->_current_path = $location;

                // Stop the loop, we found our file
                break;
            }
        }

        // Return the path
        return $path;
    }

    /**
    * Add Paths
    *
    * Traverses all added template locations and adds them
    * to Smarty so we can extend and include view files
    * correctly from a slew of different locations including
    * modules if we support them.
    *
    * @access protected
    */
    protected function _add_paths()
    {
        // Iterate over our saved locations and find the file
        foreach($this->_template_locations AS $location)
        {
            $this->CI->smarty->addTemplateDir($location);
        }
    }

    /**
     * Update Theme Paths
     *
     * Adds in the required locations for themes
     *
     * @access protected
     */
    protected function _update_template_paths()
    {
        // Store a whole heap of template locations
        $this->_template_locations = array(
            APPPATH . 'modules/' . $this->_module . '/views/',
            APPPATH . 'views/'
        );

        // Will add paths into Smarty for "smarter" inheritance and inclusion
        $this->_add_paths();
    }

    /**
    * String Parse
    *
    * Parses a string using Smarty 3
    *
    * @param string $template
    * @param array $data
    * @param boolean $return
    * @param mixed $is_include
    */
    public function string_parse($template, $data = array(), $return = FALSE, $is_include = FALSE)
    {
        return $this->CI->smarty->fetch('string:'.$template, $data);
    }

    /**
    * Parse String
    *
    * Parses a string using Smarty 3. Never understood why there
    * was two identical functions in Codeigniter that did the same.
    *
    * @param string $template
    * @param array $data
    * @param boolean $return
    * @param mixed $is_include
    */
    public function parse_string($template, $data = array(), $return = FALSE, $is_include = false)
    {
        return $this->string_parse($template, $data, $return, $is_include);
    }

}
