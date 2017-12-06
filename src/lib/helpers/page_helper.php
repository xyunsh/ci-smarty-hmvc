<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class PageParams{
	public $page_index;
	public $page_size;
	public $sord_index;
	public $sord;

	function __construct($page_size, $default_sidx = '', $default_sord = ''){
		$this->PageParams($page_size, $default_sidx, $default_sord);
	}

	private function PageParams($page_size, $default_sidx = '', $default_sord = ''){
		$this->page_index = empty($_GET['page_index']) ? 1 : intval($_GET['page_index']);
		$this->page_size = !empty($page_size) ? intval($page_size) : 20;
		$this->sord_index = empty($_GET['sortby']) ? $default_sidx : $_GET['sortby'];
		$this->sord =  empty($_GET['sort']) ? $default_sord : $_GET['sort'];
	}

	public function order(){
		return "$this->sord_index $this->sord";
	}
}

class PageResult {
	public $total_count;
	public $items;
	public $total_page;
	public $page_size;
	public $page_index;
	public $offset;
	public $link_format;

	function __construct($page_index, $page_size, $total_count){

		$total_page = ceil($total_count / $page_size);
		if ($page_index > $total_page)
			$page_index = max($total_page,1);

		$offset = $page_size * ($page_index - 1);

		$this->total_count = $total_count;
		$this->total_page = $total_page;
		$this->page_size = $page_size;
		$this->page_index = $page_index;
		$this->offset = $offset;
		$this->items = array();
	}
}

?>