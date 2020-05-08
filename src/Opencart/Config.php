<?php

namespace Bling\Opencart;

class Config {
	
	private $config;
	
	public function __construct($config) {
		$this->config = $config;
	}
	
	public function get($key) {
		return $this->config->get($key);
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 */
	public function get_default_product_config() {
		$config['stock_status_id'] = $this->config->get('config_stock_status_id');
		$config['subtract'] = $this->config->get('config_stock_subtract');
		$config['shipping'] = $this->config->get('config_shipping_required');
		$config['weight_class_id'] = $this->config->get('tgp_weight_class');
		$config['length_class_id'] = $this->config->get('tgp_length_class');
		/*$config['weight_field'] = $this->_get_weight_field();
		$config['category_fields'] = $this->_get_category_fields();
		$config['manufacturer_field'] = $this->_get_manufacturer_field();*/
	
		return $config;
	}
}
?>