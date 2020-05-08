<?php

namespace Bling\Opencart;

class Manufacturer extends \Bling\Opencart\Base {
	public function get_all() {
		$sql = "SELECT manufacturer_id, bling_id FROM `" . DB_PREFIX . "manufacturer` ";
		$result = $this->db->query($sql);
		
		return $result->rows;
	}
	
	public function insert($manufacturer) {
		$sql = "INSERT INTO " . DB_PREFIX . "manufacturer SET name = '" . $this->db->escape($manufacturer['name']) . "', bling_id = '" . $this->db->escape($manufacturer['id']) . "', sort_order = '0'";
		$this->db->query($sql);
	
		$manufacturer_id = $this->db->getLastId();
	
		$this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET manufacturer_id = '" . (int)$manufacturer_id . "', store_id = 0");
	
		// define url amigavel
		$keyword = $this->url->str2url($manufacturer['name']);
		$keyword = $this->check_keyword($keyword, $manufacturer_id, 'm');
		if ($keyword) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'manufacturer_id=" . (int)$manufacturer_id . "', keyword = '" . $this->db->escape($keyword) . "'");
		}
	
		$this->cache->delete('manufacturer');
	
		return $manufacturer_id;
	}
}
?>