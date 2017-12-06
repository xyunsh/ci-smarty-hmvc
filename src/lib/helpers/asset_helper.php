<?php 
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/*
 * Created by xsh on Mar 17, 2014
 *
 */

if (!function_exists('app_asset_url')) {
	function app_asset_url() {
		$ci = &get_instance();
		return base_url() . $ci->config->item('app_asset_path');
	}
}

if (!function_exists('app_css_url')) {
	function app_css_url($path = '') {
		$ci = &get_instance();
		return base_url() . $ci->config->item('app_css_path') . $path;
	}
}

if (!function_exists('app_js_url')) {
	function app_js_url($path = '') {
		$ci = &get_instance();
		return base_url() . $ci->config->item('app_js_path') . $path;
	}
}

if (!function_exists('app_image_url')) {
	function app_image_url($path = '', $parent_path = '') {
		$ci = &get_instance();
		return base_url() . $ci->config->item('app_image_path') . $parent_path . $path;
	}
}

if (!function_exists('app_upload_url')) {
	function app_upload_url($path = '', $parent_path = '') {
		$ci = &get_instance();
		$upload = $ci->config->item('app_upload_path');
		if ($parent_path) {
			$parent_path .= '/';
		}

		if (strpos($upload, 'http://') === 0) {
			return $upload . $parent_path . $path;
		}

		return base_url() . $upload . $parent_path . $path;
	}
}

if (!function_exists('shared_asset_url')) {
	function shared_asset_url($path = '') {
		$ci = &get_instance();
		return base_url() . $ci->config->item('shared_asset_path') . $path;
	}
}

if (!function_exists('shared_css_url')) {
	function shared_css_url($path = '') {
		$ci = &get_instance();
		return base_url() . $ci->config->item('shared_css_path') . $path;
	}
}

if (!function_exists('shared_js_url')) {
	function shared_js_url($path = '') {
		$ci = &get_instance();
		return base_url() . $ci->config->item('shared_js_path') . $path;
	}
}

if (!function_exists('shared_image_url')) {
	function shared_image_url() {
		$ci = &get_instance();
		return base_url() . $ci->config->item('shared_image_path');
	}
}

if (!function_exists('css')) {
	function css($file, $url, $media = 'all') {
		return '<link rel="stylesheet" type="text/css" href="' . $url . $file . '" media="' . $media . '">' . "\n";
	}
}

if (!function_exists('app_css')) {
	function app_css($file, $media = 'all') {
		return css($file, app_css_url());
	}
}

if (!function_exists('shared_css')) {
	function shared_css($file, $media = 'all') {
		return css($file, shared_css_url());
	}
}

if (!function_exists('js')) {
	function js($file, $js_url, $atts = array()) {
		$element = '<script type="text/javascript" src="' . $js_url . $file . '"';

		foreach ($atts as $key => $val) {
			$element .= ' ' . $key . '="' . $val . '"';
		}

		$element .= '></script>' . "\n";

		return $element;
	}
}

if (!function_exists('app_js')) {
	function app_js($file, $atts = array()) {
		return js($file, app_js_url(), $atts);
	}
}

if (!function_exists('shared_js')) {
	function shared_js($file, $atts = array()) {
		return js($file, shared_js_url(), $atts);
	}
}

/**
 * Load Image
 * Creates an <img> tag with src and optional attributes
 * @access  public
 * @param   string
 * @param   array   $atts Optional, additional key/value attributes to include in the IMG tag
 * @return  string
 */
if (!function_exists('img')) {
	function img($file, $img_url, $atts = array()) {
		$url = '<img src="' . $img_url . $file . '"';
		foreach ($atts as $key => $val) {
			$url .= ' ' . $key . '="' . $val . '"';
		}

		$url .= " />\n";
		return $url;
	}
}

if (!function_exists('app_image')) {
	function app_image($file, $atts = array()) {
		return img($file, app_image_url(), $atts);
	}
}

if (!function_exists('shared_image')) {
	function shared_image($file, $atts = array()) {
		return img($file, shared_image_url(), $atts);
	}
}

if (!function_exists('upload_image')) {
	function upload_image($file, $path = '', $atts = array()) {
		return img($file, app_upload_url($path), $atts);
	}
}

?>
