<?php
namespace Bling\Resources;

use Bling\Core\Bling;
/**
 *
 *
 * @package Resources\FormaPagamento
 * @author Rande A. Moreira <rande@adok.com.br>
 * @see https://manuais.bling.com.br/manual/?item=formas-de-pagamento
 * @version 1.0.0
 */
class FormaPagamento extends Bling {
	public function __construct($configurations) {
        parent::__construct($configurations);
    }

	/**
     *
     * Recupera formas de pagamento ativas
     *
     * @return bool|array
     * @throws \Exception
     */
    public function getFormasPagamento() {
        try {
        	$request = $this->configurations['guzzle']->get(
        		'formaspagamento/json/',
        		['query' => ['filters' => 'situacao[1]']]
        	);
            $response = \json_decode($request->getBody()->getContents(), true);
        	if ($response && is_array($response)) {
            	$list = [];
            	foreach ($response['retorno']['formaspagamento'] as $item) {
            		$list[] = $item['formapagamento'];
            	}
                return $list;
            }
            return false;
        } catch (\Exception $e){
            return $this->ResponseException($e);
        }
    }
}
