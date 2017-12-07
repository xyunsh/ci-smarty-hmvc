<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

function is_integer($x) {
    return (is_numeric($x) ? intval($x) == $x : false);
}

function start_with($haystack, mixed $needle) {
    return strpos($haystack, $needle) == 0;
}

/**
 * Formats a string with zero-based placeholders
 * {0}, {1} etc corresponding to an array of arguments
 * Must pass in a string and 1 or more arguments
 */
function string_format($str) {
    // replaces str "Hello {0}, {1}, {0}" with strings, based on
    // index in array
    $numArgs = func_num_args() - 1;

    if ($numArgs > 0) {
        $arg_list = array_slice(func_get_args(), 1);

        // start after $str
        for ($i = 0; $i < $numArgs; $i++) {
            $str = str_replace("{" . $i . "}", $arg_list[$i], $str);
        }
    }

    return $str;
}

function array_implode($glue, $separator, $array) {
    if (!$array) {
        return '';
    }

    if (!is_array($array)) {
        return $array;
    }

    $string = array();
    foreach ($array as $key => $val) {
        if (is_array($val)) {
            $val = implode(',', $val);
        }

        $string[] = "{$key}{$glue}{$val}";

    }

    return implode($separator, $string);
}

function array_merge_with_key($array1, $array2, $key) {
    $a = array();

    if ($array1) {
        foreach ($array1 as $value) {
            $a[$value[$key]] = $value;
        }
    }

    if ($array2) {
        foreach ($array2 as $value) {
            $a[$value[$key]] = $value;
        }
    }

    $result = array();

    foreach ($a as $key => $value) {
        $result[] = $value;
    }

    return $result;
}

function guid() {
    return md5(random_string('numeric', 16) . uniqid());
}

//format Y-m-d
function is_date($s) {
    $reg = "/^[0-9]{4}(\-|\/)[0-9]{1,2}(\\1)[0-9]{1,2}(|\s+[0-9]{1,2}(:[0-9]{1,2}){0,2})$/";
    return preg_match($reg, $s);
}

function is_email($m) {
    $reg = "/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/";
    return preg_match($reg, $m);
}

function is_mobile($m) {
    $reg = "/^(13[0-9]{9})|(15[0|1|2|3|5|6|7|8|9]\d{8})|(1[7|8]\d{9})$/";
    return preg_match($reg, $m);
}

function get_client_ip() {
    if (@$_SERVER["HTTP_X_FORWARDED_FOR"]) {
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } else if (@$_SERVER["HTTP_CLIENT_IP"]) {
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    } else if (@$_SERVER["REMOTE_ADDR"]) {
        $ip = $_SERVER["REMOTE_ADDR"];
    } else if (@getenv("HTTP_X_FORWARDED_FOR")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    } else if (@getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } else if (@getenv("REMOTE_ADDR")) {
        $ip = getenv("REMOTE_ADDR");
    } else {
        $ip = "Unknown";
    }

    return $ip;
}

function get_route_info() {
    $ci = &get_instance();

    $module = '';
    if (method_exists($ci->router, 'fetch_module')) {
        $module = $ci->router->fetch_module();
    }

    $controller = $ci->router->fetch_class();
    $method     = $ci->router->fetch_method();

    return array('module' => $module, 'controller' => $controller, 'method' => $method);
}

function request_is($match) {
    $route = get_route_info();

    $module     = $route['module'];
    $controller = $route['controller'];
    $method     = $route['method'];

    if (!empty($module)) {
        $module .= '/';
    }

    return stripos("$module$controller/$method", $match) === 0;
}

function request_info() {
    $requst_uri = $_SERVER['REQUEST_URI'];

    return (empty($requst_uri) ? '/' : $requst_uri);
}

function hierarchize($items, $parentId = 'parent_id', $childrenKey = 'children') {

    $keyItems = array();

    foreach ($items as $key => &$value) {
        $keyItems[$value['id']] = $value;
    }

    foreach ($keyItems as $key => &$value) {
        if (empty($value[$parentId])) {
            continue;
        }

        if (!isset($keyItems[$value[$parentId]][$childrenKey])) {
            $keyItems[$value[$parentId]][$childrenKey] = array();
        }

        $keyItems[$value[$parentId]][$childrenKey][] = &$value;
    }

    $array = array();
    foreach ($keyItems as $key => &$value) {
        if (empty($value[$parentId])) {
            $array[] = $value;
        }
    }

    return $array;
}

function create_string($char, $length) {
    $str = '';

    for ($i = 0; $i < $length; $i++) {
        $str .= $char;
    }
    return $str;
}

function log_json($level, $log) {
    log_message($level, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function full_url() {
    return "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

?>