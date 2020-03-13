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
        try {
        	if ($situacao) {
        		$situacao = (array) $situacao;
        	}
        	
            $request = $this->configurations['guzzle']->get('situacao/' . $modulo . '/json/');
            $response = \json_decode($request->getBody()->getContents(), true);
            if ($response && is_array($response)) {
                $list = [];
            	foreach ($response['retorno']['situacoes'] as $item) {
            		$list[] = $item['situacao'];
            	}
                return $list;
            }
            return false;
        } catch (\Exception $e){
            return $this->ResponseException($e);
        }
    }
}
