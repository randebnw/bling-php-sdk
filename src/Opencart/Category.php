<?php

namespace Bling\Opencart;

class Category extends \Bling\Opencart\Base {
	private $language_id;
	
	public function __construct($registry) {
		parent::__construct($registry);
		$this->language_id = $registry->get('config')->get('config_language_id');
	}

	public function get_all() {
		$sql = "SELECT category_id, bling_id, parent_id, operation_code FROM `" . DB_PREFIX . "category` WHERE bling_id IS NOT NULL AND bling_id > 0 ";
		$result = $this->db->query($sql);
		
		return $result->rows;
	}
	
	public function insert($category, $parent_id = 0) {
		$sql = "INSERT INTO " . DB_PREFIX . "category ";
		$sql .= "SET `parent_id` = '" . (int)$parent_id . "', ";
		$sql .= "`bling_id` = '" . $this->db->escape($category['id']). "', ";
		$sql .= "`top` = 0, `column` = 1, `sort_order` = '0', ";
		$sql .= "`status` = 1, `date_modified` = NOW(), `date_added` = NOW()";
		$this->db->query($sql);
		
		$category_id = $this->db->getLastId();
		
		$sql = "INSERT INTO " . DB_PREFIX . "category_description ";
		$sql .= "SET `category_id` = '" . (int)$category_id . "', ";
		$sql .= "`language_id` = '" . (int)$this->language_id . "', ";
		$sql .= "`name` = '" . $this->db->escape($category['name']) . "', ";
		$sql .= "`meta_keyword` = '', meta_description = '', ";
		$sql .= "`description` = ''";
		$this->db->query($sql);
		
		// MySQL Hierarchical Data Closure Table Pattern
		$level = 0;
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$parent_id . "' ORDER BY `level` ASC");
		foreach ($query->rows as $result) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");
				
			$level++;
		}
		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', `level` = '" . (int)$level . "'");
		
		// vincula categoria na loja
		$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = 0");
		
		// define url amigavel
		$keyword = $this->url->str2url($category['name']);
		$keyword = $this->check_keyword($keyword, $category_id, 'c');
		if ($keyword) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($keyword) . "'");
		}		
		
		return $category_id;
	}
}
?>