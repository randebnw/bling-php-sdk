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
	 * @var Attribute
	 */
	private $model_attribute;
	
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
	private $map_attributes;
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
			self::$instance->model_attribute = new \Bling\Opencart\Attribute($registry);
			self::$instance->model_option = new \Bling\Opencart\Option($registry);
			self::$instance->model_order = new \Bling\Opencart\Order($registry);
			self::$instance->config = new \Bling\Opencart\Config($registry->get('config'));
			
			self::$instance->sync_categories = self::$instance->config->get('bling_api_sync_categories');
			self::$instance->sync_brand = self::$instance->config->get('bling_api_sync_brand');
		}
		
		return self::$instance;
	}
	
	public function init_maps() {
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
					$this->map_options[$opt['name']] = $opt;
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
	public function import_product($item) {
		if (is_null($this->map_product)) {
			$this->init_maps();
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
			pr($item['options']);
			
			$oc_options = [];
			foreach ($item['options'] as $opt) {
				if (isset($this->map_options[$opt['value']])) {
					$oc_opt = $this->map_options[$opt['value']];
					if (!isset($oc_options[$oc_opt['option_id']])) {
						$oc_options[$oc_opt['option_id']] = [];
					}
					
					$oc_opt['bling_id'] = $opt['id'];
					$oc_opt['sku'] = $opt['sku'];
					$oc_opt['quantity'] = $opt['quantity'];
					$oc_options[$oc_opt['option_id']][] = $oc_opt;
				}
			}
			
			$item['options'] = $oc_options;
			pr($item['options']);
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
	 * @since 6 de dez de 2018
	 * @param unknown $tgp_product
	 */
	public function simple_update_product($product) {
		if ($product) {
			$this->model_product->simple_update($product);
		}
		
		return false;
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
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 16 de jun de 2019
	 * @param unknown $bling_id
	 */
	public function update_order_status($order, $tgp_status) {
		$bling_ids = explode('#', $order['bling_ids']);
		return $this->api->update_order_status($bling_ids, $tgp_status);
	}
	
	public function update_order_status_from_dc($order, $order_status_map, $model, &$emails_sent) {
		$bling_ids = explode('#', $order['bling_ids']);
		$tgp_status = $this->api->get_order_status($bling_ids);
		if ($tgp_status && $tgp_status->rows) {
			$all_status = [];
			foreach ($tgp_status->rows as $item) {
				$all_status[] = $item['status'];
			}
			
			$all_status = array_unique($all_status);
			
			// so altera o status do pedido na loja se o status de todos os pedidos relacionados foram os mesmos
			if (count($all_status) == 1) {
				$final_status = array_shift($all_status);
				if (isset($order_status_map[$final_status])) {
					$new_status = $order_status_map[$final_status];
					if ($new_status && $new_status != $order['order_status_id']) {
						$model->update($order['order_id'], $new_status, 'Atualização automática de status', true);
						$this->model->update_tgp_order_status($order['order_id'], $final_status);
						$emails_sent++;
					}
				}	
			}
		}
		
		// se o status for retornado pela API, mas nao estiver mapeado no sistema, vai passar como "true"
		return $tgp_status;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de dez de 2018
	 */
	public function get_companies() {
		$companies = $this->api->get_companies();
		$oc_companies = [];
		if ($companies) {
			foreach ($companies as $item) {
				$oc_companies[] = \Bling\Helper::tgp_company_2_oc_company($item);
			}
		}
		return $oc_companies;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de dez de 2018
	 */
	public function get_customer_groups() {
		$groups = $this->api->get_customer_groups();
		$oc_groups = [];
		if ($groups) {
			foreach ($groups as $item) {
				$oc_groups[] = \Bling\Helper::tgp_customer_group_2_oc_customer_group($item);
			}
		}
		return $oc_groups;
	}
	
	public function get_orders($extra_fields = []) {
		return $this->model->get_orders($extra_fields);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 */
	public function get_orders_to_export() {
		return $this->model_order->get_orders_to_export();
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 */
	public function get_order_totals() {
		return $this->model_order->get_order_totals($order_id);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 */
	public function get_order_products() {
		return $this->model_order->get_order_products($order_id);
	}
	
	public function get_orders_paid() {
		return $this->model->get_orders_paid();
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