<?php
namespace Turbo;

class Iterator extends \Cetera\Iterator\DbObject {
	
    public function __construct()
    {       
        parent::__construct();		
        $this->query->select('main.*')->from('turbo_lists', 'main');
    } 	
	
	 protected function fetchObject($row)
	 {
		 return RSSFeed::fetch($row);
	 }
	
}