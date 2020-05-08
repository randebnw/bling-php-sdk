<?php
namespace Bling\Resources;

use Bling\Core\Bling;
/**
 *
 * Essa classe é resposavel por lidar com os produtos dentro do Bling.
 *
 * @package Bling\Produto
 * @author Rande A. Moreira <rande@adok.com.br>
 * @see https://manuais.bling.com.br/manual/?item=produtos
 * @version 1.0.0
 */
class Produto extends Bling {
	const DEFAULT_PRICE = 'd';
	const CUSTOM_PRICE = 'c'; // multistore
	
    public function __construct($configurations) {
        parent::__construct($configurations);
    }

    /**
     *
     * Cria o produto dentro do Bling
     *
     * @param array $data
     * @return bool|array
     * @throws \Exception
     */
    public function createProduto(array $data) {
    	$success = false;
        try {
        	$xml = \Bling\Util\ArrayToXml::convert($data, ['rootElementName' => 'produto'], true, 'UTF-8');
            $request = $this->configurations['guzzle']->post(
                'produto/json/',
                ['query' => ['xml' => $xml]]
            );
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['produtos'][0][0]['produto']['codigo'])) {
                $success = true;
            }
        } catch (\Exception $e){
            return $this->ResponseException($e);
        }
        
    	if (!$success && isset($response['retorno']['erros'])) {
    		$error = $this->_getError($response);    		
    		throw new \Exception($error['message'], $error['code']);
    	}
    }
    
    /**
     *
     * Atualiza um produto no Bling com novo array de dados buscando por seu ID
     *
     * @return bool|mixed|void
     * @throws \Exception
     */
    public function updateProduto($codigo, $data) {
    	$success = false;
    	try {
    		$xml = \Bling\Util\ArrayToXml::convert($data, ['rootElementName' => 'produto'], true, 'UTF-8');
    		$request = $this->configurations['guzzle']->post(
    				'produto/'. $codigo .'/json/',
    				['query' => ['xml' => $xml]]
    				);
    		$response = \json_decode($request->getBody()->getContents(), true);
    		if ($response && is_array($response) && isset($response['retorno']['produtos'][0][0]['produto']['codigo'])) {
    			$success = true;
    		}
    	} catch (\Exception $e){
    		return $this->ResponseException($e);
    	}
    	
    	if (!$success && isset($response['retorno']['erros'])) {
    		$error = $this->_getError($response);    		
    		throw new \Exception($error['message'], $error['code']);
    	}
    }

	/**
     *
     * Pega os dados do produto associado ao codigo/SKU passado como parametro.
     *
     * @param string $codigo
     *
     * @return bool|array
     * @throws \Exception
     */
    public function getProduto($codigo) {
        try {
            $request = $this->configurations['guzzle']->get(
                'produto/' . $codigo . '/json/'
            );
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['produtos'][0]['produto'])) {
                return $response['retorno']['produtos'][0]['produto'];
            }
            return false;
        } catch (\Exception $e){
            return $this->ResponseException($e);
        }
    }
}
