<?php
namespace Bling;

use Bling\Core\Bling;

/**
 * BlingClient class
 * Esta classe é responsável por ser a primeira camada (uma espacie de guarda chuva)
 * para tidas as classes registradas dentro da aplicação em formato de bundle.
 * Os bundles (classes que podem ser chamadas seus metodos publicos por esta classe cliente)
 * são registrados dentra da classe abstrata extendida pelo cliente Blig\Bling
 */
class BlingClient extends Bling {
  	public function __construct($configurations) {
  		parent::__construct($configurations);
  	}
  	
  	/**
  	 * 
  	 * @author Rande A. Moreira
  	 * @since 21 de mai de 2020
  	 * @param unknown $data
  	 * @param unknown $customer_info
  	 * @param unknown $products
  	 * @param unknown $order_totals
  	 * @param unknown $config
  	 */
  	public function addOrder($data, $customer_info, $products, $order_totals, $config) {
  		$bling_data = \Bling\Opencart\Converter::toBlingOrder($data, $customer_info, $products, $order_totals, $config);
  		return $this->createPedido($bling_data);
  	}
  	
  	/**
  	 * 
  	 * @author Rande A. Moreira
  	 * @since 21 de mai de 2020
  	 * @param unknown $customer
  	 * @param unknown $address
  	 */
  	public function addCustomer($customer, $address) {
  		$search = $customer['cpf'] ? $customer['cpf'] : $customer['cnpj'];
  		$contato = $this->getContato($search);
  		if ($contato) {
  			return $contato['id'];
  		}
  		
  		$bling_data = \Bling\Opencart\Converter::toBlingCustomer($customer, $address);
  		return $this->createContato($bling_data);
  	}
}
