<?php

namespace Bling;

use Bling;
use Bling\Opencart\Order;

class OpencartClient extends \Bling\Opencart\Base {
	
	private $cart;
	private $load;
	
	private $api;
	private $url;
	
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
	private $map_option_id;
	
	private $sync_categories;
	private $sync_brand;
	
	private $new_products = 0;
	private $created = false;
	private $has_combined_options = false;
	
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
			self::$instance = new \Bling\OpencartClient($registry);
			self::$instance->model_product = new \Bling\Opencart\Product($registry);
			self::$instance->model_category = new \Bling\Opencart\Category($registry);
			self::$instance->model_manufacturer = new \Bling\Opencart\Manufacturer($registry);
			self::$instance->model_option = new \Bling\Opencart\Option($registry);
			self::$instance->model_order = new \Bling\Opencart\Order($registry);
			self::$instance->config = new \Bling\Opencart\Config($registry->get('config'));
			self::$instance->url = $registry->get('url');
			
			self::$instance->sync_categories = self::$instance->config->get('bling_api_sync_categories');
			self::$instance->sync_brand = self::$instance->config->get('bling_api_sync_brand');
			self::$instance->has_combined_options = is_file(DIR_VQMOD . 'xml/99_two_dimensional_options.xml');
		}
		
		return self::$instance;
	}
	
	/**
	 * 
	 * @param string $sku
	 * @return boolean
	 */
	public function productExists($sku) {
		$sku = trim($sku);
		if (is_null($this->map_product)) {
			$this->initMaps();
		}
		
		return isset($this->map_product[$sku]);
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
				$this->map_option_id[$item['name']] = $option_id;
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
		$this->created = false;
		
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
			if (isset($item['manufacturer']) && !isset($this->map_manufacturer[$item['manufacturer']])) {
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
		
		if (isset($item['options'])) {
			$oc_options = [];
			foreach ($item['options'] as $opt) {
				// verifica se existe o opcional
				if (!isset($this->map_option_id[$opt['name']])) {
					$option_id = $this->model_option->insert($opt['name']);
					$this->map_option_id[$opt['name']] = $option_id;
				}
				
				// verifica se existe o valor do opcional
				if (!isset($this->map_options[$opt['name']][$opt['value']])) {
					$option_id = $this->map_option_id[$opt['name']];
					$option_value_id = $this->model_option->insert_value($option_id, $opt['value']);
					$this->map_options[$opt['name']][$opt['value']] = [
						'name' => trim($opt['value']),
						'option_value_id' => $option_value_id,
						'option_id' => $option_id,
					];
				}
				
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
		} else if (isset($item['combined_options']) && $this->has_combined_options) {
			$oc_options = [];
			foreach ($item['combined_options'] as $combined_option) {
				foreach ($combined_option['options'] as $opt) {
					$option_id = 0;
					// verifica se existe o opcional
					if (!isset($this->map_option_id[$opt['name']])) {
						$option_id = $this->model_option->insert($opt['name']);
						$this->map_option_id[$opt['name']] = $option_id;
					}
						
					// verifica se existe o valor do opcional
					if (!isset($this->map_options[$opt['name']][$opt['value']])) {
						$option_id = $this->map_option_id[$opt['name']];
						$option_value_id = $this->model_option->insert_value($option_id, $opt['value']);
						$this->map_options[$opt['name']][$opt['value']] = [
							'name' => trim($opt['value']),
							'option_value_id' => $option_value_id,
							'option_id' => $option_id,
						];
					}
				}
				
				$parent_option = $combined_option['options'][0];
				$child_option = $combined_option['options'][1];
				if (isset($this->map_options[$parent_option['name']][$parent_option['value']])
					&& isset($this->map_options[$child_option['name']][$child_option['value']])) {
					
					$oc_parent_option = $this->map_options[$parent_option['name']][$parent_option['value']];
					$oc_child_option = $this->map_options[$child_option['name']][$child_option['value']];
					$parent_id = $oc_parent_option['option_id'];
					$child_id = $oc_child_option['option_id'];
					
					// verifica se ja inicializou essa combinacao
					if (!isset($oc_options[$parent_id][$child_id])) {
						$oc_options[$parent_id][$child_id] = [];
					}
		
					$oc_opt['parent_value_id'] = trim($oc_parent_option['option_value_id']);
					$oc_opt['parent_option_id'] = trim($oc_parent_option['option_id']);
					
					$oc_opt['child_value_id'] = trim($oc_child_option['option_value_id']);
					$oc_opt['child_option_id'] = trim($oc_child_option['option_id']);
					$oc_opt['sku'] = $combined_option['sku'];
					$oc_opt['quantity'] = $combined_option['quantity'];
					$oc_opt['price'] = 0;
					$oc_opt['price_prefix'] = '+';
		
					if ($opt['price'] != $item['price']) {
						$oc_opt['price'] = abs($item['price'] - $combined_option['price']);
						$oc_opt['price_prefix'] = $item['price'] < $combined_option['price'] ? '+' : '-';
					}
		
					$oc_options[$parent_id][$child_id][] = $oc_opt;
				}
			}
				
			$item['combined_options'] = $oc_options;
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
				$this->created = true;				
				$this->new_products++;
				$this->map_product[$item['sku']] = $product_id;
			}
		}
		
		return true;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 6 de ago de 2020
	 * @param unknown $item
	 * @param BnwCurl $curl
	 */
	public function importImages($item, $curl) {
		if (is_null($this->map_product)) {
			$this->initMaps();
		}
	
		$this->error = '';
	
		$product_id = 0;
		if (isset($this->map_product[$item['sku']]) && isset($item['images']) && is_array($item['images'])) {
			$product_id = $this->map_product[$item['sku']];
			
			$oc_images = [];
			foreach ($item['images'] as $key => $url) {
				$sku_folder = $this->url->str2url(substr($item['sku'], 0, 3));
				$img_file = 'data/produtos/' . $sku_folder . '/' . $this->url->str2url($item['name']) . '-' . $product_id . '-' . sprintf('%02s', $key + 1) . '.jpg';
				$oc_images[] = $img_file;
				
				// baixa o arquivo se ainda nao existir
				if (!is_file(DIR_IMAGE . $img_file) || filesize(DIR_IMAGE . $img_file) == 0) {
					// cria o diretorio se necessario
					$dir = DIR_IMAGE . dirname($img_file);
					if (!is_dir($dir)) {
						@mkdir($dir, 0755, true);
					}
			
					$img_content = $curl->requestFile($url);
					if ($img_content !== false) {
						file_put_contents(DIR_IMAGE . $img_file, $img_content);
					} else { 
						throw new \Exception($curl->get_error());
					}
				}
			}
			
			// importa imagens
			$main_image = array_shift($oc_images);
			$this->model_product->updateImages($product_id, $main_image, $oc_images);
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
	 * @param unknown $lib_correios
	 * @param unknown $lib_language
	 */
	public function getOrdersToExport($lib_correios, $lib_language) {
		return $this->model_order->getOrdersToExport($lib_correios, $lib_language);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 21 de jul de 2020
	 */
	public function getOrdersToSync() {
		return $this->model_order->getOrdersToSync();
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
	 * @param int $order_id
	 */
	public function getOrderTotals($order_id) {
		return $this->model_order->getOrderTotals($order_id);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 * @param int $order_id
	 */
	public function getOrderProducts($order_id) {
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
	 * 
	 * @author Rande A. Moreira
	 * @since 22 de mai de 2020
	 */
	public function getNewProducts() {
		return $this->new_products;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function isCreated() {
		return $this->created;
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