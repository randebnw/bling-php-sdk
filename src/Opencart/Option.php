<?php

namespace Bling\Opencart;

class Option extends \Bling\Opencart\Base {
	private $language_id;
	
	public function __construct($registry) {
		parent::__construct($registry);
		$this->language_id = $registry->get('config')->get('config_language_id');
	}

	public function get_all() {
		$sql = "SELECT opt.option_id, od.name FROM `" . DB_PREFIX . "option` opt ";
		$sql .= "JOIN `" . DB_PREFIX . "option_description` od ON (od.option_id = opt.option_id AND od.language_id = " . $this->language_id . ") ";
		$result = $this->db->query($sql);
		$options = [];
		foreach ($result->rows as $item) {
			$sql = "SELECT opt.option_value_id, od.name FROM `" . DB_PREFIX . "option_value` opt ";
			$sql .= "JOIN `" . DB_PREFIX . "option_value_description` od ON (od.option_value_id = opt.option_value_id AND od.language_id = " . $this->language_id . ") ";
			$sql .= "WHERE opt.option_id = " . $item['option_id'];
			$opt_values = $this->db->query($sql);
			
			$options[$item['option_id']]['name'] = [$item['name']];
			$options[$item['option_id']]['values'] = [];
			foreach ($opt_values->rows as $opt) {
				$options[$item['option_id']]['values'][] = [
					'name' => $opt['name'],
					'id' => $opt['option_value_id'],
					'option_id' => $item['option_id'],
				];
			}
		}
		
		return $options;
	}
	
	/*public function insert($category, $parent_id = 0) {
		$sql = "INSERT INTO " . DB_PREFIX . "category ";
		$sql .= "SET `parent_id` = '" . (int)$parent_id . "', ";
		$sql .= "`bling_id` = '" . $this->db->escape($category['id']). "', ";
		$sql .= "`top` = 0, `column` = 1, `sort_order` = '0', ";
		$sql .= "`status` = 1, `date_modified` = NOW(), `date_added` = NOW()";
		$this->db->query($sql);
		
		$category_id = $this->db->getLastId();
		return $category_id;
	}*/
}
?>