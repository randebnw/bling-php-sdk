<?php
namespace Bling\Opencart;

/**
 *
 * Converte produto do Bling para o Opencart e vice-versa
 *
 * @package Bling\Opencart
 * @author Rande A. Moreira <rande@adok.com.br>
 * @see https://manuais.bling.com.br/manual/?item=produtos
 * @version 1.0.0
 */
class Converter {
	const PRODUTO_STATUS_ATIVO = 'Ativo';
	const PRODUTO_STATUS_INATIVO = 'Inativo';
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 4 de mar de 2020
	 * @param array $data
	 */
    public static function toBlingProduct(array $data) {
        $bling_data = [];
        $bling_data['codigo'] = $data['sku'];
        $bling_data['descricao'] = $data['name'];
        $bling_data['situacao'] = $data['status'] ? self::PRODUTO_STATUS_ATIVO : self::PRODUTO_STATUS_INATIVO;
        $bling_data['descricaoCurta'] = $data['mini_description'];
        $bling_data['descricaoComplementar'] = $data['description'];
        $bling_data['vlr_unit'] = $data['price'];
        $bling_data['peso_bruto'] = $data['weigth'];
        $bling_data['estoque'] = $data['quantity'];
        if (isset($data['storage']) && is_array($data['storage'])) {
        	$bling_data['deposito']['id'] = $data['storage']['bling_id'];
        	$bling_data['deposito']['estoque'] = $data['storage']['quantity'];
        }
        
        $bling_data['gtin'] = $data['ean'];
        $bling_data['largura'] = $data['width'];
        $bling_data['altura'] = $data['height'];
        $bling_data['profundidade'] = $data['length'];
        $bling_data['unidadeMedida'] = $data['length_class_desc'];
        
        $bling_data['marca'] = $data['manufacturer'];
        
        if (isset($data['options'])) {
        	$bling_data['variacoes'] = [];
        	foreach ($data['options'] as $option) {
        		$bling_data['variacoes'][] = [
        			'variacao' => [
        				'nome' => $option['option_name'] . ':' . $option['option_value'],
        				'codigo' => $option['option_sku'],
        				'clonarDadosPai' => 'S'
        			]
        		];
        	}
        }
        
        return $bling_data;
    }
    
    /**
     * 
     * @author Rande A. Moreira
     * @since 4 de mar de 2020
     * @param array $data
     */
    public static function toOpencartProduct(array $data) {
    	$oc_data = [];
    	
    	$oc_data = [];
    	$oc_data['sku'] = $data['codigo'];
    	$oc_data['name'] = $data['description'];
    	$oc_data['status'] = $data['situacao'] == self::PRODUTO_STATUS_ATIVO ? 1 : 0;
    	$oc_data['mini_description'] = $data['descricaoCurta'];
    	$oc_data['description'] = $data['descricaoComplementar'];
    	$oc_data['price'] = $data['vlr_unit'];
    	$oc_data['weigth'] = $data['peso_bruto'];
    	$oc_data['quantity'] = $data['estoque'];
    	
    	/*if (isset($data['storage']) && is_array($data['storage'])) {
    		$oc_data['deposito']['id'] = $data['storage']['bling_id'];
    		$oc_data['deposito']['estoque'] = $data['storage']['quantity'];
    	}*/
    	
    	$oc_data['ean'] = $data['gtin'];
    	$oc_data['width'] = $data['largura'];
    	$oc_data['height'] = $data['altura'];
    	$oc_data['length'] = $data['profundidade'];
    	$oc_data['length_class_desc'] = $data['unidadeMedida'];
    	
    	$oc_data['manufacturer'] = $data['marca'];
    	
    	if (isset($data['variacoes'])) {
    		$oc_data['options'] = [];
    		foreach ($data['variacoes'] as $item) {
    			$variacao = $item['variacao'];
    			list($option_name, $option_value) = explode(':', $variacao['nome']);
    			$oc_data['options'][] = [
    				'option_name' => $option_name,
    				'option_value' => $option_value,
    				'option_sku' => $variacao['codigo'],
    			];
    		}
    	}
    	
    	return $oc_data;
    }
}
