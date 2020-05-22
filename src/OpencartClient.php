<?php

namespace Bling\Opencart;

use Bling;

class OpencartClient extends \Bling\Opencart\Base {
	
	private $cart;
	private $load;
	
	private $api;
	
	/**
	 * 
	 * @var Config
	 */
	private $config;
	
	/**
	 * 
	 * @var Product
	 */
	private $model_product;
	
	/**
	 * 
	 * @var Order
	 */
	private $model_order;
	
	/**
	 * 
	 * @var Category
	 */
	private $model_category;
	
	/**
	 * 
	 * @var Manufacturer
	 */
	private $model_manufacturer;
	
	/**
	 *
	 * @var Option
	 */
	private $model_option;
	
	private $list_companies;
	private $list_companies_info;
	private $list_customer_groups;
	private $list_zones;
	private $list_country;
	private $list_payment_conditions;
	private $map_categories;
	private $map_sub_categories;
	private $map_manufacturer;
	private $map_product;
	private $map_options;
	
	private $sync_categories;
	private $sync_brand;
	
	private static $instance;
	
	const SITUACAO_ATIVO = "A";
	const SITUACAO_INATIVO = "I";
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de jan de 2019
	 * @param unknown $registry
	 */
	public static function get_instance($registry) {
		if (self::$instance == null) {
			self::$instance = new \Bling\Opencart\OpencartClient($registry);
			self::$instance->model_product = new \Bling\Opencart\Product($registry);
			self::$instance->model_category = new \Bling\Opencart\Category($registry);
			self::$instance->model_manufacturer = new \Bling\Opencart\Manufacturer($registry);
			self::$instance->model_option = new \Bling\Opencart\Option($registry);
			self::$instance->model_order = new \Bling\Opencart\Order($registry);
			self::$instance->config = new \Bling\Opencart\Config($registry->get('config'));
			
			self::$instance->sync_categories = self::$instance->config->get('bling_api_sync_categories');
			self::$instance->sync_brand = self::$instance->config->get('bling_api_sync_brand');
		}
		
		return self::$instance;
	}
	
	public function initMaps() {
		if (is_null($this->map_categories) || is_null($this->map_manufacturer) || is_null($this->map_options)) {
			$this->map_categories = [];
			$this->map_manufacturer = [];
			
			if ($this->sync_categories) {
				$categories = $this->model_category->get_all();
				foreach ($categories as $item) {
					$this->map_categories[$item['bling_id']] = $item['category_id'];
				}
			}
			
			if ($this->sync_brand) {
				$manufacturers = $this->model_manufacturer->get_all();
				foreach ($manufacturers as $item) {
					$this->map_manufacturer[$item['bling_id']] = $item['manufacturer_id'];
				}
			}
			
			$products = $this->model_product->get_all();
			foreach ($products as $item) {
				$this->map_product[$item['sku']] = $item['product_id'];
			}
			
			$options = $this->model_option->get_all();
			foreach ($options as $option_id => $item) {
				foreach ($item['values'] as $opt) {
					// mapa no formato "NomeOpcional/ValorOpcional" => dados do opcional na loja
					$this->map_options[$item['name']][$opt['name']] = $opt;
				}
			}
		}
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 7 de mai de 2020
	 */
	public function init_reverse_maps() {
		$products = $this->model_product->get_all();
		foreach ($products as $item) {
			$this->map_product[$item['product_id']] = $item['sku'];
		}
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 7 de mai de 2020
	 */
	private function _init_list_zones() {
		if (!$this->list_zones) {
			// estado de destino
			$model_zone = $this->_load_model('localisation/zone');
			$zones = $model_zone->getZonesByCountryId($this->config->get('config_country_id'));
			
			if ($zones) {
				foreach ($zones as $item) {
					$this->list_zones[$item['zone_id']] = $item['code'];
				}
			}	
		}
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 * @param unknown $item
	 */
	public function importProduct($item) {
		if (is_null($this->map_product)) {
			$this->initMaps();
		}
		
		$this->error = '';
		
		// importa categoria se ainda nao existir
		if ($this->sync_categories) {
			if (isset($item['category']['id']) && !isset($this->map_categories[$item['category']['id']])) {
				if ($cat_id = $this->model_category->insert($item['category'])) {
					$this->map_categories[$item['category']['id']] = $cat_id;
				} else {
					$this->error = 'Erro ao importar categoria ' . $item['category']['id'];
					\Bling\Util\Log::error($this->error);
					throw new \Exception($this->error);
				}
			}
			
			// importa subcategoria se ainda nao existir
			$parent_id = $this->map_categories[$item['category']['id']];
			if (isset($item['sub_category']['id']) && !isset($this->map_sub_categories[$parent_id][$item['sub_category']['id']])) {
				if ($cat_id = $this->model_category->insert($item['sub_category'], $parent_id)) {
					$this->map_sub_categories[$parent_id][$item['sub_category']['id']] = $cat_id;
				} else {
					$this->error = 'Erro ao importar sub-categoria ' . $item['sub_category']['id'];
					\Bling\Util\Log::error($this->error);
					throw new \Exception($this->error);
				}
			}
			
			$item['categories'] = [];
			if (isset($item['category']['id'], $this->map_categories[$item['category']['id']])) {
				$item['categories'][] = $this->map_categories[$item['category']['id']];
			}
			
			if (isset($item['sub_category']['id'], $this->map_sub_categories[$parent_id][$item['sub_category']['id']])) {
				$item['categories'][] = $this->map_sub_categories[$parent_id][$item['sub_category']['id']];
			}
		}
		
		if ($this->sync_brand) {
			// importa fabricante se ainda nao existir
			if (isset($item['manufacturer']['id']) && !isset($this->map_manufacturer[$item['manufacturer']['id']])) {
				if ($manufacturer_id = $this->model_manufacturer->insert($item['manufacturer'])) {
					$this->map_manufacturer[$item['manufacturer']['id']] = $manufacturer_id;
				} else {
					$this->error = 'Erro ao importar fabricante ' . $item['manufacturer']['id'];
					\Bling\Util\Log::error($this->error);
					throw new \Exception($this->error);
				}
			}
			
			$item['manufacturer_id'] = 0;
			if (isset($item['manufacturer']['id'], $this->map_manufacturer[$item['manufacturer']['id']])) {
				$item['manufacturer_id'] = $this->map_manufacturer[$item['manufacturer']['id']];
			}	
		}
		
		// TODO - tratar combined options
		if (isset($item['options'])) {
			$oc_options = [];
			foreach ($item['options'] as $opt) {
				// TODO cadastrar o opcional se ele nao existir na loja
				// TODO opcional: verificar primeiro se existe o nome depois o valor
				if (isset($this->map_options[$opt['name']][$opt['value']])) {
					$oc_opt = $this->map_options[$opt['name']][$opt['value']];
					if (!isset($oc_options[$oc_opt['option_id']])) {
						$oc_options[$oc_opt['option_id']] = [];
					}
					
					$oc_opt['sku'] = $opt['sku'];
					$oc_opt['quantity'] = $opt['quantity'];
					$oc_opt['price'] = 0;
					$oc_opt['price_prefix'] = '+';
					
					if ($opt['price'] != $item['price']) {
						$oc_opt['price'] = abs($item['price'] - $opt['price']);
						$oc_opt['price_prefix'] = $item['price'] < $opt['price'] ? '+' : '-';
					}
					
					$oc_options[$oc_opt['option_id']][] = $oc_opt;
				}
			}
			
			$item['options'] = $oc_options;
		}
		
		$product_id = 0;
		if (isset($this->map_product[$item['sku']])) {
			// UPDATE
			$product_id = $this->map_product[$item['sku']];
			$this->model_product->update($product_id, $item);
		} else {
			// INSERT
			$product_id = $this->model_product->insert($item);
			if ($product_id) {
				$this->map_product[$item['sku']] = $product_id;
			}
		}
		
		return true;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 21 de mai de 2020
	 * @param unknown $sku
	 * @param unknown $quantity
	 */
	public function updateStock($sku, $quantity) {
		return $this->model_product->updateStock($sku, $quantity);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 * @param unknown $bling_id
	 */
	public function synchronize_product($oc_product) {
		if ($oc_product) {
			return $this->import_product($oc_product);
		}
		
		return false;
	}
	
	public function get_orders($extra_fields = []) {
		return $this->model->get_orders($extra_fields);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 */
	public function getOrdersToExport() {
		return $this->model_order->getOrdersToExport();
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 21 de mai de 2020
	 * @param unknown $bling_id
	 */
	public function getOrderByBlingId($bling_id) {
		return $this->model_order->getOrderByBlingId($bling_id);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 */
	public function getOrderTotals() {
		return $this->model_order->getOrderTotals($order_id);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 */
	public function getOrderProducts() {
		return $this->model_order->getOrderProducts($order_id);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 21 de mai de 2020
	 */
	public function getOrdersToUpdate() {
		return $this->model_order->getOrdersToUpdate();
	}
	
	/**
	 * Essa funcao sempre carrega o model do catalog/
	 *
	 * @author Rande A. Moreira
	 * @since 17 de dez de 2018
	 */
	private function _load_model($model_route) {
		$base_dir = defined('DIR_CATALOG') ? DIR_CATALOG : DIR_APPLICATION;
			
		// carrega model do catalog
		require_once VQMod::modCheck($base_dir . 'model/' . $model_route . '.php');
		$class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $model_route);
		$model = new $class($this->registry);
		
		return $model;
	}
}
?>