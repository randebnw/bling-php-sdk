<?php
namespace Bling\Resources;

use Bling\Core\Bling;
/**
 *
 *
 * @package Resources\Situacao
 * @author Rande A. Moreira <rande@adok.com.br>
 * @see https://manuais.bling.com.br/manual/?item=situacoes
 * @version 1.0.0
 */
class Situacao extends Bling {
	const MODULO_VENDAS = 'Vendas';
	
	public function __construct($configurations) {
        parent::__construct($configurations);
    }

	/**
     *
     * Recupera lista de situacoes por modulo
     *
     * @param string $modulo
     *
     * @return bool|array
     * @throws \Exception
     */
    public function getSituacoes($modulo) {
    	$list = [];
    	$success = false;
    	
        try {
        	$request = $this->configurations['guzzle']->get('situacao/' . $modulo . '/json/');
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response) && isset($response['retorno']['situacoes'])) {
            	$success = true;
            	foreach ($response['retorno']['situacoes'] as $item) {
            		$list[] = $item['situacao'];
            	}
                return $list;
            }
        } catch (\Exception $e){
            $this->ResponseException($e);
        }
        
        if (!$success && isset($response['retorno']['erros'])) {
        	$error = $this->_getError($response);
        	throw new \Exception($error['message'], $error['code']);
        }
    }
}
