<?php

class ControllerCache {

    static $controllers = array();

    public static function get_controller($controller) {
        if (!isset(self::$controllers[$controller])) {
            self::$controllers[$controller] = new $controller;
        }

        return self::$controllers[$controller];
    }
}

function smarty_function_widgets($params, &$smarty) {
    $path = $params['path'];

    if (empty($params)) {
        echo 'widgets miss path';
        return;
    }

    $ps = explode('/', $path);

    if (count($ps) == 2) {
        $controller = $ps[0];
        $method     = $ps[1];

        require_once APPPATH . 'controllers/' . $controller . '.php';
    } elseif (count($ps) == 3) {
        $module     = $ps[0];
        $controller = $ps[1];
        $method     = $ps[2];

        require_once APPPATH . 'modules/' . $module . '/controllers/' . $controller . '.php';
    }

    $c = ControllerCache::get_controller($controller);//$c = new $controller;

    unset($params['path']);

    $count = count($params);

    if ($count == 0) {
        $c->$method();
    } elseif ($count == 1) {
        $c->$method(reset($params));
    } else {
        $c->$method($params);
    }
}

?>