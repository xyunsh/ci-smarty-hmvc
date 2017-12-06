<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * Created by xsh on Feb 24, 2014
 *
 */
//登陆信息存储
interface LoginStorageInterface{
    function get($client_identity);
    function set($client_identity, $logs, $log, $max_count, $expire);
    function clear($client_identity);
}

class LoginStorage implements LoginStorageInterface{
    private $storage;

    function __construct(){
        $this->storage = new LoginNoLimitedStorage();
    }

    function get($client_identity){
        return $this->storage->get($client_identity);
    }

    function set($client_identity, $logs, $log, $max_count, $expire){
        $this->storage->set($client_identity, $logs, $log, $max_count, $expire);
    }

    function clear($client_identity){
        $this->storage->clear($client_identity);
    }
}

//db中存储
class LoginDbStorage implements LoginStorageInterface{
    private $db;

    function __construct(){
        $ci =& get_instance();

        //$this->db = $ci->load->database();
    }

    function get($client_identity){
        $logs = $this->db->getAll('select id, login_name, login_time from '. table('login_log') . ' where ip = "' . $client_identity .'" order by login_time');
        return $logs;
    }

    function set($client_identity, $logs, $log, $max_count, $expire){
        $count = count($logs);
        $login_log = array('login_time'=>$log['login_time'], 'login_name'=>$log['login_name'], 'ip'=> $client_identity);
        if($count >= $max_count){
            $this->db->update('login_log', $login_log, array('id'=>$logs[0]['id']));
        }else{
            $this->db->insert('login_log', $login_log);
        }
    }

    function clear($client_identity){
        $sql = 'DELETE FROM ' .table('login_log') . ' where ip = "' . $client_identity . '";';
        $this->db->query($sql);
    }
}

//存储登陆信息到memcached
class LoginMemcacheStorage implements LoginStorageInterface{

    private $mm;

    function __construct($mm){
        $this->mm = $mm;
    }

    function get($client_identity){
        if($this->mm){
            return $this->mm->get($client_identity);
        }
        return NULL;
    }

    function set($client_identity, $logs, $log, $max_count, $expire){
        if($this->mm){
            $logs[] = $log;
            if(count(logs) > $max_count)
                $logs = array_splice($logs,count($logs) - $max_count + 1);
            $this->mm->set($client_identity, $logs, $expire);
        }
    }

    function clear($client_identity){
        if($this->mm){
            $this->mm->delete($client_identity);
        }
    }
}

class LoginNoLimitedStorage implements LoginStorageInterface{
    function get($client_identity){
        return array();
    }

    function set($client_identity, $logs, $log, $max_count, $expire){

    }

    function clear($client_identity){

    }
}
?>
