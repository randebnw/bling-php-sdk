<?php

namespace Bling\Opencart;

abstract class Base {
	protected $registry;
	
	public function __construct($registry) {
		$this->registry = $registry;
	}
	
	public function __get($key) {
		return $this->registry->get($key);
	}
	
	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}
	
	public function check_keyword($keyword, $id, $type) {
		$sql = "SELECT COUNT(*) AS num FROM " . DB_PREFIX . "url_alias WHERE keyword = '" . $this->db->escape($keyword) . "' ";
		$result = $this->db->query($sql);
		if ($result->row['num'] > 0) {
			// keyword existe, vamos ter que gerar uma nova url
			$keyword = $keyword . '-' . $type . '-' . $id;
		}
			
		return $keyword;
	}
}
?>