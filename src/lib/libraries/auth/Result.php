<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * Created by xsh on Feb 24, 2014
 *
 */

class Result{
    public $success;
    public $code;
    public $data;
    public $message;

    private function __construct($success,$code,$data, $message){
        $this->success = $success;
        $this->code = $code;
        $this->data = $data;
        $this->message = $message;
    }

    public static function Success($data, $code = 200, $message = ''){
        return new Result(true,$code,$data, $message);
    }

    public static function Error($data='', $code='', $message = ''){
        return new Result(false,$code,$data, $message);
    }
}

?>
