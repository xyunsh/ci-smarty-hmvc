<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class base_model extends CI_Model {

    public function create_query($table) {
        return new QueryModel($table, $this->db);
    }
}

class QueryModel {
    private $_query;
    private $_order;
    private $_table;
    private $_fields;
    private $_db;

    function __construct($table, $db) {
        $this->QueryModel($table);
        $this->_db = $db;
    }

    private function QueryModel($table) {
        $this->_table  = $table;
        $this->_query  = '1=1';
        $this->_fields = '*';
    }

    public function where($query, $val = null, $enabled = true) {

        if (is_string($query)) {
            if ($enabled) {
                $w = '';

                if (is_null($val)) {
                    $w = $query;
                } else {
                    if (!(is_string($val) && empty($val))) {
                        $w = string_format($query, $this->_db->escape($val));
                    } else {
                        $w = $query;
                    }
                }

                if (!empty($w)) {
                    $this->_query .= " and ($w)";
                }
            }
        }

        if (is_array($query)) {
            foreach ($query as $key => $value) {
                $this->_query .= string_format(" and ({0} = {1})", $key, $this->_db->escape($value));
            }
        }

        return $this;
    }

    public function where_or() {
        $conditions = func_get_args();
        if (func_num_args() == 1 && is_array($conditions[0])) {
            $conditions = $conditions[0];
        }

        $q = '';

        foreach ($conditions as $key => $value) {
            $query   = $value[0];
            $val     = isset($value[1]) ? $value[1] : null;
            $enabled = isset($value[2]) ? $value[2] : true;

            if ($enabled) {
                $w = '';

                if (is_null($val)) {
                    $w = $query;
                } else {
                    if (!(is_string($val) && empty($val))) {
                        $w = string_format($query, $this->_db->escape($val));
                    }
                }

                if (empty($q)) {
                    $q = $w;
                } else {
                    $q .= " OR ($w)";
                }
            }
        }

        if (empty($q)) {
            return $this;
        }

        $this->_query .= " AND ($q)";

        return $this;
    }

    public function select($fields) {
        $this->_fields = $fields;
        return $this;
    }

    public function order($order) {
        $this->_order = $order;
        return $this;
    }

    public function get_query() {
        return $this->_query;
    }

    public function result_array() {
        $order    = empty($this->_order) ? '' : 'ORDER BY ' . $this->_order;
        $sqlQuery = string_format('SELECT {0} FROM {1} WHERE {2} {3}', $this->_fields, $this->_table, $this->_query, $order);
        return $this->_db->query($sqlQuery)->result_array();
    }

    public function result_page($page, $size) {
        $sqlCount = string_format('SELECT count(1) AS cnt FROM {0} WHERE {1}', $this->_table, $this->_query);
        $count    = $this->_db->query($sqlCount)->row_array();

        $total_count = $count['cnt'];
        $result      = new PageResult($page, $size, $total_count);

        $order         = empty($this->_order) ? '' : 'ORDER BY ' . $this->_order;
        $sqlQuery      = string_format('SELECT {0} FROM {1} WHERE {2} {3} LIMIT {4},{5}', $this->_fields, $this->_table, $this->_query, $order, $result->offset, $size);
        $result->items = $this->_db->query($sqlQuery)->result_array();

        return $result;
    }

    public function result_page_array($page, $size) {
        $result = $this->result_page($page, $size);

        return (array) $result;
    }
}
?>
