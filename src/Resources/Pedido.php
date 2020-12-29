<?php
namespace Bling\Resources;

use Bling\Core\Bling;

/**
 *
 * Essa classe é resposavel por lidar com os pedidos dentro do Bling.
 *
 * @package Bling\Pedido
 * @author Rande A. Moreira <rande@adok.com.br>
 * @see https://manuais.bling.com.br/manual/?item=pedidos
 * @version 1.0.0
 */
class Pedido extends Bling {
	const EXCEPTION_DUPLICATED_ORDER = 39;
	
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
    public function createPedido(array $data) {
    	$numero = '';
        try {
        	\Bling\Util\Log::debug(json_encode($data));
        	$xml = \Bling\Util\ArrayToXml::convert($data, ['rootElementName' => 'pedido'], true, 'UTF-8');
        	$request = $this->configurations['guzzle']->post(
                'pedido/json/',
                ['body' => ['xml' => $xml]]
            );
            $response = \json_decode($request->getBody()->getContents(), true);
        	if ($response && is_array($response) && isset($response['retorno']['pedidos'][0]['pedido']['numero'])) {
    			$numero = $response['retorno']['pedidos'][0]['pedido']['numero'];
    		}
        } catch (\Exception $e){
            $this->ResponseException($e);
        }
        
        if (!$numero && isset($response['retorno']['erros'])) {
        	$error = $this->_getError($response);
        	throw new \Exception($error['message'], $error['code']);
        }
        
        return $numero;
    }
    
    /**
     *
     * Atualiza um pedido no Bling com o novo status
     *
     * @return bool|mixed|void
     * @throws \Exception
     */
    public function updatePedido($numero, $situacao) {
    	$success = false;
    	try {
    		$data['idSituacao'] = $situacao;
    		$xml = \Bling\Util\ArrayToXml::convert($data, ['rootElementName' => 'pedido'], true, 'UTF-8');
    		
    		$request = $this->configurations['guzzle']->put(
    			'pedido/'. $numero .'/json/',
    			['query' => ['xml' => $xml]]
    		);
    		$response = \json_decode($request->getBody()->getContents(), true);
    		if ($response && is_array($response) && isset($response['retorno']['pedidos'][0]['pedido']['numero'])) {
    			$success = true;
    		}
    	} catch (\Exception $e){
    		$this->ResponseException($e);
    	}
    	
    	if (!$success && isset($response['retorno']['erros'])) {
    		$error = $this->_getError($response);
    		throw new \Exception($error['message'], $error['code']);
    	}
    	
    	return $success;
    }

	/**
     *
     * Recupera lista de pedidos por data/situacao
     *
     * @param date $dataEmissao
     * @param int|array $situacao
     *
     * @return bool|array
     * @throws \Exception
     */
    public function getPedidos($dataEmissao, $situacao) {
        try {
        	$filters = 'dataEmissao[' . $dataEmissao . ' TO ' . $dataEmissao . ']';
        	if ($situacao) {
        		$situacao = (array) $situacao;
        		$filters .= ';idSituacao[' . implode(',', $situacao) . ']';
        	}
        	
            $request = $this->configurations['guzzle']->get(
                'pedidos/json/',
            	['query' => ['filters' => $filters]]
            );
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['pedidos'])) {
                $list = [];
            	foreach ($response['retorno']['pedidos'] as $item) {
            		$list[] = $item['pedido'];
            	}
                return $list;
            }
            return false;
        } catch (\Exception $e){
            $this->ResponseException($e);
        }
    }
    
    /**
     *
     * Recupera lista de pedidos por data/situacao
     *
     * @param date $dataEmissao
     * @param int|array $situacao
     *
     * @return bool|array
     * @throws \Exception
     */
    public function getPedidosAlterados($dataInicio, $dataFim, $situacao) {
    	try {
    		$filters = 'dataAlteracao[' . $dataInicio . ' TO ' . $dataFim . ']';
    		if ($situacao) {
    			$situacao = (array) $situacao;
    			$filters .= ';idSituacao[' . implode(',', $situacao) . ']';
    		}
    		 
    		$request = $this->configurations['guzzle']->get(
    			'pedidos/json/',
    			['query' => ['filters' => $filters]]
    		);
    		$response = \json_decode($request->getBody()->getContents(), true);
    		if ($response && is_array($response) && isset($response['retorno']['pedidos'])) {
    			$list = [];
    			foreach ($response['retorno']['pedidos'] as $item) {
    				$list[] = $item['pedido'];
    			}
    			return $list;
    		}
    		return false;
    	} catch (\Exception $e){
    		$this->ResponseException($e);
    	}
    }
    
    /**
     * 
     * @author Rande A. Moreira
     * @since 21 de mai de 2020
     * @param unknown $numero
     * @return mixed[]|boolean
     */
    public function getPedido($numero) {
    	try {
    		$request = $this->configurations['guzzle']->get(
    			'pedidos/' . $numero . '/json/'
    		);
    		$response = \json_decode($request->getBody()->getContents(), true);
    		if ($response && is_array($response) && isset($response['retorno']['pedidos'])) {
    			$list = [];
    			foreach ($response['retorno']['pedidos'] as $item) {
    				$list[] = $item['pedido'];
    			}
    			return $list;
    		}
    		return false;
    	} catch (\Exception $e){
    		$this->ResponseException($e);
    	}
    }
}
