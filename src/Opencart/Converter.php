<?php
namespace Bling\Opencart;

use Bling\Core\Util;

/**
 *
 * Converte dados do Bling para o Opencart e vice-versa
 *
 * @package Bling\Opencart
 * @author Rande A. Moreira <rande@adok.com.br>
 * @see https://manuais.bling.com.br/manual/?item=produtos
 * @version 1.0.0
 */
class Converter {
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 4 de mar de 2020
	 * @param array $data
	 * @param array $config
     * @param array $weight_lib
	 */
    public static function toBlingProduct(array $data, $config, $weight_lib, $is_new) {
    	$storage_id = $config->get('bling_api_storage');
    	$sync_name = $config->get('bling_api_sync_name');
    	$sync_description = $config->get('bling_api_sync_description');
    	$sync_price = $config->get('bling_api_sync_price') && $config->get('bling_api_store_price') == \Bling\Resources\Produto::DEFAULT_PRICE;
    	$sync_brand = $config->get('bling_api_sync_brand');
    	
        $bling_data = [];
        $bling_data['codigo'] = $data['sku'];
        
        if (isset($data['name']) && ($sync_name || $is_new)) {
        	$bling_data['descricao'] = $data['name'];
        }
        
        if (isset($data['status'])) {
        	$bling_data['situacao'] = $data['status'] ? \Bling\Resources\Produto::SITUACAO_ATIVO : \Bling\Resources\Produto::SITUACAO_INATIVO;
        }
        
        if (isset($data['price']) && ($sync_price || $is_new)) {
        	$bling_data['vlr_unit'] = $data['price'];
        }
        
        if (isset($data['description']) && !empty($data['description']) && ($sync_description || $is_new)) {
        	$bling_data['descricaoComplementar'] = html_entity_decode($data['description'], ENT_QUOTES, 'UTF-8');
        	if (strlen($bling_data['descricaoComplementar']) > 5000) {
        		$bling_data['descricaoComplementar'] = substr(strip_tags($bling_data['descricaoComplementar']), 0, 5000);
        	}
        }
        
        if (isset($data['mini_description']) && ($sync_description || $is_new)) {
        	$bling_data['descricaoCurta'] = html_entity_decode($data['mini_description'], ENT_QUOTES, 'UTF-8');
        }
        
        if (isset($data['quantity'])) {
        	$bling_data['estoque'] = $data['quantity'];
        }
        
        if (isset($data['storage']) && is_array($data['storage'])) {
        	$bling_data['deposito']['id'] = $data['storage']['bling_id'];
        	$bling_data['deposito']['estoque'] = $data['storage']['quantity'];
        }
        
        if (isset($data['ean'])) {
        	$bling_data['gtin'] = $data['ean'];
        }
        
        if (isset($data['width'])) {
        	$bling_data['largura'] = $data['width'];
        }
        
        if (isset($data['height'])) {
        	$bling_data['altura'] = $data['height'];
        }
        
        if (isset($data['length'])) {
        	$bling_data['profundidade'] = $data['length'];
        }
        
        $unidadesMedida = \Bling\Core\Util::getUnidadesMedida();
        if (isset($data['length_class_id'])) {
        	$map_length = $config->get('bling_api_map_length_id');
        	foreach ($map_length as $bling_key => $length_class_id) {
        		if ($length_class_id == $data['length_class_id']) {
        			$bling_data['unidadeMedida'] = $unidadesMedida[$bling_key];
        			break;
        		}
        	}
        }
        
        if (isset($data['weight'])) {
        	$bling_data['peso_bruto'] = $weight_lib->convertToKg($data['weight'], $data['weight_class_id']);
        	$bling_data['peso_liq'] = $weight_lib->convertToKg($data['weight'], $data['weight_class_id']);
        }
        
        if (isset($data['manufacturer']) && ($sync_brand || $is_new)) {
        	$bling_data['marca'] = $data['manufacturer'];
        }
        
        if (isset($data['image']) || isset($data['images'])) {
        	$bling_data['imagens'] = [];
        	if (isset($data['image']) && !empty($data['image'])) {
        		$bling_data['imagens'][] = ['url' => $data['image']];
        	}
        	
        	if (isset($data['images']) && is_array($data['images'])) {
        		foreach ($data['images'] as $img) {
        			if ($img) {
        				$bling_data['imagens'][] = ['url' => $img];
        			}
        		}
        	}
        }
        
        if (isset($data['options']) && count($data['options']) > 0) {
        	$bling_data['variacoes']['variacao'] = [];
        	foreach ($data['options'] as $option) {
        		$variacao = [
        			'nome' => $option['option_name'] . ':' . $option['option_value'],
        			'codigo' => $option['option_sku'],
        			'clonarDadosPai' => $option['use_parent_info'] ? 'S' : 'N'
        		];
        		if ($storage_id) {
        			$variacao['deposito']['id'] = $storage_id;
        			$variacao['deposito']['estoque'] = $option['quantity'];
        		} else {
        			$variacao['estoque'] = $option['quantity'];
        		}
        		
        		if (isset($option['price'])) {
        			$variacao['vlr_unit'] = $option['price'];
        		}
        		
        		$bling_data['variacoes']['variacao'][] = $variacao;
        	}
        }
        
        return $bling_data;
    }
    
    /**
     * 
     * @author Rande A. Moreira
     * @since 21 de mai de 2020
     * @param unknown $data
     * @param unknown $address
     * @return string
     */
    public static function toBlingCustomer($data, $address) {
    	$cliente = [];
    	if ($data['cpf']) {
    		$cliente['nome'] = trim($data['firstname']) . ' ' . trim($data['lastname']);
    		$cliente['tipoPessoa'] = \Bling\Resources\Contato::PESSOA_FISICA;
    		$cliente['cpf_cnpj'] = $data['cpf'];
    		$cliente['ie_rg'] = $data['rg'];
    		$cliente['sexo'] = $data['sexo'] == 'm' ? \Bling\Resources\Contato::SEXO_M : \Bling\Resources\Contato::SEXO_F;
    		if ($data['data_nascimento'] && $data['data_nascimento'] != '0000-00-00') {
    			$cliente['dataNascimento'] = date('d/m/Y', strtotime($data['data_nascimento']));
    		}
    		
    		$cliente['contribuinte'] = \Bling\Resources\Contato::CONTRIBUINTE_NAO;
    	} else {
    		$cliente['nome'] = $data['razao_social'];
    		$cliente['tipoPessoa'] = \Bling\Resources\Contato::PESSOA_JURIDICA;
    		$cliente['cpf_cnpj'] = $data['cnpj'];
    		$cliente['ie_rg'] = $data['inscricao_estadual'] ? strtoupper($data['inscricao_estadual']) : \Bling\Resources\Contato::IE_ISENTO;
    		$cliente['contribuinte'] = $data['inscricao_estadual'] ? \Bling\Resources\Contato::CONTRIBUINTE : \Bling\Resources\Contato::CONTRIBUINTE_ISENTO;
    	}
    	 
    	$cliente['fone'] = $data['telephone'];
    	$cliente['celular'] = $data['fax'];
    	$cliente['email'] = $data['email'];
    	
    	$cliente['endereco'] = $address['address_1'];
    	$cliente['numero'] = $address['numero'];
    	$cliente['complemento'] = $address['complemento'];
    	$cliente['bairro'] = $address['address_2'];
    	$cliente['cep'] = $address['postcode'];
    	$cliente['cidade'] = $address['city'];
    	$cliente['uf'] = $address['uf'];
    	$cliente['tipos_contatos'][] = ['tipo_contato' => ['descricao' => \Bling\Resources\Contato::CONTATO_CLIENTE]];
    	
    	return $cliente;
    }
    
    /**
     * 
     * @author Rande A. Moreira
     * @since 21 de mai de 2020
     * @param array $data
     * @param unknown $customer_info
     * @param unknown $products
     * @param unknown $order_totals
     * @param unknown $config
     */
    public static function toBlingOrder(array $data, $customer_info, $products, $order_totals, $config) {
    	$bling_data = array();
    	$date_field = $config->get('bling_api_order_date');
    	$bling_data['data'] = date('d/m/Y', strtotime($data[$date_field]));
    	$bling_data['numero_loja'] = $data['order_id'];
    	$bling_data['loja'] = $config->get('bling_api_store_code');
    	
    	$cliente['id'] = $customer_info['bling_id'];
    	if ($data['cpf']) {
    		$cliente['nome'] = trim($customer_info['firstname']) . ' ' . trim($customer_info['lastname']);
    		$cliente['tipoPessoa'] = \Bling\Resources\Contato::PESSOA_FISICA;
    		$cliente['cpf_cnpj'] = $data['cpf'];
    	} else {
    		$cliente['nome'] = $data['razao_social'];
    		$cliente['tipoPessoa'] = \Bling\Resources\Contato::PESSOA_JURIDICA;
    		$cliente['cpf_cnpj'] = $data['cnpj'];
    		$cliente['ie'] = $data['inscricao_estadual'] ? $data['inscricao_estadual'] : \Bling\Resources\Contato::IE_ISENTO;
     		$cliente['contribuinte'] = $data['inscricao_estadual'] ? \Bling\Resources\Contato::CONTRIBUINTE : \Bling\Resources\Contato::CONTRIBUINTE_ISENTO;
    	}
    	
    	$cliente['endereco'] = $data['payment_address_1'];
    	$cliente['numero'] = $data['payment_numero'];
    	$cliente['complemento'] = $data['payment_complemento'];
    	$cliente['bairro'] = $data['payment_address_2'];
    	$cliente['cep'] = $data['payment_postcode'];
    	$cliente['cidade'] = $data['payment_city'];
    	$cliente['uf'] = $data['payment_uf'];
    	$cliente['fone'] = $data['telephone'];
    	$cliente['celular'] = $data['fax'];
    	$cliente['email'] = $data['email'];
    	$bling_data['cliente'] = $cliente;
    	
    	$has_shipping = false;
    	$shipping_value = 0;
    	$discount_value = 0;
    	foreach ($order_totals as $item) {
    		if ($item['code'] == 'shipping') {
    			$has_shipping = true;
    			$shipping_value = $item['value'];
    		}
    		
    		if ($item['value'] < 0) {
    			$discount_value += abs($item['value']);
    		}
    	}
    	
    	if ($has_shipping && $data['shipping_code']) {
    		$transporte['transportadora'] = $data['shipping_company_name'];
    		if ($data['is_tracking']) {
    			$transporte['servico_correios'] = $data['servico_correios'];
    		}
    		
    		$dados_etiqueta['nome'] = trim($data['shipping_firstname']) . ' ' . trim($data['shipping_lastname']);
    		$dados_etiqueta['endereco'] = $data['shipping_address_1'];
    		$dados_etiqueta['numero'] = $data['shipping_numero'];
    		$dados_etiqueta['complemento'] = $data['shipping_complemento'];
    		$dados_etiqueta['bairro'] = $data['shipping_address_2'];
    		$dados_etiqueta['cep'] = $data['shipping_postcode'];
    		$dados_etiqueta['municipio'] = $data['shipping_city'];
    		$dados_etiqueta['uf'] = $data['shipping_uf'];
    		$transporte['dados_etiqueta'] = $dados_etiqueta;
    		
    		// TODO volumes/codigo rastreamento
    		$bling_data['transporte'] = $transporte;
    	}
    	
    	$bling_data['itens'] = [];
    	foreach ($products as $item) {
    		$bling_data['itens'][] = [
    			'item' => [
    				'codigo' => $item['sku'],
    				'descricao' => $item['name'],
    				'qtde' => $item['quantity'],
    				'vlr_unit' => $item['price']
    			] 
    		];
    	}
    	
    	$map_payment = $config->get('bling_api_map_payment');
    	if (isset($map_payment[$data['payment_code']]) && !empty($map_payment[$data['payment_code']]) && $config->get('bling_api_sync_payment_info')) {
    		$bling_data['idFormaPagamento'] = $map_payment[$data['payment_code']];
    		// TODO parcelas
    	}
    	
    	if ($config->get('bling_api_sync_shipping')) {
    		$bling_data['vlr_frete'] = round($shipping_value, 2);
    	}
    	
    	if ($config->get('bling_api_sync_discount')) {
    		$bling_data['vlr_desconto'] = round($discount_value, 2);
    	}
    	
    	$bling_data['obs'] = $data['comment'];
    	$bling_data['obs_internas'] = 'Pedido cadastrado pela loja virtual: ' . $data['store_name'];
		
		return $bling_data;
    }
    
    /**
     * 
     * @author Rande A. Moreira
     * @since 4 de mar de 2020
     * @param array $data
     * @param array $config
     * @param array $weight_lib
     */
    public static function toOpencartProduct(array $data, $config, $weight_lib) {
    	$oc_data = [];
    	
    	$oc_data = [];
    	$oc_data['subtract'] = $config->get('config_stock_subtract');
    	$oc_data['shipping'] = $config->get('config_shipping_required');
    	$oc_data['stock_status_id'] = $config->get('config_stock_status_id');
    	$oc_data['sku'] = $data['codigo'];
    	$oc_data['name'] = $data['descricao'];
    	$oc_data['status'] = \Bling\Resources\Produto::isAtivo($data['situacao']) ? 1 : 0;
    	$oc_data['mini_description'] = $data['descricaoCurta'];
    	$oc_data['description'] = $data['descricaoComplementar'];
    	$oc_data['price'] = $data['preco'];
    	$oc_data['special'] = 0;
    	
    	// trata campos exclusivos de produto e que nao existem para servicos
    	if (!\Bling\Resources\Produto::isServico($data['tipo'])) {
    		$oc_data['quantity'] = $data['estoqueAtual'];
    		$oc_data['weight'] = $data['pesoBruto'];
    		$oc_data['ean'] = $data['gtin'];
    		$oc_data['weight_class_id'] = $weight_lib->getIdByUnit('kg');
    		
    		$oc_data['width'] = $data['larguraProduto'];
    		$oc_data['height'] = $data['alturaProduto'];
    		$oc_data['length'] = $data['profundidadeProduto'];
    		 
    		$unidadesMedida = \Bling\Core\Util::getUnidadesMedida();
    		$map_length = $config->get('bling_api_map_length_id');
    		foreach ($unidadesMedida as $key => $label) {
    			if ($label == $data['unidadeMedida']) {
    				$oc_data['length_class_id'] = $map_length[$key];
    				break;
    			}
    		}
    	} else {
    		$oc_data['quantity'] = 999;
    		$oc_data['weight'] = 0;
    		$oc_data['ean'] = '';
    		$oc_data['width'] = 0;
    		$oc_data['height'] = 0;
    		$oc_data['length'] = 0;
    		$oc_data['length_class_id'] = $config->get('config_length_class_id');
    		$oc_data['weight_class_id'] = $config->get('config_weight_class_id');
    	}
    	
    	if (isset($data['produtoLoja']) && $config->get('bling_api_store_price') == \Bling\Resources\Produto::CUSTOM_PRICE) {
    		if ($data['produtoLoja']['preco']['preco'] > 0) {
    			$oc_data['price'] = $data['produtoLoja']['preco']['preco'];
    		}
    	
    		if ($data['produtoLoja']['preco']['precoPromocional'] > 0) {
    			$oc_data['special'] = $data['produtoLoja']['preco']['precoPromocional'];
    		}
    	}
    	
    	/*if (isset($data['storage']) && is_array($data['storage'])) {
    		$oc_data['deposito']['id'] = $data['storage']['bling_id'];
    		$oc_data['deposito']['estoque'] = $data['storage']['quantity'];
    	}*/
    	
    	$oc_data['manufacturer'] = trim($data['marca']);
    	
    	if (isset($data['opcionais'])) {
    		$oc_data['options'] = [];
    		foreach ($data['opcionais'] as $item) {
    			$oc_data['options'][] = [
    				'sku' => $item['codigo'],
					'name' => trim($item['nome']),
					'value' => trim($item['valor']),
					'quantity' => $item['estoque'],
					'price' => $item['preco']
    			];
    		}
    	}
    	
    	$empty_fields = ['model', 'upc', 'jan', 'isbn', 'mpn', 'location', 'minimum', 'points', 'sort_order', 'tax_class_id'];
    	foreach ($empty_fields as $item) {
    		$oc_data[$item] = '';
    	}
    	
    	return $oc_data;
    }
    
    /**
     * 
     * @author Rande A. Moreira
     * @since 12 de mar de 2020
     * @param array $data
     * @param unknown $config
     * @param unknown $map_zones
     */
    public static function toOpencartCustomer(array $data, $config, $map_zones) {
    	$oc_data = [];
    	
    	$oc_data['customer_group_id']  = $config->get('dc_default_customer_group');
    	$names = explode(' ', $data['nome']);
    	$oc_data['firstname'] = array_shift($names);
    	$oc_data['lastname'] = ' ';
    	if ($names) {
    		$oc_data['lastname'] = implode(' ', $names);
    	}
    	
    	$oc_data['apelido'] = '';
    	$oc_data['email'] = $data['email'];
    	$oc_data['telephone'] = preg_replace('/([^0-9])/i', '', $data['fone']);
    	$oc_data['fax'] = preg_replace('/([^0-9])/i', '', $data['celular']);
    	$oc_data['rg'] = $data['rg'];
    	$oc_data['cpf'] = '';
    	$oc_data['cnpj'] = '';
    	$oc_data['inscricao_estadual'] = '';
    	$oc_data['data_nascimento'] = '';
    	$oc_data['razao_social'] = '';
    	$oc_data['sexo'] = '';
    	$oc_data['password'] = uniqid();
    	$oc_data['company'] = '';
    	$oc_data['company_id'] = '';
    	$oc_data['tax_id'] = '';
    	
    	$cpf_cnpj = preg_replace('/([^0-9])/i', '', $data['cnpj']);
    	if (strlen($cpf_cnpj) == 11) {
    		$oc_data['cpf'] = $cpf_cnpj;
    		$oc_data['data_nascimento'] = isset($data['dataNascimento']) ? $data['dataNascimento'] : '';
    		$oc_data['sexo'] = (isset($data['sexo']) && $data['sexo'] == \Bling\Core\Util::CLIENTE_SEXO_FEMININO) ? 'f' : 'm';
    	} else {
    		$oc_data['cnpj'] = $cpf_cnpj;
    		$oc_data['inscricao_estadual'] = $data['ie'];
    		$oc_data['razao_social'] = $data['nome'];
    	}
    	
    	$oc_data['address_1'] = $data['endereco'];
		$oc_data['address_2'] = $data['bairro'];
		$oc_data['city']      = $data['cidade'];
		$oc_data['numero']    = $data['numero'];
		$oc_data['complemento']    = $data['complemento'];
		$oc_data['postcode']  = preg_replace('/([^0-9])/i', '', $data['cep']);;
    	$oc_data['country_id'] = $config->get('config_country_id');
    	$oc_data['zone_id'] = isset($map_zones[$data['uf']]) ? $map_zones[$data['uf']]['zone_id'] : $config->get('config_zone_id');
    	
    	return $oc_data;
    }
    
    public static function toOpencartOrder(array $data, $customer_info, $map_products, $country_info, $map_zones, $config, $currency) {
    	$oc_data = [];
    	
    	$oc_data['bling_id'] = $data['numero'];
    	$oc_data['invoice_prefix'] = $config->get('config_invoice_prefix');
    	$oc_data['store_id'] = $config->get('config_store_id');
    	$oc_data['store_name'] = $config->get('config_name');
    	$oc_data['affiliate_id'] = 0;
    	$oc_data['commission'] = 0;
    	
    	$oc_data['language_id'] = $config->get('config_language_id');
    	$oc_data['currency_id'] = $currency->getId();
    	$oc_data['currency_code'] = $currency->getCode();
    	$oc_data['currency_value'] = $currency->getValue($currency->getCode());
    	$oc_data['user_agent'] = 'Bling/Api';
    	$oc_data['accept_language'] = '';
    	$oc_data['vouchers'] = [];
    	$oc_data['comment'] = 'Pedido #' . $data['numero'] . ' importado do Bling.';
    	$oc_data['ip'] = $_SERVER['REMOTE_ADDR'];
    	$oc_data['forwarded_ip'] = '';
    	
    	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    		$oc_data['forwarded_ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
    	} elseif(!empty($_SERVER['HTTP_CLIENT_IP'])) {
    		$oc_data['forwarded_ip'] = $_SERVER['HTTP_CLIENT_IP'];
    	}
    		
    	if ($oc_data['store_id']) {
    		$oc_data['store_url'] = $config->get('config_ssl');
    	} else {
    		$oc_data['store_url'] = HTTPS_SERVER;
    	}
    	
    	// dados basicos do cadastro do cliente 
    	$oc_data['customer_id'] = $customer_info['customer_id'];
		$oc_data['customer_group_id'] = $customer_info['customer_group_id'];
		$oc_data['firstname'] = $customer_info['firstname'];
		$oc_data['lastname'] = $customer_info['lastname'];
		$oc_data['cpf'] = $customer_info['cpf'];
		$oc_data['cnpj'] = $customer_info['cnpj'];
		$oc_data['inscricao_estadual'] = $customer_info['inscricao_estadual'];
		$oc_data['razao_social'] = $customer_info['razao_social'];
		$oc_data['rg'] = $customer_info['rg'];
		$oc_data['sexo'] = $customer_info['sexo'];
		$oc_data['data_nascimento'] = $customer_info['data_nascimento'];
		$oc_data['email'] = $customer_info['email'];
		$oc_data['telephone'] = $customer_info['telephone'];
		$oc_data['fax'] = $customer_info['fax'];
		
		// endereco de cobranca
		$bling_address = $data['cliente'];
		$payment_zone = isset($map_zones[$bling_address['uf']]) ? $map_zones[$bling_address['uf']] : ['zone_id' => $config->get('config_zone_id'), 'name' => 'MG'];
		$payment_address['apelido'] = '';
		$payment_address['company'] = '';
		$payment_address['company_id'] = '';
		$payment_address['tax_id'] = '';
		$payment_address['firstname'] = $customer_info['firstname'];
		$payment_address['lastname'] = $customer_info['lastname'];
		$payment_address['address_1'] = $bling_address['endereco'];
		$payment_address['address_2'] = $bling_address['bairro'];
		$payment_address['complemento'] = $bling_address['complemento'];
		$payment_address['numero'] = $bling_address['numero'];
		$payment_address['city'] = $bling_address['cidade'];
		$payment_address['postcode'] = preg_replace('/([^0-9])/i', '', $bling_address['cep']);;
		$payment_address['zone'] = $payment_zone['name'];
		$payment_address['zone_id'] = $payment_zone['zone_id'];
		$payment_address['country'] = $country_info['name'];
		$payment_address['country_id'] = $country_info['country_id'];
		$payment_address['address_format'] = $country_info['address_format'];
    	
		foreach ($payment_address as $key => $val) {
			$oc_data['payment_' . $key] = $val;
		}
	
		// forma de pagamento
		$oc_data['payment_method'] = '';
		$oc_data['payment_code'] = '';
		$payment_map = $config->get('bling_map_payment');
		if (isset($data['parcelas']) && $data['parcelas']) {
			foreach ($data['parcelas'] as $parcela) {
				$oc_data['payment_method'] = $parcela['parcela']['forma_pagamento']['descricao'];;
				
				$payment_id = $parcela['parcela']['forma_pagamento']['id'];
				if (isset($payment_map[$payment_id])) {
					$oc_data['payment_code'] = $payment_map[$payment_id];
				}
			}
		}
		
		if (!$oc_data['payment_code']) {
			// TODO definir como vai ficar a forma de pagamento
			$oc_data['payment_code'] = 'cod';
			//throw new \Exception('Pagamento do pedido ' . $data['numero'] . ' não esta mapeado.');
		}
		
		// endereco de entrega
		$shipping_address = $payment_address;
		if (isset($data['transporte']['enderecoEntrega'])) {
			$entrega = $data['transporte']['enderecoEntrega'];
			$names = explode(' ', $entrega['nome']);
			$shipping_address['firstname'] = array_shift($names);
			$shipping_address['lastname'] = ' ';
			if ($names) {
				$shipping_address['lastname'] = implode(' ', $names);
			}
			
			$shipping_zone = isset($map_zones[$entrega['uf']]) ? $map_zones[$entrega['uf']] : ['zone_id' => $config->get('config_zone_id'), 'name' => 'MG'];
			$shipping_address['address_1'] = $entrega['endereco'];
			$shipping_address['address_2'] = $entrega['bairro'];
			$shipping_address['complemento'] = $entrega['complemento'];
			$shipping_address['numero'] = $entrega['numero'];
			$shipping_address['city'] = $entrega['cidade'];
			$shipping_address['postcode'] = preg_replace('/([^0-9])/i', '', $entrega['cep']);;
			$shipping_address['zone'] = $shipping_zone['name'];
			$shipping_address['zone_id'] = $shipping_zone['zone_id'];
		}
		
		foreach ($shipping_address as $key => $val) {
			$oc_data['shipping_' . $key] = $val;
		}
		
		// forma de entrega
		$oc_data['shipping_method'] = 'Frete';
		$oc_data['shipping_code'] = 'flat.flat';
		$oc_data['tracking'] = [];
		if (isset($data['valorfrete'], $data['transporte']['transportadora'])) {
			$oc_data['shipping_method'] = $data['transporte']['transportadora'];			
			
			// TODO tratar outros casos que nao sejam correios
			if (strpos(strtolower($data['transporte']['transportadora']), 'correios') !== false) {
				if (isset($data['transporte']['volumes'])) {
					// armazena codigos de rastreamento
					foreach ($data['transporte']['volumes'] as $volume) {
						if (isset($volume['volume']['codigoRastreamento'])) {
							$oc_data['tracking'][] = $volume['volume']['codigoRastreamento'];
						}
					}
					
					// pega o primeiro volume apenas para identificar o tipo de servico
					$volume = $data['transporte']['volumes'][0]['volume'];
					$oc_data['shipping_method'] .= ' - ' . $volume['servico'];
					if (isset($volume['prazoEntregaPrevisto'])) {
						$oc_data['shipping_method'] .= ' (Previsão ' . $volume['prazoEntregaPrevisto'] . ' dias úteis)';
					}
					
					switch ($volume['servico']) {
						case 'PAC':
							$oc_data['shipping_code'] = 'correios.04510';
							break;
						case 'SEDEX':
							$oc_data['shipping_code'] = 'correios.04014';
							break;
						case 'SEDEX 10':
							$oc_data['shipping_code'] = 'correios.40215';
							break;
						default:
							break;
					}
				}
			}
		}
		
		// produtos
		$product_data = [];
		$oc_data['has_stock'] = true;
		foreach ($data['itens'] as $product) {
			$bling_product = $product['item'];
			
			if (!isset($map_products[$bling_product['codigo']])) {
				throw new \Exception('Produto com SKU ' . $bling_product['codigo'] . ' não encontrado na loja virtual.');
			}
			
			/*$option_data = array();
			foreach ($product['option'] as $option) {
				if ($option['type'] != 'file') {
					$value = $option['option_value'];
				} else {
					$value = $this->encryption->decrypt($option['option_value']);
				}
					
				$option_data[] = array(
					'product_option_id'       => $option['product_option_id'],
					'product_option_value_id' => $option['product_option_value_id'],
					'option_id'               => $option['option_id'],
					'option_value_id'         => $option['option_value_id'],
					'name'                    => $option['name'],
					'value'                   => $value,
					'type'                    => $option['type']
				);
			}*/
		
			$product_info = $map_products[$bling_product['codigo']];
			$product_data[] = [
				'product_id' => $product_info['product_id'],
				'dc_id' 	 => $product_info['dc_id'],
				'dc_product_price' 	=> isset($bling_product['dc_product_price']) ? $bling_product['dc_product_price'] : '',
				'company_id' => isset($bling_product['company_id']) ? $bling_product['company_id'] : '',
				'is_special' => true, // forcar usar o preco definido
				'discount'   => 0,
				'name'       => $bling_product['descricao'],
				'model'      => $product_info['model'],
				'option'     => [], // TODO options
				'download'   => [],
				'quantity'   => $bling_product['quantidade'],
				'subtract'   => $product_info['subtract'],
				'price'      => $bling_product['valorunidade'], // TODO verificar se esse preço ja vem com o desconto
				'total'      => $bling_product['quantidade'] * $bling_product['valorunidade'],
				'tax'        => 0, // TODO impostos
				'reward'     => 0 // TODO reward
			];
			
			if ($bling_product['quantidade'] > $product_info['quantity']) {
				$oc_data['has_stock'] = false;
			}
		}
		
		$oc_data['products'] = $product_data;
		
		// totais
		$total_data = [];
		$total_data[] = [
			'code'       => 'sub_total',
			'title'      => 'Sub-total',
			'text'       => $currency->format($data['totalprodutos']),
			'value'      => $data['totalprodutos'],
			'sort_order' => $config->get('sub_total_sort_order')
		];
		
		// TODO desconto
		
		if (isset($data['valorfrete'])) {
			$shipping_title = 'Frete';
			if (isset($data['transporte']['transportadora'])) {
				$shipping_title .= ' - ' . $data['transporte']['transportadora'];
			}
			
			$total_data[] = [
				'code'       => 'shipping',
				'title'      => $shipping_title,
				'text'       => $currency->format($data['valorfrete']),
				'value'      => $data['valorfrete'],
				'sort_order' => $config->get('shipping_sort_order')
			];
		}
		
		$total_data[] = [
			'code'       => 'total',
			'title'      => 'Total',
			'text'       => $currency->format($data['totalvenda']),
			'value'      => $data['totalvenda'],
			'sort_order' => $config->get('total_sort_order')
		];
		
		$oc_data['totals'] = $total_data;
		$oc_data['total'] = $data['totalvenda'];
    	 
    	return $oc_data;
    }
}
