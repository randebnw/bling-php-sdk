<?php

namespace Bling\Opencart;

class Product extends \Bling\Opencart\Base {
	
	private $language_id;
	private $customer_group_id;
	
	private $sync_name;
	private $sync_price;
	private $sync_categories;
	private $sync_description;
	private $sync_mini_description;
	private $sync_brand;
	private $auto_sum_stock;
	private $has_combined_options;
	
	public function __construct($registry) {
		parent::__construct($registry);
		$this->language_id = $registry->get('config')->get('config_language_id');
		$this->customer_group_id = $registry->get('config')->get('config_customer_group_id');
		
		$this->sync_name = $registry->get('config')->get('bling_api_sync_name');
		$this->sync_description = $registry->get('config')->get('bling_api_sync_description');
		$this->sync_mini_description = $registry->get('config')->get('bling_api_sync_mini_description');
		$this->sync_price = $registry->get('config')->get('bling_api_sync_price');
		$this->sync_categories = $registry->get('config')->get('bling_api_sync_categories');
		$this->sync_brand = $registry->get('config')->get('bling_api_sync_brand');
		$this->auto_sum_stock = $registry->get('config')->get('bling_api_auto_sum_stock');
		$this->has_combined_options = is_file(DIR_VQMOD . 'xml/99_two_dimensional_options.xml');
	}
	
	public function get_all() {
		$sql = "SELECT product_id, sku FROM `" . DB_PREFIX . "product` WHERE sku IS NOT NULL AND sku != '' ";
		$result = $this->db->query($sql);
		
		return $result->rows;
	}
	
	public function insert($data) {
		\Bling\Util\Log::debug('NEW PRODUCT INSERT > ' . $data['sku']);
		
		$sql = "INSERT INTO " . DB_PREFIX . "product ";
		$sql .= "SET model = '" . $this->db->escape($data['model']) . "', ";
		$sql .= "sku = '" . $this->db->escape($data['sku']) . "', ";
		$sql .= "upc = '" . $this->db->escape($data['upc']) . "', ";
		$sql .= "ean = '" . $this->db->escape($data['ean']) . "', ";
		$sql .= "jan = '" . $this->db->escape($data['jan']) . "', ";
		$sql .= "isbn = '" . $this->db->escape($data['isbn']) . "', ";
		$sql .= "mpn = '" . $this->db->escape($data['mpn']) . "', ";
		$sql .= "location = '" . $this->db->escape($data['location']) . "', ";
		$sql .= "quantity = '" . (int)$data['quantity'] . "', ";
		$sql .= "minimum = '" . (int)$data['minimum'] . "', ";
		$sql .= "subtract = '" . (int)$data['subtract'] . "', ";
		$sql .= "stock_status_id = '" . (int)$data['stock_status_id'] . "', ";
		$sql .= "date_available = '" . $this->db->escape(date('Y-m-d', strtotime('1 day ago'))) . "', ";
		
		if ($this->sync_brand) {
			$sql .= "manufacturer_id = '" . (int)$data['manufacturer_id'] . "', ";
		}
		
		$sql .= "shipping = '" . (int)$data['shipping'] . "', ";
		$sql .= "price = '" . (float)$data['price'] . "', ";
		$sql .= "points = '" . (int)$data['points'] . "', ";
		$sql .= "weight = '" . (float)$data['weight'] . "', ";
		$sql .= "weight_class_id = '" . (int)$data['weight_class_id'] . "', ";
		$sql .= "length = '" . (float)$data['length'] . "', ";
		$sql .= "width = '" . (float)$data['width'] . "', ";
		$sql .= "height = '" . (float)$data['height'] . "', ";
		$sql .= "length_class_id = '" . (int)$data['length_class_id'] . "', ";
		$sql .= "status = '" . (int)$data['status'] . "', ";
		$sql .= "tax_class_id = '" . $this->db->escape($data['tax_class_id']) . "', ";
		$sql .= "sort_order = '" . (int)$data['sort_order'] . "', date_added = NOW(), api_modified = NOW() ";
		
		$this->db->query($sql);
		
		$product_id = $this->db->getLastId();
		
		$sql = "INSERT INTO " . DB_PREFIX . "product_description ";
		$sql .= "SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$this->language_id . "', ";
		$sql .= "name = '" . $this->db->escape($data['name']) . "', ";
		$sql .= "meta_keyword = '', meta_description = '', ";
		$sql .= "description = '" . $this->db->escape($data['description']) . "', ";
		$sql .= "mini_description = '" . $this->db->escape($data['mini_description']) . "', ";
		$sql .= "tag = ''";
		$this->db->query($sql);
		
		$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = 0");
		
		// CATEGORIAS
		if ($this->sync_categories && $data['categories']) {
			foreach ($data['categories'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "', is_bling = 1");
			}
		}
		
		// PROMOCOES
		if ($this->sync_price && $data['special'] > 0) {
			$sql = "INSERT INTO " . DB_PREFIX . "product_special ";
			$sql .= "SET product_id = '" . (int)$product_id . "', ";
			$sql .= "customer_group_id = '" . (int)$this->customer_group_id . "', ";
			$sql .= "price = '" . (float)$data['special'] . "', ";
			$sql .= "priority = '1', date_start = '', date_end = '' ";
			$this->db->query($sql);
		}
		
		// OPCIONAIS
		$is_combined = false;
		if (isset($data['options']) && $data['options']) {
			foreach ($data['options'] as $option_id => $values) {
				$this->db->query("
					INSERT INTO " . DB_PREFIX . "product_option SET 
					product_id = '" . (int)$product_id . "', 
					option_id = '" . (int)$option_id . "', 
					required = 1
				");
				
				$product_option_id = $this->db->getLastId();
				foreach ($values as $product_option_value) {
					$this->db->query("
						INSERT INTO " . DB_PREFIX . "product_option_value SET 
						product_option_id = '" . (int)$product_option_id . "', 
						product_id = '" . (int)$product_id . "',
						option_sku = '" . $this->db->escape($product_option_value['sku']) . "',
						option_id = '" . (int)$option_id . "', 
						option_value_id = '" . (int)$product_option_value['option_value_id'] . "', 
						quantity = " . (int)$product_option_value['quantity'] . ", 
						subtract = " . (int)$data['subtract'] . ", 
						price = " . (float)$product_option_value['price'] . ", 
						price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', 
						points = 0, points_prefix = '+', weight = '0.00', weight_prefix = '+'
					");
				}
			}
		} else if ($this->has_combined_options && isset($data['combined_options']) && $data['combined_options']) {
			$is_combined = true;
			foreach ($data['combined_options'] as $parent_id => $combined_option) {
				foreach ($combined_option as $child_id => $values) {
					foreach ($values as $option_value) {
						$this->db->query("
							INSERT INTO " . DB_PREFIX . "tdo_option_value SET product_id = '" . (int)$product_id . "', 
							parent_option_id = '" . (int)$option_value['parent_option_id'] . "', 
							child_option_id = '" . (int)$option_value['child_option_id'] . "', 
							parent_option_value_id = '" . (int)$option_value['parent_value_id'] . "', 
							child_option_value_id = '" . (int)$option_value['child_value_id'] . "'
						");
						$tdo_id = $this->db->getLastId();
						$this->db->query("
							INSERT INTO " . DB_PREFIX . "tdo_data SET tdo_id = " . (int) $tdo_id . ", 
							product_id = '" . (int)$product_id . "', model = '" . $option_value['sku'] . "', 
							extra = '', quantity = '" . (int)$option_value['quantity'] . "', 
							subtract = " . (int)$data['subtract'] . ", 
							price_prefix = '" . $option_value['price_prefix'] . "', 
							price = '" . $option_value['price'] . "', 
							special = ''
						");
					}	
				}
			}
		}
		
		// define url amigavel
		$keyword = $this->url->str2url($data['name']);
		$keyword = $this->check_keyword($keyword, $product_id, 'p');
		if ($keyword) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($keyword) . "'");
		}
		
		$this->_autoSumStock($product_id, $is_combined);
		return $product_id;
	}
	
	public function update($product_id, $data) {
		// DADOS GERAIS
		$sql = "UPDATE " . DB_PREFIX . "product ";
		$sql .= "SET sku = '" . $this->db->escape($data['sku']) . "', ";
		$sql .= "ean = '" . $this->db->escape($data['ean']) . "', ";
		
		if ($this->sync_brand) {
			$sql .= "manufacturer_id = '" . (int)$data['manufacturer_id'] . "', ";
		}
		
		if ($this->sync_price) {
			$sql .= "price = '" . (float)$data['price'] . "', ";
		}
		
		$sql .= "quantity = '" . (int)$data['quantity'] . "', ";
		$sql .= "weight = '" . (float)$data['weight'] . "', ";
		$sql .= "weight_class_id = '" . (int)$data['weight_class_id'] . "', ";
		$sql .= "length = '" . (float)$data['length'] . "', ";
		$sql .= "width = '" . (float)$data['width'] . "', ";
		$sql .= "height = '" . (float)$data['height'] . "', ";
		$sql .= "length_class_id = '" . (int)$data['length_class_id'] . "', ";
		$sql .= "status = " . (int)$data['status'] . ", ";
		$sql .= "api_modified = NOW() ";
		$sql .= "WHERE product_id = '" . (int)$product_id . "'";
		$this->db->query($sql);
		
		// NOME/DESCRICAO
		if ($this->sync_name || $this->sync_description || $this->sync_mini_description) {
			$updates = [];
			if ($this->sync_name) {
				$updates[] = " name = '" . $this->db->escape($data['name']) . "' ";
			}
			
			if ($this->sync_description) {
				$updates[] = " description = '" . $this->db->escape($data['description']) . "' ";
			}
			
			if ($this->sync_mini_description) {
				$updates[] = " mini_description = '" . $this->db->escape($data['mini_description']) . "' ";
			}
			
			if ($updates) {
				$sql = "UPDATE " . DB_PREFIX . "product_description ";
				$sql .= "SET " . implode(',', $updates);
				$sql .= "WHERE product_id = '" . (int)$product_id . "' ";
				$sql .= "AND language_id = '" . (int)$this->language_id . "' ";
				$this->db->query($sql);
			}
		}
		
		// PROMOCOES
		if ($this->sync_price && $data['special'] > 0) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");
				
			$sql = "INSERT INTO " . DB_PREFIX . "product_special ";
			$sql .= "SET product_id = '" . (int)$product_id . "', ";
			$sql .= "customer_group_id = '" . (int)$this->customer_group_id . "', ";
			$sql .= "price = '" . (float)$data['special'] . "', ";
			$sql .= "priority = '1', date_start = '', date_end = '' ";
			$this->db->query($sql);
		}
		
		// CATEGORIAS
		if ($this->sync_categories && $data['categories']) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND is_bling = 1");
			foreach ($data['categories'] as $category_id) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "', is_bling = 1");
			}	
		}
		
		// OPCIONAIS
		$is_combined = false;
		if (isset($data['options']) && $data['options']) {
			$product_options = $this->db->query("
				SELECT ov.product_option_value_id, ov.option_sku, o.option_id, o.product_option_id 
				FROM " . DB_PREFIX . "product_option o 
				LEFT JOIN " . DB_PREFIX . "product_option_value ov ON ov.product_option_id = o.product_option_id
				WHERE o.product_id = " . $product_id
			);
			$existing_options = [];
			$map_product_option_id = [];
			$options_values_id = [];
			foreach ($product_options->rows as $item) {
				foreach ($data['options'] as $option_id => $values) {
					if ($item['option_id'] == $option_id) {
						$map_product_option_id[$option_id] = $item['product_option_id'];
						foreach ($values as $key => $v) {
							if ($v['sku'] == $item['option_sku']) {
								$data['options'][$option_id][$key]['product_option_value_id'] = $item['product_option_value_id'];
							}
						}
						
						break;
					}
				}
			}
			
			foreach ($data['options'] as $option_id => $values) {
				if (!isset($map_product_option_id[$option_id])) {
					// se ainda nao existe o opcional, eh preciso incluir
					$this->db->query("
						INSERT INTO " . DB_PREFIX . "product_option SET
						product_id = '" . (int)$product_id . "',
						option_id = '" . (int)$option_id . "',
						required = 1
					");
					
					$product_option_id = $this->db->getLastId();
				} else {
					$product_option_id = $map_product_option_id[$option_id];
				}
				
				foreach ($values as $product_option_value) {
					// se ainda nao existe na loja, inclui
					if (!isset($product_option_value['product_option_value_id'])) {
						$sql = "
							INSERT INTO " . DB_PREFIX . "product_option_value SET
							product_option_id = '" . (int)$product_option_id . "',
							product_id = '" . (int)$product_id . "',
							option_sku = '" . $this->db->escape($product_option_value['sku']) . "',
							option_id = '" . (int)$option_id . "',
							option_value_id = '" . (int)$product_option_value['option_value_id'] . "',
							quantity = " . (int)$product_option_value['quantity'] . ",
							subtract = " . (int)$data['subtract'] . ",
							price = " . (float)$product_option_value['price'] . ", 
							price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "',
							points = 0, points_prefix = '+', weight = '0.00', weight_prefix = '+'
						";
					} else {
						// se ja existe, soh faz o update
						$sql = "
							UPDATE " . DB_PREFIX . "product_option_value SET
							quantity = " . (int)$product_option_value['quantity'] . " ";
						if ($this->sync_price) {
							$sql .= "
								, price = " . (float)$product_option_value['price'] . "
								, price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "' ";
						}
					
						$sql .= "WHERE product_option_value_id = " . $product_option_value['product_option_value_id'];
					}
					
					$options_values_id[] = $product_option_value['option_value_id'];
					$this->db->query($sql);
				}
				
				// exclui opcoes que nao existem mais
				$this->db->query("
					DELETE FROM " . DB_PREFIX . "product_option_value 
					WHERE product_option_id = " . (int)$product_option_id . " AND option_value_id NOT IN (" . implode(",", $options_values_id) . ") ");
			}
		} else if ($this->has_combined_options && isset($data['combined_options']) && $data['combined_options']) {
			$is_combined = true;
			$product_options = $this->db->query("
				SELECT ov.id, ov.parent_option_id, ov.child_option_id, ov.parent_option_value_id, ov.child_option_value_id
				FROM " . DB_PREFIX . "tdo_option_value ov
				WHERE ov.product_id = " . $product_id
			);
			
			$map_combined_id = [];
			foreach ($product_options->rows as $item) {
				$parent_option_id = $item['parent_option_id'];
				$child_option_id = $item['child_option_id'];
				$parent_option_value_id = $item['parent_option_value_id'];
				$child_option_value_id = $item['child_option_value_id'];
				$map_combined_id[$parent_option_id][$child_option_id][$parent_option_value_id][$child_option_value_id] = $item['id'];
			}
			
			$not_delete = [];
			foreach ($data['combined_options'] as $parent_id => $combined_option) {
				foreach ($combined_option as $child_id => $values) {
					foreach ($values as $option_value) {
						$parent_value_id = $option_value['parent_value_id'];
						$child_value_id = $option_value['child_option_value_id'];
						if (!isset($map_combined_id[$parent_id][$child_id][$parent_value_id][$child_value_id])) {
							$this->db->query("
								INSERT INTO " . DB_PREFIX . "tdo_option_value SET product_id = '" . (int)$product_id . "',
								parent_option_id = '" . (int)$option_value['parent_option_id'] . "',
								child_option_id = '" . (int)$option_value['child_option_id'] . "',
								parent_option_value_id = '" . (int)$option_value['parent_value_id'] . "',
								child_option_value_id = '" . (int)$option_value['child_value_id'] . "'
							");
							$tdo_id = $this->db->getLastId();
							$this->db->query("
								INSERT INTO " . DB_PREFIX . "tdo_data SET tdo_id = " . (int) $tdo_id . ",
								product_id = '" . (int)$product_id . "', model = '" . $option_value['sku'] . "',
								extra = '', quantity = '" . (int)$option_value['quantity'] . "',
								subtract = " . (int)$data['subtract'] . ",
								price_prefix = '" . $option_value['price_prefix'] . "',
								price = '" . $option_value['price'] . "',
								special = ''
							");
							
							$not_delete[] = $tdo_id;
						} else {
							$tdo_id = $map_combined_id[$parent_id][$child_id][$parent_value_id][$child_value_id];
							$sql = "UPDATE " . DB_PREFIX . "tdo_data ";
							$sql .= "SET model = '" . $option_value['sku'] . "', "; 
							$sql .= "quantity = '" . (int)$option_value['quantity'] . "' "; 
							
							if ($this->sync_price) {
								$sql .= ", price_prefix = '" . $option_value['price_prefix'] . "' "; 
								$sql .= ", price = '" . $option_value['price'] . "' "; 
							}
							
							$sql .= "WHERE tdo_id = " . (int) $tdo_id;
							$this->db->query($sql);
							
							$not_delete[] = $tdo_id;
						}
					}
				}
			}
			
			// exclui opcoes que nao existem mais
			$this->db->query("DELETE FROM " . DB_PREFIX . "tdo_option_value WHERE product_id = " . (int)$product_id . " AND `id` NOT IN (" . implode(',', $not_delete) . ")");
			$this->db->query("DELETE FROM " . DB_PREFIX . "tdo_data WHERE product_id = " . (int)$product_id . " AND tdo_id NOT IN (" . implode(',', $not_delete) . ")");
		}
		
		$this->_autoSumStock($product_id, $is_combined);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 6 de ago de 2020
	 * @param unknown $product_id
	 * @param unknown $main_image
	 * @param unknown $extra_images
	 */
	public function updateImages($product_id, $main_image, $extra_images) {
		$sql = "UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($main_image) . "' WHERE product_id = " . (int) $product_id;
		$this->db->query($sql);
		
		$sql = "DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = " . (int) $product_id;
		$this->db->query($sql);
		
		if (is_array($extra_images)) {
			foreach ($extra_images as $key => $img) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_image 
					SET product_id = '" . (int)$product_id . "', 
					image = '" . $this->db->escape(html_entity_decode($img, ENT_QUOTES, 'UTF-8')) . "', 
					sort_order = " . (int)$key);
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
		$sql = "SELECT product_id FROM " . DB_PREFIX . "product WHERE sku = '" . $this->db->escape($sku) . "'";
		$product = $this->db->query($sql);
		$success = false;
		if (isset($product->row['product_id'])) {
			// eh produto comum, atualiza o estoque principal
			$sql = "UPDATE " . DB_PREFIX . "product SET quantity = " . (int) $quantity . ", api_modified = NOW() WHERE product_id = " . (int) $product->row['product_id'];
			$this->db->query($sql);
			
			$this->_autoSumStock($product->row['product_id']);
			
			$success = true;
		} else {
			// se nao, entao pode ser um opcional
			$sql = "SELECT product_id FROM " . DB_PREFIX . "product_option_value WHERE option_sku = '" . $this->db->escape($sku) . "'";
			$product = $this->db->query($sql);
			if (isset($product->row['product_id'])) {
				$sql = "UPDATE " . DB_PREFIX . "product_option_value SET quantity = " . (int) $quantity . " WHERE option_sku = '" . $this->db->escape($sku) . "'";
				$this->db->query($sql);
				
				$this->_autoSumStock($product->row['product_id']);
				$success = true;
			}
		}
		
		return $success;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 24 de mai de 2020
	 * @param unknown $product_id
	 */
	private function _autoSumStock($product_id, $is_combined) {
		if ($this->auto_sum_stock) {
			$stock_table = 'product_option_value';
			if ($is_combined && $this->has_combined_options) {
				$stock_table = 'tdo_data';
			}
			
			$sql = "SELECT SUM(quantity) AS total FROM " . DB_PREFIX . $stock_table . " WHERE product_id = " . (int) $product_id;
			$result = $this->db->query($sql);
			
			// o estoque soh sera atualizado se o produto realmente tiver opcionais do tipo "escolha"
			if (isset($result->row['total']) && !is_null($result->row['total'])) {
				$sql = "UPDATE " . DB_PREFIX . "product SET quantity = " . (int)$result->row['total'] . " WHERE product_id = " . (int) $product_id;
				$this->db->query($sql);
			}
		}
	}
}
?>