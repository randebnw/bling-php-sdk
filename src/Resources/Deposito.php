<?php
namespace Bling\Resources;

use Bling\Core\Bling;
/**
 *
 * Essa classe é resposavel por lidar com os depositos dentro do Bling.
 *
 * @package Bling\Deposito
 * @author Rande A. Moreira <rande@adok.com.br>
 * @see https://manuais.bling.com.br/manual/?item=depositos
 * @version 1.0.0
 */
class Deposito extends Bling {
	const SITUACAO_ATIVO = 'A';
	const SITUACAO_INATIVO = 'I';
	
    public function __construct($configurations) {
        parent::__construct($configurations);
    }
    
	/**
     *
     * Pega os dados do produto associado ao codigo/SKU passado como parametro.
     *
     * @param date $dataEmissao
     * @param int|array $situacao
     *
     * @return bool|array
     * @throws \Exception
     */
    public function getDepositos($situacao = '') {
    	$list = [];
    	$options = [];
    	$allowed = ['I', 'A'];
    	$success = false;
    	
        try {
        	if ($situacao) {
        		$options['query'] = ['situacao' => $situacao];
        	}
        	$request = $this->configurations['guzzle']->get('depositos/json/', $options);
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['depositos'])) {
            	$success = true;
            	foreach ($response['retorno']['depositos'] as $item) {
            		$list[] = $item['deposito'];
            	}
            	return $list;
            }
        } catch (\Exception $e){
            return $this->ResponseException($e);
        }
        
        if (!$success && isset($response['retorno']['erros'])) {
        	$error = $this->_getError($response);
        	throw new \Exception($error['message'], $error['code']);
        }
    }
}
