<?php

namespace Bling\Opencart;

class Order extends \Bling\Opencart\Base {
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 5 de mai de 2020
	 * @param unknown $order_id
	 */
	public function getOrderTotals($order_id) {
		// recupera apenas fretes e descontos
		$sql = "SELECT * FROM " . DB_PREFIX . "order_total ot ";
		$sql .= "WHERE ot.order_id = '" . (int)$order_id . "' ";
		$sql .= "AND (ot.code = 'shipping' OR ot.value < 0) ";
		
		$query = $this->db->query($sql);
		return $query->rows;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 5 de mai de 2020
	 * @param unknown $order_id
	 */
	public function getOrderProducts($order_id) {
		$sql = "SELECT op.*, p.sku, opt.name AS option_name, opt.value AS option_value, pov.option_sku ";
		$sql .= "FROM " . DB_PREFIX . "order_product op ";
		$sql .= "JOIN " . DB_PREFIX . "product p ON op.product_id = p.product_id ";
		$sql .= "LEFT JOIN " . DB_PREFIX . "order_option opt ON opt.order_product_id = op.order_product_id ";
		$sql .= "LEFT JOIN " . DB_PREFIX . "product_option_value pov ON pov.product_option_value_id = opt.product_option_value_id ";
		$sql .= "WHERE op.order_id = " . (int)$order_id . " ";
		$query = $this->db->query($sql);
		
		$result = [];
		if ($query->rows) {
			foreach ($query->rows as $row) {
				if (!isset($result[$row['order_product_id']])) {
					$result[$row['order_product_id']] = $row;
				}
				
				// sobrescreve o sku com opcional, se houver
				if (!empty($row['option_sku'])) {
					$result[$row['order_product_id']]['sku'] = $row['option_sku'];
					$result[$row['order_product_id']]['name'] .= ' ' . $row['option_name'] . ':' . $row['option_value'];
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * Recupera pedidos que serao exportados para o Bling
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 */
	public function getOrdersToExport($bnw_correios, $language) {
		if ($this->config->get('bling_api_order_status_export')) {
			$sql = $this->_getBasicSql();
			$sql .= "WHERE o.order_status_id IN (" . implode(",", $this->config->get('bling_api_order_status_export')) . ") ";
			$sql .= "AND (o.bling_id IS NULL OR o.bling_id = '') ";
			$result = $this->db->query($sql);
			
			return $this->_parseOrders($result->rows, $bnw_correios, $language);
		}
		
		return [];
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 21 de jul de 2020
	 * @param unknown $bnw_correios
	 * @param unknown $language
	 */
	public function getOrdersToSync() {
		if ($this->config->get('bling_api_order_status_sync_to_bling')) {
			$order_statuses = [];
			foreach ($this->config->get('bling_api_order_status_sync_to_bling') as $order_status_id => $bling_status_id) {
				if ($bling_status_id) {
					$order_statuses[] = $order_status_id;
				}
			}
			
			if ($order_statuses) {
				$fields = ['o.order_id', 'o.order_status_id', 'o.bling_id'];
					
				$sql = "SELECT " . implode(', ', $fields) . " ";
				$sql .= "FROM `" . DB_PREFIX . "order` o ";
				$sql .= "WHERE o.order_status_id IN (" . implode(",", $order_statuses) . ") ";
				$sql .= "AND o.bling_id IS NOT NULL AND o.bling_id != '' ";
					
				// verifica se ja foi enviada atualizacao pro Bling
				$sql .= "AND (o.bling_status_id IS NULL OR o.order_status_id != o.bling_status_id) ";
				$result = $this->db->query($sql);
				return $result->rows;
			}
		}
	
		return [];
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 21 de jul de 2020
	 */
	private function _getBasicSql() {
		$query_uf = "SELECT `code` FROM " . DB_PREFIX . "zone z WHERE z.zone_id = %s";
		$payment_uf = sprintf($query_uf, 'o.payment_zone_id');
		$shipping_uf = sprintf($query_uf, 'o.shipping_zone_id');
		
		$fields = [
			'o.order_id', 'o.date_added', 'o.date_modified', 'o.store_name', 'o.store_id', 'o.comment',
			'o.language_id', 'o.customer_id', 'o.customer_group_id',
			'o.order_status_id', 'o.firstname', 'o.lastname', 'o.email', 'o.telephone', 'o.fax',
			'o.cpf', 'o.cnpj', 'o.razao_social', 'o.inscricao_estadual',
			'o.payment_address_1', 'o.payment_numero', 'o.payment_address_2',
			'o.payment_complemento', 'o.payment_city', 'o.payment_postcode', 'o.payment_code',
			'o.shipping_firstname', 'o.shipping_lastname',
			'o.shipping_address_1', 'o.shipping_numero', 'o.shipping_address_2',
			'o.shipping_complemento', 'o.shipping_city', 'o.shipping_postcode',
			'o.shipping_code', 'o.shipping_method',
			'REPLACE(o.shipping_code, \'correios.\', \'\') AS servico_correios'
		];
		
		$sql = "SELECT " . implode(', ', $fields) . ", ";
		$sql .= "(" . $payment_uf . ") AS payment_uf, ";
		$sql .= "(" . $shipping_uf . ") AS shipping_uf, ";
		$sql .= "(SELECT GROUP_CONCAT(sg.object SEPARATOR ',') FROM " . DB_PREFIX . "bnw_correios sg WHERE sg.order_id = o.order_id GROUP BY sg.order_id) AS objects ";
		
		$sql .= "FROM `" . DB_PREFIX . "order` o ";
		return $sql;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 21 de jul de 2020
	 * @param unknown $rows
	 * @param unknown $bnw_correios
	 * @param unknown $language
	 */
	private function _parseOrders($rows, $bnw_correios, $language) {
		$shipping_language = [];
		foreach ($rows as $key => $row) {
			
			$shipping_code = explode('.', $row['shipping_code']);
			$shipping_code = array_shift($shipping_code);
		
			$shipping_company_name = '';
			if ($shipping_code) {
				if (!isset($shipping_language[$shipping_code])) {
					$shipping_language[$shipping_code] = $language->load('shipping/' . $shipping_code);
				}
					
				$shipping_company_name = $shipping_language[$shipping_code]['text_title'];
				$shipping_company_name .= ' - ' . $row['shipping_method'];
			}
		
			$rows[$key]['shipping_company_name'] = $shipping_company_name;
		
			// indica se existe geracao de etiqueta automatica pra esse pedido
			$rows[$key]['is_tracking'] = $bnw_correios->is_tracking($row['servico_correios'], $row['shipping_code']);
		
			// indica se a forma de entrega desse pedido é "Correios" de alguma forma
			$rows[$key]['is_correios'] = $bnw_correios->is_correios_anyway($row['shipping_code']);
			if ($rows[$key]['is_correios']) {
				// o is_correios vai ter o servico_correios atualizado caso exista algum mapeamento para outra forma de entrega
				$rows[$key]['servico_correios'] = $rows[$key]['is_correios'];
			}
		}
		
		return $rows;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 5 de mai de 2020
	 */
	public function getOrderByBlingId($bling_id) {
		$sql = "SELECT o.order_id, o.order_status_id, o.language_id, o.customer_id, ";
		$sql .= "o.shipping_code, REPLACE(o.shipping_code, 'correios.', '') AS servico_correios, ";
		$sql .= "(SELECT GROUP_CONCAT(sg.object SEPARATOR ',') FROM " . DB_PREFIX . "bnw_correios sg WHERE sg.order_id = o.order_id GROUP BY sg.order_id) AS objects, ";
		$sql .= "(SELECT SUM(op.reward) FROM " . DB_PREFIX . "order_product op WHERE op.order_id = o.order_id) AS reward ";
		$sql .= "FROM `" . DB_PREFIX . "order` o ";
		$sql .= "WHERE o.bling_id = " . (int) $bling_id;
		$result = $this->db->query($sql);
		return $result->row;
	}
}
?>