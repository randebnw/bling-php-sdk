<?php

namespace Bling\Opencart;

class Option extends \Bling\Opencart\Base {
	private $language_id;
	private $default_type;
	const DEFAULT_TYPE = 'select';
	
	public function __construct($registry) {
		parent::__construct($registry);
		$this->language_id = (int) $registry->get('config')->get('config_language_id');
		
		$default_option_type = $registry->get('config')->get('bling_api_default_option_type');
		$this->default_type = $default_option_type ? $default_option_type : self::DEFAULT_TYPE;
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
			
			$options[$item['option_id']]['name'] = trim($item['name']);
			$options[$item['option_id']]['values'] = [];
			foreach ($opt_values->rows as $opt) {
				$options[$item['option_id']]['values'][] = [
					'name' => trim($opt['name']),
					'option_value_id' => $opt['option_value_id'],
					'option_id' => $item['option_id'],
				];
			}
		}
		
		return $options;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 23 de mai de 2020
	 * @param unknown $name
	 */
	public function insert($name) {
		$sql = "INSERT INTO `" . DB_PREFIX . "option` SET type = '" . $this->db->escape($this->default_type) . "', sort_order = 0";
		$this->db->query($sql);
		$option_id = $this->db->getLastId();
		
		$sql = "INSERT INTO " . DB_PREFIX . "option_description SET option_id = " . (int)$option_id . ", ";
		$sql .= "language_id = " . $this->language_id . ", name = '" . $this->db->escape(trim($name)) . "'";
		$this->db->query($sql);
		
		return $option_id;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 23 de mai de 2020
	 * @param unknown $option_id
	 * @param unknown $value
	 * @return unknown
	 */
	public function insert_value($option_id, $value) {
		$sql = "INSERT INTO " . DB_PREFIX . "option_value SET option_id = " . (int)$option_id . ", image = '', sort_order = 0";
		$this->db->query($sql);
		$option_value_id = $this->db->getLastId();
				
		$sql = "INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = " . (int)$option_value_id . ", ";
		$sql .= "language_id = " . $this->language_id . ", option_id = " . (int)$option_id . ", name = '" . $this->db->escape($value) . "'";
		$this->db->query($sql);
		return $option_value_id;
	}
}
?>